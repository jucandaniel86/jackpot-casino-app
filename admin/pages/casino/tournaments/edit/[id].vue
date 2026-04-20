<script setup lang="ts">
import { APP_BREADCRUMBS } from "~/app/breadcrumbs";
import type { Tournament, TournamentPayload } from "~/types/tournaments";

const title = "Tournaments :: Edit";

const router = useRouter();
const route = useRoute();
const { toastSuccess, toastError, axiosErrorAlert } = useAlert();
const api = useTournamentsApi();

const loading = ref(false);
const item = ref<Tournament | null>(null);

function backToList() {
  router.push({ name: "casino-tournaments" });
}

async function load() {
  loading.value = true;
  try {
    const result = await api.getTournament(String(route.params.id));
    if (!result.success || !result.data) {
      toastError(result.message || "Failed to load tournament.");
      item.value = null;
      return;
    }
    item.value = result.data;
  } catch (err: any) {
    axiosErrorAlert(err, true);
  } finally {
    loading.value = false;
  }
}

async function save(payload: TournamentPayload) {
  loading.value = true;
  try {
    const result = await api.updateTournament(String(route.params.id), payload);
    if (!result.success || !result.data) {
      toastError(result.message || "Failed to update tournament.");
      return;
    }

    toastSuccess(result.message || "Tournament updated successfully.");
    item.value = result.data;
  } finally {
    loading.value = false;
  }
}

useHead({ title });
onMounted(load);
</script>

<template>
  <SharedBreadcrumb :items="APP_BREADCRUMBS.TOURNAMENTS_EDIT" :title="title" />

  <v-card>
    <v-card-title class="d-flex justify-end">
      <v-btn
        value="nearby"
        @click.prevent="backToList"
        prepend-icon="mdi-arrow-left"
      >
        <span>Back to tournaments</span>
      </v-btn>
    </v-card-title>
    <v-card-text>
      <v-skeleton-loader v-if="loading && !item" type="article" />
      <TournamentsForm v-else :loading="loading" :item="item" @save="save" />
    </v-card-text>
  </v-card>
</template>

