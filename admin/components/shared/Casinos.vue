<script setup lang="ts">
type Casino = {
  int_casino_id: string;
  name: string;
  logo?: string | null;
};

type Props = {
  modelValue?: string | number[] | null | string[];
  label?: string;
  multiple?: boolean;
  clearable?: boolean;
  hideDetails?: boolean;
  density?: "default" | "comfortable" | "compact";
};

const props = withDefaults(defineProps<Props>(), {
  label: "Casino",
  multiple: false,
  clearable: true,
  hideDetails: false,
  density: "compact",
});

const emit = defineEmits(["update:modelValue"]);

const casinos = ref<Casino[]>([]);
const loading = ref(false);

const model = computed({
  get: () => props.modelValue,
  set: (value) => emit("update:modelValue", value),
});

const fetchCasinos = async (): Promise<void> => {
  loading.value = true;
  try {
    const { data } = await useAPIFetch("/casinos");
    const response = data?.data ?? data ?? [];
    casinos.value = Array.isArray(response) ? response : [];
  } finally {
    loading.value = false;
  }
};

onMounted(fetchCasinos);
</script>

<template>
  <v-select
    v-model="model"
    :items="casinos"
    :loading="loading"
    :label="label"
    item-title="name"
    item-value="int_casino_id"
    :multiple="multiple"
    :clearable="clearable"
    :hide-details="hideDetails"
    :density="density"
    :disabled="loading"
    color="primary"
  >
    <template #item="{ props: itemProps, item }">
      <v-list-item v-bind="itemProps">
        <template #prepend>
          <v-avatar size="22" v-if="item?.raw?.logo">
            <v-img :src="item.raw.logo" alt="" />
          </v-avatar>
        </template>
      </v-list-item>
    </template>
    <template #selection="{ item }">
      <div class="d-flex align-center ga-2">
        <v-avatar size="18" v-if="item?.raw?.logo">
          <v-img :src="item.raw.logo" alt="" />
        </v-avatar>
        <span>{{ item?.raw?.name }}</span>
      </div>
    </template>
  </v-select>
</template>
