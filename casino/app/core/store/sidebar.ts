import type { MenuItemConfig } from '~/config/Menu.config'
import * as Pk from '../../../package.json'

/* eslint-disable @typescript-eslint/no-explicit-any */
export const useSidebarStore = defineStore(
  'sidebar',
  () => {
    const sidebarOpen = ref(false)
    const sidebar = ref<MenuItemConfig[]>([])
    const version = ref<string>(Pk.version)

    const setSidebarOpen = (payload: boolean) => {
      sidebarOpen.value = payload
    }

    const toggleSidebar = () => {
      sidebarOpen.value = !sidebarOpen.value
    }

    const setSidebar = (_payload: any) => {
      if (sidebar.value.length === 0) {
        sidebar.value = _payload
      }
    }

    return {
      version,
      sidebarOpen,
      sidebar,
      setSidebarOpen,
      toggleSidebar,
      setSidebar,
    }
  },
  {
    persist: {
			key: 'casino-sidebar',
			storage: localStorage,
		},
		
  },
)
