<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
const { display } = useContainerOptions(options)
const router = useRouter()

const goToProviderPage = (slug: string) => {
  router.push(slug)
}
</script>
<template>
  <v-slide-group v-if="display" show-arrows :id="options.id">
    <v-slide-group-item v-for="(provider, i) in options.data.providerLogos" :key="`Provider${i}`">
      <button
        class="provider-link"
        :style="{
          width: options.data.width,
          height: options.data.height,
        }"
        @click.prevent="goToProviderPage(provider.slug)"
      >
        <img :src="provider.imageUrl" />
      </button>
    </v-slide-group-item>
    <template #next="{ next }">
      <button class="carousel_btn next" @click.prevent="next">
        <IconPlay />
      </button>
    </template>
    <template #prev="{ prev }">
      <button class="carousel_btn prev" @click.prevent="prev">
        <IconPlay />
      </button>
    </template>
  </v-slide-group>
</template>
