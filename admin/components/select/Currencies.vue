<script setup lang="ts">
//models
const model = defineModel();
const items = ref<any[]>([]);
const loading = ref<boolean>(false);

//methods
const getCurrencies = async (): Promise<void> => {
  loading.value = true;
  const { data } = await useAPIFetch("/wallet/currencies", { active: 1 });
  items.value = data.data;

  loading.value = false;
};

//onMounted
onMounted(() => {
  getCurrencies();
});
</script>
<template>
  <v-autocomplete
    v-model="model"
    :loading="loading"
    :disabled="loading"
    :items="items"
    hide-details
    density="compact"
    :auto-select-first="true"
    label="Currency"
  />
</template>
