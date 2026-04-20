<script setup lang="ts">
import type { PromotionT } from '~/config/Promotions.config'

const props = defineProps<PromotionT>()
</script>
<template>
  <div class="promotion__item">
    <img :src="props.image" :alt="props.title" loading="lazy" />
    <div class="content">
      <div class="title">
        <div v-html="props.title" />
        <div v-html="props.description" />
      </div>
      <div class="d-flex justify-space-between align-center ga-2">
        <shared-action-button
          v-if="props.primaryAction"
          :action="props.primaryAction.action"
          :title="props.primaryAction.title"
          :color="props.primaryAction.color"
        />
        <shared-action-button
          v-if="props.secondaryAction"
          :action="props.secondaryAction.action"
          :title="props.secondaryAction.title"
          :color="props.secondaryAction.color"
        />
      </div>
    </div>
  </div>
</template>
