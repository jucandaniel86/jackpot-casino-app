<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { useAuthStore } from '~/core/store/auth'
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core'

import { PROFILE_TABS } from '~/config/Profile.config'
import type { TabType } from '~/core/types/Game'
import { useAppStore } from '~/core/store/app'

//composables
const route = useRoute()
const { replace } = useRouter()
const { t } = useI18n()

//models
const tabs = ref(PROFILE_TABS)
const currentTab = ref()
const { isLogged } = storeToRefs(useAuthStore())
const { setPageLoading } = useAppStore()
const playerProfile = ref<any>()
const breakpoints = useBreakpoints(breakpointsTailwind)
const isMobile = breakpoints.smaller('sm')

//methods
const onTabChange = (tab: TabType) => {
  replace({ query: { tab: tab.id } })
}
const getDefaultTab = () => {
  if (route.query.tab) {
    const tab = tabs.value.find((el) => el.id === route.query.tab)
    if (tab) {
      return tab.id
    }
  }
  return tabs.value[0] ? tabs.value[0].id : 'account-info'
}

const loadProfile = async (): Promise<void> => {
  setPageLoading(true)
  const profileData: any = await useAPIFetch('/player/profile')
  playerProfile.value = profileData
  setPageLoading(false)
}

onMounted(() => {
  currentTab.value = getDefaultTab()
  if (isLogged) {
    loadProfile()
  }
})

watch(isLogged, () => {
  if (isLogged.value) {
    loadProfile()
  }
})

definePageMeta({
  middleware: 'auth',
})
</script>
<template>
  <div v-if="isLogged">
    <v-tabs v-model="currentTab" align-tabs="start" class="mb-5">
      <v-tab
        v-for="(tab, i) in tabs"
        :key="`Tab${i}`"
        :value="tab.id"
        :class="{ 'v-tab--selected': currentTab && tab.id === currentTab }"
        @click.prevent="onTabChange(tab)"
      >
        {{ t(tab.label) }}
      </v-tab>
    </v-tabs>
    <v-tabs-window v-model="currentTab">
      <v-tabs-window-item value="account-info">
        <ProfileAccountInfo v-if="playerProfile" :profile="playerProfile" />
      </v-tabs-window-item>
      <v-tabs-window-item value="settings">
        <ProfileSettings v-if="playerProfile" :profile="playerProfile" />
      </v-tabs-window-item>
      <v-tabs-window-item value="activity"><ProfileActivity /> </v-tabs-window-item>
      <v-tabs-window-item value="bonus-history"><ProfileBonus /></v-tabs-window-item>
    </v-tabs-window>

    <div v-if="isMobile" style="height: 100px" />
  </div>
  <div v-else class="d-flex justify-center align-center h-100 mt-5">
    <v-alert class="mb-1" border="start" density="compact" color="purple" variant="tonal">
      {{ t('settings.noAccess') }}
    </v-alert>
  </div>
</template>
