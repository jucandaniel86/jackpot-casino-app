<script setup lang="ts">
import type { TabsFetchData } from '~/core/types/TabsFetch'

//props
type CategoryCarouselT = {
  id: string
  data: TabsFetchData
}

const props = defineProps<CategoryCarouselT>()

const { style } = useResolutionVars(props.data.resolutionsConfig)
</script>
<template>
  <div>
    <div class="mb-4">
      <ul ref="scrollContent" class="game_ul_list" :style="style">
        <li v-for="(game, index) in props.data.initialState.data" :key="index">
          <div class="carousel__item">
            <GameItem :game="game" />
          </div>
        </li>
      </ul>
    </div>

    <div class="d-flex justify-center align-center mt-3">
      <v-btn class="v-btn-custom" color="purple">Load More</v-btn>
    </div>
  </div>
</template>
