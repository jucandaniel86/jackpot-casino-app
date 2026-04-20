<script setup lang="ts">
import type { CategoryType } from "./types";

//props
type CategoryModalProps = {
  loading: boolean;
  item?: CategoryType | undefined | null;
};
const props = defineProps<CategoryModalProps>();

//models
let form = ref({
  name: "",
  restricted: false,
  parent_id: 0,
  icon: "",
  seo: {
    title: "",
    description: "",
    displayTitle: "",
    displayDescription: "",
    indexed: 1,
  },
});

//emitters
const emitters = defineEmits(["onClose", "onSave"]);

const handleSave = () => emitters("onSave", form.value);
const handleClose = () => emitters("onClose");

watch(props, () => {
  form.value = {
    ...form.value,
    ...props.item,
  };
});

onMounted(() => {
  form.value = {
    ...form.value,
    ...props.item,
  };
});
</script>
<template>
  <v-card max-width="500" class="mx-auto w-100" :loading="props.loading">
    <v-card-title>Save Games Category</v-card-title>

    <v-card-text>
      <v-text-field
        v-model="form.name"
        hide-details
        density="compact"
        label="Category Name"
      />
      <SelectCategories v-model="form.parent_id" :multiple="false" />
      <SharedIcons v-model="form.icon" :icon="form.icon" />
      <v-checkbox
        v-model="form.restricted"
        :true-value="1"
        :false-value="0"
        label="Restricted"
        hide-details
      />
      <SharedSeo v-model="form.seo" />
    </v-card-text>
    <v-card-actions class="d-flex align-center justify-space-between">
      <v-btn
        color="blue"
        size="small"
        variant="flat"
        :disabled="props.loading"
        @click.prevent="handleSave"
      >
        <v-icon icon="mdi-content-save" />
        Save
      </v-btn>
      <v-btn
        color="blue"
        size="small"
        variant="flat"
        @click.prevent="handleClose"
      >
        <v-icon icon="mdi-close" />
        Close
      </v-btn>
    </v-card-actions>
  </v-card>
</template>
