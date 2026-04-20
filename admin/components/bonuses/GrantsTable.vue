<script setup lang="ts">
import type { BonusGrant } from "~/types/bonuses";

const bonuses = useBonusesStore();
const { grants, grantsTotal } = storeToRefs(bonuses);
const { toastError } = useAlert();

const page = ref(1);
const perPage = ref(25);
const search = ref("");
const status = ref("");
const source = ref("");
const mode = ref<"" | "rule" | "amount">("");
const ruleId = ref<number | null>(null);
const dateFrom = ref("");
const dateTo = ref("");

const showEvents = ref(false);
const selectedGrant = ref<BonusGrant | null>(null);

const loading = computed(() => bonuses.loading.grants);

const headers = [
  { title: "ID", key: "id", sortable: false },
  { title: "Player", key: "player_id", sortable: false },
  { title: "Rule", key: "bonus_rule_id", sortable: false },
  { title: "Status", key: "status", sortable: false },
  { title: "Source", key: "source_type", sortable: false, width: "120px" },
  { title: "Granted", key: "amount_granted_ui", sortable: false },
  { title: "Remaining", key: "amount_remaining_ui", sortable: false },
  { title: "Wager Req.", key: "wagering_required_ui", sortable: false },
  { title: "Wager Prog.", key: "wagering_progress_ui", sortable: false },
  { title: "Created", key: "created_at", sortable: false },
  { title: "Actions", key: "actions", sortable: false },
];

const filters = computed(() => ({
  page: page.value,
  length: perPage.value,
  per_page: perPage.value,
  search: search.value || undefined,
  status: status.value || undefined,
  source_type: source.value || undefined,
  source: source.value || undefined,
  bonus_rule_id: ruleId.value || undefined,
  mode: mode.value || undefined,
  rule_id: ruleId.value || undefined,
  date_from: dateFrom.value || undefined,
  date_to: dateTo.value || undefined,
}));

const load = async () => {
  const result = await bonuses.fetchGrants(filters.value);
  if (!result.success) {
    toastError(result.message || "Failed to load grants.");
  }
};

const openEvents = (grant: BonusGrant) => {
  selectedGrant.value = grant;
  showEvents.value = true;
};

const formatAmount = (
  ui: number | string | null | undefined,
  base: number | string | null | undefined,
): string => {
  const uiValue = ui ?? base;
  if (uiValue === null || uiValue === undefined || uiValue === "") {
    return "-";
  }
  return String(uiValue);
};

watch(
  [page, perPage],
  () => {
    load();
  },
  { immediate: true },
);

const applyFilters = () => {
  page.value = 1;
  load();
};

const resetFilters = () => {
  search.value = "";
  status.value = "";
  source.value = "";
  mode.value = "";
  ruleId.value = null;
  dateFrom.value = "";
  dateTo.value = "";
  page.value = 1;
  load();
};
</script>

<template>
  <v-card>
    <v-card-title>Bonus Grants</v-card-title>
    <v-divider />

    <v-card-text>
      <v-row>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="search"
            label="Search"
            variant="outlined"
            density="comfortable"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field
            v-model="status"
            label="Status"
            variant="outlined"
            density="comfortable"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field
            v-model="source"
            label="Source"
            variant="outlined"
            density="comfortable"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="mode"
            label="Mode"
            variant="outlined"
            density="comfortable"
            :items="[
              { title: 'All', value: '' },
              { title: 'Rule', value: 'rule' },
              { title: 'Amount', value: 'amount' },
            ]"
            item-title="title"
            item-value="value"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model.number="ruleId"
            type="number"
            label="Rule ID"
            variant="outlined"
            density="comfortable"
          />
        </v-col>

        <v-col cols="12" md="3">
          <v-text-field
            v-model="dateFrom"
            type="datetime-local"
            label="Date from"
            variant="outlined"
            density="comfortable"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="dateTo"
            type="datetime-local"
            label="Date to"
            variant="outlined"
            density="comfortable"
          />
        </v-col>
        <v-col cols="12" md="6" class="d-flex ga-2 align-center">
          <v-btn color="primary" :loading="loading" @click="applyFilters"
            >Apply Filters</v-btn
          >
          <v-btn variant="tonal" @click="resetFilters">Reset</v-btn>
        </v-col>
      </v-row>

      <v-skeleton-loader v-if="loading" type="table" />

      <v-data-table
        v-else
        :headers="headers"
        :items="grants"
        item-key="id"
        density="comfortable"
        fixed-header
        height="72vh"
        :items-per-page="perPage"
        hide-default-footer
      >
        <template #item.player_id="{ item }">
          {{ item.player_username ?? "-" }}
        </template>

        <template #item.bonus_rule_id="{ item }">
          <div>{{ item.bonus_rule_id ?? item.rule_id ?? "-" }}</div>
          <div
            v-if="item.meta?.rule_name"
            class="text-caption text-medium-emphasis"
          >
            {{ item.meta?.rule_name }}
          </div>
        </template>

        <template #item.source_type="{ item }">
          <div>{{ item.source_type ?? item.source ?? "-" }}</div>
          <div
            v-if="item.source_ref"
            class="text-caption text-medium-emphasis"
            style="max-width: 120px"
          >
            {{ item.source_ref }}
          </div>
        </template>

        <template #item.amount_granted_ui="{ item }">
          {{ formatAmount(item.amount_granted_ui, item.amount_granted_base) }}
          {{ item.currency_code || "" }}
        </template>

        <template #item.amount_remaining_ui="{ item }">
          {{
            formatAmount(item.amount_remaining_ui, item.amount_remaining_base)
          }}
          {{ item.currency_code || "" }}
        </template>

        <template #item.wagering_required_ui="{ item }">
          {{
            formatAmount(item.wagering_required_ui, item.wagering_required_base)
          }}
          {{ item.currency_code || "" }}
        </template>

        <template #item.wagering_progress_ui="{ item }">
          {{
            formatAmount(item.wagering_progress_ui, item.wagering_progress_base)
          }}
          {{ item.currency_code || "" }}
        </template>

        <template #item.actions="{ item }">
          <v-btn
            size="small"
            variant="tonal"
            color="info"
            @click="openEvents(item)"
          >
            Events
          </v-btn>
        </template>

        <template #no-data>
          <div class="text-medium-emphasis py-6">No grants found.</div>
        </template>
      </v-data-table>

      <div class="d-flex justify-space-between align-center mt-4">
        <div class="text-body-2 text-medium-emphasis">
          Total: {{ grantsTotal }}
        </div>

        <div class="d-flex ga-3 align-center">
          <v-select
            v-model="perPage"
            density="compact"
            variant="outlined"
            hide-details
            style="max-width: 120px"
            :items="[10, 25, 50, 100]"
            label="Per page"
          />
          <v-pagination
            v-model="page"
            :length="Math.max(1, Math.ceil(grantsTotal / perPage))"
            density="comfortable"
          />
        </div>
      </div>
    </v-card-text>
  </v-card>

  <BonusesGrantEventsDialog v-model="showEvents" :grant="selectedGrant" />
</template>
