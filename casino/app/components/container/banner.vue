<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'
import type { BannerItem } from '../shared/BannerItem.vue'

const { options } = defineProps<{ options: ContainerType }>()
const { display, styles } = useContainerOptions(options)

const banners: BannerItem[] = options.data.banners
</script>
<template>
  <div v-if="display" :id="options.id" :style="styles">
    <v-carousel :continuous="false" :show-arrows="false" height="300" hide-delimiter-background>
      <!-- <v-carousel-item v-for="(slide, i) in slides" :key="i"> </v-carousel-item> -->
      <v-carousel-item v-for="(banner, i) in banners" :key="i">
        <shared-banner-item :option="banner" />
      </v-carousel-item>
    </v-carousel>
  </div>
</template>
