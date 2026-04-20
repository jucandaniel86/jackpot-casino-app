// ~/core/utils/payloadCrypto.ts
const IV_LEN = 12
const TAG_LEN = 16
const VER = 'v1:'

const te = new TextEncoder()
const td = new TextDecoder()

function b64ToU8(b64: string): Uint8Array {
  const bin = atob(b64)
  const out = new Uint8Array(bin.length)
  for (let i = 0; i < bin.length; i++) out[i] = bin.charCodeAt(i)
  return out
}

function u8ToB64(u8: Uint8Array): string {
  let bin = ''
  for (const byte of u8) bin += String.fromCharCode(byte)
  return btoa(bin)
}

function toArrayBuffer(u8: Uint8Array): ArrayBuffer {
  const buffer = new ArrayBuffer(u8.byteLength)
  new Uint8Array(buffer).set(u8)
  return buffer
}

async function importKeyFromB64(keyB64: string) {
  const raw = b64ToU8(keyB64)
  const rawBuffer = new ArrayBuffer(raw.byteLength)
  new Uint8Array(rawBuffer).set(raw)
  return crypto.subtle.importKey('raw', rawBuffer, 'AES-GCM', false, ['encrypt', 'decrypt'])
}

async function decryptAesGcm(
  key: CryptoKey,
  enc: Uint8Array,
  iv: Uint8Array,
  aad: string,
): Promise<ArrayBuffer> {
  const encBuffer = toArrayBuffer(enc)
  const ivBuffer = toArrayBuffer(iv)

  if (aad) {
    return crypto.subtle.decrypt(
      {
        name: 'AES-GCM',
        iv: ivBuffer,
        additionalData: toArrayBuffer(te.encode(aad)),
        tagLength: 128,
      },
      key,
      encBuffer,
    )
  }

  return crypto.subtle.decrypt({ name: 'AES-GCM', iv: ivBuffer, tagLength: 128 }, key, encBuffer)
}

export async function encryptPayload(data: unknown, keyB64: string, aad = ''): Promise<string> {
  const key = await importKeyFromB64(keyB64)
  const iv = crypto.getRandomValues(new Uint8Array(IV_LEN))
  const pt = te.encode(JSON.stringify(data))

  const enc = new Uint8Array(
    await crypto.subtle.encrypt(
      { name: 'AES-GCM', iv, additionalData: te.encode(aad), tagLength: 128 },
      key,
      pt,
    ),
  )

  const ct = enc.slice(0, enc.length - TAG_LEN)
  const tag = enc.slice(enc.length - TAG_LEN)

  const out = new Uint8Array(IV_LEN + ct.length + TAG_LEN)
  out.set(iv, 0)
  out.set(ct, IV_LEN)
  out.set(tag, IV_LEN + ct.length)

  return VER + u8ToB64(out)
}

export async function decryptPayload(payload: string, keyB64: string, aad = ''): Promise<any> {
  const key = await importKeyFromB64(keyB64)
  const raw = payload.startsWith(VER) ? payload.slice(VER.length) : payload
  const blob = b64ToU8(raw)

  const iv = blob.slice(0, IV_LEN)
  const ct = blob.slice(IV_LEN, blob.length - TAG_LEN)
  const tag = blob.slice(blob.length - TAG_LEN)

  const enc = new Uint8Array(ct.length + TAG_LEN)
  enc.set(ct, 0)
  enc.set(tag, ct.length)

  let pt: ArrayBuffer
  try {
    pt = await decryptAesGcm(key, enc, iv, aad)
  } catch (err) {
    if (aad && err instanceof DOMException && err.name === 'OperationError') {
      pt = await decryptAesGcm(key, enc, iv, '')
    } else {
      throw err
    }
  }

  return JSON.parse(td.decode(pt))
}
