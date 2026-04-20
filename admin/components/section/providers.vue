<script setup lang="ts">
import type { ProviderT } from "../providers/types";
import { type ContainerType } from "./setup";

type SectionContainerT = {
  item: ContainerType;
};
const props = defineProps<SectionContainerT>();
const providersLoading = ref<boolean>(false);
const providers = ref<ProviderT[]>([]);

//models
const form = ref({
  width: "",
  height: "",
  providers: [],
});

//emits
const emits = defineEmits(["onUpdate"]);

//methods
const getProviders = async (): Promise<void> => {
  providersLoading.value = true;
  const { data } = await useAPIFetch("/providers/list");
  providers.value = data.data;
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
  await getProviders();

  if (typeof props.item.data?.width !== "undefined") {
    form.value.width = props.item.data?.width;
  }
  if (typeof props.item.data?.height !== "undefined") {
    form.value.height = props.item.data?.height;
  }

  if (typeof props.item.data?.providers !== "undefined") {
    form.value.providers = props.item.data?.providers;
  }
});

const selectAll = () =>
  (form.value.providers = [...providers.value].map((el) => el.id) as any);
</script>
<template>
  <div>
    <v-progress-circular color="blue" v-if="providersLoading" />

    <v-row>
      <v-col cols="6">
        <v-text-field
          v-model="form.width"
          label="Icon Width"
          hide-details
          density="compact"
        />
      </v-col>
      <v-col cols="6">
        <v-text-field
          v-model="form.height"
          label="Icon Height"
          hide-details
          density="compact"
        />
      </v-col>
      <v-col cols="10">
        <v-select
          :loading="providersLoading"
          v-model="form.providers"
          :items="providers"
          label="Providers"
          item-value="id"
          item-title="name"
          chips
          closable-chips
          multiple
          hide-details
          density="compact"
        ></v-select>
      </v-col>
      <v-col cols="2">
        <v-btn
          v-if="providers.length !== form.providers.length"
          @click.prevent="selectAll"
          flat
          density="compact"
          color="blue"
          >Select All</v-btn
        >
      </v-col>
    </v-row>
  </div>
</template>
