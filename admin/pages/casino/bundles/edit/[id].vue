<script setup lang="ts">
import { APP_BREADCRUMBS } from "~/app/breadcrumbs";
import type { Bundle, BundlePayload } from "~/types/bundles";

const title = "Bundles :: Edit";

const router = useRouter();
const route = useRoute();
const { toastSuccess, toastError, axiosErrorAlert } = useAlert();
const api = useBundlesApi();

const loading = ref(false);
const item = ref<Bundle | null>(null);

function backToList() {
  router.push({ name: "casino-bundles" });
}

async function load() {
  loading.value = true;
  try {
    const result = await api.getBundle(String(route.params.id));
    if (!result.success || !result.data) {
      toastError(result.message || "Failed to load bundle.");
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

async function save(payload: BundlePayload) {
  loading.value = true;
  try {
    const result = await api.updateBundle(String(route.params.id), payload);
    if (!result.success || !result.data) {
      toastError(result.message || "Failed to update bundle.");
      return;
    }

    toastSuccess(result.message || "Bundle updated successfully.");
    item.value = result.data;
  } finally {
    loading.value = false;
  }
}

useHead({ title });
onMounted(load);
</script>

<template>
  <SharedBreadcrumb :items="APP_BREADCRUMBS.BUNDLES_EDIT" :title="title" />

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
      <v-skeleton-loader v-if="loading && !item" type="article" />
      <BundlesForm v-else :loading="loading" :item="item" @save="save" />
    </v-card-text>
  </v-card>
</template>

