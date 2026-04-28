<script setup lang="ts">
import moment from "moment";
import SuspiciousPlayersTable from "./SuspiciousPlayersTable.vue";
import DuplicatesTable from "./DuplicatesTable.vue";
import GameAbuseTable from "./GameAbuseTable.vue";
import { useLayoutStore } from "~/store/app";

const { currentCasinoId } = storeToRefs(useLayoutStore());
const tab = ref<"overview" | "players" | "duplicates" | "gameAbuse">(
  "overview"
);
const currency = ref(useDefaultCurrency());

// interval default: last 7 days
const from = ref(moment().subtract(2, "month").startOf("day").toISOString());
const to = ref(moment().add(1, "day").startOf("day").toISOString());
 
const query = computed(() => ({
  from: from.value,
  to: to.value,
  currency_code: currency.value,
}));

const queryBase = computed(() => ({
  from: from.value,
  to: to.value,
  currency_code: currency.value,
}));

const {
  data: overview,
  refresh: refreshOverview,
  status: s1,
} = await useApiUseFetch(
  "/stats/risk/overview",
  computed(() => ({
    ...queryBase.value,
    min_bets: 30,
    min_wagered: "10.00000000",
		int_casino_id: currentCasinoId.value
  }))
);

const {
  data: players,
  refresh: refreshPlayers,
  status: s2,
} = await useApiUseFetch(
  "/stats/risk/players",
  computed(() => ({
    ...queryBase.value,
    min_bets: 30,
    min_wagered: "10.00000000",
    limit: 50,
		int_casino_id: currentCasinoId.value
  }))
);

const {
  data: duplicates,
  refresh: refreshDuplicates,
  status: s3,
} = await useApiUseFetch(
  "/stats/risk/duplicates",
  computed(() => ({ ...queryBase.value, limit: 200, int_casino_id: currentCasinoId.value }))
);

const {
  data: gameAbuse,
  refresh: refreshGameAbuse,
  status: s4,
} = await useApiUseFetch(
  "/stats/risk/game-abuse",
  computed(() => ({ ...queryBase.value, limit: 50, int_casino_id: currentCasinoId.value }))
);

const loading = computed(
  () =>
    s1.value === "pending" ||
    s2.value === "pending" ||
    s3.value === "pending" ||
    s4.value === "pending"
);

function refreshCurrent() {
  if (tab.value === "overview") return refreshOverview();
  if (tab.value === "players") return refreshPlayers();
  if (tab.value === "duplicates") return refreshDuplicates();
  if (tab.value === "gameAbuse") return refreshGameAbuse();
}
</script>
<template>
  <v-container fluid class="pa-0">
    <v-row class="align-center mb-4">
      <v-col cols="12" md="6">
        <div class="text-h5 font-weight-bold">Risk & Fraud</div>
        <div class="text-body-2 text-medium-emphasis">
          Monitor suspicious players, duplicate callbacks, and game anomalies.
        </div>
      </v-col>

      <v-col cols="12" md="6" class="d-flex justify-end ga-2">
        <SelectCurrencies v-model="currency" style="max-width: 120px" />
        <v-btn
          variant="outlined"
          color="blue"
          density="comfortable"
          @click="refreshCurrent"
        >
          <v-icon start icon="mdi-refresh" />
          Refresh
        </v-btn>
      </v-col>
    </v-row>
    <v-card>
      <v-tabs v-model="tab" density="comfortable">
        <v-tab value="overview">Overview</v-tab>
        <v-tab value="players">Suspicious Players</v-tab>
        <v-tab value="duplicates">Duplicate Transactions</v-tab>
        <v-tab value="gameAbuse">Game Abuse</v-tab>
      </v-tabs>

      <v-divider />

      <v-card-text>
        <v-progress-linear v-if="loading" indeterminate class="mb-4" />

        <div v-if="tab === 'overview'">
          <RiskOverview :data="overview" />
        </div>

        <div v-else-if="tab === 'players'">
          <SuspiciousPlayersTable :data="players" />
        </div>

        <div v-else-if="tab === 'duplicates'">
          <DuplicatesTable :data="duplicates" />
        </div>

        <div v-else>
          <GameAbuseTable :data="gameAbuse" />
        </div>
      </v-card-text>
    </v-card>
  </v-container>
</template>
