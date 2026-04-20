<script setup lang="ts">
import type {
  BonusStatsCurrencyBreakdownItem,
  BonusStatsDailyTrendItem,
  BonusStatsResponse,
  BonusStatsTopRuleItem,
} from "~/types/bonuses";

const bonuses = useBonusesStore();
const { stats } = storeToRefs(bonuses);
const { toastError } = useAlert();

const loading = computed(() => bonuses.loading.stats);
const granularity = ref<"day" | "week" | "month">("day");

const statusLabelMap: Record<string, string> = {
  active: "Active",
  consumed: "Consumed",
  expired: "Expired",
  revoked: "Revoked",
};

const sourceLabelMap: Record<string, string> = {
  automatic: "Automatic",
  manual_segment: "Manual Segment",
  manual_user: "Manual User",
};

const toNumber = (value: unknown): number => {
  if (typeof value === "number") {
    return Number.isFinite(value) ? value : 0;
  }

  if (typeof value === "string") {
    const normalized = value.replace(/,/g, "").trim();
    const parsed = Number(normalized);
    return Number.isFinite(parsed) ? parsed : 0;
  }

  return 0;
};

const formatInt = (value: unknown): string =>
  new Intl.NumberFormat().format(toNumber(value));

const formatBase = (value: unknown): string =>
  new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 8,
  }).format(toNumber(value));

const amountValue = (
  data: Record<string, unknown> | undefined | null,
  prefix: string,
): number => {
  if (!data) return 0;
  return toNumber(data[`${prefix}_ui`] ?? data[`${prefix}_base`]);
};

const amountDisplay = (
  data: Record<string, unknown> | undefined | null,
  prefix: string,
  currencyCode?: string,
): string => {
  const value = amountValue(data, prefix);
  const formatted = formatBase(value);

  if (!currencyCode) {
    return formatted;
  }

  return `${formatted} ${currencyCode}`;
};

const humanize = (value: string): string =>
  value.replaceAll("_", " ").replace(/\b\w/g, (char) => char.toUpperCase());

const statusBreakdown = computed<Record<string, number | string>>(
  () =>
    (stats.value?.status_breakdown as Record<string, number | string>) ||
    (stats.value?.by_status as Record<string, number | string>) ||
    {},
);

const sourceBreakdown = computed<Record<string, number | string>>(
  () =>
    (stats.value?.source_breakdown as Record<string, number | string>) ||
    (stats.value?.by_source as Record<string, number | string>) ||
    {},
);

const aggregateCurrencyCode = computed(
  () => (stats.value?.aggregate_currency_code as string | undefined) || "",
);

const totalGrants = computed(() =>
  toNumber(
    stats.value?.total_grants ??
      stats.value?.totals?.total_grants ??
      Object.values(statusBreakdown.value).reduce(
        (sum, item) => sum + toNumber(item),
        0,
      ),
  ),
);

const totalGrantedAmount = computed(() =>
  amountValue(
    {
      total_granted_ui: stats.value?.total_granted_ui,
      total_granted_base:
        stats.value?.total_granted_base ?? stats.value?.totals?.total_granted_base,
    },
    "total_granted",
  ),
);

const totalRemainingAmount = computed(() =>
  amountValue(
    {
      total_remaining_ui: stats.value?.total_remaining_ui,
      total_remaining_base:
        stats.value?.total_remaining_base ??
        stats.value?.totals?.total_remaining_base,
    },
    "total_remaining",
  ),
);

const totalConsumedAmount = computed(() => {
  const direct = amountValue(
    {
      total_consumed_ui: stats.value?.total_consumed_ui,
      total_consumed_base:
        stats.value?.total_consumed_base ??
        stats.value?.totals?.total_consumed_base,
    },
    "total_consumed",
  );

  if (direct > 0) {
    return direct;
  }

  const fallback = totalGrantedAmount.value - totalRemainingAmount.value;
  return fallback > 0 ? fallback : 0;
});

const consumptionRatePct = computed(() => {
  const direct = toNumber(stats.value?.consumption_rate_pct);
  if (direct > 0) {
    return direct;
  }

  if (totalGrantedAmount.value <= 0) {
    return 0;
  }

  return (totalConsumedAmount.value / totalGrantedAmount.value) * 100;
});

