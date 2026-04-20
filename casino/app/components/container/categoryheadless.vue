<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()

const { style, itemsPerRow } = useResolutionVars(options.data.resolutionConfig)
const page = ref(options.data.initialState.page)
const totalItems = ref(options.data.initialState.totalItems)
const size = ref(options.data.initialState.size)
const games = ref(options.data.initialState.data)
const loading = ref<boolean>(false)

const getGames = async (): Promise<void> => {
  if (page.value * size.value >= totalItems.value) return

  page.value = page.value += 1
  loading.value = true

  const { data } = await useAPIFetch(options.data.fetchUrl, {
    page: page.value,
  })

  games.value = [...games.value, ...data]
  loading.value = false
}

const displayedGames = computed(() => {
  const columns = Math.floor((page.value * size.value) / itemsPerRow.value)
  return [...games.value].splice(0, columns * itemsPerRow.value)
})
</script>
<template>
  <div :id="options.id" :key="options.id">
    <Teleport :to="'body'">
      <div
        v-if="loading"
        class="d-flex w-100 h-100 position-absolute top-0 left-0 justify-center align-center"
      >
        <v-progress-circular color="purple" indeterminate />
      </div>
    </Teleport>
    <div class="mb-4">
      <ul ref="scrollContent" class="game_ul_list" :style="style">
        <li v-for="(game, index) in displayedGames" :key="index">
          <div class="carousel__item">
            <GameItem :game="game" />
          </div>
        </li>
      </ul>
    </div>

    <div v-if="page * size < totalItems" class="d-flex justify-center align-center mt-3">
      <v-btn class="v-btn-custom" color="purple" @click.prevent="getGames">Load More</v-btn>
    </div>
  </div>
</template>
