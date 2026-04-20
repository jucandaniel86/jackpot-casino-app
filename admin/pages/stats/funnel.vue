<script setup lang="ts">
import moment from "moment";
import ConversionFunnelCard from "~/components/stats/ConversionFunnelCard.vue";
import { useLayoutStore } from "~/store/app";

const { currentCasinoId } = storeToRefs(useLayoutStore());

const currency = ref("PEP");
const from = ref(moment().subtract(7, "days").startOf("day").toISOString());
const to = ref(moment().add(1, "day").startOf("day").toISOString());

const {
  data: funnel,
  refresh,
  status,
} = await useApiUseFetch(
  "/stats/funnel",
  computed(() => ({
    from: from.value,
    to: to.value,
    currency_code: currency.value,
    bets_10_threshold: 10,
    bets_100_threshold: 100,
		int_casino_id: currentCasinoId.value,
  })),
);

useHead({
  title: "Conversion funnel",
});
</script>

<template>
  <v-container fluid class="pa-0">
    <v-card class="mb-4" color="white" variant="elevated">
      <v-card-text class="d-flex align-center justify-space-between">
        <div class="w-50">
          <div class="text-h5 font-weight-bold">Stats</div>
          <div class="text-caption text-medium-emphasis">
            Funnels & engagement.
          </div>
        </div>
        <div class="d-flex justify-end ga-2 w-50">
          <SelectCurrencies v-model="currency" style="max-width: 140px" />
          <v-btn variant="outlined" flat color="primary" @click="refresh">
            <v-icon start icon="mdi-refresh" />
            Refresh
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <ConversionFunnelCard
      :data="funnel"
      title="Conversion funnel (7d cohort)"
      :currencyCode="currency"
    />
  </v-container>
</template>
