import { defineNuxtPlugin } from 'nuxt/app'
import {
  RainbowKitVuePlugin,
  mainnet,
  zkSync,
  scroll,
  polygonZkEvm,
  immutableZkEvm,
  avalanche,
} from 'use-rainbowkit-vue'
import 'use-rainbowkit-vue/style.css'
import { h } from 'vue'

export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.use(RainbowKitVuePlugin, {
    appName: 'RainbowKit demo',
    projectId: 'YOUR_PROJECT_ID',
    chains: [mainnet, zkSync, scroll, polygonZkEvm, immutableZkEvm, avalanche],
    ///Extra options
    enableChainModalOnConnect: false, /// by default is true
    connectModalTeleportTarget: '#rainbowkit-modal', /// Make sure this element exists
    chainModalTeleportTarget: '#rainbowkit-modal', /// Make sure this element exists
    accountModalTeleportTarget: '#rainbowkit-modal', /// Make sure this element exists
    connectModalIntro: (/*{ compactModalEnabled, getWallet }*/) => {
      return () => {
        return h('div', 'You can start your journey here by using web3 wallet.')
      }
    },
  })
})
