<script setup lang="ts">
import { useAuthStore } from '~/core/store/auth'
import type { GameType } from '~/core/types/Game'

//props
const props = defineProps<{ game: GameType }>()

//composables
const router = useRouter()
const { isLogged } = storeToRefs(useAuthStore())
const { t } = useI18n()

//models
const reveal = ref(false)

//computed
const image = computed(() => `${props.game.imageUrl}`)

//methods
const playRealMoney = () => {
  router.push({
    name: 'game-slug',
    params: { slug: props.game.realPlayUrl },
    query: { demo: 0 },
  })
}

const playForFun = () => {
  router.push({
    name: 'game-slug',
    params: { slug: props.game.realPlayUrl },
    query: { demo: 1 },
  })
}
</script>
<template>
  <div class="game__item" @mouseout="reveal = false" @mouseover="reveal = true">
    <div class="wrapper">
      <img class="mx-auto" width="100%" :src="image" />
      <h2 v-show="reveal" class="game__title">{{ props.game.name }}</h2>
      <div class="real-play" @click="playRealMoney">
        <button class="">
          <IconPlay />
        </button>
      </div>
      <div v-if="props.game.hasDemo" v-show="reveal" class="demo-play" @click="playForFun">
        {{ isLogged ? t('casino.play') : t('casino.fun') }}
      </div>
    </div>
  </div>
</template>
<style scoped>
.game-favorite-btn {
  cursor: pointer;
  position: absolute;
  top: 0.6rem;
  right: 0.6rem;
  color: #fff;
  font-size: 1rem;
  border: none;
}
.game-favorite-btn svg {
  width: 1rem;
  height: 1rem;
}
</style>
