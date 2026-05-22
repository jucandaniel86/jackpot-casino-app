<script setup lang="ts">
import { TOURNAMENTS_HEADERS } from "./config";
import type {
  Tournament,
  TournamentListFilters,
  TournamentPrizeAward,
  TournamentRandomPlayer,
} from "~/types/tournaments";

const router = useRouter();
const { confirmDelete, toastSuccess, toastError, axiosErrorAlert } = useAlert();
const api = useTournamentsApi();

const items = ref<Tournament[]>([]);
const total = ref(0);
const loading = ref(false);
const errorMessage = ref<string | null>(null);
const extractionDialog = ref(false);
const extractionLoading = ref(false);
const extractionTournament = ref<Tournament | null>(null);
const eligiblePlayers = ref<TournamentRandomPlayer[]>([]);
const extractedAwards = ref<TournamentPrizeAward[]>([]);

const page = ref(1);
const perPage = ref(20);

const filters = reactive<{
  search: string;
  status: string | null;
  is_active: boolean | null;
  started_from: string | null;
  started_to: string | null;
  ended_from: string | null;
  ended_to: string | null;
  game_id: string;
}>({
  search: "",
  status: null,
  is_active: null,
  started_from: null,
  started_to: null,
  ended_from: null,
  ended_to: null,
  game_id: "",
});

const sortBy = ref<
  Array<{ key: string; order?: "asc" | "desc" | boolean }>
>([{ key: "started_at", order: "desc" }]);

const headers = ref(TOURNAMENTS_HEADERS);

const toQuery = (): TournamentListFilters => {
  const sort = sortBy.value?.[0] ?? null;
  const sortKey = sort?.key ? String(sort.key) : "started_at";
  const sortDirection =
    String(sort?.order ?? "desc").toLowerCase() === "asc" ? "asc" : "desc";

  return {
    page: page.value,
    per_page: perPage.value,
    sort_by:
      ([
        "name",
        "started_at",
        "ended_at",
        "status",
        "created_at",
      ] as const).includes(sortKey as any)
        ? (sortKey as any)
        : "started_at",
    sort_direction: sortDirection,
    search: filters.search?.trim() ? filters.search.trim() : null,
    status: (filters.status as any) || undefined,
    is_active: filters.is_active,
    started_from: filters.started_from || null,
    started_to: filters.started_to || null,
    ended_from: filters.ended_from || null,
    ended_to: filters.ended_to || null,
    game_id: filters.game_id?.trim() ? filters.game_id.trim() : null,
  };
};

