/* eslint-disable @typescript-eslint/ban-ts-comment */
import * as jwtDecode from 'jwt-decode'
import { useAuthStore } from '~/core/store/auth'

export type AuthEventCheckOptions = {
  intervalMs?: number
  runOnStart?: boolean
  onChecked?: (isLogged: boolean) => void
  onLoggedOut?: () => void
}

export const useAuthEventCheck = (options: AuthEventCheckOptions = {}) => {
  const { intervalMs = 3000, runOnStart = true, onChecked, onLoggedOut } = options

  const authStore = useAuthStore()
  const { token } = storeToRefs(authStore)
  let timerId: number | null = null
  const lastLogged = ref<boolean | null>(null)

  const getIsLoggedNow = () => {
    if (!token.value) return false
    try {
      const decodedToken = jwtDecode.jwtDecode(token.value as any)
      const currentTime = Date.now() / 1000
      //@ts-ignore
      if (typeof decodedToken !== 'undefined' && decodedToken?.exp < currentTime) {
        return false
      }
      return true
    } catch (_error: any) {
      console.warn('err', _error)
      return false
    }
  }

  const check = () => {
    const logged = getIsLoggedNow()

    onChecked?.(logged)
    if (lastLogged.value === null) {
      lastLogged.value = logged
      return
    }
    if (lastLogged.value && !logged) onLoggedOut?.()
    lastLogged.value = logged
  }

  const onFocus = () => check()
  const onVisibilityChange = () => {
    if (document.visibilityState === 'visible') check()
  }
  const onPageShow = () => check()

  const start = () => {
    if (typeof window === 'undefined') return
    window.addEventListener('focus', onFocus)
    document.addEventListener('visibilitychange', onVisibilityChange)
    window.addEventListener('pageshow', onPageShow)

    if (intervalMs > 0) timerId = window.setInterval(check, intervalMs)
    if (runOnStart) check()
  }

  const stop = () => {
    if (typeof window === 'undefined') return
    window.removeEventListener('focus', onFocus)
    document.removeEventListener('visibilitychange', onVisibilityChange)
    window.removeEventListener('pageshow', onPageShow)
    if (timerId !== null) {
      window.clearInterval(timerId)
      timerId = null
    }
  }

  onMounted(start)
  onBeforeUnmount(stop)

  return { start, stop, check }
}
