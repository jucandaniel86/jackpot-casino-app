<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
</script>
<template>
  <div :id="options.id" v-html="options.data.text" class="text-white"></div>
</template>
