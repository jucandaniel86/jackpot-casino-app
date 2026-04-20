<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
</script>
<template>
  <div>
    <h1 class="text-h5 text-white font-weight-bold mb-3 mt-2">Promotions</h1>
    <div v-if="options.data.promotions.length" class="promotions__wrapper">
      <PromotionItem
        v-for="(promotion, i) in options.data.promotions"
        :key="`Promotion${i}`"
        v-bind="promotion"
      />
    </div>
    <p v-else class="text-white mt-12">There are no promotions ongoing.</p>
  </div>
</template>
