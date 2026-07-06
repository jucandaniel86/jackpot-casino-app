import * as Pk from '../../../package.json'

export const useAppStore = defineStore(
  'app',
  () => {
    const pageLoading = ref(false)
    const snackbar = ref(false)
    const snackbarMessage = ref('')
    const loadWallets = ref<boolean>(false)
    const currentLanguage = ref<string>('en') //@todo make an enum
    const version = ref<string>(Pk.version)

    const setLoadWallets = (_payload: boolean) => {
      loadWallets.value = _payload
    }

    const setPageLoading = (loading: boolean) => {
      pageLoading.value = loading
    }

    const setSnackbar = (message: string) => {
      snackbarMessage.value = message
      toggleSnackbar(true)
    }

    const toggleSnackbar = (_payload: boolean) => {
      snackbar.value = _payload
    }

    const setCurrentLanguage = (language: string) => {
      currentLanguage.value = language
    }

    return {
      version,
      pageLoading,
      snackbar,
      snackbarMessage,
      loadWallets,
      currentLanguage,
      setSnackbar,
      toggleSnackbar,
      setPageLoading,
      setLoadWallets,
      setCurrentLanguage,
    }
  },
  {
    persist: false,
  },
)
