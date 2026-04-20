<script setup lang="ts">
import SweepsMetricsCard from "~/components/stats/SweepsMetricsCard.vue";

const currency = ref("PEP");
const period = ref<[string, string]>([
  new Date(Date.now() - 7 * 24 * 3600 * 1000).toISOString(),
  new Date().toISOString(),
]);

const statusItems = [
  { title: "All", value: null },
  { title: "Pending", value: "pending" },
  { title: "Failed", value: "failed" },
  { title: "Confirmed", value: "confirmed" },
];

const status = ref<string | null>(null);

const headers = [
  { title: "Username", key: "username" },
  { title: "Txid", key: "txid" },
  { title: "Amount", key: "amount" },
  { title: "Currency", key: "currency" },
  { title: "To address", key: "to_address" },
  { title: "Status", key: "status" },
  { title: "Created", key: "created_at" },
  { title: "", key: "actions", sortable: false },
];

const items = ref<any[]>([]);
const total = ref(0);
const page = ref(1);
const perPage = ref(50);
const loading = ref(false);

const detailsOpen = ref(false);
const selected = ref<any>(null);

function chipColor(s: string) {
  if (s === "pending") return "warning";
  if (s === "failed") return "error";
  return "success";
}
function symFromCurrency(c: string) {
  return c?.includes(":") ? c.split(":")[1] : c;
}
function fmt(v: any) {
  const n = Number(v || 0);
  return n.toLocaleString(undefined, { maximumFractionDigits: 2 });
}

function openList(st: "pending" | "failed") {
  status.value = st;
  page.value = 1;
  fetchList();
}

function openDetails(item: any) {
  selected.value = item;
  detailsOpen.value = true;
}

async function fetchList() {
  loading.value = true;
  try {
    const { data } = await useAPIFetch("/crypto/sweeps", {
      currency_code: currency.value,
      status: status.value ?? undefined,
      from: period.value[0],
      to: period.value[1],
      page: page.value,
      per_page: perPage.value,
    });

    const res: any = data.result;
    items.value = res?.data ?? [];
    total.value = res?.total ?? 0;
  } finally {
    loading.value = false;
  }
}

watch(
  [currency, period],
  () => {
    page.value = 1;
    fetchList();
  },
  { deep: true, immediate: true },
);
</script>

<template>
  <div class="d-flex flex-column ga-4">
    <div class="d-flex flex-wrap ga-3 align-center">
      <SelectCurrencies v-model="currency" />
      <SelectDatetimepicker v-model="period" />
      <v-spacer />
    </div>

    <SweepsMetricsCard :currency="currency" :period="period" @open="openList" />

    <v-card class="pa-4">
      <div class="d-flex align-center justify-space-between">
        <div class="text-subtitle-1 font-weight-medium">Sweeps list</div>

        <div class="d-flex ga-2">
          <v-select
            v-model="status"
            :items="statusItems"
            label="Status"
            density="compact"
            variant="outlined"
            style="max-width: 180px"
            hide-details
          />
          <v-btn variant="tonal" @click="fetchList" :loading="loading"
            >Refresh</v-btn
          >
        </div>
      </div>

      <v-divider class="my-3" />

      <v-data-table
        :headers="headers"
        :items="items"
        :items-length="total"
        :loading="loading"
        fixed-header
        height="520"
        class="elevation-0"
        item-key="uuid"
        v-model:page="page"
        v-model:items-per-page="perPage"
        @update:page="fetchList"
        @update:items-per-page="fetchList"
      >
        <template #item.amount="{ item }">
          <div class="text-no-wrap">
            {{ fmt(item.amount_base) }} {{ symFromCurrency(item.currency) }}
          </div>
        </template>

        <template #item.status="{ item }">
          <v-chip :color="chipColor(item.status)" variant="tonal" size="small">
            {{ item.status }}
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-btn size="small" variant="text" @click="openDetails(item)"
            >More</v-btn
          >
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="detailsOpen" max-width="800">
      <v-card rounded="xl">
        <v-card-title class="d-flex align-center justify-space-between">
          <span>Transaction details</span>
          <v-btn icon="mdi-close" variant="text" @click="detailsOpen = false" />
        </v-card-title>
        <v-divider />
        <v-card-text>
          <pre class="text-caption">{{
            JSON.stringify(selected, null, 2)
          }}</pre>
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>
