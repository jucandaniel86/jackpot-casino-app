<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { useFullscreen } from '@vueuse/core'
import { useAppStore } from '~/core/store/app'
import { useAuthStore } from '~/core/store/auth'
import { useGameStore } from '~/core/store/game'
import { OverlaysTypes } from '~/core/types/Overlays'
import { ref, computed } from 'vue'

//models
const el = useTemplateRef('gameIframe')
const loadingPlayerSesson = ref<boolean>(false)
const startGame = ref<boolean>(false)
const gameLoading = ref<boolean>(false)
const game = ref<any>()
const favLoadingResponse = ref<boolean>(false)
const favorites = ref<any>()
const iframeURL = ref('')
const iframeError = ref('')
const bodyScrollLockY = ref(0)
const previousBodyStyles = ref({
  overflow: '',
  position: '',
  top: '',
  width: '',
})
const isBodyScrollLocked = ref(false)

//composables
const { setPageLoading, setSidebar, setLoadWallets } = useAppStore()
const route = useRoute()
const router = useRouter()
const { isLogged } = storeToRefs(useAuthStore())
const { toggle } = useFullscreen(el)
const { openOverlay } = useUtils()
const { name } = useDisplay()
const { setActivePlaySession } = useGameStore()
const { t } = useI18n()
const tryRealMoney = ref

//methods
const startGameSession = async (demo: boolean): Promise<void> => {
  if (!isLogged.value) {
    tryRealMoney.value = true
    openOverlay(OverlaysTypes.LOGIN)
    return
  }

  startGame.value = true
  loadingPlayerSesson.value = true

  const { data, success }: any = await useApiPostFetch('/player/play', {
    game_id: game.value.rgs_game_id,
    demo,
  })

  if (!success) {
    iframeError.value = t('gamePage.startSessionError')
    loadingPlayerSesson.value = false
    return
  }

  if (
    typeof data.response === 'undefined' ||
    typeof data.response.launch_url === 'undefined' ||
    data.response.launch_url === ''
  ) {
    iframeError.value = t('gamePage.startSessionError')
    loadingPlayerSesson.value = false
    return
  }

  if (!demo && data && data.session_id) {
    setActivePlaySession(data.session_id)
  }
  if (data.response.launch_url) {
    if (el.value) {
      el.value.src = data.response.launch_url
      el.value.onload = () => {
        loadingPlayerSesson.value = false
      }
    }
  }

  startGame.value = true
}

const startDemoSession = async (): Promise<void> => {
  startGame.value = true
  loadingPlayerSesson.value = true

  const { data, success }: any = await useApiPostFetch('/demo', {
    game_id: game.value.rgs_game_id,
  })

  if (
    typeof data.response === 'undefined' ||
    typeof data.response.launch_url === 'undefined' ||
    data.response.launch_url === ''
  ) {
    iframeError.value = t('gamePage.startSessionError')
    loadingPlayerSesson.value = false
    return
  }

  if (success) {
    if (data && data.response.launch_url) {
      if (el.value) {
        el.value.src = data.response.launch_url
        el.value.onload = () => {
          loadingPlayerSesson.value = false
        }
      }
    }
  }

  startGame.value = true
}

const back = () => router.back()

const toggleFavorite = async (): Promise<void> => {
  favLoadingResponse.value = true
  const data = await useApiPostFetch('/player/favorite', {
    gameID: game.value.gameID,
  })
  if (data.success) {
    game.value.favorite = !game.value.favorite
  }
  favLoadingResponse.value = false
}

const getGamePage = async (): Promise<void> => {
  const slug = route.params.slug
  gameLoading.value = true
  setPageLoading(true)

  const gameData = await useAPIFetch(`/game/${slug}`)

  if (gameData.status && gameData.status === 404) {
    setPageLoading(false)
    if (import.meta.client) {
      throw showError({
        statusCode: 404,
        statusMessage: t('gamePage.err404Title'),
        message: t('gamePage.err404Content'),
        fatal: true,
      })
    } else {
      throw createError({
        statusCode: 404,
        statusMessage: t('gamePage.err404Title'),
        message: t('gamePage.err404Content'),
        fatal: true,
      })
    }
  }
  if (gameData.children.main) {
    game.value = gameData.children.main.find(
      (item: any) => item.container === 'GamePlayContainer',
    )?.data
    favorites.value = gameData.children.main.find(
      (item: any) => item.container === 'FavouriteGamesContainer',
    )
    useSeoMeta({
      title: game.value.name,
    })
  }

  if (gameData.children.leftSidebar) {
    setSidebar(gameData.children.leftSidebar)
  }
  setPageLoading(false)

  gameLoading.value = false
}

const mobile = computed(() => ['sm', 'xs'].indexOf(name.value) !== -1)
const teleportGameOnMobile = computed(() => mobile.value && startGame.value)

