<script setup lang="ts">
import type { ProviderT } from "./types";
//props
type ProviderFormT = {
  selectedProvider: ProviderT | undefined;
};

const props = defineProps<ProviderFormT>();

//composables
const { required } = useRules();
const { alertSuccess, axiosErrorAlert } = useAlert();

//models
const loading = ref<boolean>(false);
const valid = ref<boolean>(false);
const form = ref({
  name: "",
  id: 0,
  thumbnail: "",
  active: 0,
});
const thumbnail = ref<any[]>([]);

//emitters
const emitters = defineEmits([
  "providers:close-modal",
  "providers:reload-list",
]);

//methods
const save = async () => {
  const formData = new FormData();
  formData.append("name", form.value.name);
  formData.append("active", String(form.value.active));
  if (thumbnail.value) {
    //@ts-ignore
    formData.append("thumbnail_file", thumbnail.value);
  }
  formData.append("id", form.value.id as any);

  const { success, data, error } = await useApiPostFetch(
    `/providers/save`,
    formData
  );

  if (success) {
    alertSuccess(data.message);
    emitters("providers:close-modal");
    emitters("providers:reload-list");
    return;
  }

  if (!success && error) {
    axiosErrorAlert(error);
  }
};

//watchers
watch(
  props,
  () => {
    form.value = {
      ...form.value,
      ...props.selectedProvider,
    };
  },
  { deep: true }
);

//onMounted
onMounted(() => {
  form.value = {
    ...form.value,
    ...props.selectedProvider,
  };
});
</script>
<template>
  <v-card title="Save Provider" :loading="loading">
    <v-card-text>
      <v-text-field
        v-model="form.name"
        label="Name"
        required
        :rules="[required]"
        density="compact"
      ></v-text-field>

      <v-img
        v-if="form.thumbnail"
        :max-width="100"
        aspect-ratio="16/9"
        cover
        :src="form.thumbnail"
      >
        <template v-slot:placeholder>
          <div class="d-flex align-center justify-center fill-height">
            <v-progress-circular
              color="grey-lighten-4"
              indeterminate
            ></v-progress-circular>
          </div>
        </template>
      </v-img>

      <v-file-input
        v-model="thumbnail"
        label="Logo"
        accept="image/*"
        show-size
        density="compact"
      ></v-file-input>

      <v-checkbox
        v-model="form.active"
        :false-value="0"
        :true-value="1"
        label="Active"
      />
    </v-card-text>
    <v-card-actions class="d-flex justify-space-between align-center">
      <v-btn
        :disabled="loading"
        @click="save"
        density="compact"
        variant="flat"
        color="blue"
        >Save</v-btn
      >
      <v-btn
        @click="emitters('providers:close-modal')"
        color="red"
        density="compact"
        variant="flat"
        :disabled="loading"
        >Close</v-btn
      >
    </v-card-actions>
  </v-card>
</template>
