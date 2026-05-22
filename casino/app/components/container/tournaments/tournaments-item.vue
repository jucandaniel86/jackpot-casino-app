<script setup lang="ts">
import type { Tournament } from './tournaments-config'
import type { GameType } from '~/core/types/Game'

const props = defineProps<{ tournament: Tournament; closed?: boolean }>()
const router = useRouter()

const detailsOpen = ref(!props.closed)
const gamesIndex = ref(0)

watch(
  () => props.closed,
  (closed) => {
    detailsOpen.value = !closed
  },
)

const statusLabel = computed(() => {
  if (props.tournament.status === 'active') return 'LIVE NOW'
  if (props.tournament.status === 'upcoming') return 'UPCOMING'
  if (props.tournament.status === 'finished') return 'FINISHED'
  return String(props.tournament.status || '').toUpperCase()
})

const subtitle = computed(() => props.tournament.ui?.subtitle ?? null)
const playersCount = computed(() => props.tournament.ui?.players_count ?? null)
const endsInLabel = computed(() => props.tournament.ui?.ends_in_label ?? null)
const prizePoolLabel = computed(() => props.tournament.ui?.prize_pool_label ?? null)
const progressPercent = computed(() => {
  const v = props.tournament.ui?.progress_percent
  const n = typeof v === 'number' ? v : null
  if (n === null) return 0
  return Math.max(0, Math.min(100, n))
})

const tournamentGames = computed<GameType[]>(() =>
  (props.tournament.games ?? [])
    .map((game) => {
      const gameId = String(game.game_id ?? game.pivot?.game_id ?? game.id ?? '')
      const slug = game.slug || gameId
      const imageUrl = game.thumbnail_url || game.thumbnail || props.tournament.thumbnail || ''

      if (!gameId || !imageUrl) return null

      return {
        id: gameId,
        name: game.name || `Game ${gameId}`,
        imageUrl,
        realPlayUrl: slug,
        demoPlayUrl: slug,
        hasDemo: false,
      }
    })
    .filter((game): game is GameType => game !== null),
)
const maxGamesIndex = computed(() => Math.max(0, tournamentGames.value.length - 3))

watch(
  () => props.tournament.id,
  () => {
    gamesIndex.value = 0
  },
)

watch(maxGamesIndex, (maxIndex) => {
  if (gamesIndex.value > maxIndex) {
    gamesIndex.value = maxIndex
  }
})

const openTournament = () => {
  if (!props.tournament.id) return

  router.push({
    path: `/tournaments/${props.tournament.id}`,
  })
}
</script>
<template>
  <div class="t-root mb-5">
    <!-- Tournament Item: Featured -->
    <v-card class="t-feature" variant="flat">
      <v-row>
        <v-col class="t-feature__thumb" cols="12" md="auto">
          <v-img
            class="t-feature__img"
            :src="props.tournament.thumbnail || undefined"
            cover
            alt="Tournament Thumbnail"
          />
        </v-col>

        <v-col class="t-feature__content" cols="12" md="auto">
          <div class="t-feature__inner">
            <div class="t-feature__top">
              <div>
                <div class="t-badges">
                  <span class="t-badge t-badge--live">{{ statusLabel }}</span>
                  <span v-if="playersCount !== null" class="t-badge t-badge--players">
                    <v-icon class="t-badge__icon" icon="mdi-account-group" size="14" />
                    {{ playersCount }} players
                  </span>
                </div>

                <h3 class="t-title">
                  {{ props.tournament.name }}
                </h3>
                <p v-if="subtitle" class="t-subtitle">
                  {{ subtitle }}
                </p>
              </div>

              <div class="t-ends">
                <div class="t-ends__label">Ends in</div>
                <div class="t-ends__value">
                  <v-icon icon="mdi-timer-outline" size="16" />
                  {{ endsInLabel || '—' }}
                </div>
              </div>
            </div>

            <div class="t-feature__bottom">
              <div>
                <div class="t-prize__label">Prize Pool</div>
                <div class="t-prize__value">
                  {{ prizePoolLabel || '—' }}
                </div>
              </div>

              <div class="d-flex ga-2">
                <v-btn class="t-cta" variant="flat" @click="openTournament">
                  JOIN TOURNAMENT
                </v-btn>
              </div>
            </div>
          </div>
        </v-col>
      </v-row>

      <div class="t-progress">
        <div class="t-progress__bar" :style="{ width: `${progressPercent}%` }" />
      </div>
    </v-card>
  </div>
</template>

<style scoped>
@import url('./tournaments.css');
</style>
