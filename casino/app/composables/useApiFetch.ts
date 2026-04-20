// useAPIFetch.ts (adaptat din functia ta)
import { useAuthStore } from '~/core/store/auth'
import { useLog } from './useLog'
import { encryptPayload, decryptPayload } from '~/core/utils/payloadCrypto'

const { log, logError } = useLog()

export const useAPIFetch = async (
  path: string,
  _payload: Record<string, any> = {},
  method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE' = 'GET',
): Promise<any> => {
  const config = useRuntimeConfig()
  const options: any = { method }
  const { token } = storeToRefs(useAuthStore())

  const aad = `${method}:${path}`
  const keyB64 = config.public.payloadCryptoKey as string

  options.baseURL = config.public.baseURL
  options.headers = new Headers()
  options.headers.set('Authorization', `Bearer ${token.value}`)
  options.headers.set('content-type', 'application/json')
  options.headers.set('X-Payload-AAD', aad)

  const merged = { ..._payload, casino_id: config.public.casinoID }

  if (method === 'GET') {
    options.query = merged
  } else {
    options.body = {
      payload: await encryptPayload(merged, keyB64, aad),
    }
  }

  log({ path: options.baseURL + path, method, params: merged }, true)

  try {
    const data = await $fetch(path, options)

    if (data && typeof data === 'object' && 'payload' in data && typeof data.payload === 'string') {
      const decrypted = await decryptPayload(data.payload, keyB64, aad)
      log(decrypted, false)
      return decrypted
    }

    log(data, false)
    return data
  } catch (err) {
    logError(err)
    throw err
  }
}
