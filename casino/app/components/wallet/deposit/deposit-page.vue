<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import type { WalletT } from '~/core/types/Wallet'

const props = defineProps<{ currency: WalletT; backLabel: string }>()

const { convertCurrency } = useUtils()

const networkData = computed(() => {
  if (!props.currency.networkData) return []

  return Object.entries(props.currency.networkData).map((network: any) => network[1].name)
})

const network = ref('')

onMounted(() => {
  if (networkData.value.length) {
    network.value = networkData.value[0]
  }
})
</script>
<template>
  <div class="d-flex ga-2 flex-column">
    <div class="currency-page">
      <v-row class="w-100">
        <v-col v-if="networkData.length" cols="6">
          <div class="text-subtitle-1 text-white">Choose network*</div>
          <v-select
            v-model="network"
            :items="networkData"
            hide-details
            density="compact"
            single-line
          />
        </v-col>
        <v-col cols="12">
          <div class="currency-page-details">
            <div>
              <div class="fw-600">
                Minimum Deposit:
                {{ convertCurrency(props.currency.minAmount, props.currency.precision) }}
                {{ props.currency.code }}
              </div>
              <ul>
                <li>
                  <p>
                    Please ensure that you enter the correct address corresponding to your chosen
                    network in the third party wallet.
                  </p>
                </li>
                <li>
                  <p>Values sent below the minimum deposit value will be lost.</p>
                </li>
              </ul>

              <div class="currency-page-wallet mt-2">
                <SharedQrCode
                  v-if="props.currency.owner_address"
                  :address="props.currency.owner_address"
                />
                <span v-else>No address available.</span>
              </div>
            </div>
          </div>
        </v-col>
      </v-row>
    </div>
  </div>
</template>
