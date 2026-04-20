<script setup lang="ts">
import { useAuthStore } from '~/core/store/auth'
import { OverlaysTypes } from '~/core/types/Overlays'

const { logout } = useAuthStore()
const router = useRouter()
const { t } = useI18n()

const goToSettings = () => router.push({ name: 'profile' })
const openWallet = () => router.replace({ query: { overlay: OverlaysTypes.WALLET } })

const handleLogout = async () => {
  await logout()
  router.push('/')
}
</script>
<template>
  <div class="d-flex justify-end" style="width: 60px">
    <v-menu location="bottom right">
      <template #activator="{ props }">
        <v-btn v-bind="props" class="user-top-activator pa-0">
          <shared-icon :icon="'brand-ico-settings2'" class="svg-icon user-icon" />
        </v-btn>
      </template>
      <v-list class="user-profile-list">
        <v-list-item @click.prevent="openWallet">
          <template #prepend>
            <shared-icon icon="brand-ico-wallet2" class="svg-icon" />
          </template>
          <v-list-item-title>{{ t('header.wallet') }}</v-list-item-title>
        </v-list-item>
        <v-list-item @click.prevent="goToSettings">
          <template #prepend>
            <shared-icon icon="brand-ico-settings2" class="svg-icon" />
          </template>
          <v-list-item-title>{{ t('header.settings') }}</v-list-item-title>
        </v-list-item>
        <v-list-item @click.prevent="handleLogout">
          <template #prepend>
            <shared-icon icon="brand-ico-Logout-black" class="svg-icon" />
          </template>
          <v-list-item-title>{{ t('header.logout') }}</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>
  </div>
</template>
