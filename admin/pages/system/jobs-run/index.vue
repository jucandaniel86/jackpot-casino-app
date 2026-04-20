<template>
  <div class="d-flex flex-column ga-4">
    <div class="d-flex flex-wrap ga-3 align-center">
      <SelectDatetimepicker v-model="period" />
      <v-select
        v-model="status"
        :items="statusItems"
        label="Status"
        density="compact"
        variant="outlined"
        style="max-width: 220px"
        hide-details
      />
      <v-spacer />
      <v-btn variant="tonal" :loading="loading" @click="fetchAll"
        >Refresh</v-btn
      >
    </div>

    <v-card rounded="xl">
      <v-tabs v-model="tab" grow>
        <v-tab value="all">All</v-tab>
        <v-tab value="SolanaDepositScan">Deposit Scan</v-tab>
        <v-tab value="SolanaSweep">Sweep</v-tab>
        <v-tab value="SolanaTxConfirm">Tx Confirm</v-tab>
      </v-tabs>

      <v-divider />

      <v-card-text class="pt-4">
        <v-data-table
          :headers="headers"
          :items="items"
          :items-length="total"
          :loading="loading"
          item-key="uuid"
          fixed-header
          height="620"
          class="elevation-0"
          v-model:page="page"
          v-model:items-per-page="perPage"
          @update:page="fetchAll"
          @update:items-per-page="fetchAll"
        >
          <template #item.status="{ item }">
            <v-chip
              :color="chipColor(item.status)"
              variant="tonal"
              size="small"
            >
              {{ item.status }}
            </v-chip>
          </template>

          <template #item.job_name="{ item }">
            <div class="text-no-wrap font-weight-medium">
              {{ item.job_name || "—" }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ shortClass(item.job_class) }}
            </div>
          </template>

          <template #item.started_at="{ item }">
            <div class="text-no-wrap">{{ fmtDate(item.started_at) }}</div>
          </template>

          <template #item.duration_ms="{ item }">
            <div class="text-no-wrap">
              {{ item.duration_ms ? `${item.duration_ms} ms` : "—" }}
            </div>
          </template>

          <template #item.actions="{ item }">
            <v-btn size="small" variant="text" @click="openDetails(item.uuid)">
              Details
            </v-btn>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <v-dialog v-model="detailsOpen" max-width="900">
      <v-card rounded="xl">
        <v-card-title class="d-flex align-center justify-space-between">
          <span>Job Run Details</span>
          <v-btn icon="mdi-close" variant="text" @click="detailsOpen = false" />
        </v-card-title>
        <v-divider />
        <v-card-text>
          <div v-if="details" class="d-flex flex-column ga-3">
            <div class="d-flex flex-wrap ga-2">
              <v-chip variant="tonal">{{ details.job_name }}</v-chip>
              <v-chip variant="tonal" :color="chipColor(details.status)">{{
                details.status
              }}</v-chip>
              <v-chip variant="tonal">Attempt: {{ details.attempt }}</v-chip>
              <v-chip variant="tonal"
                >Duration: {{ details.duration_ms ?? "—" }} ms</v-chip
              >
            </div>

            <div class="text-caption text-medium-emphasis">
              {{ details.job_class }}
            </div>

            <v-divider />

            <div>
              <div class="text-subtitle-2 mb-1">Context</div>
              <pre class="text-caption">{{
                JSON.stringify(details.context ?? {}, null, 2)
              }}</pre>
            </div>

            <div>
              <div class="text-subtitle-2 mb-1">Result</div>
              <pre class="text-caption">{{
                JSON.stringify(details.result ?? {}, null, 2)
              }}</pre>
            </div>

            <div v-if="details.status === 'failed'">
              <div class="text-subtitle-2 mb-1">Error</div>
              <pre class="text-caption">{{ details.error_message }}</pre>
              <v-expansion-panels variant="accordion" class="mt-2">
                <v-expansion-panel>
                  <v-expansion-panel-title>Stack trace</v-expansion-panel-title>
                  <v-expansion-panel-text>
                    <pre class="text-caption">{{ details.error_trace }}</pre>
                  </v-expansion-panel-text>
                </v-expansion-panel>
              </v-expansion-panels>
            </div>
          </div>

          <div v-else class="text-medium-emphasis">Loading…</div>
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
const tab = ref<
  "all" | "SolanaDepositScan" | "SolanaSweep" | "SolanaTxConfirm"
>("all");

const period = ref<[string, string]>([
  new Date(Date.now() - 24 * 3600 * 1000).toISOString(),
  new Date().toISOString(),
]);

const statusItems = [
  { title: "All", value: null },
  { title: "Running", value: "running" },
  { title: "Success", value: "success" },
  { title: "Failed", value: "failed" },
];

const status = ref<string | null>(null);

const headers = [
  { title: "Job", key: "job_name" },
  { title: "Status", key: "status" },
  { title: "Started", key: "started_at" },
  { title: "Finished", key: "finished_at" },
  { title: "Duration", key: "duration_ms" },
  { title: "", key: "actions", sortable: false },
];

const items = ref<any[]>([]);
const total = ref(0);
const page = ref(1);
const perPage = ref(50);
const loading = ref(false);

const detailsOpen = ref(false);
const details = ref<any>(null);

function chipColor(s: string) {
  if (s === "running") return "warning";
  if (s === "failed") return "error";
  return "success";
}

function fmtDate(v: any) {
  if (!v) return "—";
  const d = new Date(v);
  return d.toLocaleString();
}

function shortClass(v: string) {
  if (!v) return "";
  return v.split("\\").slice(-2).join("\\");
}

async function fetchAll() {
  loading.value = true;
  try {
    const query: any = {
      from: period.value[0],
      to: period.value[1],
      status: status.value ?? undefined,
      page: page.value,
      per_page: perPage.value,
    };
    if (tab.value !== "all") query.job_name = tab.value;

    const { data } = await useAPIFetch("/system/job-runs", query);

    const res: any = data.result;
    items.value = res?.data ?? [];
    total.value = res?.total ?? 0;
  } finally {
    loading.value = false;
  }
}

async function openDetails(uuid: string) {
  detailsOpen.value = true;
  details.value = null;

  const { data } = await useAPIFetch(`/system/job-runs/${uuid}`);
  details.value = data?.result ?? null;
}

watch(
  [tab, status, period],
  () => {
    page.value = 1;
    fetchAll();
  },
  { deep: true, immediate: true },
);

useHead({
  title: "Jobs Run",
});
</script>
