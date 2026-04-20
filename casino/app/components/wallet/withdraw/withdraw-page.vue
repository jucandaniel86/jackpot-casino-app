<script setup lang="ts">
import { useAppStore } from '~/core/store/app'
import type { WalletT } from '~/core/types/Wallet'

//props
const props = defineProps<{ currency: WalletT; backLabel: string }>()

//models
const receiveAddress = ref<string>('')
const amount = ref()
const eur = ref()
const loading = ref(false)
const save = ref(false)
const errorMessage = ref('')
const activeWithdraws = ref<any[]>([])

//composables
const { t } = useI18n()
const { convertCurrency } = useUtils()
const { success } = useAlerts()
const { setLoadWallets } = useAppStore()

//methods
const makeWithdrawRequest = async () => {
  loading.value = true
  const payload = {
    wallet_id: props.currency.wallet_id,
    to_address: receiveAddress.value,
    amount: String(amount.value),
    meta: {
      eur: eur.value,
    },
  }

  const { error } = await useApiPostFetch('/player/withdraw-requests', payload)

  errorMessage.value = ''
  if (error) {
    errorMessage.value = error.message
  } else {
    success('Your withdraw will be processed')
    setLoadWallets(true)
    activeWithdraws.value.push({})
    receiveAddress.value = ''
    amount.value = null
    eur.value = null
  }
  loading.value = false
}

const getWithdraws = async () => {
  loading.value = true
  const { data } = await useAPIFetch('/player/withdraw-requests', {
    wallet_id: props.currency.wallet_id,
  })
  activeWithdraws.value = data
  loading.value = false
}

onMounted(async () => {
  await getWithdraws()
})
</script>
<template>
  <div class="d-flex ga-2 flex-column">
    <div v-if="loading" class="currency-page pa-1">
      <v-progress-linear indeterminate color="purple" />
    </div>
    <template v-else-if="activeWithdraws.length > 0">
      <div class="currency-page">
        <p>{{ t('wallets.withdraw_pending') }}</p>
      </div>
    </template>
    <template v-else>
      <div class="currency-page pa-1">
        <v-row class="w-100">
          <v-col cols="10" md="10" sm="10" class="flex-start">
            <v-tooltip :text="t('wallets.withdraw_amount')" location="top" max-width="250">
              <template #activator="{ props }">
                <span v-bind="props">{{ t('wallets.withdraw_balance') }}</span>
                <v-icon v-bind="props" icon="mdi-help" size="sm" />
              </template>
            </v-tooltip>
            <span class="currency-page-amount">
              {{ convertCurrency(props.currency.available, props.currency.precision) }}
              {{ props.currency.code }}
            </span>
          </v-col>
          <v-col cols="2" md="2" sm="2">
            <SharedIcon
              :icon="`currency-ico-${props.currency.code.toLowerCase()}`"
              :style="{ width: '50px', height: '50px' }"
            />
          </v-col>
        </v-row>
      </div>
      <div class="currency-page pa-1">
        <v-row class="mt-0">
          <v-col cols="12">
            <span>{{
              t('wallets.minAmount', {
                amount: `${convertCurrency(
                  props.currency.minAmount,
                  props.currency.precision,
                )} ${props.currency.code}`,
              })
            }}</span>
            <br />
            <span class="disclaimer-txt">{{ t('wallets.withdraw_disclaimer') }}</span>
          </v-col>
          <v-col cols="12">
            <v-text-field
              v-model="receiveAddress"
              hide-details
              :label="t('wallets.address', { currency: props.currency.code })"
            />
          </v-col>
          <v-col cols="12">
            <SharedBalanceConvertor
              :max-amount="currency.available"
              :currency="currency.code"
              :precision="currency.precision"
              :label="t('wallets.amount')"
              @update:amount="amount = $event"
              @update:eur="eur = $event"
              :display-exchange="false"
            />
          </v-col>
          <v-col cols="12">
            <span v-if="errorMessage" class="err-message">{{ errorMessage }}</span>
            <v-btn
              color="purple"
              class="w-100"
              :disabled="save"
              max-width="200"
              @click.prevent="makeWithdrawRequest"
              >{{ t('wallets.requestWithdraw') }}</v-btn
            >
          </v-col>
          <v-col cols="12">
            <span class="disclaimer-txt" v-html="t('wallets.withdraw_disclaimer2')" />
          </v-col>
        </v-row>
      </div>
    </template>
  </div>
</template>
<style scoped>
.disclaimer-txt {
  font-size: 14px;
}
.err-message {
  color: #ff0000;
  font-size: 12px;
  display: block;
  padding: 0.2rem 0;
}
</style>
