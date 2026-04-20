<script setup lang="ts">
import { useAuthStore } from '~/core/store/auth'
import { OverlaysTypes } from '~/core/types/Overlays'

//composables
const { replace } = useRouter()
const { isLogged } = storeToRefs(useAuthStore())
const { name } = useDisplay()
const { t } = useI18n()
const config = useRuntimeConfig()
const pepagyBuyURL = computed(() => config.public.pepagyBuyURL)
const pepagyBuyURLRadium = computed(() => config.public.pepagyBuyURLRadium)

//methods
const login = () => replace({ query: { overlay: OverlaysTypes.LOGIN } })
const register = () => replace({ query: { overlay: OverlaysTypes.REGISTER } })
const openExternal = (url?: string) => {
  if (!url) return
  window.open(url, '_blank', 'noopener,noreferrer')
}
const openPump = () => openExternal(pepagyBuyURL.value)
const openRadium = () => openExternal(pepagyBuyURLRadium.value)
</script>
<template>
  <v-app-bar color="primary" height="48" class="layout-header d-flex align-center justify-center">
    <v-container>
      <div class="d-flex justify-space-between align-center header-content">
        <v-app-bar-title class="d-flex align-center logo-wrapper">
          <nuxt-link :to="'/'">
            <icon-logo v-if="['lg', 'md', 'xl'].indexOf(name) !== -1" class="layout-logo" />
            <icon-logo-sm v-if="['sm', 'xs'].indexOf(name) !== -1" class="layout-logo" />
          </nuxt-link>
        </v-app-bar-title>

        <div v-if="['md', 'lg', 'xl'].includes(name)" class="buy-crypto-container">
          <a
            :href="pepagyBuyURLRadium"
            target="_blank"
            rel="noopener noreferrer"
            @click.prevent="openRadium"
            >BUY on RADIUM</a
          >
          <a
            :href="pepagyBuyURL"
            target="_blank"
            rel="noopener noreferrer"
            @click.prevent="openPump"
            >BUY on PUMP</a
          >
        </div>

        <auth-wallet v-if="isLogged" />

        <div v-if="!isLogged" class="d-flex ga-2">
          <v-btn color="primary" variant="flat" @click.prevent="login">
            {{ t('header.logIn') }}
          </v-btn>
          <v-btn color="purple" variant="flat" @click.prevent="register">
            {{ t('header.signUp') }}</v-btn
          >
        </div>
        <auth-profile v-else />
      </div>
    </v-container>
  </v-app-bar>
</template>
