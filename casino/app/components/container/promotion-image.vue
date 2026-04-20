<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
</script>
<template>
  <div :id="options.id" class="d-flex w-100">
    <v-img :src="options.data.image" class="w-100" />
  </div>
</template>
