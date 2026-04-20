<script setup lang="ts">
import { ContainerSection, type ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
const { display, styles } = useContainerOptions(options)
</script>
<template>
  <div v-if="display" :id="options.id" class="d-flex" :style="styles">
    <template v-for="(children, i) in options.children" :key="`${options.id}_${i}`">
      <container-html
        v-if="children.container === ContainerSection.HTML"
        :options="children"
      />
      <container-logos
        v-if="children.container === ContainerSection.LOGOS"
        :options="children"
      />
      <container-button
        v-if="children.container === ContainerSection.BUTTON"
        :options="children"
      />
    </template>
  </div>
</template>
