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
const tournamentNow = ref(Date.now())
let tournamentTimer: ReturnType<typeof setInterval> | null = null
let tournamentStreamController: AbortController | null = null
let tournamentStreamKey: string | null = null
let tournamentStandingAnimationTimer: ReturnType<typeof setTimeout> | null = null
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
const { isLogged, token } = storeToRefs(useAuthStore())
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
const tournament = computed(() => game.value?.tournament ?? null)
const tournamentStanding = ref<any>(null)
const tournamentStandingUpdated = ref(false)
const tournamentEndsLabel = computed(() => {
  if (!tournament.value?.ended_at) {
    return ''
  }

  const endTime = new Date(tournament.value.ended_at).getTime()
  if (Number.isNaN(endTime)) {
    return ''
  }

  const diffInMinutes = Math.max(0, Math.ceil((endTime - tournamentNow.value) / 60000))
  if (diffInMinutes <= 0) {
    return 'Ending now'
  }

  const days = Math.floor(diffInMinutes / 1440)
  const hours = Math.floor((diffInMinutes % 1440) / 60)
  const minutes = diffInMinutes % 60

  if (days > 0) {
    return `Ends in ${days}d ${hours}h`
  }

  if (hours > 0) {
    return `Ends in ${hours}h ${minutes}m`
  }

  return `Ends in ${minutes}m`
})

const setTournamentStanding = (standing: any, animate = false): void => {
  const previousRank = tournamentStanding.value?.rank ?? null
  const previousPoints = tournamentStanding.value?.points ?? null
  const nextRank = standing?.rank ?? null
  const nextPoints = standing?.points ?? null

  tournamentStanding.value = standing

  if (!animate || (previousRank === nextRank && previousPoints === nextPoints)) {
    return
  }

  tournamentStandingUpdated.value = false
  requestAnimationFrame(() => {
    tournamentStandingUpdated.value = true
  })

  if (tournamentStandingAnimationTimer) {
    clearTimeout(tournamentStandingAnimationTimer)
  }

  tournamentStandingAnimationTimer = setTimeout(() => {
    tournamentStandingUpdated.value = false
  }, 900)
}

const syncTournamentStanding = (): void => {
  setTournamentStanding(
    tournament.value?.user_standing ? { ...tournament.value.user_standing } : null,
  )
}

const handleTournamentStreamEvent = (eventName: string, data: string): void => {
  if (eventName !== 'standing' || !data) {
    return
  }

  try {
    const msg = JSON.parse(data)
    if (String(msg.tournament_id) !== String(tournament.value?.id)) {
      return
    }

    setTournamentStanding(
      {
        rank: msg.rank,
        points: msg.points ?? 0,
      },
      true,
    )
  } catch (error) {
    console.warn('Tournament score stream error:', error)
  }
}

const parseTournamentStreamChunk = (chunk: string, buffer: { value: string }): void => {
  buffer.value += chunk

  const parts = buffer.value.split(/\r?\n\r?\n/)
  buffer.value = parts.pop() ?? ''

  for (const part of parts) {
    const lines = part.split(/\r?\n/)
    let eventName = 'message'
    const dataLines: string[] = []

    for (const line of lines) {
      if (line.startsWith(':')) {
        continue
      }

      if (line.startsWith('event:')) {
        eventName = line.slice(6).trim()
        continue
      }

      if (line.startsWith('data:')) {
        dataLines.push(line.slice(5).trimStart())
      }
    }

    handleTournamentStreamEvent(eventName, dataLines.join('\n'))
  }
}

const closeTournamentScoreStream = (): void => {
  if (tournamentStreamController) {
    tournamentStreamController.abort()
    tournamentStreamController = null
  }

  tournamentStreamKey = null
}

const initTournamentScoreStream = async (): Promise<void> => {
  if (!import.meta.client || !isLogged.value || !token.value || !tournament.value?.id) {
    closeTournamentScoreStream()
    return
  }

  const config = useRuntimeConfig()
  const slug = String(route.params.slug)
  const streamKey = `${tournament.value.id}:${slug}:${token.value}`

  if (tournamentStreamController && tournamentStreamKey === streamKey) {
    return
  }

  closeTournamentScoreStream()

  const controller = new AbortController()
  tournamentStreamController = controller
  tournamentStreamKey = streamKey

  const baseURL = String(config.public.baseURL)
  const normalizedBaseURL = baseURL.endsWith('/') ? baseURL : `${baseURL}/`
  const streamUrl = new URL(
    `player/games/${encodeURIComponent(slug)}/tournament/stream`,
    normalizedBaseURL,
  )
  streamUrl.searchParams.set('casino_id', String(config.public.casinoID))

  try {
    const response = await fetch(streamUrl.toString(), {
      headers: {
        Accept: 'text/event-stream',
        Authorization: `Bearer ${token.value}`,
      },
      signal: controller.signal,
    })

    if (!response.ok || !response.body) {
      throw new Error(`Tournament stream failed with status ${response.status}`)
    }

    const reader = response.body.getReader()
    const decoder = new TextDecoder()
    const buffer = { value: '' }

    while (!controller.signal.aborted) {
      const { done, value } = await reader.read()

      if (done) {
        break
      }

      parseTournamentStreamChunk(decoder.decode(value, { stream: true }), buffer)
    }
  } catch (error: any) {
    if (!controller.signal.aborted) {
      console.warn('Tournament score stream connection error:', error)
    }
  } finally {
    if (tournamentStreamController === controller) {
      tournamentStreamController = null
      tournamentStreamKey = null
    }
  }
}

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
  syncTournamentStanding()
  initTournamentScoreStream()
  tournamentTimer = setInterval(() => {
    tournamentNow.value = Date.now()
  }, 60000)
})

