<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { useScroll, useResizeObserver } from '@vueuse/core'
import type { GameType } from '~/core/types/Game'

type FavoritesSectionType = {
  games: GameType[]
  title: string
  id: string
}

//props
const props = defineProps<FavoritesSectionType>()

//models
const scrollContent = useTemplateRef<HTMLUListElement>('scrollContent')
const scrollContainer = useTemplateRef<HTMLDivElement>('scrollContainer')
const currentIndex = ref(0)
const arrowDisabled = ref<boolean>(false)

//composables
const { name } = useDisplay()

//methods
const { x } = useScroll(scrollContent, { behavior: 'smooth' })

const prev = () => (x.value = 0)
const next = () => {
  arrowDisabled.value = false
  x.value = scrollContent.value?.scrollWidth || 0
}

onMounted(() => {
  let timeout: string | number | NodeJS.Timeout | null | undefined = null
  useResizeObserver(scrollContainer, () => {
    if (timeout) {
      clearTimeout(timeout)
    }

    timeout = setTimeout(() => {
      x.value = 0
    }, 500)
  })
})

const onScroll = () => {
  setTimeout(() => {
    const ItemWidth = scrollContent.value?.children[0]?.getBoundingClientRect().width || 0
    currentIndex.value = 1 + Math.round(x.value / ItemWidth)

    setTimeout(() => {
      arrowDisabled.value = false
    }, 100)

    console.log(currentIndex)
  }, 50)
}

const l = () => {
  if (scrollContainer.value && scrollContent.value) {
    return (
      scrollContainer.value.clientWidth /
      scrollContent.value.children[0]!.getBoundingClientRect().width
    )
  }
  return 0
}

const itemsPerRow = computed(() => {
  switch (name.value) {
    case 'lg':
      return 5
    case 'md':
      return 4
    case 'sm':
      return 3
    case 'xs':
      return 3
    default:
      return 5
  }
})

const displayArrows = computed(() => {
  return itemsPerRow.value > props.games.length
})
</script>
<template>
  <div :id="props.id">
    <div class="d-flex justify-space-between">
      <h3 class="category_header text-white">
        <span class="text-white">{{ props.title }}</span>
      </h3>
    </div>
    <div
      ref="scrollContainer"
      :style="{
        '--itemsPerRow': itemsPerRow,
        '--aspect-ratio': 0,
      }"
      class="game-carousel__container text-white"
    >
      <ul ref="scrollContent" class="game_ul_carousel_list" @scroll="onScroll">
        <li
          v-for="(game, i) in props.games"
          :key="`Game${i}`"
          :class="{
            disabled: i >= currentIndex - 1 && i < currentIndex + l() - 2 ? false : true,
          }"
        >
          <div class="carousel__item">
            <game-item :game="game" />
          </div>
        </li>
      </ul>
      <button
        v-if="currentIndex > 1 || !displayArrows"
        class="carousel_btn prev"
        :disabled="arrowDisabled"
        @click.prevent="prev"
      >
        <IconPlay />
      </button>
      <button
        v-if="!displayArrows"
        class="carousel_btn next"
        :disabled="arrowDisabled"
        @click.prevent="next"
      >
        <IconPlay />
      </button>
    </div>
  </div>
</template>
