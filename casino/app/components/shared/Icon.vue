<script setup lang="ts">
type IconT = {
  icon: string
  class?: string
  fill?: string
}
const props = defineProps<IconT>()
</script>
<template>
  <svg viewBox="0 0 24 24" :class="props.class" :fill="props.fill">
    <use :xlink:href="`#${props.icon}`" />
  </svg>
</template>
