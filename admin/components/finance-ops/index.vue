<script setup lang="ts">
import moment from "moment";
import FinanceOpsOverview from "~/components/stats/FinanceOpsOverview.vue";
import { useLayoutStore } from "~/store/app";

const currency = ref(useDefaultCurrency());
const { currentCasinoId } = storeToRefs(useLayoutStore());

// default: today
const from = ref(moment().subtract(30, "day").startOf("day").toISOString());
const to = ref(moment().add(1, "day").startOf("day").toISOString());

const unclaimedDays = ref(7);

const { data, refresh, status } = await useApiUseFetch(
  "/stats/finance-ops",
  computed(() => ({
    from: from.value,
    to: to.value,
    currency_code: currency.value,
    unclaimed_days: unclaimedDays.value,
		int_casino_id: currentCasinoId.value,
  })),
);
</script>

<template>
  <v-container fluid class="pa-0">
    <v-card class="mb-4" color="white" variant="elevated">
      <v-card-text class="d-flex align-center justify-space-between">
        <div>
          <div class="text-h5 font-weight-bold">Finance / Ops</div>
          <div class="text-caption text-medium-emphasis">
            Deposits, withdrawals, cashflow, wallet distribution, unclaimed
            balances.
          </div>
        </div>
        <div class="d-flex justify-end ga-2 w-100">
          <SelectCurrencies
            v-model="currency"
            variant="outlined"
            style="max-width: 160px"
          />
          <v-text-field
            v-model="unclaimedDays"
            type="number"
            label="Unclaimed days"
            variant="outlined"
            density="compact"
            hide-details
            style="max-width: 160px"
          />

          <v-btn variant="flat" color="primary" @click="refresh">
            <v-icon start icon="mdi-refresh" />
            Refresh
          </v-btn>
        </div>
      </v-card-text>
    </v-card>
    <v-card>
      <v-card-text>
        <v-progress-linear
          v-if="status === 'pending'"
          indeterminate
          class="mb-4"
        />
        <FinanceOpsOverview :data="data" :currency="currency" />
      </v-card-text>
    </v-card>
  </v-container>
</template>
