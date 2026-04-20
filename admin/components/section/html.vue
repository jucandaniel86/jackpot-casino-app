<script setup lang="ts">
import { type ContainerType } from "./setup";

type SectionContainerT = {
  item: ContainerType;
};
const props = defineProps<SectionContainerT>();

//models
const form = ref({
  html: "",
});

const emits = defineEmits(["onUpdate"]);
const defaultContent = computed(() =>
  typeof props.item.data?.html !== "undefined" ? props.item.data.html : ""
);

watch(
  form,
  () => {
    emits("onUpdate", form.value);
  },
  { deep: true }
);

onMounted(() => {
  if (typeof props.item.data?.html !== "undefined") {
    form.value.html = props.item.data?.html;
  }
});
</script>
<template>
  <SharedEditor
    v-model="form.html"
    label="Description"
    :content="defaultContent"
  />
</template>
