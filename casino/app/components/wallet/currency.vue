<script setup lang="ts">
import type { WalletT } from '~/core/types/Wallet'

const props = defineProps<{ currency: WalletT }>()
const { convertCurrency } = useUtils()
const emitters = defineEmits(['onClick'])
</script>
<template>
  <div class="wallet_currency_item" @click.prevent="emitters('onClick', props.currency)">
    <div class="icon">
      <SharedIcon
        :icon="`currency-ico-${props.currency.code.toLowerCase()}`"
        :style="{ width: '50px', height: '50px' }"
      />
    </div>
    <div class="content">
      <p class="fw-600">{{ props.currency.name }} ({{ props.currency.code }})</p>
      <div class="fw-600">
        {{ convertCurrency(props.currency.available, props.currency.precision) }}
      </div>
      <p class="fw-400">
        Min. Amount:
        <span class="fw-600">{{ convertCurrency(props.currency.minAmount, 4) }}</span>
      </p>
    </div>
  </div>
</template>
