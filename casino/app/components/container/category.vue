<script setup lang="ts">
import { useScroll, useResizeObserver } from '@vueuse/core'
import type { ContainerType } from '~/core/types/Container'

//props
const props = defineProps<{ options: ContainerType }>()

//models
const scrollContent = useTemplateRef<HTMLUListElement>('scrollContent')
const scrollContainer = useTemplateRef<HTMLDivElement>('scrollContainer')
const currentIndex = ref(0)
const arrowDisabled = ref<boolean>(false)

//composables
const resolutionsConfig = ref(props.options.data.resolutionConfig)
const { style } = useResolutionVars(resolutionsConfig.value)

//methods
const { x } = useScroll(scrollContent, { behavior: 'smooth' })

const prev = () => (x.value = 0)
const next = () => {
  arrowDisabled.value = false
  x.value = scrollContent.value?.scrollWidth || 0
}

const getRowGap = () => {
  // const rowGap = Number(
  //   getComputedStyle(scrollContent.value).getPropertyValue('row-gap').slice(0, -2),
  // )
  // return isNaN(rowGap) ? 0 : rowGap
}

const r = () => {
  // const ItemWidth = scrollContent.value?.children[0]?.getBoundingClientRect().width || 0
  // let l = 0
  // if (scrollContainer.value && scrollContent.value && scrollContainer.value.children.length) {
  //   l = Math.floor(
  //     scrollContainer.value.clientWidth /
  //       scrollContent.value.children[0].getBoundingClientRect().width,
  //   )
  // }
  // const nextScroll =
  //   Math.round(x.value / ItemWidth) * ItemWidth - (l * ItemWidth + l * getRowGap()) * 1
  // console.log('next scroll', nextScroll)
  // x.value = nextScroll
  // , s = Math.round(B / t) * t - (l * n.current.children[0].getBoundingClientRect().width + l * S()) * (f ? -1 : 1);
  // e.scroll({
  //     left: s,
  //     behavior: "smooth"
  // }),
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
</script>
<template>
  <div :id="props.options.id">
    <div class="d-flex justify-space-between">
      <h3 class="category_header">
        <SharedIcon
          v-if="props.options.data?.icon"
          :icon="props.options.data.icon"
          class="svg-icon"
          style="fill: #fff"
        />
        <span>{{ props.options.data.title }}</span>
      </h3>

      <NuxtLink :to="`/${props.options.data.slug}`" class="view-more">View All</NuxtLink>
    </div>

    <div ref="scrollContainer" :style="style" class="game-carousel__container">
      <ul ref="scrollContent" class="game_ul_carousel_list" @scroll="onScroll">
        <li v-for="(game, i) in props.options.data.initialState.data" :key="`Game${i}`">
          <div class="carousel__item">
            <game-item :game="game" />
          </div>
        </li>
      </ul>
      <button
        v-if="currentIndex > 1"
        class="carousel_btn prev"
        :disabled="arrowDisabled"
        @click.prevent="prev"
      >
        <IconPlay />
      </button>
      <button class="carousel_btn next" :disabled="arrowDisabled" @click.prevent="next">
        <IconPlay />
      </button>
    </div>
  </div>
</template>
