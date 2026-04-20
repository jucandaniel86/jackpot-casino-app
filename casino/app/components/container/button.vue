<script setup lang="ts">
import type { ContainerType } from "~/core/types/Container";

const { options } = defineProps<{ options: ContainerType }>();
const { display, styles } = useContainerOptions(options);
</script>
<template>
  <div v-if="display" :style="styles" class="ButtonContainer">
    <v-btn color="purple">{{ options.data.title }}</v-btn>
  </div>
</template>
<style scoped>
.ButtonContainer {
  width: auto;
  box-sizing: border-box;
  display: grid;
  grid-template-columns: 100%;
  grid-template-rows: 100%;
}
</style>
