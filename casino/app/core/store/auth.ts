/* eslint-disable @typescript-eslint/ban-ts-comment */
/* eslint-disable @typescript-eslint/no-unused-vars */
/* eslint-disable @typescript-eslint/no-explicit-any */
import * as jwtDecode from 'jwt-decode'
import { useAppStore } from './app'
import { useWalletStore } from './wallet'

type UserType = {
  username: string
  email: string
  id: string
  created_at: string
  status: string
}

export const useAuthStore = defineStore(
  'auth',
  () => {
    //composables
    const { setWallet, getCurrentWallet } = useWalletStore()

    //models
    const token = ref<string | null | undefined>('')
    const user = ref<UserType | null | undefined>()
    const connectedWallet = ref<string | boolean>(false)

    //methods
    const setToken = (_token: string | null | undefined) => {
      token.value = _token
    }

    const setUser = (_user: UserType | null) => {
      user.value = _user
    }

    const clearAuthState = () => {
      setToken(null)
      setUser(null)
      setWallet(null)
    }

    const logout = async (): Promise<void> => {
      try {
        await useApiPostFetch('/player/logout')
      } finally {
        clearAuthState()
      }
    }

    const refresh = async (): Promise<void> => {
      const { success, data } = await useApiPostFetch('/player/refresh')

      if (success && data && data.authorization) {
        setToken(data.authorization.token)
      }
    }

    const walletConnectLogin = async (wallet: string): Promise<boolean> => {
      const { setSnackbar } = useAppStore()
      connectedWallet.value = wallet
      const { success, data } = await useApiPostFetch('/player/wallet-connect', { wallet })
      const router = useRouter()
      if (success && data && data.authorization) {
        if (data.user) {
          setSnackbar(`Welcome ${data.user.username}`)
        }

        setToken(data.authorization.token)
        router.replace({ query: {} })
        return true
      } else {
        return false
      }
    }

    const isLogged = computed(() => {
      if (token.value) {
        try {
          const decodedToken = jwtDecode.jwtDecode(token.value as any)
          const currentTime = Date.now() / 1000
          if (typeof decodedToken !== 'undefined') {
            //@ts-ignore
            console.log('decoded', decodedToken?.exp, decodedToken?.exp < currentTime)
          }

          //@ts-ignore
          if (typeof decodedToken !== 'undefined' && decodedToken?.exp < currentTime) {
            return false
          }
          return true
        } catch (_error) {
          return false
        }
      }

      return false
    })

    const setConnectedWallet = (_wallet: string) => (connectedWallet.value = _wallet)

    watch(isLogged, () => {
      if (isLogged.value) {
        getCurrentWallet()
      } else {
        setWallet(null)
      }
    })

    return {
      isLogged,
      token,
      user,
      connectedWallet,
      setToken,
      setUser,
      logout,
      refresh,
      walletConnectLogin,
      setConnectedWallet,
    }
  },
  {
    persist: true,
  },
)
