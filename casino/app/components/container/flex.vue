<script setup lang="ts">
import { ContainerSection, type ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
const { display, styles } = useContainerOptions(options)
// const debbug = false
</script>
<template>
  <div v-if="display" :id="options.id" class="flex-container" :style="styles">
    <div class="fl">
      <template v-for="(children, i) in options.children" :key="`${options.id}_${i}`">
        <container-html v-if="children.container === ContainerSection.HTML" :options="children" />
        <container-logos v-if="children.container === ContainerSection.LOGOS" :options="children" />
      </template>
    </div>
  </div>
</template>
<style scoped>
.flex-container {
  display: grid;
  grid-template-columns: 100%;
  grid-template-rows: 100%;
  flex: 1 1 0%;
}

.fl {
  display: flex;
  flex-direction: column;
  justify-content: space-evenly;
  -webkit-box-align: center;
  align-items: center;
  gap: 2px;
}
</style>