const activeGrants = computed(() =>
  toNumber(stats.value?.active_grants ?? statusBreakdown.value.active),
);
const consumedGrants = computed(() =>
  toNumber(stats.value?.consumed_grants ?? statusBreakdown.value.consumed),
);
const expiredGrants = computed(() =>
  toNumber(stats.value?.expired_grants ?? statusBreakdown.value.expired),
);
const revokedGrants = computed(() =>
  toNumber(stats.value?.revoked_grants ?? statusBreakdown.value.revoked),
);

const currencyBreakdown = computed<BonusStatsCurrencyBreakdownItem[]>(() =>
  Array.isArray(stats.value?.currency_breakdown)
    ? stats.value.currency_breakdown
    : [],
);

const dailyTrend = computed<BonusStatsDailyTrendItem[]>(() =>
  Array.isArray(stats.value?.daily_trend) ? stats.value.daily_trend : [],
);

const topRules = computed<BonusStatsTopRuleItem[]>(() =>
  Array.isArray(stats.value?.top_rules) ? stats.value.top_rules : [],
);

const dailyTrendRows = computed(() =>
  dailyTrend.value.map((item, index) => ({
    label: trendXLabel(item, index),
    grantsCount: toNumber(item.grants_count),
    granted: amountDisplay(
      item as unknown as Record<string, unknown>,
      "granted",
      aggregateCurrencyCode.value,
    ),
    consumed: amountDisplay(
      item as unknown as Record<string, unknown>,
      "consumed",
      aggregateCurrencyCode.value,
    ),
    remaining: amountDisplay(
      item as unknown as Record<string, unknown>,
      "remaining",
      aggregateCurrencyCode.value,
    ),
  })),
);

const topRulesRows = computed(() =>
  topRules.value.map((item) => ({
    ruleName: item.rule_name || `Rule #${item.rule_id ?? "-"}`,
    grantsCount: toNumber(item.grants_count),
    granted: amountDisplay(
      item as unknown as Record<string, unknown>,
      "granted",
      aggregateCurrencyCode.value,
    ),
    consumed: amountDisplay(
      item as unknown as Record<string, unknown>,
      "consumed",
      aggregateCurrencyCode.value,
    ),
    remaining: amountDisplay(
      item as unknown as Record<string, unknown>,
      "remaining",
      aggregateCurrencyCode.value,
    ),
  })),
);

const kpis = computed(() => [
  {
    key: "total_grants",
    label: "Total Grants",
    value: formatInt(totalGrants.value),
  },
  {
    key: "total_granted_ui",
    label: "Total Granted",
    value: amountDisplay(
      {
        total_granted_ui: stats.value?.total_granted_ui,
        total_granted_base: stats.value?.total_granted_base,
      },
      "total_granted",
      aggregateCurrencyCode.value,
    ),
  },
  {
    key: "total_remaining_ui",
    label: "Total Remaining",
    value: amountDisplay(
      {
        total_remaining_ui: stats.value?.total_remaining_ui,
        total_remaining_base: stats.value?.total_remaining_base,
      },
      "total_remaining",
      aggregateCurrencyCode.value,
    ),
  },
  {
    key: "total_consumed_ui",
    label: "Total Consumed",
    value: amountDisplay(
      {
        total_consumed_ui: stats.value?.total_consumed_ui,
        total_consumed_base: stats.value?.total_consumed_base,
      },
      "total_consumed",
      aggregateCurrencyCode.value,
    ),
  },
  {
    key: "consumption_rate_pct",
    label: "Consumption Rate (%)",
    value: `${consumptionRatePct.value.toFixed(2)}%`,
  },
  {
    key: "active_grants",
    label: "Active Grants",
    value: formatInt(activeGrants.value),
  },
  {
    key: "consumed_grants",
    label: "Consumed Grants",
    value: formatInt(consumedGrants.value),
  },
  {
    key: "expired_grants",
    label: "Expired Grants",
    value: formatInt(expiredGrants.value),
  },
  {
    key: "revoked_grants",
    label: "Revoked Grants",
    value: formatInt(revokedGrants.value),
  },
]);

const statusRows = computed(() =>
  Object.entries(statusBreakdown.value).map(([raw, value]) => ({
    raw,
    label: statusLabelMap[raw] || humanize(raw),
    value: toNumber(value),
  })),
);

const sourceRows = computed(() =>
  Object.entries(sourceBreakdown.value).map(([raw, value]) => ({
    raw,
    label: sourceLabelMap[raw] || humanize(raw),
    value: toNumber(value),
  })),
);

