<script setup lang="ts">
type BalanceConvertorProps = {
  label: string
  maxAmount: number
  currency: string
  exchangeRate?: number
  precision: number
  error?: string
  displayExchange: boolean
}

const props = withDefaults(defineProps<BalanceConvertorProps>(), {
  exchangeRate: 1,
  error: '',
  displayExchange: true,
})

const { t } = useI18n()
const { convertCurrency } = useUtils()

const emit = defineEmits<{
  (e: 'update:amount', value: number | null): void
  (e: 'update:eur', value: number | null): void
}>()

const amountInput = ref('')
const eurInput = ref('')
const syncing = ref(false)

const currencyIcon = computed(() => `currency-ico-${props.currency.toLowerCase()}`)

const formatNumber = (value: number, decimals: number) => {
  if (!Number.isFinite(value)) return ''
  return convertCurrency(value, decimals)
}

const toNumber = (value: string) => {
  const num = Number(value)
  return Number.isFinite(num) ? num : null
}

const clampAmount = (value: number) => Math.min(Math.max(value, 0), props.maxAmount)

const updateFromAmount = (value: number) => {
  const clamped = clampAmount(value)
  const eurValue = props.exchangeRate > 0 ? clamped * props.exchangeRate : 0

  amountInput.value = formatNumber(clamped, props.precision)
  eurInput.value = formatNumber(eurValue, 2)
  emit('update:amount', clamped)
  emit('update:eur', eurValue)
}

const updateFromEur = (value: number) => {
  const amountValue = props.exchangeRate > 0 ? value / props.exchangeRate : 0
  const clamped = clampAmount(amountValue)
  const eurValue = props.exchangeRate > 0 ? clamped * props.exchangeRate : 0

  amountInput.value = formatNumber(clamped, props.precision)
  eurInput.value = formatNumber(eurValue, 2)
  emit('update:amount', clamped)
  emit('update:eur', eurValue)
}

const setMax = () => {
  syncing.value = true
  updateFromAmount(props.maxAmount)
  syncing.value = false
}

const amountRules = [
  (value: string) => value !== '' || 'Amount is required',
  (value: string) => (toNumber(value) ?? 0) > 0 || 'Amount must be greater than 0',
  (value: string) => (toNumber(value) ?? 0) <= props.maxAmount || `Max ${props.maxAmount}`,
]

watch(
  () => props.maxAmount,
  (value) => {
    const current = toNumber(amountInput.value)
    if (current === null || current <= 0) return
    if (current > value) updateFromAmount(value)
  },
)

watch(
  () => props.exchangeRate,
  () => {
    const current = toNumber(amountInput.value)
    if (current === null || current <= 0) return
    updateFromAmount(current)
  },
)

watch(amountInput, (value) => {
  if (syncing.value) return
  const num = toNumber(value)
  if (num === null) {
    eurInput.value = ''
    emit('update:amount', null)
    return
  }

  syncing.value = true
  const clamped = clampAmount(num)
  if (clamped !== num) amountInput.value = formatNumber(clamped, props.precision)
  if (clamped > 0 && props.exchangeRate > 0) {
    eurInput.value = formatNumber(clamped * props.exchangeRate, 2)
    emit('update:eur', clamped * props.exchangeRate)
  } else {
    eurInput.value = ''
    emit('update:eur', null)
  }
  emit('update:amount', clamped)
  syncing.value = false
})

watch(eurInput, (value) => {
  if (syncing.value) return
  const num = toNumber(value)
  if (num === null) return

  syncing.value = true
  updateFromEur(num)
  syncing.value = false
})
</script>

<template>
  <div class="balance-convertor">
    <div class="balance-convertor-label">{{ props.label }}</div>
    <v-row no-gutters class="balance-convertor-row justify-center align-center">
      <v-col cols="12" :sm="props.displayExchange ? 5 : 12">
        <v-text-field
          v-model="amountInput"
          type="number"
          min="0"
          density="compact"
          color="primary"
          hide-details="auto"
          :rules="amountRules"
          :error="Boolean(props.error)"
        >
          <template #append-inner>
            <div class="d-flex align-center ga-2 balance-convertor-append">
              <div class="pa-2">
                <SharedIcon :icon="currencyIcon" class="svg-icon" />
              </div>

              <v-btn class="balance-convertor-max" size="x-small" variant="tonal" @click="setMax">
                MAX
              </v-btn>
            </div>
          </template>
        </v-text-field>
      </v-col>

      <v-col
        v-if="props.displayExchange"
        cols="12"
        sm="2"
        class="d-flex justify-center align-center balance-convertor-equal"
      >
        <span>=</span>
      </v-col>

      <v-col cols="12" sm="5" v-if="props.displayExchange">
        <v-text-field
          v-model="eurInput"
          type="number"
          density="compact"
          color="primary"
          hide-details="auto"
          :error="Boolean(props.error)"
        >
          <template #prepend-inner>
            <SharedIcon icon="currency-ico-eur" class="svg-icon" />
          </template>
        </v-text-field>
      </v-col>
    </v-row>
    <div v-if="props.error" class="balance-convertor-error">{{ props.error }}</div>
    <div class="balance-convertor-rate" v-if="props.displayExchange">
      {{ t('wallets.rate') }}: {{ convertCurrency(props.exchangeRate, props.precision) }}
    </div>
  </div>
</template>

<style scoped>
.balance-convertor-label {
  margin-bottom: 8px;
  font-weight: 600;
}

.balance-convertor-rate {
  margin-top: 8px;
  font-size: 14px;
  color: #acb0c3;
}

.balance-convertor-error {
  margin-top: 6px;
  font-size: 13px;
  color: #ff2e2e;
}
</style>
