<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
</script>
<template>
  <div :id="options.id">
    <h1 class="text-white">
      <strong>{{ options.data.title }}</strong>
    </h1>
  </div>
</template>
