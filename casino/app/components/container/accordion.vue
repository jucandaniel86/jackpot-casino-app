<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
</script>
<template>
  <div :id="options.id">
    <v-expansion-panels>
      <v-expansion-panel>
        <v-expansion-panel-title>{{ options.data.title }}</v-expansion-panel-title>
        <v-expansion-panel-text v-html="`${options.data.description}`" />
      </v-expansion-panel>
    </v-expansion-panels>
  </div>
</template>
