import { defineNuxtPlugin } from 'nuxt/app'
import { WagmiPlugin } from '@wagmi/vue'
import { WagmiConfig } from '../app/config/Wagmi.config'
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'

const queryClient = new QueryClient()

export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.use(WagmiPlugin, { config: WagmiConfig })
  nuxtApp.vueApp.use(VueQueryPlugin, { queryClient })
})
