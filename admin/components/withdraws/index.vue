<script setup lang="ts">
type Row = any;

const currency = ref<string | null>(null);
const status = ref<string | null>("pending");
const period = ref<[string | null, string | null]>([null, null]);

const page = ref(1);
const perPage = ref(25);
const total = ref(0);
const items = ref<Row[]>([]);
const loading = ref(false);

const dialog = ref(false);
const selected = ref<Row | null>(null);

const headers = [
  { title: "Username", key: "username", width: 180 },
  { title: "Amount", key: "amount_ui", width: 140 },
  { title: "Currency", key: "currency", width: 120 },
  { title: "To", key: "to_address", width: 260 },
  { title: "Status", key: "status", width: 140 },
  { title: "Created", key: "created_at", width: 200 },
  { title: "Actions", key: "actions", sortable: false, width: 160 },
] as const;

async function fetchData() {
  loading.value = true;
  try {
    const query: any = { page: page.value, per_page: perPage.value };
    if (currency.value)
      query.currency = currency.value.includes(":")
        ? currency.value
        : `SOLANA:${currency.value}`;
    if (status.value) query.status = status.value;
    if (period.value?.[0]) query.from = period.value[0];
    if (period.value?.[1]) query.to = period.value[1];

    const { data }: any = await useAPIFetch("/withdraw-requests", query);
    const payload = data?.data ?? data;
    items.value = payload?.data ?? [];
    total.value = Number(payload?.total ?? 0);
  } finally {
    loading.value = false;
  }
}

watch([currency, status, period], () => {
  page.value = 1;
  fetchData();
});
onMounted(fetchData);

function openDetails(row: Row) {
  selected.value = row;
  dialog.value = true;
}

const statusColor = (value: string) => {
  if (value === "approved") return "info";
  if (value === "completed") return "success";
  if (value === "rejected") return "error";
  if (value === "failed") return "error";
  return "warning";
};

async function approve() {
  if (!selected.value) return;
  await useApiPostFetch(`/withdraw-requests/${selected.value.uuid}/approve`, {
    note: "Approved",
  });
  dialog.value = false;
  fetchData();
}

async function reject() {
  if (!selected.value) return;
  await useApiPostFetch(`/withdraw-requests/${selected.value.uuid}/reject`, {
    reason: "Rejected by admin",
  });
  dialog.value = false;
  fetchData();
}

const txid = ref("");
async function complete() {
  if (!selected.value) return;
  await useApiPostFetch(`/withdraw-requests/${selected.value.uuid}/complete`, {
    txid: txid.value || null,
  });
  dialog.value = false;
  txid.value = "";
  fetchData();
}
 
</script>

<template>
  <v-container fluid class="pa-0">  
    <v-card class="mb-4" color="white" variant="elevated">
      <v-card-text class="d-flex align-center justify-space-between">
        <div>
          <div class="text-h5 font-weight-bold">Withdraw Requests</div>
          <div class="text-caption text-medium-emphasis">
            Manual approval & payout
          </div>
        </div>
        <v-btn variant="flat" color="primary" @click="fetchData">
          <v-icon start icon="mdi-refresh" /> Refresh
        </v-btn>
      </v-card-text>
    </v-card>

    <v-card variant="elevated" color="white" class="mb-4">
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="3">
            <div class="text-caption text-medium-emphasis mb-1">Currency</div>
            <SelectCurrencies v-model="currency" />
          </v-col>

          <v-col cols="12" md="3">
            <div class="text-caption text-medium-emphasis mb-1">Status</div>
            <v-select
              v-model="status"
              :items="[
                { title: 'Pending', value: 'pending' },
                { title: 'Approved', value: 'approved' },
                { title: 'Completed', value: 'completed' },
                { title: 'Rejected', value: 'rejected' },
                { title: 'Failed', value: 'failed' },
              ]"
              item-title="title"
              item-value="value"
              label="Status"
              variant="outlined"
              density="comfortable"
            />
          </v-col>

          <v-col cols="12" md="6">
            <div class="text-caption text-medium-emphasis mb-1">Date range</div>
            <SelectDatetimepicker v-model="period" />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card variant="elevated" color="blue-grey-lighten-5">
      <v-data-table-server
        :headers="headers as any"
        :items="items"
        :items-length="total"
        :loading="loading"
        fixed-header
        height="560"
        v-model:page="page"
        v-model:items-per-page="perPage"
        @update:page="fetchData"
        @update:items-per-page="fetchData"
      >
        <template #item.to_address="{ item }">
          <span
            style="
              max-width: 240px;
              overflow: hidden;
              text-overflow: ellipsis;
              display: inline-block;
            "
          >
            {{ item.to_address }}
          </span>
        </template>

        <template #item.status="{ item }">
          <v-chip size="small" :color="statusColor(item.status)" label>
            {{ item.status }}
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-btn
            size="small"
            variant="flat"
            color="primary"
            @click="openDetails(item)"
          >
            Details
          </v-btn>
        </template>
      </v-data-table-server>
    </v-card>

    <v-dialog v-model="dialog" max-width="900">
      <v-card v-if="selected">
        <v-card-title class="d-flex justify-space-between align-center">
          <div>Withdraw Request</div>
          <v-btn icon="mdi-close" variant="text" @click="dialog = false" />
        </v-card-title>
        <v-divider />

        <v-card-text class="d-flex flex-column ga-4">
          <v-row dense>
            <v-col cols="12" md="7">
              <v-list density="compact">
                <v-list-item title="Username" :subtitle="selected.username" />
                <v-list-item title="UUID" :subtitle="selected.uuid" />
                <v-list-item title="Status" :subtitle="selected.status" />
                <v-list-item title="Currency" :subtitle="selected.currency" />
                <v-list-item title="Amount" :subtitle="selected.amount_ui" />
                <v-list-item
                  title="To address"
                  :subtitle="selected.to_address"
                />
                <v-list-item
                  title="Created at"
                  :subtitle="selected.created_at"
                />
              </v-list>

              <v-text-field
                v-model="txid"
                label="Txid (optional, proof)"
                variant="outlined"
                density="comfortable"
              />
            </v-col>

            <v-col cols="12" md="5">
              <div class="text-subtitle-2 font-weight-bold mb-2">
                QR (Receiving address)
              </div>
              <SharedQrCode :text="selected.to_address" />
            </v-col>
          </v-row>

          <div>
            <div class="text-subtitle-2 font-weight-bold mb-2">Meta</div>
            <v-card variant="tonal" color="blue-grey-lighten-1">
              <v-card-text>
                <pre
                  style="
                    white-space: pre-wrap;
                    word-break: break-word;
                    margin: 0;
                  "
                  >{{ JSON.stringify(selected.meta, null, 2) }}
                </pre>
              </v-card-text>
            </v-card>
          </div>
        </v-card-text>

        <v-divider />
        <v-card-actions class="justify-end ga-2">
          <v-btn
            color="error"
            variant="flat"
            @click="reject"
            v-if="['pending', 'approved'].includes(selected.status)"
          >
            Reject
          </v-btn>
          <v-btn
            color="secondary"
            variant="flat"
            @click="approve"
            v-if="selected.status === 'pending'"
          >
            Approve
          </v-btn>
          <v-btn
            color="primary"
            variant="flat"
            @click="complete"
            v-if="['pending', 'approved'].includes(selected.status)"
          >
            Mark Completed
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>
