<script setup lang="ts">
import type { ProviderT } from "../providers/types";
import { type ContainerType } from "./setup";

type SectionContainerT = {
  item: ContainerType;
};
const props = defineProps<SectionContainerT>();
const providersLoading = ref<boolean>(false);
const tabs = ref<any[]>([]);

//models
const form = ref({
  tab: [],
});

//emits
const emits = defineEmits(["onUpdate"]);

//methods
const getTabs = async (): Promise<void> => {
  providersLoading.value = true;
  const { data } = await useAPIFetch("/tags/list");
  tabs.value = data.data;
  providersLoading.value = false;
};

//watch
watch(
  form,
  () => {
    emits("onUpdate", form.value);
  },
  { deep: true }
);

onMounted(async () => {
  await getTabs();

  if (typeof props.item.data?.tab !== "undefined") {
    form.value.tab = props.item.data?.tab;
  }
});
</script>
<template>
  <div>
    <div class="d-flex justify-center align-center">
      <v-progress-circular color="blue" v-if="providersLoading" indeterminate />
    </div>

    <v-row>
      <v-col cols="12">
        <v-select
          :loading="providersLoading"
          v-model="form.tab"
          :items="tabs"
          label="TAB"
          item-value="id"
          item-title="name"
          chips
          closable-chips
          multiple
          hide-details
          density="compact"
        ></v-select>
      </v-col>
    </v-row>
  </div>
</template>
