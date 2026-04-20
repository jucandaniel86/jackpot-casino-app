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

watch(
  form,
  () => {
    emits("onUpdate", form.value);
  },
  { deep: true }
);
</script>
<template>
  <SharedResolutionConfig>
    <template #LG> </template>
  </SharedResolutionConfig>
</template>
