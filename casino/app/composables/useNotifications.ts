import { useAuthStore } from '~/core/store/auth'
import { useEventSource } from '@vueuse/core'
import { useAlerts } from './useAlerts'
import { useAppStore } from '~/core/store/app'

/* eslint-disable @typescript-eslint/ban-ts-comment */
export const useNotifications = () => {
  const { setLoadWallets } = useAppStore()
  const { isLogged } = storeToRefs(useAuthStore())

  const markAsReadNotification = async (id: number) => {
    await useApiPostFetch(`/player/notifications/${id}/read`)
    setLoadWallets(true)
  }

  const initNotifications = () => {
    //@ts-ignore
    const messages = ref<any[]>([])
    //@ts-ignore
    const config = useRuntimeConfig()
    const { token } = storeToRefs(useAuthStore())
    const { info } = useAlerts()

    if (!isLogged.value) return

    const { status, data, error, close } = useEventSource(
      `${config.public.baseURL}player/notifications/stream?token=${token.value}`,
      ['notification', 'ping'],
      {
        autoReconnect: {
          retries: 3,
          delay: 1000,
          onFailed() {
            console.log('Failed to connect EventSource after 3 retries')
          },
        },
      },
    )

    watch(data, (raw) => {
      if (!raw) return
      try {
        const msg = JSON.parse(raw)
        messages.value.push(msg)
        if (typeof msg.data == 'undefined' || msg.data == null) return

        switch (msg.data.type) {
          case 'deposit':
            info(`Your ${msg.data.currency} was credited with ${msg.data.amount}`)
            console.log('here o trigger', msg.id)
            markAsReadNotification(msg.id)
            break
        }
      } catch (e: any) {
        console.warn('EventStream Err:', e)
      }
    })

    watch(isLogged, () => {
      initNotifications()
    })

    return {
      messages,
      status,
      error,
      close,
    }
  }

  return {
    initNotifications,
  }
}