async function fetchData() {
  loading.value = true;
  errorMessage.value = null;

  try {
    const result = await api.listTournaments(toQuery());
    if (!result.success || !result.data) {
      errorMessage.value = result.message || "Failed to fetch tournaments.";
      items.value = [];
      total.value = 0;
      return;
    }

    items.value = result.data.items;
    total.value = result.data.total;
  } catch (err: any) {
    errorMessage.value = "Failed to fetch tournaments.";
    axiosErrorAlert(err, true);
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  page.value = 1;
  fetchData();
}

function onReset() {
  filters.search = "";
  filters.status = null;
  filters.is_active = null;
  filters.started_from = null;
  filters.started_to = null;
  filters.ended_from = null;
  filters.ended_to = null;
  filters.game_id = "";
  perPage.value = 20;
  page.value = 1;
  sortBy.value = [{ key: "started_at", order: "desc" }];
  fetchData();
}

function goCreate() {
  router.push({ name: "casino-tournaments-add" });
}

function goEdit(id: string) {
  router.push({ name: "casino-tournaments-edit-id", params: { id } });
}

function onDelete(item: Tournament) {
  confirmDelete(async () => {
    const result = await api.deleteTournament(String(item.id));
    if (!result.success) {
      toastError(result.message || "Failed to delete tournament.");
      return;
    }

    toastSuccess(result.message || "Tournament deleted successfully.");
    fetchData();
  });
}

async function onClone(item: Tournament) {
  const result = await api.cloneTournament(String(item.id));
  if (!result.success) {
    toastError(result.message || "Failed to clone tournament.");
    return;
  }

  toastSuccess(result.message || "Tournament cloned successfully.");
  fetchData();
}

async function onEnd(item: Tournament) {
  const result = await api.endTournament(String(item.id));
  if (!result.success) {
    toastError(result.message || "Failed to end tournament.");
    return;
  }

  toastSuccess(result.message || "Tournament ended successfully.");
  fetchData();
}

async function onExportChanceList(item: Tournament) {
  const result = await api.exportChanceList(String(item.id));
  if (!result.success || !result.data) {
    toastError(result.message || "Failed to export chance list.");
    return;
  }

  const url = URL.createObjectURL(result.data);
  const link = document.createElement("a");
  link.href = url;
  link.download = `tournament-${item.id}-chance-list.csv`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

async function openRandomExtraction(item: Tournament) {
  extractionTournament.value = item;
  extractionDialog.value = true;
  eligiblePlayers.value = [];
  extractedAwards.value = [];
  extractionLoading.value = true;

  const result = await api.randomExtractionEligible(String(item.id));
  extractionLoading.value = false;

  if (!result.success || !result.data) {
    toastError(result.message || "Failed to load eligible players.");
    return;
  }

  eligiblePlayers.value = result.data.eligible_players ?? [];
}

async function extractRandomAwards() {
  if (!extractionTournament.value) return;

  extractionLoading.value = true;
  const result = await api.randomExtraction(String(extractionTournament.value.id));
  extractionLoading.value = false;

  if (!result.success || !result.data) {
    toastError(result.message || "Failed to run random extraction.");
    return;
  }

  eligiblePlayers.value = result.data.eligible_players ?? [];
  extractedAwards.value = result.data.awards ?? [];
}

async function approveRandomAwards() {
  if (!extractionTournament.value || extractedAwards.value.length === 0) return;

  extractionLoading.value = true;
  const result = await api.approveRandomExtraction(
    String(extractionTournament.value.id),
    extractedAwards.value,
  );
  extractionLoading.value = false;

  if (!result.success) {
    toastError(result.message || "Failed to approve random extraction.");
    return;
  }

  toastSuccess(result.message || "Random extraction approved successfully.");
  extractionDialog.value = false;
  fetchData();
}

function canRandomExtract(item: Tournament) {
  return (
    item.tournament_type === "RANDOM" &&
    item.status === "finished" &&
    !item.random_prizes_allocated_at
  );
}

function statusColor(status: string) {
  if (status === "active") return "success";
  if (status === "scheduled") return "info";
  if (status === "finished") return "secondary";
  if (status === "cancelled") return "error";
  return "warning";
}

function fmtDate(v: any) {
  if (!v) return "—";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  return d.toLocaleString();
}
</script>

<template>
  <v-container fluid class="pa-0">
    <v-card class="mb-4" color="white" variant="elevated">
      <v-card-text class="d-flex align-center justify-space-between">
        <div>
          <div class="text-h5 font-weight-bold">Tournaments</div>
          <div class="text-caption text-medium-emphasis">
            Create and manage tournaments
          </div>
        </div>
        <div class="d-flex ga-2">
          <v-btn variant="flat" color="primary" @click="fetchData">
            <v-icon start icon="mdi-refresh" /> Refresh
          </v-btn>
          <v-btn variant="flat" color="blue" @click="goCreate">
            <v-icon start icon="mdi-plus" /> Add tournament
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <v-card variant="elevated" color="white" class="mb-4">
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="3">
            <div class="text-caption text-medium-emphasis mb-1">Search</div>
            <v-text-field
              v-model="filters.search"
              placeholder="Name / status / etc."
              variant="outlined"
              density="comfortable"
              clearable
              hide-details
              @keyup.enter="onSearch"
            />
          </v-col>

          <v-col cols="12" md="2">
            <div class="text-caption text-medium-emphasis mb-1">Status</div>
            <v-select
              v-model="filters.status"
              :items="[
                { title: 'Any', value: null },
                { title: 'Draft', value: 'draft' },
                { title: 'Scheduled', value: 'scheduled' },
                { title: 'Active', value: 'active' },
                { title: 'Finished', value: 'finished' },
                { title: 'Cancelled', value: 'cancelled' },
              ]"
              item-title="title"
              item-value="value"
              variant="outlined"
              density="comfortable"
              hide-details
              clearable
            />
          </v-col>

          <v-col cols="12" md="2">
            <div class="text-caption text-medium-emphasis mb-1">Active now</div>
            <v-select
              v-model="filters.is_active"
              :items="[
                { title: 'Any', value: null },
                { title: 'Yes', value: true },
                { title: 'No', value: false },
              ]"
              item-title="title"
              item-value="value"
              variant="outlined"
              density="comfortable"
              hide-details
            />
          </v-col>

          <v-col cols="12" md="2">
            <div class="text-caption text-medium-emphasis mb-1">Per page</div>
            <v-select
              v-model="perPage"
              :items="[10, 20, 25, 50, 100]"
              variant="outlined"
              density="comfortable"
              hide-details
            />
          </v-col>

          <v-col cols="12" md="3" class="d-flex align-end ga-2">
            <v-btn color="success" variant="flat" @click="onSearch">
              <v-icon start icon="mdi-magnify" /> Search
            </v-btn>
            <v-btn variant="tonal" color="blue-grey" @click="onReset">
              <v-icon start icon="mdi-filter-off" /> Reset
            </v-btn>
          </v-col>

          <v-col cols="12" md="3">
            <div class="text-caption text-medium-emphasis mb-1">
              Started from
            </div>
            <v-text-field
              v-model="filters.started_from"
              type="datetime-local"
              variant="outlined"
              density="comfortable"
              hide-details
              clearable
            />
          </v-col>
          <v-col cols="12" md="3">
            <div class="text-caption text-medium-emphasis mb-1">Started to</div>
            <v-text-field
              v-model="filters.started_to"
              type="datetime-local"
              variant="outlined"
              density="comfortable"
              hide-details
              clearable
            />
          </v-col>
          <v-col cols="12" md="3">
            <div class="text-caption text-medium-emphasis mb-1">Ended from</div>
            <v-text-field
              v-model="filters.ended_from"
              type="datetime-local"
              variant="outlined"
              density="comfortable"
              hide-details
              clearable
            />
          </v-col>
          <v-col cols="12" md="3">
            <div class="text-caption text-medium-emphasis mb-1">Ended to</div>
            <v-text-field
              v-model="filters.ended_to"
              type="datetime-local"
              variant="outlined"
              density="comfortable"
              hide-details
              clearable
            />
          </v-col>

          <v-col cols="12" md="4">
            <div class="text-caption text-medium-emphasis mb-1">Game ID</div>
            <v-text-field
              v-model="filters.game_id"
              placeholder="UUID"
              variant="outlined"
              density="comfortable"
              hide-details
              clearable
              @keyup.enter="onSearch"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-alert v-if="errorMessage" type="error" class="mb-4" variant="tonal">
      {{ errorMessage }}
    </v-alert>

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
        v-model:sort-by="sortBy"
        @update:page="fetchData"
        @update:items-per-page="fetchData"
        @update:sort-by="fetchData"
      >
        <template #item.thumbnail="{ item }">
          <v-img
            v-if="item.thumbnail || item.thumbnail_url"
            :src="(item.thumbnail_url || item.thumbnail) as any"
            width="120"
            height="60"
            cover
          />
          <span v-else class="text-medium-emphasis">—</span>
        </template>

        <template #item.status="{ item }">
          <v-chip size="small" :color="statusColor(item.status)" label>
            {{ item.status }}
          </v-chip>
        </template>

        <template #item.tournament_type="{ item }">
          <v-chip
            size="small"
            :color="item.tournament_type === 'RANDOM' ? 'deep-purple' : 'blue-grey'"
            label
          >
            {{ item.tournament_type || "DEFAULT" }}
          </v-chip>
        </template>

        <template #item.started_at="{ item }">
          {{ fmtDate(item.started_at) }}
        </template>

        <template #item.ended_at="{ item }">
          {{ fmtDate(item.ended_at) }}
        </template>

        <template #item.created_at="{ item }">
          {{ fmtDate(item.created_at) }}
        </template>

        <template #item.games_count="{ item }">
          {{ Array.isArray(item.games) ? item.games.length : 0 }}
        </template>

        <template #item.prizes_count="{ item }">
          {{ Array.isArray(item.prizes) ? item.prizes.length : 0 }}
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex ga-2 flex-wrap">
            <v-btn
              size="small"
              variant="flat"
              color="info"
              @click="goEdit(String(item.id))"
            >
              Edit
            </v-btn>
            <v-btn
              size="small"
              variant="tonal"
              color="primary"
              @click="onClone(item)"
            >
              Clone
            </v-btn>
            <v-btn
              size="small"
              variant="tonal"
              color="warning"
              :disabled="item.status === 'finished'"
              @click="onEnd(item)"
            >
              End
            </v-btn>
            <v-btn
              v-if="item.status === 'finished'"
              size="small"
              variant="tonal"
              color="success"
              @click="onExportChanceList(item)"
            >
              Export list
            </v-btn>
            <v-btn
              v-if="canRandomExtract(item)"
              size="small"
              variant="flat"
              color="deep-purple"
              @click="openRandomExtraction(item)"
            >
              Random extraction
            </v-btn>
            <v-btn
              size="small"
              variant="flat"
              color="error"
              @click="onDelete(item)"
            >
              Delete
            </v-btn>
          </div>
        </template>

        <template #no-data>
          <div class="text-medium-emphasis py-6">
            No tournaments found. Adjust filters or create a new tournament.
          </div>
        </template>
      </v-data-table-server>
    </v-card>

    <v-dialog v-model="extractionDialog" max-width="980">
      <v-card>
        <v-card-title class="d-flex align-center justify-space-between">
          <span>Random extraction</span>
          <v-btn icon="mdi-close" variant="text" @click="extractionDialog = false" />
        </v-card-title>
        <v-divider />
        <v-card-text>
          <v-alert
            type="info"
            variant="tonal"
            class="mb-4"
          >
            Eligible players are selected by tournament range. Each point is one draw chance.
          </v-alert>

          <v-progress-linear v-if="extractionLoading" indeterminate color="primary" class="mb-4" />

          <div class="text-subtitle-2 mb-2">Eligible players</div>
          <v-table density="compact" class="mb-4">
            <thead>
              <tr>
                <th>User ID</th>
                <th>Username</th>
                <th class="text-right">Points / chances</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="eligiblePlayers.length === 0">
                <td colspan="3" class="text-medium-emphasis">No eligible players.</td>
              </tr>
              <tr v-for="player in eligiblePlayers" :key="player.user_id">
                <td>{{ player.user_id }}</td>
                <td>{{ player.username }}</td>
                <td class="text-right">{{ player.points }}</td>
              </tr>
            </tbody>
          </v-table>

          <template v-if="extractedAwards.length">
            <div class="text-subtitle-2 mb-2">Extracted prizes</div>
            <v-table density="compact">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Prize</th>
                  <th>Winner</th>
                  <th class="text-right">Points</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="award in extractedAwards" :key="`${award.tournament_prize_id}-${award.draw_position}`">
                  <td>{{ award.draw_position }}</td>
                  <td>{{ award.prize_name }} ({{ award.prize_currency || "" }} {{ award.prize_amount }})</td>
                  <td>{{ award.username || award.user_id }}</td>
                  <td class="text-right">{{ award.points }}</td>
                </tr>
              </tbody>
            </v-table>
          </template>
        </v-card-text>
        <v-divider />
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="extractionDialog = false">Close</v-btn>
          <v-btn
            color="deep-purple"
            variant="flat"
            :loading="extractionLoading"
            :disabled="eligiblePlayers.length === 0"
            @click="extractRandomAwards"
          >
            Extract
          </v-btn>
          <v-btn
            v-if="extractedAwards.length"
            color="success"
            variant="flat"
            :loading="extractionLoading"
            @click="approveRandomAwards"
          >
            Approve
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>
