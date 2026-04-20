<script setup lang="ts">
import { onMounted } from 'vue'
import { useAppStore } from './core/store/app'
import { useAuthStore } from './core/store/auth'

const { pageLoading } = storeToRefs(useAppStore())
const { initNotifications } = useNotifications()
const { logout } = useAuthStore()

useAuthEventCheck({
  intervalMs: 10000,
  onLoggedOut: () => {
    logout()
  },
})
onMounted(() => {
  initNotifications()
})
</script>
<template>
  <Loader v-if="pageLoading" />
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
</template>
