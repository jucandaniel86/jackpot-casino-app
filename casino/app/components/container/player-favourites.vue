<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { useAuthStore } from '~/core/store/auth'
import type { ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()

const favorites = ref<unknown[]>()

//composables
const { isLogged } = storeToRefs(useAuthStore())
const { style } = useResolutionVars(JSON.parse(options.data.resolutionConfig))

//methods
const getFavouritesGames = async () => {
  try {
    const gamesData = await useAPIFetch(`/player/favorite-games`)
    favorites.value = gamesData.games
  } catch (err) {
    console.log('err', err)
  }
}

const handleOnFavoriteAction = (_payload: boolean) => {
  if (!_payload) {
    getFavouritesGames()
  }
}

watch(isLogged, () => {
  if (isLogged.value) {
    getFavouritesGames()
  }
})

onMounted(() => {
  favorites.value = options.data.games
})

const FavoriteGames = computed(() => {
  return favorites.value?.map((el: any) => ({
    ...el,
    favorite: true,
  }))
})
</script>
<template>
  <div>
    <div class="d-flex justify-center align-center mt-2 mb-2">
      <h1 class="d-flex ga-2 justify-center align-center w-100">
        <span class="text-white">{{ 'Favourites Games' }}</span>
      </h1>
    </div>
    <v-alert
      v-if="!isLogged"
      border="start"
      color="deep-purple-accent-4"
      variant="tonal"
      class="mt-3 mb-3"
    >
      You are not logged
    </v-alert>
    <div v-else class="mb-4">
      <ul ref="scrollContent" class="game_ul_list" :style="style">
        <li v-for="(game, index) in FavoriteGames as any" :key="index">
          <div class="carousel__item">
            <GameItem :game="game" @onFavoriteAction="handleOnFavoriteAction" />
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>
