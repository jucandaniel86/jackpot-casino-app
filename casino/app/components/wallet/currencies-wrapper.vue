<script setup lang="ts">
import { useWalletStore } from '~/core/store/wallet'
import type { WalletT } from '~/core/types/Wallet'

type DepositItemT = {
  label: string
  wallets: WalletT[]
}

//props
const props = defineProps<DepositItemT>()

//composables
const { setCurrentWalletDepositPage } = useWalletStore()

//emitters
const emitters = defineEmits(['onWalletChange'])

//models
const searchOpen = ref<boolean>(false)
const search = ref('')
const currencyLimit = ref(4)

//composables
const currentWallets = computed(() => {
  if (search.value.length >= 1) {
    return props.wallets.filter((wallet) =>
      wallet.code.toLowerCase().includes(search.value.toLowerCase()),
    )
  }
  if (currencyLimit.value > 0) {
    return [...props.wallets].splice(0, currencyLimit.value)
  }

  return props.wallets
})

//methods
const handleWalletChange = (wallet: WalletT) => {
  emitters('onWalletChange', wallet)
  setCurrentWalletDepositPage(wallet)
}
</script>
<template>
  <div>
    <div class="d-flex justify-space-between align-center ga-4 mb-2">
      <span class="wallet-item-title">{{ props.label }}</span>
      <div class="wallet-item-search__wrapper" :class="{ 'full-width': searchOpen }">
        <div class="content">
          <div class="input-wrapper">
            <span class="svg-wrapper" @click.prevent="searchOpen = true">
              <IconSearch />
            </span>
            <input v-model="search" placeholder="Search for currency" />
          </div>
        </div>
      </div>
    </div>
    <ul v-if="currentWallets" class="wallet-currency-list">
      <li v-for="(currency, i) in currentWallets" :key="`CurrencyWallet${i}`">
        <WalletCurrency :currency="currency" @on-click="handleWalletChange" />
      </li>
    </ul>
    <div
      v-if="currencyLimit < currentWallets.length"
      class="wallet-currency-item-viewall"
      @click="currencyLimit = -1"
    >
      View all
    </div>
  </div>
</template>
