<!-- eslint-disable vue/no-v-html -->
<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
const { display } = useContainerOptions(options)
</script>
<template>
  <div v-if="display" :id="options.id" class="offer-container">
    <div v-for="(offer, i) in options.data.offers" :key="`Offer${i}`" class="offer-item">
      <img :src="offer.thumbnail" style="width: 100%; display: block" />
      <div class="offer-content">
        <p :style="offer.contentStyle">{{ offer.content }}</p>
      </div>
      <div class="d-flex align-center justify-center pl-10 pr-10">
        <shared-action-button
          v-if="offer.button"
          :action="offer.button.action"
          :color="offer.button.color"
          :title="offer.button.title"
          class="w-100 mb-4"
        />
      </div>
    </div>
  </div>
</template>
<style scoped>
.offer-container {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  place-items: normal;
  gap: 24px;
  -webkit-box-pack: justify;
  justify-content: space-between;
  box-sizing: border-box !important;
}
.offer-item {
  width: auto;
  border-radius: 20px;
  overflow: hidden;
  text-align: center;
  border: 1px solid #494f65;
  background-color: #12151b;
}
.offer-content {
  background-color: #12151b;
  padding: 0px 25px;
}
.offer-content p {
  font-weight: 400;
  font-size: 19.04px;
  text-align: centеr;
  vertical-align: middle;
  color: #dddddd;
  padding: 0px 15px 0px 15px;
  margin: 1em 0;
}
</style>
