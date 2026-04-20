/* eslint-disable @typescript-eslint/ban-ts-comment */
/* eslint-disable @typescript-eslint/no-unused-vars */
/* eslint-disable @typescript-eslint/no-explicit-any */
import * as jwtDecode from 'jwt-decode'
import { useAuthStore } from '~/core/store/auth'

export default defineNuxtRouteMiddleware((to, _from) => {
  const { token } = storeToRefs(useAuthStore())
  const { setToken } = useAuthStore()

  if (token.value) {
    try {
      const decodedToken = jwtDecode.jwtDecode(token.value as any)
      const currentTime = Date.now() / 1000

      //@ts-ignore
      if (typeof decodedToken !== 'undefined' && decodedToken?.exp < currentTime) {
        setToken(null)
        return
      }
    } catch (_error) {
      setToken(null)
      console.warn('[AUTH MIDDLEWARE]', _error)
      return
    }
  } else if (to.name !== 'login') {
    setToken(null)
  }
})
