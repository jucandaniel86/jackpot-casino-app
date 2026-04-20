<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
</script>
<template>
  <div v-if="options.data.button" :id="options.id" class="d-flex justify-center align-center w-100">
    <shared-action-button
      :action="options.data.button.action"
      :color="options.data.button.color"
      :title="options.data.button.title"
      style="width: 250px !important"
    />
  </div>
</template>
