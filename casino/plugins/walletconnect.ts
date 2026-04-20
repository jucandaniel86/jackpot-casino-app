/* eslint-disable @typescript-eslint/ban-ts-comment */
/* eslint-disable @typescript-eslint/no-explicit-any */
import { defineNuxtPlugin } from 'nuxt/app'
//@ts-ignore
import VueConnectWallet, { ConnectWalletButton } from 'vue-connect-wallet'

export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.component(ConnectWalletButton)
  nuxtApp.vueApp.use(VueConnectWallet)
})
