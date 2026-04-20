<script setup lang="ts">
type IconLabelT = {
  label: string
  icon?: string
  fill?: string
}
const props = defineProps<IconLabelT>()
</script>
<template>
  <span class="d-flex align-center">
    <SharedIcon v-if="props.icon" :icon="props.icon" :fill="props.fill" class="svg-icon" />
    <span>{{ props.label }}</span>
  </span>
</template>
<style scoped>
.svg-icon {
  height: 1em;
  position: relative;
  width: 1em;
  flex-shrink: 0;
  display: inline-block;
  margin-right: 8px;
}
</style>
