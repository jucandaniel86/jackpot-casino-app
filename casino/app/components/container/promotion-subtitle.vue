<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
</script>
<template>
  <div :id="options.id">
    <h2 class="text-white">
      <strong>{{ options.data.subtitle }}</strong>
    </h2>
  </div>
</template>
