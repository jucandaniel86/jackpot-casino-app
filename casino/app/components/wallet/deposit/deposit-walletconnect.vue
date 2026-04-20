<script setup lang="ts">
import { useQueryClient } from '@tanstack/vue-query'
import { useBalance, useAccount } from '@wagmi/vue'
import { watchEffect } from 'vue'
import { createAppKitWalletButton } from '@reown/appkit-wallet-button'

const { address, connector, isConnected } = useAccount()
const { isset } = useUtils()
const appKitWalletButton = createAppKitWalletButton()
const isReady = ref(false)

const queryClient = useQueryClient()
const { data: balance, queryKey } = useBalance({
  address: address.value,
})
const MIN_AMOUNT = 0.001
const error = ref('')

//emitters
const emitters = defineEmits(['onBack'])

//computed
const walletBalance = computed(() => {
  if (!balance.value) return 0
  return Number(balance.value?.value).toFixed(2)
})

//methods
const handleAmountChange = (amount: number) => {
  if (amount <= 0) {
    error.value = 'Minimum amount is ' + MIN_AMOUNT
  } else {
    error.value = ''
  }
  if (isset(balance.value?.value)) {
    if (balance.value && balance.value?.value < amount) {
      error.value = 'Insufficient funds'
    }
  }
}

//mounted
onMounted(() => {
  isReady.value = appKitWalletButton.isReady() as boolean
})

watchEffect(() => {
  queryClient.invalidateQueries({ queryKey })
  if (isset(balance.value?.value)) {
    if (Number(balance.value?.value) === 0) {
      error.value = 'Insufficient funds'
    }
  }
})
</script>
<template>
  <div>
    <div id="rainbowkit-modal" />
    <div class="d-flex ga-2 flex-column">
      <div class="currency-page-back" style="cursor: pointer" @click="emitters('onBack')">
        <IconRightArrow class="svg-icon" />
        <span>Wallet Connect</span>
      </div>
      <div class="d-flex flex-column ga-4 align-center mt-1">
        <span class="text-white font-weight-bold d-flex align-center ga-1">
          Deposit with WalletConnect
          <IconWalletConnect style="color: #3396ff" />
        </span>
        <span v-if="isConnected" class="text-white"
          >Wallet is connected to {{ connector?.name }}
          <IconConnected />
        </span>
        <button :disabled="!isReady" @click="() => appKitWalletButton.connect('walletConnect')">
          Connect to Google
        </button>
      </div>
      <div class="d-flex align-center justify-space-between w-100 wallet-balance" v-if="balance">
        <span>Wallet Balance</span>
        <span class="text-white font-weight-bold">{{ balance.value }} {{ balance.symbol }}</span>
      </div>

      <div class="d-flex align-center justify-space-between w-100 mb-1 pl-2 pr-2">
        <span class="text-white font-weight-bold">Amount</span>
        <span class="text-white font-weight-bold">${{ walletBalance }}</span>
      </div>
    </div>

    <FormAmount
      currency="ETH"
      class="mb-1"
      :max="20"
      :error="error"
      @on-change="handleAmountChange"
    />

    <p class="disclaimer mb-2">
      Only ETH deposits are supported via WalletConnect. Please enter the ETH amount for your
      deposit above and confirm the transaction in your wallet. Make sure the address on the
      transaction is 0x4Ddb182191E9633DCdd8f3d2f72AbB415140219e
    </p>

    <button class="deposit-btn" :disabled="error !== ''">Deposit</button>
    <button class="back-btn mt-2" @click="emitters('onBack')">Back</button>
  </div>
</template>
<style scoped>
.wallet-address {
  background: #21242e;
  border: solid 1px #494f65;
  box-shadow: 0 4px 4px 0 rgba(0, 0, 0, 0.5);
  color: #fff;
  height: 42px;
  min-height: 42px;
  min-width: 64px;
  padding: 10px 13px;
  border-radius: 9px;
  font-weight: 700;
  cursor: pointer;
  transition:
    background-color 250ms cubic-bezier(0.4, 0, 0.2, 1),
    box-shadow 250ms cubic-bezier(0.4, 0, 0.2, 1),
    border-color 250ms cubic-bezier(0.4, 0, 0.2, 1),
    color 250ms cubic-bezier(0.4, 0, 0.2, 1);
  font-size: 14px;

  position: relative;
  align-items: center;
  font-size: 0.875rem;
  line-height: 1.75;
  text-transform: uppercase;
  min-width: 64px;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  text-transform: unset !important;
  vertical-align: middle !important;
  white-space: nowrap;
  white-space-collapse: collapse;
  width: 120px;
}
.wallet-address:hover {
  background-color: #14151a;
  border: 1px solid #494f65;
}
.wallet-balance {
  color: rgb(172, 176, 195);
  height: 42px;
  margin-top: 5px;
  background: rgb(33, 36, 46);
  border-radius: 6px;
  cursor: default;
  padding: 8px;
}
.wallet-balance:hover {
  background: #2c303f;
}
.disclaimer {
  font-size: 14px;
  color: #fff;
  font-weight: 400;
}
.deposit-btn,
.back-btn {
  width: 100%;
  cursor: default;
  box-shadow: 0 4px 4px 0 rgba(0, 0, 0, 0.5);
  height: 42px;
  min-height: 42px;
  padding: 10px 13px;
  border-radius: 9px;
  font-size: 14px;
  font-weight: 700;
  transition:
    background-color 250ms cubic-bezier(0.4, 0, 0.2, 1),
    box-shadow 250ms cubic-bezier(0.4, 0, 0.2, 1),
    border-color 250ms cubic-bezier(0.4, 0, 0.2, 1),
    color 250ms cubic-bezier(0.4, 0, 0.2, 1);
  background-color: var(--textlink-light-text-color);
  color: #fff;
}

.deposit-btn:disabled {
  color: #ba8c99;
  background-color: #851c3a;
}

.back-btn {
  background-color: #21242e;
  border: solid 1px #494f65;
}
.back-btn:hover {
  background-color: #14151a;
  border: solid 1px #494f65;
}
</style>
