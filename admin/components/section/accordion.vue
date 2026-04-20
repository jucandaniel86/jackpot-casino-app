<script setup lang="ts">
import { type ContainerType } from "./setup";

type SectionContainerT = {
  item: ContainerType;
};
const props = defineProps<SectionContainerT>();

//models
const form = ref({
  title: "",
  description: "",
});

const emits = defineEmits(["onUpdate"]);
const defaultContent = computed(() =>
  typeof props.item.data?.description !== "undefined"
    ? props.item.data.description
    : ""
);

watch(
  form,
  () => {
    emits("onUpdate", form.value);
  },
  { deep: true }
);

onMounted(() => {
  if (typeof props.item.data?.title !== "undefined") {
    form.value.title = props.item.data?.title;
  }
  if (typeof props.item.data?.description !== "undefined") {
    form.value.description = props.item.data?.description;
  }
});
</script>
<template>
  <v-text-field
    v-model="form.title"
    hide-details
    density="compact"
    label="Title"
  />
  <SharedEditor
    v-model="form.description"
    label="Description"
    :content="defaultContent"
  />
</template>