const trendXLabel = (item: BonusStatsDailyTrendItem, index: number) => {
  if (granularity.value === "week") {
    return (
      item.week || item.period || item.date || item.day || String(index + 1)
    );
  }

  if (granularity.value === "month") {
    return (
      item.month || item.period || item.date || item.day || String(index + 1)
    );
  }

  return item.date || item.day || item.period || String(index + 1);
};

const trendChart = computed(() => ({
  options: {
    chart: { stacked: false, toolbar: { show: false } },
    dataLabels: { enabled: false },
    stroke: { curve: "smooth", width: [2, 2, 2, 2] },
    xaxis: {
      categories: dailyTrend.value.map((item, index) =>
        trendXLabel(item, index),
      ),
    },
    yaxis: [{ title: { text: "Values" } }],
    legend: { position: "top" },
  },
  series: [
    {
      name: "grants_count",
      type: "line",
      data: dailyTrend.value.map((item) => toNumber(item.grants_count)),
    },
    {
      name: "granted_amount",
      type: "area",
      data: dailyTrend.value.map((item) =>
        amountValue(item as Record<string, unknown>, "granted"),
      ),
    },
    {
      name: "consumed_amount",
      type: "area",
      data: dailyTrend.value.map((item) =>
        amountValue(item as Record<string, unknown>, "consumed"),
      ),
    },
    {
      name: "remaining_amount",
      type: "area",
      data: dailyTrend.value.map((item) =>
        amountValue(item as Record<string, unknown>, "remaining"),
      ),
    },
  ],
}));

const statusDonut = computed(() => ({
  options: {
    labels: statusRows.value.map((row) => row.label),
    legend: { position: "bottom" },
  },
  series: statusRows.value.map((row) => row.value),
}));

const sourceDonut = computed(() => ({
  options: {
    labels: sourceRows.value.map((row) => row.label),
    legend: { position: "bottom" },
  },
  series: sourceRows.value.map((row) => row.value),
}));

const topRulesBar = computed(() => ({
  options: {
    chart: { toolbar: { show: false } },
    plotOptions: { bar: { horizontal: true } },
    dataLabels: { enabled: false },
    xaxis: {
      categories: topRules.value.map(
        (item) => item.rule_name || `Rule #${item.rule_id ?? "-"}`,
      ),
    },
  },
  series: [
    {
      name: "grants_count",
      data: topRules.value.map((item) => toNumber(item.grants_count)),
    },
  ],
}));

const hasAnyData = computed(() => {
  if (!stats.value) return false;

  return (
    totalGrants.value > 0 ||
    statusRows.value.length > 0 ||
    sourceRows.value.length > 0 ||
    dailyTrend.value.length > 0 ||
    topRules.value.length > 0 ||
    currencyBreakdown.value.length > 0
  );
});

const load = async () => {
  const result = await bonuses.fetchStats();
  if (!result.success) {
    toastError(result.message || "Failed to load bonus stats.");
  }
};

onMounted(load);
</script>

