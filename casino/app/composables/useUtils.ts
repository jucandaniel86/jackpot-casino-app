import moment from 'moment'
import { useAuthStore } from '~/core/store/auth'
import { OverlaysTypes } from '~/core/types/Overlays'

export const useUtils = () => {
  const router = useRouter()

  const wait = (duration: number) => new Promise((resolve) => setTimeout(resolve, duration))

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const isset = (_var: any) => _var === null || typeof _var !== 'undefined'

  const onImagesLoadingEnded = () => {
    return Promise.all(
      Array.from(document.images)
        .filter((img) => !img.complete)
        .map(
          (img) =>
            new Promise((resolve) => {
              img.onload = img.onerror = resolve
            }),
        ),
    )
  }

  const convertCurrency = (amount: number, decimals = 8) => {
    return Number(amount).toFixed(decimals)
  }

  const convertDate = (date: string, format = 'MMM DD YYYY HH:mm') => {
    return moment(date).format(format)
  }

  const openOverlay = (overlay: OverlaysTypes) => {
    const { isLogged } = useAuthStore()

    if (!isLogged && overlay === OverlaysTypes.WALLET) {
      return router.replace({ query: { overlay: OverlaysTypes.LOGIN } })
    }
    if (isLogged && overlay === OverlaysTypes.REGISTER) {
      return false
    }

    return router.replace({ query: { overlay } })
  }

  return {
    wait,
    isset,
    onImagesLoadingEnded,
    convertCurrency,
    convertDate,
    openOverlay,
  }
}
