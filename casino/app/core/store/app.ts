import type { MenuItemConfig } from '~/config/Menu.config'
import * as Pk from '../../../package.json'

/* eslint-disable @typescript-eslint/no-explicit-any */
export const useAppStore = defineStore(
  'app',
  () => {
    const pageLoading = ref(false)
    const sidebarOpen = ref(false)
    const snackbar = ref(false)
    const snackbarMessage = ref('')
    const sidebar = ref<MenuItemConfig[]>([])
    const loadWallets = ref<boolean>(false)
    const currentLanguage = ref<string>('en') //@todo make an enum
    const version = ref<string>(Pk.version)

    const setLoadWallets = (_payload: boolean) => {
      loadWallets.value = _payload
    }

    const setPageLoading = (loading: boolean) => {
      pageLoading.value = loading
    }

    const setSidebarOpen = (payload: boolean) => {
      sidebarOpen.value = payload
    }

    const toggleSidebar = () => {
      sidebarOpen.value = !sidebarOpen.value
    }

    const setSnackbar = (message: string) => {
      snackbarMessage.value = message
      toggleSnackbar(true)
    }

    const toggleSnackbar = (_payload: boolean) => {
      snackbar.value = _payload
    }

    const setSidebar = (_payload: any) => {
      if (sidebar.value.length === 0) {
        sidebar.value = _payload
      }
    }

    const setCurrentLanguage = (language: string) => {
      currentLanguage.value = language
    }

    return {
      version,
      pageLoading,
      sidebarOpen,
      snackbar,
      snackbarMessage,
      sidebar,
      loadWallets,
      currentLanguage,
      setSnackbar,
      toggleSnackbar,
      setPageLoading,
      setSidebarOpen,
      toggleSidebar,
      setSidebar,
      setLoadWallets,
      setCurrentLanguage,
    }
  },
  {
    persist: false,
  },
)
