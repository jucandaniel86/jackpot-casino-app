import * as Pk from '../../../package.json'
import type { MenuItemConfig } from '~/config/Menu.config'
import { useAPIFetch } from '~/composables/useApiFetch'

export const useSidebarStore = defineStore('sidebar', () => {
  const sidebarOpen = ref(false)
  const sidebar = ref<MenuItemConfig[]>([])
  const sidebarLoaded = ref(false)
  const sidebarLoading = ref(false)
  const version = ref<string>(Pk.version)
  let fetchPromise: Promise<void> | null = null

  const setSidebarOpen = (payload: boolean) => {
    sidebarOpen.value = payload
  }

  const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
  }

  const fetchSidebar = async (force = false): Promise<void> => {
    if (sidebarLoaded.value && !force) {
      return
    }

    if (fetchPromise) {
      return fetchPromise
    }

    sidebarLoading.value = true

    fetchPromise = useAPIFetch('/sidebar')
      .then((response) => {
        const items = Array.isArray(response) ? response : response?.data
        sidebar.value = Array.isArray(items) ? items : []
        sidebarLoaded.value = true
      })
      .finally(() => {
        sidebarLoading.value = false
        fetchPromise = null
      })

    return fetchPromise
  }

  return {
    version,
    sidebarOpen,
    sidebar,
    sidebarLoaded,
    sidebarLoading,
    setSidebarOpen,
    toggleSidebar,
    fetchSidebar,
  }
})
