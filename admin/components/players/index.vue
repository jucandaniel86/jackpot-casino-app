<script setup lang="ts">
import moment from "moment";
import { PLAYERS_TABLE_HEADERS } from "./headers";

//composables
const router = useRouter();

//models
const totalItems = ref(0);
const items = ref<any[]>();
const loading = ref(true);
const overviewLoading = ref(true);
const overview = ref<any>({
  player_countries: [],
  deposit_balance: { display: "0", items: [] },
  total_player_balance: { display: "0", items: [] },
});
const searchText = ref("");
const options = ref<any>({});

//methods
const reloadOverview = async () => {
  overviewLoading.value = true;
  const { success, data } = await useAPIFetch("/players/overview");

  if (success) {
    overview.value = data.data;
  }

  overviewLoading.value = false;
};

const reloadList = async () => {
  loading.value = true;
  const { page, itemsPerPage } = options.value;

  const { success, data } = await useAPIFetch("/players/list", {
    start: page,
    length: itemsPerPage,
    search: searchText.value,
  });
  if (success) {
    items.value = data.data.items;
    totalItems.value = data.data.total;
  }

  loading.value = false;
};
const refreshPlayers = () => {
  reloadOverview();
  reloadList();
};
const emptyValue = "-";
const formatDate = (date?: string | null) =>
  date ? moment(date).fromNow() : emptyValue;
const formatDateTime = (date?: string | null) =>
  date ? moment(date).format("YYYY-MM-DD HH:mm") : emptyValue;
const playerStatus = (item: any) => {
  if (item.status) return String(item.status);
  return Number(item.active) === 1 ? "ACTIVE" : "DISABLED";
};
const statusColor = (item: any) =>
  playerStatus(item) === "ACTIVE" ? "success" : "error";
const displayValue = (value?: string | number | null) => value ?? emptyValue;
const activity = (userID: number) =>
  router.push({ name: "players-activity-id", params: { id: userID } });
const wallets = (userID: number) =>
  router.push({ name: "players-wallets-id", params: { id: userID } });
const sessions = (userID: number) =>
  router.push({ name: "players-sessions-id", params: { id: userID } });
const countryLabels = computed(() =>
  overview.value.player_countries.map((item: any) => item.country),
);
const countrySeries = computed(() => [
  {
    name: "Players",
    data: overview.value.player_countries.map(
      (item: any) => item.players_count,
    ),
  },
]);
const countriesChartOptions = computed(() => ({
  chart: {
    type: "bar",
    toolbar: { show: false },
  },
  plotOptions: {
    bar: {
      borderRadius: 4,
      horizontal: true,
    },
  },
  dataLabels: {
    enabled: true,
  },
  xaxis: {
    categories: countryLabels.value,
    labels: {
      precision: 0,
    },
  },
  colors: ["#1976d2"],
  tooltip: {
    y: {
      formatter: (value: number) => `${value} players`,
    },
  },
}));

//watchers
watch(
  options,
  () => {
    reloadList();
  },
  { deep: true },
);

watch(searchText, () => {
  if (searchText.value && searchText.value.length > 2) {
    return reloadList();
  }
  if (searchText.value.length <= 1) reloadList();
});

//onMounted
onMounted(() => {
  reloadOverview();
  reloadList();
});
</script>
<template>
  <v-container fluid class="pa-0">
    <v-card class="mb-4" color="white" variant="elevated">
      <v-card-text class="d-flex align-center justify-space-between">
        <div>
          <div class="text-h5 font-weight-bold">Players</div>
          <div class="text-caption text-medium-emphasis">
            Manage your platform players here.
          </div>
        </div>
        <v-btn variant="flat" color="primary" @click="refreshPlayers">
          <v-icon start icon="mdi-refresh" /> Refresh
        </v-btn>
      </v-card-text>
    </v-card>

    <v-row class="mb-4">
      <v-col cols="12" md="6">
        <v-card height="100%">
          <v-card-title class="text-subtitle-1 font-weight-bold">
            Player Countries
          </v-card-title>
          <v-card-text>
            <v-skeleton-loader
              v-if="overviewLoading"
              type="image"
              height="220"
            />
            <div
              v-else-if="!overview.player_countries.length"
              class="text-body-2 text-medium-emphasis"
            >
              No country data.
            </div>
            <ClientOnly v-else>
              <apexchart
                type="bar"
                height="220"
                :options="countriesChartOptions"
                :series="countrySeries"
              />
            </ClientOnly>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card height="100%">
          <v-card-text>
            <div class="d-flex align-center justify-space-between mb-4">
              <div class="text-subtitle-2 text-medium-emphasis">
                Deposit Balance Total
              </div>
              <v-icon icon="mdi-cash-plus" color="success" />
            </div>
            <v-skeleton-loader v-if="overviewLoading" type="heading" />
            <div v-else class="text-h6 font-weight-bold">
              {{ overview.deposit_balance.display }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card height="100%">
          <v-card-text>
            <div class="d-flex align-center justify-space-between mb-4">
              <div class="text-subtitle-2 text-medium-emphasis">
                Players Balance Total
              </div>
              <v-icon icon="mdi-wallet" color="primary" />
            </div>
            <v-skeleton-loader v-if="overviewLoading" type="heading" />
            <div v-else class="text-h6 font-weight-bold">
              {{ overview.total_player_balance.display }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-card>
      <v-card-title class="d-flex justify-space-between align-center ga-2">
        <v-text-field
          v-model="searchText"
          label="Search"
          chips
          hide-details
          density="compact"
        ></v-text-field>
      </v-card-title>
      <v-card-text>
        <v-data-table
          :headers="PLAYERS_TABLE_HEADERS"
          :items="items"
          :options.sync="options"
          :server-items-length="totalItems"
          :loading="loading"
          :search="searchText"
          class="elevation-1"
          :height="400"
          density="compact"
          fixed-header
        >
          <template v-slot:item="{ item }">
            <tr>
              <td>
                {{ item.email }}
                <v-chip size="x-small" class="d-flex"
                  >Created at: {{ formatDate(item.created_at) }}</v-chip
                >
              </td>
              <td>{{ item.username }}</td>
              <td>{{ item.fixed_id }}</td>
              <td>
                <v-chip
                  size="x-small"
                  :color="statusColor(item)"
                  variant="tonal"
                >
                  {{ playerStatus(item) }}
                </v-chip>
              </td>
              <td>{{ displayValue(item.player_balance) }}</td>
              <td>{{ displayValue(item.player_balance_available) }}</td>
              <td>{{ displayValue(item.last_ip) }}</td>
              <td>{{ formatDateTime(item.created_at) }}</td>
              <td>{{ formatDateTime(item.last_login_at) }}</td>
              <td>
                <v-btn
                  flat
                  variant="tonal"
                  color="blue"
                  @click.prevent="activity(item.id)"
                  size="small"
                  class="mt-1 mb-1"
                  prepend-icon="mdi-chart-line"
                  >Activity</v-btn
                >
								<div />
                <v-btn
                  flat
                  variant="tonal"
                  color="blue"
                  @click.prevent="wallets(item.id)"
                  size="small"
                  class="mr-1 mb-1"
                  prepend-icon="mdi-wallet-outline"
                  >Wallets</v-btn
                >
								<div />
                <v-btn
                  flat
                  variant="tonal"
                  color="blue"
                  @click.prevent="sessions(item.id)"
                  size="small"
                  class="mr-1 mb-1"
                  prepend-icon="mdi-account-card-outline"
                  >Sessions</v-btn
                >
              </td>
            </tr>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>
  </v-container>
</template>
