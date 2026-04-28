<script setup lang="ts">
const tab = ref<"overview" | "funnel" | "games" | "cohorts" | "segments">(
  "overview",
);
const currency = ref(useDefaultCurrency());
const period = ref<[string, string]>([
  new Date(Date.now() - 7 * 24 * 3600 * 1000).toISOString(),
  new Date().toISOString(),
]);

const loading = ref(false);
const overview = ref<any>(null);
const games = ref<any>(null);
const cohorts = ref<any>(null);
const segments = ref<any>(null);
const funnel = ref<any>(null);

const rtpHeaders = [
  { title: "Game", key: "name" },
  { title: "Stake", key: "stake_sum" },
  { title: "Payout", key: "payout_sum" },
  { title: "RTP", key: "rtp" },
];

const cohortHeaders = [
  { title: "Cohort", key: "cohort_key" },
  { title: "Start", key: "cohort_start" },
  { title: "Users", key: "users" },
  { title: "D1", key: "d1.rate" },
  { title: "D7", key: "d7.rate" },
  { title: "D30", key: "d30.rate" },
];

const segmentHeaders = [
  { title: "Player ID", key: "player_id" },
  { title: "Username", key: "username" },
  { title: "Created", key: "created_at" },
];

async function refresh() {
  loading.value = true;
  try {
    const baseQuery = {
      from: period.value[0],
      to: period.value[1],
      currency_code: currency.value,
    };

    // parallel fetch
    const [o, g, c, s, f] = await Promise.all([
      useAPIFetch("/marketing/overview", baseQuery),
      useAPIFetch("/marketing/games", baseQuery),
      useAPIFetch("/marketing/cohorts", baseQuery),
      useAPIFetch("/marketing/segments", {
        ...baseQuery,
        inactive_days: 7,
        limit: 50,
      }),
      useAPIFetch("/marketing/funnel", baseQuery),
    ]);

    overview.value = (o as any).data.result;
    games.value = (g as any).data.result;
    cohorts.value = (c as any).data.result;
    segments.value = (s as any).data.result;
    funnel.value = (f as any).data.result;

    console.log("SEGMENTS", segments.value);
  } finally {
    loading.value = false;
  }
}

watch([currency, period], () => refresh(), { deep: true, immediate: true });

// --- Charts
const depositsChart = computed(() => {
  const rows = overview.value?.timeseries?.deposits_daily ?? [];
  const cats = rows.map((r: any) => r.date);
  const series = [
    {
      name: "Deposits (base)",
      data: rows.map((r: any) => Number(r.sum_base ?? 0)),
    },
  ];
  return {
    options: {
      chart: { toolbar: { show: false } },
      xaxis: { categories: cats },
      stroke: { width: 2 },
      dataLabels: { enabled: false },
    },
    series,
  };
});

const topGgrChart = computed(() => {
  const rows = games.value?.top_ggr ?? [];
  return {
    options: {
      chart: { toolbar: { show: false } },
      xaxis: { categories: rows.map((r: any) => r.name) },
      dataLabels: { enabled: false },
    },
    series: [{ name: "GGR", data: rows.map((r: any) => Number(r.ggr ?? 0)) }],
  };
});

const firstBetChart = computed(() => {
  const rows = games.value?.top_first_bet ?? [];
  return {
    options: {
      chart: { toolbar: { show: false } },
      xaxis: { categories: rows.map((r: any) => r.name) },
      dataLabels: { enabled: false },
    },
    series: [
      {
        name: "Users",
        data: rows.map((r: any) => Number(r.first_bet_users ?? 0)),
      },
    ],
  };
});

const ftdChart = computed(() => {
  const rows = overview.value?.timeseries?.ftd_daily ?? [];
  return {
    options: {
      chart: { toolbar: { show: false } },
      xaxis: { categories: rows.map((r: any) => r.date) },
      dataLabels: { enabled: false },
      stroke: { width: 2 },
    },
    series: [
      { name: "FTD", data: rows.map((r: any) => Number(r.ftd_count ?? 0)) },
    ],
  };
});

const funnelChart = computed(() => {
  const steps = funnel.value?.result?.steps ?? [];
  const labels = steps.map((s: any) => s.label);
  const counts = steps.map((s: any) => Number(s.count ?? 0));

  return {
    options: {
      chart: { toolbar: { show: false } },
      plotOptions: { bar: { horizontal: true, barHeight: "70%" } },
      xaxis: { categories: labels },
      dataLabels: { enabled: true },
      tooltip: {
        y: { formatter: (v: any) => `${v}` },
      },
    },
    series: [{ name: "Users", data: counts }],
  };
});

