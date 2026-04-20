<!-- eslint-disable vue/no-v-html -->
<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
const { display, styles } = useContainerOptions(options)
</script>
<template>
  <div
    v-if="display"
    :id="options.id"
    :style="styles"
    style="color: #fff"
    class="content-page__wraper"
    v-html="options.data.html"
  ></div>
</template>
