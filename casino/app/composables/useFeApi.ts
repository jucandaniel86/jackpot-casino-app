import { useAuthStore } from '~/core/store/auth'
import { OverlaysTypes } from '~/core/types/Overlays'

type GamePostMessageType =
  | 'visitHistory'
  | 'visitLobby'
  | 'visitCashier'
  | 'notifyGameIsReady'
  | 'notifyRoundStart'
  | 'notifyRoundEnd'

type GamePostMessageCallback = (event: MessageEvent, payload: any) => void | Promise<void>

const gameEventCallbacks = new Map<GamePostMessageType, Set<GamePostMessageCallback>>()

export const useFeApi = () => {
  const { openOverlay } = useUtils()
  const { isLogged } = storeToRefs(useAuthStore())
  const router = useRouter()

  const log = (event: MessageEvent) => {
    console.groupCollapsed(
      '%c GAME POST MESSAGE %c received ',
      'background:#ff8a00;color:#111;font-size:15px;font-weight:900;padding:4px 8px;border-radius:4px;font-family:Arial, sans-serif;',
      'color:#ff8a00;font-size:14px;font-weight:800;font-family:Arial, sans-serif;',
    )
    console.log(
      '%cSource',
      'color:#f32424;font-size:13px;font-weight:900;font-family:Arial, sans-serif;',
      'game iframe',
    )
    console.log(
      '%cOrigin',
      'color:#f32424;font-size:13px;font-weight:900;font-family:Arial, sans-serif;',
      event.origin,
    )
    console.log(
      '%cPayload',
      'color:#f32424;font-size:13px;font-weight:900;font-family:Arial, sans-serif;',
      event.data,
    )
    console.groupEnd()
  }

  const onGameEvent = (type: GamePostMessageType, callback: GamePostMessageCallback) => {
    if (!gameEventCallbacks.has(type)) {
      gameEventCallbacks.set(type, new Set())
    }

    gameEventCallbacks.get(type)?.add(callback)

    return () => {
      gameEventCallbacks.get(type)?.delete(callback)
    }
  }

  const dispatchGameEvent = async (type: GamePostMessageType, event: MessageEvent) => {
    const handlers = gameEventCallbacks.get(type)
    if (!handlers || handlers.size === 0) {
      return
    }

    for (const callback of handlers) {
      await callback(event, event.data)
    }
  }

  const readPostMessage = async (event: MessageEvent) => {
    log(event)

    const type = event.data?.type as GamePostMessageType | undefined
    if (!type) {
      return
    }

    switch (type) {
      case 'visitHistory':
        visitHistory()
        break
      case 'visitLobby':
        visitLobby()
        break
      case 'visitCashier':
        visitCashier()
        break
    }

    await dispatchGameEvent(type, event)
  }

  const visitHistory = () => {
    if (!isLogged.value) return openOverlay(OverlaysTypes.LOGIN)

    router.push({ name: 'profile', query: { tab: 'activity', history: 'true' } })
  }

  const visitLobby = () => router.push('/')

  const visitCashier = () => router.push('/bundles')

  return {
    readPostMessage,
    onGameEvent,
  }
}
