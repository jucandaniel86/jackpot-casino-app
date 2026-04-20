<!-- eslint-disable vue/require-prop-types -->
<script setup lang="ts">
import { ButtonActionTypesEnum } from '~/core/types/ActionButton'
import type { MenuItemConfig } from '../../config/Menu.config'
import { useAppStore } from '~/core/store/app'
import { useSidebarStore } from '~/core/store/sidebar'
import type { OverlaysTypes } from '~/core/types/Overlays'
const { name } = useDisplay()

//models
const route = useRoute()
const drawer = ref(false)
const rail = ref(false)

//composables
const { sidebarOpen, sidebar } = storeToRefs(useSidebarStore())
const router = useRouter()
const { openOverlay } = useUtils()
const { setSidebarOpen } = useSidebarStore()
const { width } = useDisplay()
const { version } = storeToRefs(useAppStore())

//computed
const isMobile = computed(() => ['xs', 'sm'].indexOf(name.value) !== -1)
const sidebarWidth = computed(() => (isMobile.value ? width.value : 240))
const railVal = computed(() => {
  if (isMobile.value) {
    return false
  }
  return !sidebarOpen.value
})
const handleOnClick = (item: MenuItemConfig) => {
  switch (item.actionType) {
    case ButtonActionTypesEnum.OPEN_INTERNAL_PAGE: {
      if (item.slug) {
        return router.push({ path: item.slug })
      }
      return null
    }
    case ButtonActionTypesEnum.OPEN_OVERLAY: {
      if (item.slug) {
        openOverlay(item.slug as OverlaysTypes)
      }
      return null
    }
    case ButtonActionTypesEnum.OPEN_EXTERNAL_PAGE: {
      let whereToOpen = '_self'
      if (!item.isSameTab) {
        whereToOpen = '_blank'
      }
      if (item.slug) {
        return window.open(item.slug, whereToOpen)
      }
      return null
    }
    default:
      return null
  }
}

watch(sidebarOpen, () => {
  if (isMobile.value) {
    rail.value = false
    drawer.value = sidebarOpen.value
    return
  }
  // drawer.value = sidebarOpen.value
  rail.value = sidebarOpen.value
})

onMounted(() => {
  // rail.value = isMobile.value ? false : true
  drawer.value = isMobile.value ? false : true
})
</script>
<template>
  <v-navigation-drawer
    v-model="drawer"
    :rail="railVal"
    :rail-width="56"
    :width="sidebarWidth"
    :permanent="!isMobile"
    :class="{ extended: sidebarOpen }"
  >
    <div v-if="sidebarOpen" class="sidebar-arrow-container d-flex justify-center align-center" />
    <div
      class="sidebar-arrow-container d-flex justify-end align-center"
      :class="{ expanded: sidebarOpen, expandedMobile: sidebarOpen && isMobile }"
    >
      <v-btn
        icon
        class="rail-arrow"
        size="small"
        :class="{ right: sidebarOpen }"
        @click.prevent="setSidebarOpen(!sidebarOpen)"
      >
        <icon-right-arrow class="svg-arrow" />
      </v-btn>
    </div>
    <v-list density="compact" nav>
      <template v-for="(item, i) in sidebar" :key="item.id">
        <v-list-item
          :value="item"
          color="primary"
          variant="plain"
          link
          :class="{ active: route.path.replace('/', '') === item.slug }"
          @click.prevent="handleOnClick(item)"
        >
          <template #prepend>
            <v-tooltip :text="item.title" color="white" :disabled="sidebarOpen">
              <template #activator="{ props }">
                <shared-icon v-bind="props" :icon="item.icon" fill="#e0e6ff" class="brand-icon" />
              </template>
            </v-tooltip>
          </template>

          <v-list-item-title v-if="(isMobile && sidebarOpen) || (!isMobile && !railVal)">{{
            item.title
          }}</v-list-item-title>
        </v-list-item>
        <v-divider v-if="i === 3 || i === 7" :key="`AppMenuDivider${i}`" />
      </template>
    </v-list>
    <sup class="sidebar-version">v.{{ version }}</sup>
  </v-navigation-drawer>
</template>
<style scoped>
.sidebar-version {
  color: #fff;
  display: block;
  position: absolute;
  top: 100%;
  margin-top: -20px;
  left: 10px;
  text-align: center;
}
</style>
