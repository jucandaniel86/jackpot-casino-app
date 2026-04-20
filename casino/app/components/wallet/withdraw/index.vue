<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { useWalletStore } from '~/core/store/wallet'
import { WalletWithdrawPage } from '#components'
import type { WalletT } from '~/core/types/Wallet'

//composables
const { currentWalletDepositPage, cryptoWallets, fiatWallets } = storeToRefs(useWalletStore())
const { setCurrentWalletDepositPage } = useWalletStore()

//methods
const handleBackToMain = () => {
  setCurrentWalletDepositPage(null)
}

onMounted(() => {
  setCurrentWalletDepositPage(cryptoWallets.value.find((w) => w.code === 'PEP') as WalletT)
})
</script>
<template>
  <div>
    <WalletWithdrawPage
      :currency="currentWalletDepositPage as WalletT"
      :back-label="'Withdraw in crypto'"
      @on-back="handleBackToMain"
    />
  </div>
</template>
