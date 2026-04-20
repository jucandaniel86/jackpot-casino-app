<script setup lang="ts">
//setup
const COLUMNS = [
  { label: "Game / Game ID", value: "game" },
  { label: "User Name / Email / User ID", value: "user" },
  { label: "Transaction ID / Operator Transaction ID", value: "transaction" },
  { label: "Round ID", value: "round" },
  { label: "Session ID", value: "session" },
];
//emitters
const emitters = defineEmits(["search:update"]);
//models
const model = ref({
  item: "",
  column: COLUMNS[0].value,
});
//computed
const label = computed(
  () => COLUMNS.find((column) => column.value === model.value.column)?.label
);

watch(
  model,
  () => {
    emitters("search:update", model.value);
  },
  { deep: true }
);
</script>
<template>
  <div class="d-flex w-100 ga-1 align-center">
    <span>Search</span>
    <v-text-field
      v-model="model.item"
      hide-details
      density="compact"
      :label="label"
    />
    <span>Column:</span>
    <v-select
      v-model="model.column"
      hide-details
      density="compact"
      :items="COLUMNS"
      item-title="label"
      item-value="value"
      class="w-25"
    />
  </div>
</template>
