<script setup lang="ts">
import { onClickOutside } from '@vueuse/core'
import type { ContainerType } from '~/core/types/Container'
import type { GameType } from '~/core/types/Game'

const { options } = defineProps<{ options: ContainerType }>()
const { itemsPerRow } = useResolutionVars(options.data.resolutionConfig)

const search = ref<string>('')
const menu = ref<boolean>(false)
const loading = ref<boolean>(false)
const resultsGames = ref<GameType[]>([])
const resultProviders = ref([])
const resultCategories = ref([])
const target = useTemplateRef<HTMLElement>('SearchResultsContainer')

//composables
const { t } = useI18n()

//methods
const appSearch = async (): Promise<void> => {
  loading.value = true

  const { games, providers, categories } = await useAPIFetch('/search', {
    search: search.value,
  })

  resultsGames.value = games ?? []
  resultCategories.value = categories ?? []
  resultProviders.value = providers ?? []

  loading.value = false
}

const clearSearch = () => {
  resultsGames.value = []
  resultCategories.value = []
  resultProviders.value = []
  menu.value = false
}

const totalResults = computed(() => {
  return resultsGames.value.length + resultCategories.value.length + resultProviders.value.length
})

watch(search, () => {
  if (String(search.value).length == 0) {
    clearSearch()
  }

  if (String(search.value).length <= 1 || !search.value) return
  menu.value = true
  appSearch()
})

watch(menu, () => {
  if (!menu.value) {
    clearSearch()
    search.value = ''
  }
})

onClickOutside(target, () => (menu.value = false))
</script>
<template>
  <div ref="SearchResultsContainer" class="w-100 position-relative">
    <v-text-field
      v-model="search"
      hide-details
      rounded
      variant="solo"
      density="compact"
      class="app-search pl-0 pr-0 w-100"
      :placeholder="t('search.placeholder')"
      prepend-inner-icon="mdi-magnify"
      clearable
      width="100%"
      @click:clear="clearSearch"
    />
    <v-container v-if="menu">
      <v-card
        class="pl-0 pr-0 w-100 mx-auto mt-2 search-results position-absolute right-0"
        style="background: var(--search-results-bgcolor) !important"
      >
        <v-card-title v-if="String(search).length > 0" class="pl-6 pr-6">
          <div class="d-flex justify-space-between b-bottom">
            <span class="search__title">{{ t('search.results') }}</span>
            <span class="search__title">{{
              t('search.totalResults', { total: totalResults })
            }}</span>
          </div>
        </v-card-title>
        <v-card-text
          class="pl-6 pr-6 position-relative"
          :style="{
            minHeight: '300px',
          }"
        >
          <div
            v-if="loading"
            class="d-flex justify-center align-center w-100 h-100 position: absolute"
          >
            <v-progress-circular indeterminate color="purple" />
          </div>

          <div v-if="resultsGames.length" class="w-100 mt-1 d-flex flex-column">
            <span class="results__title font-weight-bold">{{ t('search.games') }}</span>
            <div
              class="game-search-list"
              :style="`grid-template-columns: repeat(${itemsPerRow}, 1fr);`"
            >
              <GameItem
                v-for="game in resultsGames"
                :key="`SearchResult${game.id}`"
                :game="game"
                style="aspect-ratio: 392 / 499"
              />
            </div>
          </div>
          <div
            v-else-if="!loading && String(search).length > 0"
            class="w-100 mt-4 mb-4 d-flex justify-center"
          >
            <p class="text-white">{{ t('search.noResults', { search }) }}</p>
          </div>
        </v-card-text>
      </v-card>
    </v-container>
  </div>
</template>
