<script setup lang="ts">
import type { ProviderT } from "../providers/types";

const model = defineModel();
const items = ref<ProviderT[]>();
const loading = ref<boolean>(false);

const getProviders = async (): Promise<void> => {
  loading.value = true;
  const { data } = await useAPIFetch("/providers/list");
  items.value = data.data;
  loading.value = false;
};
onMounted(() => {
  getProviders();
});
</script>
<template>
  <v-autocomplete
    v-model="model"
    :loading="loading"
    label="Provider"
    :items="items"
    :item-title="'name'"
    :item-value="'id'"
    hide-details
    density="compact"
    auto-select-first
  />
</template>
