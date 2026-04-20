<script setup lang="ts">
import moment from "moment";
import Money from "~/components/shared/Money.vue";

type Row = {
  username: string | null;
  uuid: string;
  currency: string;
  amount: string;
  type: "deposit" | "withdraw" | "sweep" | "game_win" | "game_bet";
  status: "pending" | "confirmed" | "failed";
  txid: string | null;
  to_address: string | null;
  link: string | null;
  meta: any;
  created_at: string;
};

const currency = ref<string | null>("PEP");
const type = ref<string | null>(null);
const status = ref<string | null>(null);
const period = ref([moment().subtract(1, "week"), moment()]);
const page = ref(1);
const perPage = ref(25);

const items = ref<Row[]>([]);
const total = ref(0);
const loading = ref(false);

const selected = ref<Row | null>(null);
const dialog = ref(false);

const headers = [
  { title: "Username", key: "username", width: 160 },
  { title: "Txid", key: "txid", width: 260 },
  { title: "Amount", key: "amount", width: 140 },
  { title: "Currency", key: "currency", width: 120 },
  { title: "To", key: "to_address", width: 260 },
  { title: "More", key: "actions", width: 120, sortable: false },
] as const;

const convertDateToUTC = (_date: any) => {
  return moment(_date).format("YYYY-MM-DD HH:mm:ss");
};

async function fetchData() {
  loading.value = true;
  try {
    const query: any = {
      page: page.value,
      per_page: perPage.value,
    };

    if (currency.value) query.currency_code = currency.value;
    if (type.value) query.type = type.value;
    if (status.value) query.status = status.value;
    if (period.value?.[0]) query.from = convertDateToUTC(period.value[0]);
    if (period.value?.[1]) query.to = convertDateToUTC(period.value[1]);

    const { data }: any = await useAPIFetch("/stats/transactions", query);
    // Laravel Resource collection
    items.value = data.data;
    total.value = data.meta?.total ?? 0;
  } finally {
    loading.value = false;
  }
}

watch([currency, type, status, period], () => {
  page.value = 1;
  fetchData();
});

onMounted(fetchData);

function openDetails(row: Row) {
  selected.value = row;
  dialog.value = true;
}

const statusColor = (value: Row["status"]) => {
  if (value === "confirmed") return "success";
  if (value === "failed") return "error";
  return "warning";
};

const typeColorMap: Record<Row["type"], string> = {
  deposit: "#16a34a",
  withdraw: "#f97316",
  sweep: "#0ea5e9",
  game_win: "#2563eb",
  game_bet: "#7c3aed",
};

const typeColor = (value: Row["type"]) => typeColorMap[value] ?? "#64748b";

const formatDate = (value: string) => moment(value).format("LLL");
</script>