//onmounted
onUnmounted(async () => {
  if (tournamentTimer) {
    clearInterval(tournamentTimer)
  }
  if (tournamentStandingAnimationTimer) {
    clearTimeout(tournamentStandingAnimationTimer)
  }
  closeTournamentScoreStream()
  unlockBodyScroll()
  setActivePlaySession('')
  setLoadWallets(true)
})

//watchers
watch(isLogged, async () => {
  await getGamePage()
  syncTournamentStanding()
  initTournamentScoreStream()
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

watch(
  () => tournament.value?.id,
  () => {
    syncTournamentStanding()
    initTournamentScoreStream()
  },
)
</script>
<template>
  <div>
    <div class="gameplay-wrapper mb-10">
      <div class="gameplay-main">
        <div v-if="!gameLoading && tournament" class="game-tournament-strip mt-1">
          <div class="game-tournament-strip__glow" aria-hidden="true" />
          <div class="game-tournament-strip__thumbnail">
            <img
              v-if="tournament.thumbnail_url"
              :src="tournament.thumbnail_url"
              :alt="tournament.name"
            />
            <v-icon v-else icon="mdi-trophy" size="30" />
          </div>

          <div class="game-tournament-strip__content">
            <div class="game-tournament-strip__eyebrow">
              <v-icon color="#f32424" icon="mdi-fire" size="16" />
              Live tournament
            </div>
            <div class="game-tournament-strip__title">{{ tournament.name }}</div>
            <div v-if="tournamentEndsLabel" class="game-tournament-strip__timer">
              <v-icon icon="mdi-timer-sand" size="16" />
              {{ tournamentEndsLabel }}
            </div>
          </div>

          <div
            v-if="tournamentStanding"
            class="game-tournament-strip__standing"
            :class="{ 'game-tournament-strip__standing--updated': tournamentStandingUpdated }"
          >
            <span>#{{ tournamentStanding.rank || '-' }}</span>
            <small>{{ tournamentStanding.points || 0 }} pts</small>
          </div>

        </div>

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
.gameplay-main {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
}

.game-tournament-strip {
  position: relative;
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 14px;
  margin-bottom: 12px;
  padding: 12px 14px;
  overflow: hidden;
  border: 1px solid rgba(255, 213, 104, 0.7);
  border-radius: 8px;
  background:
    radial-gradient(circle at top left, rgba(255, 238, 157, 0.85), transparent 28%),
    linear-gradient(135deg, #ff8a00 0%, #ffb526 47%, #f45c16 100%);
  box-shadow: 0 12px 30px rgba(255, 111, 0, 0.22);
  color: #1a1007;
}

.game-tournament-strip__glow {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.28), transparent);
  transform: translateX(-65%);
  animation: tournament-strip-shine 4s ease-in-out infinite;
}

.game-tournament-strip__thumbnail {
  position: relative;
  z-index: 1;
  display: grid;
  place-items: center;
  width: 54px;
  height: 54px;
  overflow: hidden;
  border: 2px solid rgba(255, 255, 255, 0.8);
  border-radius: 8px;
  background: rgba(21, 17, 12, 0.88);
  color: #ffd85f;
  box-shadow: 0 8px 18px rgba(38, 19, 2, 0.22);
}

.game-tournament-strip__thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.game-tournament-strip__content {
  position: relative;
  z-index: 1;
  min-width: 0;
}

.game-tournament-strip__eyebrow,
.game-tournament-strip__timer {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 800;
  line-height: 1.2;
  text-transform: uppercase;
}

.game-tournament-strip__title {
  overflow: hidden;
  margin: 2px 0;
  font-size: 20px;
  font-weight: 900;
  line-height: 1.15;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.game-tournament-strip__standing {
  position: relative;
  z-index: 1;
  display: grid;
  min-width: 78px;
  padding: 8px 10px;
  border: 1px solid rgba(255, 255, 255, 0.45);
  border-radius: 8px;
  background: rgba(21, 17, 12, 0.18);
  text-align: center;
  transform-origin: center;
}

.game-tournament-strip__standing--updated {
  animation: tournament-standing-pop 0.9s ease;
}

.game-tournament-strip__standing span {
  font-size: 18px;
  font-weight: 900;
  line-height: 1;
}

.game-tournament-strip__standing small {
  font-size: 11px;
  font-weight: 800;
  line-height: 1.2;
  text-transform: uppercase;
}

@keyframes tournament-strip-shine {
  0%,
  45% {
    transform: translateX(-70%);
  }
  100% {
    transform: translateX(70%);
  }
}

@keyframes tournament-standing-pop {
  0% {
    transform: scale(1);
    box-shadow: 0 0 0 rgba(255, 255, 255, 0);
  }
  24% {
    transform: scale(1.08);
    box-shadow: 0 0 0 5px rgba(255, 255, 255, 0.22);
  }
  55% {
    transform: scale(0.98);
  }
  100% {
    transform: scale(1);
    box-shadow: 0 0 0 rgba(255, 255, 255, 0);
  }
}

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

@media (max-width: 720px) {
  .game-tournament-strip {
    grid-template-columns: auto minmax(0, 1fr);
    gap: 10px;
    padding: 10px;
  }

  .game-tournament-strip__thumbnail {
    width: 48px;
    height: 48px;
  }

  .game-tournament-strip__title {
    font-size: 16px;
  }

  .game-tournament-strip__standing {
    grid-column: 1 / -1;
    width: 100%;
  }
}
</style>
