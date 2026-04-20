/* eslint-disable @typescript-eslint/no-unused-vars */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/* eslint-disable @typescript-eslint/no-explicit-any */
import { useAuthStore } from '~/core/store/auth'
import { useLog } from './useLog'
import { encryptPayload, decryptPayload } from '~/core/utils/payloadCrypto'

const { log, logError } = useLog()

export const useApiPostFetch = async (path: any, _payload: any = {}): Promise<any> => {
  const config = useRuntimeConfig()
  const { token } = storeToRefs(useAuthStore())
  const options: any = {}
  const _return: any = {
    success: true,
    data: null,
    error: null,
  }

  const aad = `POST:${path}`
  const keyB64 = config.public.payloadCryptoKey

  const plainBody = {
    casino_id: config.public.casinoID,
    ..._payload,
  }

  options.baseURL = config.public.baseURL
  options.method = 'post'
  options.watch = false
  options.server = false

  if (config.public.encrypted) {
    options.body = {
      payload: await encryptPayload(plainBody, keyB64, aad),
    }
  } else {
    options.body = plainBody
  }

  // //@ts-ignore
  options.onRequest = ({ request, options }: any) => {
    options.headers.set('Authorization', `Bearer ${token.value}`)
    options.headers.set('Accept', 'application/json')
    options.headers.set('Content-Type', 'application/json')
    options.headers.set('X-Payload-AAD', aad)
  }

  log(
    {
      path: options.baseURL + path,
      params: plainBody,
    },
    true,
  )

  try {
    const data = await $fetch(path, options)

    if (data && typeof data === 'object' && typeof data.payload === 'string') {
      _return.data = await decryptPayload(data.payload, keyB64, aad)
    } else {
      _return.data = data
    }

    log(_return.data, false)
    return _return
  } catch (error: any) {
    _return.success = false

    try {
      if (error?.data?.payload && typeof error.data.payload === 'string') {
        _return.error = await decryptPayload(error.data.payload, keyB64, aad)
      } else {
        _return.error = error?.data ?? error
      }
    } catch {
      _return.error = error?.data ?? error
    }

    logError(_return)
    return _return
  }
}
