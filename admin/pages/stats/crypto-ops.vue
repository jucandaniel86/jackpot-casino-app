<script setup lang="ts">
import CryptoOpsOverview from "~/components/stats/CryptoOpsOverview.vue";
import { useLayoutStore } from "~/store/app";

const { currentCasinoId } = storeToRefs(useLayoutStore());

const currency = ref(useDefaultCurrency());
const from = ref(new Date(Date.now() - 86400000).toISOString()); // last 24h
const to = ref(new Date().toISOString());

const { data, status, refresh } = await useApiUseFetch(
  "/stats/crypto-ops",

  computed(() => ({
    from: from.value,
    to: to.value,
    currency_code: currency.value,
		int_casino_id: currentCasinoId.value,
  })),
);

useHead({
  title: "Crypto OPS",
});
</script>

<template>
  <v-container fluid class="pa-0">
    <v-card class="mb-4" color="white" variant="elevated">
      <v-card-text class="d-flex align-center justify-space-between">
        <div>
          <div class="text-h5 font-weight-bold">Crypto Ops</div>
          <div class="text-caption text-medium-emphasis">
            On-chain reconciliation, deposit latency & fee monitoring
          </div>
        </div>
        <v-btn variant="flat" color="primary" @click="refresh">
          <v-icon start icon="mdi-refresh" /> Refresh
        </v-btn>
      </v-card-text>
    </v-card>

    <v-progress-linear
      v-if="status === 'pending'"
      indeterminate
      color="primary"
    />

    <CryptoOpsOverview v-if="data" :data="data" />
  </v-container>
</template>
