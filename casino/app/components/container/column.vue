<script setup lang="ts">
import { ContainerSection, type ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
const { display, styles } = useContainerOptions(options)
</script>
<template>
  <div v-if="display" :id="options.id" class="Column" :style="styles">
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
<style>
.Column {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  place-items: normal;
  gap: 24px;
  -webkit-box-pack: justify;
  justify-content: space-between;
  box-sizing: border-box !important;
}
</style>