const funnelHeaders = [
  { title: "Step", key: "label" },
  { title: "Users", key: "count" },
  { title: "Rate from registered", key: "rate_from_registered" },
  { title: "Rate from prev", key: "rate_from_prev" },
  { title: "Drop from prev", key: "drop_from_prev" },
];

useHead({
  title: "Marketing",
});
</script>

<template>
  <div class="d-flex flex-column ga-4">
    <!-- Filters -->
    <v-row>
      <v-col cols="12" md="5">
        <SelectCurrencies v-model="currency" />
      </v-col>
      <v-col cols="12" md="5">
        <SelectDatetimepicker v-model="period" />
      </v-col>
      <v-col cols="12" md="2">
        <v-btn variant="tonal" :loading="loading" @click="refresh"
          >Refresh</v-btn
        >
      </v-col>
    </v-row>

    <v-spacer />

    <v-card rounded="0" class="panel-card">
      <v-tabs v-model="tab" grow class="marketing-tabs">
        <v-tab value="overview">Overview</v-tab>
        <v-tab value="funnel">Funnel</v-tab>
        <v-tab value="games">Games</v-tab>
        <v-tab value="cohorts">Cohorts</v-tab>
        <v-tab value="segments">Segments</v-tab>
      </v-tabs>
      <v-divider />

      <v-card-text class="pt-4">
        <template v-if="tab === 'overview'">
          <v-row>
            <v-col cols="12" md="3">
              <v-card rounded="0" variant="flat" class="kpi-card">
                <v-card-text>
                  <div class="text-caption text-medium-emphasis">Registrations</div>
                  <div class="text-h5 font-weight-bold">
                    {{ overview?.cards?.registrations ?? 0 }}
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" md="3">
              <v-card rounded="0" variant="flat" class="kpi-card">
                <v-card-text>
                  <div class="text-caption text-medium-emphasis">FTD</div>
                  <div class="text-h5 font-weight-bold">
                    {{ overview?.cards?.ftd ?? 0 }}
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" md="3">
              <v-card rounded="0" variant="flat" class="kpi-card">
                <v-card-text>
                  <div class="text-caption text-medium-emphasis">Active players</div>
                  <div class="text-h5 font-weight-bold">
                    {{ overview?.cards?.active_players ?? 0 }}
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" md="3">
              <v-card rounded="0" variant="flat" class="kpi-card">
                <v-card-text>
                  <div class="text-caption text-medium-emphasis">GGR</div>
                  <div class="text-h5 font-weight-bold">
                    {{ overview?.cards?.ggr ?? "0" }}
                    <span class="text-caption">PEP</span>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <v-row class="mt-2">
            <v-col cols="12" md="6">
              <v-card rounded="0" variant="flat" class="panel-card chart-card">
                <v-card-title>Deposits / day</v-card-title>
                <v-card-text>
                  <apexchart
                    height="320"
                    type="line"
                    :options="depositsChart.options"
                    :series="depositsChart.series"
                  />
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" md="6">
              <v-card rounded="0" variant="flat" class="panel-card chart-card">
                <v-card-title>FTD / day</v-card-title>
                <v-card-text>
                  <apexchart
                    height="320"
                    type="line"
                    :options="ftdChart.options"
                    :series="ftdChart.series"
                  />
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </template>

        <template v-else-if="tab === 'funnel'">
          <v-row>
            <v-col cols="12" md="6">
              <v-card rounded="0" variant="flat" class="panel-card chart-card">
                <v-card-title>Conversion funnel</v-card-title>
                <v-card-text>
                  <apexchart
                    height="360"
                    type="bar"
                    :options="funnelChart.options"
                    :series="funnelChart.series"
                  />
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" md="6">
              <v-card rounded="0" variant="flat" class="panel-card">
                <v-card-title>Funnel details</v-card-title>
                <v-card-text>
                  <v-data-table
                    :headers="funnelHeaders"
                    :items="funnel?.result?.steps ?? []"
                    density="compact"
                    class="text-caption"
                  >
                    <template #item.rate_from_registered="{ item }: any">
                      {{
                        (Number(item.rate_from_registered) * 100).toFixed(2)
                      }}%
                    </template>
                    <template #item.rate_from_prev="{ item }: any">
                      {{ (Number(item.rate_from_prev) * 100).toFixed(2) }}%
                    </template>
                    <template #item.drop_from_prev="{ item }: any">
                      <v-chip
                        size="small"
                        :color="
                          Number(item.drop_from_prev) > 0.2 ? 'orange' : 'green'
                        "
                        variant="tonal"
                      >
                        {{ (Number(item.drop_from_prev) * 100).toFixed(2) }}%
                      </v-chip>
                    </template>
                  </v-data-table>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </template>

        <template v-else-if="tab === 'games'">
          <v-row>
            <v-col cols="12" md="6">
              <v-card rounded="0" variant="flat" class="panel-card chart-card">
                <v-card-title>Top games by GGR</v-card-title>
                <v-card-text>
                  <apexchart
                    height="360"
                    type="bar"
                    :options="topGgrChart.options"
                    :series="topGgrChart.series"
                  />
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" md="6">
              <v-card rounded="0" variant="flat" class="panel-card chart-card">
                <v-card-title>Top games as first bet</v-card-title>
                <v-card-text>
                  <apexchart
                    height="360"
                    type="bar"
                    :options="firstBetChart.options"
                    :series="firstBetChart.series"
                  />
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <v-card rounded="0" variant="flat" class="panel-card mt-4">
            <v-card-title>RTP per game (top by stake)</v-card-title>
            <v-data-table
              :headers="rtpHeaders"
              :items="games?.rtp ?? []"
              density="compact"
            />
          </v-card>
        </template>

        <template v-else-if="tab === 'cohorts'">
          <v-card rounded="0" variant="flat" class="panel-card">
            <v-card-title>Cohorts retention (weekly)</v-card-title>
            <v-data-table
              :headers="cohortHeaders"
              :items="cohorts?.rows ?? []"
              density="compact"
            />
          </v-card>
        </template>

        <template v-else-if="tab === 'segments'">
          <v-row>
            <v-col cols="12" md="6">
              <v-card rounded="0" variant="flat" class="panel-card">
                <v-card-title>Never played</v-card-title>
                <v-data-table
                  :headers="segmentHeaders"
                  :items="segments?.segments?.never_played?.items ?? []"
                  density="compact"
                />
              </v-card>
            </v-col>

            <v-col cols="12" md="6">
              <v-card rounded="0" variant="flat" class="panel-card">
                <v-card-title>At risk</v-card-title>
                <v-data-table
                  :headers="segmentHeaders"
                  :items="segments?.segments?.at_risk?.items ?? []"
                  density="compact"
                />
              </v-card>
            </v-col>

            <v-col cols="12" md="6">
              <v-card rounded="0" variant="flat" class="panel-card">
                <v-card-title>High Value</v-card-title>
                <v-data-table
                  :headers="segmentHeaders"
                  :items="segments?.segments?.high_value?.items ?? []"
                  density="compact"
                />
              </v-card>
            </v-col>
          </v-row>
        </template>

        <template v-else>
          <v-alert type="info" variant="tonal"> </v-alert>
        </template>
      </v-card-text>
    </v-card>
  </div>
