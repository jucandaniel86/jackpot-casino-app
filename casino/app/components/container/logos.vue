<!-- eslint-disable vue/no-v-html -->
<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
const { display } = useContainerOptions(options)
const { name } = useDisplay()

const images = computed(() => options.data.logos)
const gap = computed(() => {
  switch (name.value) {
    case 'sm':
      return options.data.gap.SM
    case 'lg':
      return options.data.gap.LG
    case 'md':
      return options.data.gap.MD
    case 'xl':
      return options.data.gap.XL
    case 'xs':
      return options.data.gap.XS
    default:
      return '5px'
  }
})
</script>
<template>
  <div v-if="display" :id="options.id" class="logos-container-wrap">
    <div class="content">
      <div v-if="options.data.title" class="logos-title">
        <h3>{{ options.data.title }}</h3>
      </div>
      <div class="logos-container">
        <ul :style="{ columnGap: gap }">
          <li v-for="(image, i) in images" :key="`Logo${i}`">
            <img :src="image" loading="lazy" />
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
<style scoped>
.logos-container-wrap {
  padding: 20px 5px;
  min-width: 0%;
  width: 100%;
  box-sizing: border-box;
  display: grid;
  grid-template-columns: 100%;
  grid-template-rows: 100%;
  background: rgb(18, 21, 27);
}
.logos-container-wrap .content {
  display: flex;
  flex-direction: column;
  justify-content: space-evenly;
  -webkit-box-align: center;
  align-items: center;
  gap: 2px;
}

.logos-container-wrap .logos-title {
  min-width: 0%;
  width: auto;
  box-sizing: border-box;
  margin: 0px 0px;
  display: grid;
  grid-template-columns: 100%;
  grid-template-rows: 100%;
  color: #fff;
  overflow-wrap: break-word;
}
.logos-container {
  display: flex;
  flex-direction: column;
  -webkit-box-align: center;
  align-items: center;
  -webkit-box-pack: center;
  justify-content: center;
  gap: 24px;
}
.logos-container ul {
  align-items: center;
  -moz-column-gap: var(--imageGap);
  column-gap: var(--imageGap);
  display: inline-flex;
  flex-wrap: wrap;
  justify-content: center;
  list-style: none;
  margin: var(--margin);
  padding: 0;
  row-gap: 8px;
}
</style>
