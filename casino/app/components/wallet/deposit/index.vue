<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { useWalletStore } from '~/core/store/wallet'
import type { WalletT } from '~/core/types/Wallet'

//enums
enum DepositViewTypes {
  WALLET_ITEM = 'WALLET_ITEM',
  WALLETS = 'WALLETS',
  WALLET_CONNECT = 'WALLET_CONNECT',
}

const currentView = ref<DepositViewTypes>(DepositViewTypes.WALLET_ITEM)

//composables
const { currentWalletDepositPage, cryptoWallets, fiatWallets } = storeToRefs(useWalletStore())
const { setCurrentWalletDepositPage } = useWalletStore()

const handleBackToMain = () => {
  setCurrentWalletDepositPage(null)
  currentView.value = DepositViewTypes.WALLETS
}

const handleWalletChange = (wallet: WalletT) => {
  setCurrentWalletDepositPage(wallet)
  currentView.value = DepositViewTypes.WALLET_ITEM
}

//computed
onMounted(() => {
  setCurrentWalletDepositPage(null)
  currentView.value = DepositViewTypes.WALLETS
  handleWalletChange(cryptoWallets.value.find((w) => w.code === 'PEP') as WalletT)
})

watch(
  currentWalletDepositPage,
  (wallet) => {
    currentView.value = wallet ? DepositViewTypes.WALLET_ITEM : DepositViewTypes.WALLETS
  },
  { immediate: true },
)
</script>
<template>
  <WalletDepositPage
    v-if="currentView === DepositViewTypes.WALLET_ITEM && currentWalletDepositPage"
    :currency="currentWalletDepositPage"
    :back-label="'Deposit in crypto'"
    @on-back="handleBackToMain"
  />
</template>