</template>

<style scoped>
.marketing-page {
  --marketing-surface: #f8fafc;
  --marketing-surface-soft: #ffffff;
  --marketing-accent-1: #1d4ed8;
  --marketing-accent-2: #0ea5e9;
  --marketing-accent-3: #2563eb;
  background: linear-gradient(
      135deg,
      rgba(29, 78, 216, 0.06) 0%,
      rgba(14, 165, 233, 0.04) 100%
    ),
    repeating-linear-gradient(
      135deg,
      rgba(148, 163, 184, 0.12) 0,
      rgba(148, 163, 184, 0.12) 1px,
      transparent 1px,
      transparent 12px
    );
  padding: 12px;
}

.panel-card {
  background: var(--marketing-surface-soft);
  border: 1px solid rgba(148, 163, 184, 0.35);
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
  border-radius: 0 !important;
}

.chart-card {
  border-color: rgba(29, 78, 216, 0.35);
}

.kpi-card {
  background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
  border: 1px solid rgba(29, 78, 216, 0.35);
  color: #0f172a;
  border-radius: 0 !important;
}

.marketing-tabs :deep(.v-tab) {
  border-radius: 0 !important;
  text-transform: none;
  font-weight: 600;
}

.marketing-tabs :deep(.v-tab--selected) {
  color: #ffffff;
  background: linear-gradient(120deg, #1d4ed8 0%, #0ea5e9 100%);
}

:deep(.v-btn) {
  border-radius: 0 !important;
}

:deep(.v-chip) {
  border-radius: 0 !important;
}

:deep(.v-data-table) {
  background: transparent;
}

:deep(.v-data-table thead th) {
  background: rgba(226, 232, 240, 0.8);
  color: #0f172a;
}
</style>