const lockBodyScroll = (): void => {
  if (!import.meta.client) {
    return
  }
  if (isBodyScrollLocked.value) {
    return
  }

  const body = document.body
  previousBodyStyles.value = {
    overflow: body.style.overflow,
    position: body.style.position,
    top: body.style.top,
    width: body.style.width,
  }
  bodyScrollLockY.value = window.scrollY
  body.style.overflow = 'hidden'
  body.style.position = 'fixed'
  body.style.top = `-${bodyScrollLockY.value}px`
  body.style.width = '100%'
  isBodyScrollLocked.value = true
}

const unlockBodyScroll = (): void => {
  if (!import.meta.client) {
    return
  }
  if (!isBodyScrollLocked.value) {
    return
  }

  const body = document.body
  body.style.overflow = previousBodyStyles.value.overflow
  body.style.position = previousBodyStyles.value.position
  body.style.top = previousBodyStyles.value.top
  body.style.width = previousBodyStyles.value.width
  window.scrollTo(0, bodyScrollLockY.value)
  isBodyScrollLocked.value = false
}

//mounted
onMounted(async () => {
  await getGamePage()
})

//onmounted
onUnmounted(async () => {
  unlockBodyScroll()
  setActivePlaySession('')
  setLoadWallets(true)
})

//watchers
watch(isLogged, async () => {
  await getGamePage()
  if (isLogged.value && tryRealMoney.value) {
    tryRealMoney.value = false
    startGameSession(false)
  }
})

watch(
  teleportGameOnMobile,
  (isTeleported) => {
    if (isTeleported) {
      lockBodyScroll()
      return
    }
    unlockBodyScroll()
  },
  { immediate: true },
)
</script>
<template>
  <div>
    <div class="gameplay-wrapper mb-10">
      <div
        v-if="!gameLoading"
        class="gameplay-content"
        :style="{
          aspectRatio: mobile ? 'unset' : '16 / 9',
        }"
      >
        <div class="gameplay-overlay">
          <div class="game-wrapper" :class="{ 'game-blur': !startGame }">
            <div v-if="iframeError" class="game-iframe-error">{{ iframeError }}</div>
            <Teleport to="body" :disabled="!teleportGameOnMobile">
              <div
                id="gameIframeWrapper"
                class="game-iframe-wrapper"
                :class="{ mobile: teleportGameOnMobile }"
              >
                <div class="overlay-bar">
                  <v-btn size="x-small" class="overlay-close" @click.prevent="back"
                    ><IconClose
                  /></v-btn>
                </div>

                <iframe ref="gameIframe" class="game-iframe" allow="fullscreen" :src="iframeURL" />
                <v-progress-circular
                  v-if="loadingPlayerSesson"
                  indeterminate
                  color="yellow"
                  class="game-iframe-loader"
                />
              </div>
            </Teleport>
          </div>
          <div v-if="!startGame" class="gameplay-currencymessage">
            <p :class="{ 'text-center': mobile }">
              {{ t('gamePage.currencyDisclaimer') }}
            </p>
            <p
              class="w-100 d-flex ga-1 justify-center"
              :class="{ 'text-center flex-column align-center': mobile }"
            >
              <v-btn
                :disabled="loadingPlayerSesson"
                color="purple"
                class="w-100"
                max-width="200"
                @click.prevent="startGameSession(false)"
                >{{ t('gamePage.realMoney') }}</v-btn
              >
              <v-btn
                :disabled="loadingPlayerSesson"
                color="purple"
                class="w-100"
                max-width="200"
                @click.prevent="startDemoSession"
                >{{ t('gamePage.demo') }}</v-btn
              >
            </p>
          </div>
        </div>
      </div>
      <div v-if="!mobile" class="gameplay-toolbar d-flex ga-2 flex-column">
        <button class="game-tool-btn" @click.prevent="back"><IconClose /></button>
        <button class="game-tool-btn" @click.prevent="toggle"><IconFullscreen /></button>
        <button
          v-if="isLogged && game"
          :disabled="favLoadingResponse"
          class="game-tool-btn"
          @click.prevent="toggleFavorite"
        >
          <IconFav
            :style="{
              fill: game.favorite ? '#ff4242' : 'currentColor',
              color: game.favorite ? '#ff4242' : 'currentColor',
            }"
          />
        </button>
      </div>
    </div>

    <ContainerFavorites
      v-if="isLogged && !gameLoading && favorites && favorites.data.length"
      :id="favorites.id"
      :options="favorites"
      :games="favorites.data"
      title="Favorites"
    />
  </div>
</template>
<style>
.game-iframe-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.game-iframe-wrapper.mobile {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 9999;
  width: 100vw;
  height: 100dvh;
  background: #000;
}

.game-iframe-wrapper .overlay-bar {
  display: none;
  width: 100%;
  height: 40px;
  background-color: rgba(0, 0, 0, 0.7);
  justify-content: flex-end;
  z-index: 1;
}

.game-iframe-wrapper.mobile .overlay-bar {
  display: flex;
}
</style>
