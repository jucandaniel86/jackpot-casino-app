<script setup lang="ts">
import { APP_BREADCRUMBS } from "~/app/breadcrumbs";
import type { TournamentPayload } from "~/types/tournaments";

const title = "Tournaments :: Add";

const router = useRouter();
const { toastSuccess, toastError } = useAlert();
const api = useTournamentsApi();

const loading = ref(false);

function backToList() {
  router.push({ name: "casino-tournaments" });
}

async function save(payload: TournamentPayload) {
  loading.value = true;
  try {
    const result = await api.createTournament(payload);
    if (!result.success || !result.data) {
      toastError(result.message || "Failed to create tournament.");
      return;
    }

    toastSuccess(result.message || "Tournament created successfully.");
    backToList();
  } finally {
    loading.value = false;
  }
}

useHead({ title });
</script>

<template>
  <SharedBreadcrumb :items="APP_BREADCRUMBS.TOURNAMENTS_ADD" :title="title" />

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
      <TournamentsForm :loading="loading" @save="save" />
    </v-card-text>
  </v-card>
</template>