<template>
  <v-container fluid class="pa-0">
    <v-card class="mb-4" color="white" variant="elevated">
      <v-card-text class="d-flex align-center justify-space-between">
        <div>
          <div class="text-h5 font-weight-bold">Crypto Transactions</div>
          <div class="text-caption text-medium-emphasis">
            Deposits & withdrawals (on-chain)
          </div>
        </div>
        <v-btn variant="flat" color="primary" @click="fetchData">
          <v-icon start icon="mdi-refresh" />
          Refresh
        </v-btn>
      </v-card-text>
    </v-card>

    <!-- Filters -->
    <v-card variant="elevated" color="white" class="mb-4">
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="3">
            <SelectCurrencies v-model="currency" variant="outlined" />
          </v-col>

          <v-col cols="12" md="2">
            <v-select
              v-model="type"
              :items="[
                { title: 'All', value: null },
                { title: 'Deposit', value: 'deposit' },
                { title: 'Withdraw', value: 'withdraw' },
                { title: 'Sweep', value: 'sweep' },
                { title: 'Game win', value: 'game_win' },
                { title: 'Game bet', value: 'game_bet' },
              ]"
              item-title="title"
              item-value="value"
              label="Type"
              variant="outlined"
              density="compact"
              clearable
            />
          </v-col>

          <v-col cols="12" md="2">
            <v-select
              v-model="status"
              :items="[
                { title: 'All', value: null },
                { title: 'Pending', value: 'pending' },
                { title: 'Confirmed', value: 'confirmed' },
                { title: 'Failed', value: 'failed' },
              ]"
              item-title="title"
              item-value="value"
              label="Status"
              variant="outlined"
              density="compact"
              clearable
            />
          </v-col>

          <v-col cols="12" md="5">
            <SelectDatetimepicker v-model="period" />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Table -->
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
        <template #item.username="{ item }">
          <span class="font-weight-medium">{{ item.username || "—" }}</span>
        </template>

        <template #item.txid="{ item }">
          <div class="d-flex flex-column">
            <span
              class="text-body-2"
              style="
                max-width: 240px;
                overflow: hidden;
                text-overflow: ellipsis;
              "
            >
              {{ item.txid || "—" }}
            </span>
            <div class="d-flex align-center ga-2 mt-1">
              <v-chip size="x-small" :color="statusColor(item.status)" label>
                {{ item.status }}
              </v-chip>
              <v-chip size="x-small" :color="typeColor(item.type)" label>
                {{ item.type }}
              </v-chip>
            </div>
          </div>
        </template>

        <template #item.amount="{ item }">
          <span
            class="font-weight-medium"
            :style="{ color: typeColor(item.type) }"
          >
            <Money :ui="item.amount" :precision="2" />
          </span>
        </template>

        <template #item.to_address="{ item }">
          <span
            style="
              max-width: 240px;
              overflow: hidden;
              text-overflow: ellipsis;
              display: inline-block;
            "
          >
            {{ item.to_address || "—" }}
          </span>
        </template>

        <template #item.actions="{ item }">
          <v-btn
            size="small"
            color="primary"
            variant="tonal"
            @click="openDetails(item)"
          >
            More details
          </v-btn>
        </template>
      </v-data-table-server>
    </v-card>

    <!-- Details modal -->
    <v-dialog v-model="dialog" max-width="880">
      <v-card v-if="selected">
        <v-card-title class="d-flex justify-space-between align-center">
          <div>Transaction details</div>
          <v-btn icon="mdi-close" variant="text" @click="dialog = false" />
        </v-card-title>

        <v-divider />

        <v-card-text class="d-flex flex-column ga-3">
          <v-row dense>
            <v-col cols="12" md="6">
              <v-list density="compact">
                <v-list-item
                  title="Username"
                  :subtitle="selected.username || '—'"
                />
                <v-list-item title="UUID" :subtitle="selected.uuid" />
                <v-list-item title="Type">
                  <template #subtitle>
                    <v-chip
                      size="x-small"
                      :color="typeColor(selected.type)"
                      label
                    >
                      {{ selected.type }}
                    </v-chip>
                  </template>
                </v-list-item>
                <v-list-item title="Status">
                  <template #subtitle>
                    <v-chip
                      size="x-small"
                      :color="statusColor(selected.status)"
                      label
                    >
                      {{ selected.status }}
                    </v-chip>
                  </template>
                </v-list-item>
                <v-list-item title="Currency" :subtitle="selected.currency" />
                <v-list-item title="Amount" :subtitle="selected.amount" />
                <v-list-item
                  title="Created at"
                  :subtitle="formatDate(selected.created_at)"
                />
              </v-list>
            </v-col>

            <v-col cols="12" md="6">
              <v-list density="compact">
                <v-list-item title="Txid" :subtitle="selected.txid || '—'" />
                <v-list-item
                  title="To address"
                  :subtitle="selected.to_address || '—'"
                />
                <v-list-item title="Explorer">
                  <template #subtitle>
                    <a
                      v-if="selected.link"
                      :href="selected.link"
                      target="_blank"
                      rel="noreferrer"
                    >
                      Open in explorer
                    </a>
                    <span v-else>—</span>
                  </template>
                </v-list-item>
              </v-list>
            </v-col>
          </v-row>

          <div>
            <div class="text-subtitle-2 font-weight-bold mb-2">Meta</div>
            <v-card variant="tonal">
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
        <v-card-actions class="justify-end">
          <v-btn variant="outlined" @click="dialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>
