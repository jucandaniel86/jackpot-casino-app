<script setup lang="ts">
import { APP_BREADCRUMBS } from "~/app/breadcrumbs";
import type { BundlePayload } from "~/types/bundles";

const title = "Bundles :: Add";

const router = useRouter();
const { toastSuccess, toastError } = useAlert();
const api = useBundlesApi();

const loading = ref(false);

function backToList() {
  router.push({ name: "casino-bundles" });
}

async function save(payload: BundlePayload) {
  loading.value = true;
  try {
    const result = await api.createBundle(payload);
    if (!result.success || !result.data) {
      toastError(result.message || "Failed to create bundle.");
      return;
    }

    toastSuccess(result.message || "Bundle created successfully.");
    backToList();
  } finally {
    loading.value = false;
  }
}

useHead({ title });
</script>

<template>
  <SharedBreadcrumb :items="APP_BREADCRUMBS.BUNDLES_ADD" :title="title" />

  <v-card>
    <v-card-title class="d-flex justify-end">
      <v-btn
        value="nearby"
        @click.prevent="backToList"
        prepend-icon="mdi-arrow-left"
      >
        <span>Back to bundles</span>
      </v-btn>
    </v-card-title>
    <v-card-text>
      <BundlesForm :loading="loading" @save="save" />
    </v-card-text>
  </v-card>
</template>