<template>
  <div class="d-flex flex-column ga-4">
    <v-row>
      <v-col cols="12" class="d-flex justify-end ga-2">
        <v-select
          v-model="granularity"
          :items="[
            { title: 'Day', value: 'day' },
            { title: 'Week', value: 'week' },
            { title: 'Month', value: 'month' },
          ]"
          item-title="title"
          item-value="value"
          variant="outlined"
          density="compact"
          hide-details
          style="max-width: 160px"
          label="Granularity"
        />
        <v-btn color="primary" variant="tonal" :loading="loading" @click="load">
          Refresh stats
        </v-btn>
      </v-col>
    </v-row>

    <v-skeleton-loader v-if="loading && !stats" type="card, card, card" />

    <v-alert v-else-if="!hasAnyData" type="info" variant="tonal">
      No bonus analytics data available for the selected context.
    </v-alert>

    <template v-else>
      <v-row>
        <v-col
          v-for="kpi in kpis"
          :key="kpi.key"
          cols="12"
          sm="6"
          md="4"
          lg="3"
        >
          <v-card>
            <v-card-text>
              <div class="text-caption text-medium-emphasis">
                {{ kpi.label }}
              </div>
              <div class="text-h6 font-weight-bold">{{ kpi.value }}</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title>Status Breakdown</v-card-title>
            <v-divider />
            <v-card-text>
              <apexchart
                v-if="statusRows.length"
                type="donut"
                height="300"
                :options="statusDonut.options"
                :series="statusDonut.series"
              />
              <div v-else class="text-medium-emphasis">
                No status breakdown data.
              </div>
              <div class="mt-2 text-caption text-medium-emphasis">
                Raw keys:
                {{ statusRows.map((row) => row.raw).join(", ") || "-" }}
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card>
            <v-card-title>Source Breakdown</v-card-title>
            <v-divider />
            <v-card-text>
              <apexchart
                v-if="sourceRows.length"
                type="donut"
                height="300"
                :options="sourceDonut.options"
                :series="sourceDonut.series"
              />
              <div v-else class="text-medium-emphasis">
                No source breakdown data.
              </div>
              <div class="mt-2 text-caption text-medium-emphasis">
                Raw keys:
                {{ sourceRows.map((row) => row.raw).join(", ") || "-" }}
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12">
          <v-card>
            <v-card-title>Daily Trend</v-card-title>
            <v-divider />
            <v-card-text>
              <apexchart
                v-if="dailyTrend.length"
                type="line"
                height="350"
                :options="trendChart.options"
                :series="trendChart.series"
              />
              <div v-else class="text-medium-emphasis">
                No daily trend data.
              </div>
              <v-table
                v-if="dailyTrendRows.length"
                density="comfortable"
                class="mt-4"
              >
                <thead>
                  <tr>
                    <th>Day</th>
                    <th>Grants</th>
                    <th>Granted</th>
                    <th>Consumed</th>
                    <th>Remaining</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(row, index) in dailyTrendRows"
                    :key="`${row.label}-${index}`"
                  >
                    <td>{{ row.label }}</td>
                    <td>{{ formatInt(row.grantsCount) }}</td>
                    <td>{{ row.granted }}</td>
                    <td>{{ row.consumed }}</td>
                    <td>{{ row.remaining }}</td>
                  </tr>
                </tbody>
              </v-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title>Top Rules by Grants</v-card-title>
            <v-divider />
            <v-card-text>
              <apexchart
                v-if="topRules.length"
                type="bar"
                height="320"
                :options="topRulesBar.options"
                :series="topRulesBar.series"
              />
              <div v-else class="text-medium-emphasis">No top rules data.</div>
              <v-table
                v-if="topRulesRows.length"
                density="comfortable"
                class="mt-4"
              >
                <thead>
                  <tr>
                    <th>Rule</th>
                    <th>Grants</th>
                    <th>Granted</th>
                    <th>Consumed</th>
                    <th>Remaining</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, index) in topRulesRows" :key="`${index}`">
                    <td>{{ row.ruleName }}</td>
                    <td>{{ formatInt(row.grantsCount) }}</td>
                    <td>{{ row.granted }}</td>
                    <td>{{ row.consumed }}</td>
                    <td>{{ row.remaining }}</td>
                  </tr>
                </tbody>
              </v-table>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card>
            <v-card-title>Currency Breakdown</v-card-title>
            <v-divider />
            <v-card-text>
              <v-table density="comfortable">
                <thead>
                  <tr>
                    <th>Currency</th>
                    <th>Grants</th>
                    <th>Granted</th>
                    <th>Consumed</th>
                    <th>Remaining</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(row, index) in currencyBreakdown"
                    :key="`${row.currency_code}-${index}`"
                  >
                    <td>{{ row.currency_code || "-" }}</td>
                    <td>{{ formatInt(row.grants_count) }}</td>
                    <td>
                      {{
                        amountDisplay(
                          row as unknown as Record<string, unknown>,
                          "granted",
                          row.currency_code,
                        )
                      }}
                    </td>
                    <td>
                      {{
                        amountDisplay(
                          row as unknown as Record<string, unknown>,
                          "consumed",
                          row.currency_code,
                        )
                      }}
                    </td>
                    <td>
                      {{
                        amountDisplay(
                          row as unknown as Record<string, unknown>,
                          "remaining",
                          row.currency_code,
                        )
                      }}
                    </td>
                  </tr>
                  <tr v-if="!currencyBreakdown.length">
                    <td
                      colspan="5"
                      class="text-center text-medium-emphasis py-4"
                    >
                      No currency breakdown data.
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </div>
</template>
