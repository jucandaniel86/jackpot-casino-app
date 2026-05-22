<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import type { Tournament } from '../../components/container/tournaments/tournaments-config'
import type { GameType } from '~/core/types/Game'
import { ContainerSection, type ContainerType } from '~/core/types/Container'
import { useAppStore } from '~/core/store/app'

type TournamentPageResponse = {
  success?: boolean
  message?: string
  data?: {
    sidebar?: unknown
    tournament?: Tournament | null
  }
}

const route = useRoute()
const { setPageLoading, setSidebar } = useAppStore()

const props = reactive<{ tournament: Tournament; closed?: boolean }>({
  tournament: {} as Tournament,
  closed: false,
})

const panel = ref<'leaderboard' | 'prizes'>('leaderboard')
const detailsOpen = ref(true)
const loaded = ref(false)

const loadTournament = async (): Promise<void> => {
  setPageLoading(true)

  try {
    const slug = String(route.params.slug ?? '')
    const response = (await useAPIFetch(`/tournaments/${slug}`)) as TournamentPageResponse
    const data = response?.data ?? null

    if (!response?.success || !data?.tournament) {
      throw createError({
        statusCode: 404,
        statusMessage: 'Tournament not found',
        message: response?.message || 'Tournament not found.',
        fatal: true,
      })
    }

    props.tournament = data.tournament
    detailsOpen.value = !props.closed
    loaded.value = true

    if (data.sidebar) {
      setSidebar(data.sidebar)
    }
  } finally {
    setPageLoading(false)
  }
}

watch(
  () => route.params.slug,
  () => {
    loadTournament()
  },
)

onMounted(() => {
  loadTournament()
})

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

const protocols = computed(() => props.tournament.ui?.protocols ?? [])
const description = computed(() => props.tournament.ui?.description ?? null)
const rulesNote = computed(() => props.tournament.ui?.rules_note ?? null)

const leaderboard = computed(() => props.tournament.ui?.leaderboard ?? [])
const standing = computed(() => props.tournament.ui?.user_standing ?? null)
const showDetails = computed(() => !props.closed || detailsOpen.value)

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

const eligibleGamesContainer = computed<ContainerType>(() => ({
  id: `tournament-${props.tournament.id || 'current'}-eligible-games`,
  container: ContainerSection.CATEGORY_HEADLESS,
  children: [],
  appearance: {
    resolutionConfig: {
      XS: { isVisible: true },
      SM: { isVisible: true },
      MD: { isVisible: true },
      LG: { isVisible: true },
      XL: { isVisible: true },
    },
  },
  data: {
    title: 'Eligible Games',
    initialState: {
      page: 1,
      size: 96,
      totalItems: tournamentGames.value.length,
      data: tournamentGames.value,
    },
    resolutionConfig: {
      XS: {
        aspectRatioPercentage: 140,
        headingFontFamily: '',
        headingFontSize: 16,
        headingFontWeight: '600',
        itemsPerRow: 2,
        viewMoreFontFamily: '',
        viewMoreFontSize: 14,
        viewMoreFontWeight: '600',
      },
      SM: {
        aspectRatioPercentage: 140,
        headingFontFamily: '',
        headingFontSize: 16,
        headingFontWeight: '600',
        itemsPerRow: 3,
        viewMoreFontFamily: '',
        viewMoreFontSize: 14,
        viewMoreFontWeight: '600',
      },
      MD: {
        aspectRatioPercentage: 140,
        headingFontFamily: '',
        headingFontSize: 16,
        headingFontWeight: '600',
        itemsPerRow: 4,
        viewMoreFontFamily: '',
        viewMoreFontSize: 14,
        viewMoreFontWeight: '600',
      },
      LG: {
        aspectRatioPercentage: 140,
        headingFontFamily: '',
        headingFontSize: 16,
        headingFontWeight: '600',
        itemsPerRow: 5,
        viewMoreFontFamily: '',
        viewMoreFontSize: 14,
        viewMoreFontWeight: '600',
      },
      XL: {
        aspectRatioPercentage: 140,
        headingFontFamily: '',
        headingFontSize: 16,
        headingFontWeight: '600',
        itemsPerRow: 6,
        viewMoreFontFamily: '',
        viewMoreFontSize: 14,
        viewMoreFontWeight: '600',
      },
    },
  },
}))
</script>
<template>
  <div v-if="loaded" class="t-root mb-5">
    <!-- Tournament Item: Featured -->
    <v-card class="t-feature mt-5" variant="flat">
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
            </div>
          </div>
        </v-col>
      </v-row>

      <div class="t-progress">
        <div class="t-progress__bar" :style="{ width: `${progressPercent}%` }" />
      </div>
    </v-card>

    <Transition name="t-details">
      <v-row v-if="showDetails" class="t-below">
        <!-- Rules Section (left) -->
        <v-col cols="12" md="7">
          <v-card class="t-rules" variant="flat">
            <div class="t-rules__header">
              <v-icon icon="mdi-gavel" class="t-rules__icon" />
              <div class="t-rules__title">Tournament Protocols</div>
            </div>

            <div class="t-rules__scroll">
              <div v-if="protocols.length" class="t-rules__grid">
                <div v-for="(p, i) in protocols" :key="`p-${i}`" class="t-rule-card">
                  <div class="t-rule-card__label">{{ p.label }}</div>
                  <div class="t-rule-card__value">{{ p.value }}</div>
                </div>
              </div>
              <div v-else class="t-muted">No rules/protocols available.</div>

              <div class="t-rules__text">
                <p v-if="description" class="t-desc">{{ description }}</p>
                <p v-if="rulesNote" class="t-note">{{ rulesNote }}</p>
              </div>
            </div>
          </v-card>
        </v-col>

        <!-- Right Sidebar: Leaderboard & Prizes -->
        <v-col cols="12" md="5">
          <v-card class="t-sidebar" variant="flat">
            <div class="t-sidebar__tabs">
              <v-btn
                class="t-sidebar__tab"
                variant="text"
                :class="{ 't-sidebar__tab--active': panel === 'leaderboard' }"
                @click="panel = 'leaderboard'"
              >
                Leaderboard
              </v-btn>
              <v-btn
                class="t-sidebar__tab"
                variant="text"
                :class="{ 't-sidebar__tab--active': panel === 'prizes' }"
                @click="panel = 'prizes'"
              >
                Prizes
              </v-btn>
            </div>

            <div class="t-sidebar__content">
              <v-window v-model="panel" class="t-sidebar__window">
                <v-window-item value="leaderboard">
                  <div class="t-sidebar__scroll">
                    <div class="t-sidebar__body">
                      <table class="t-table">
                        <thead>
                          <tr>
                            <th>Position</th>
                            <th>Player</th>
                            <th class="t-right-align">Score</th>
                            <th class="t-right-align">Prizes</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-if="leaderboard.length === 0">
                            <td class="t-muted t-center" colspan="4">No leaderboard data.</td>
                          </tr>
                          <tr v-for="row in leaderboard" :key="`lb-${row.position}-${row.player}`">
                            <td>
                              <div
                                class="t-rank"
                                :class="{
                                  't-rank--gold': row.position === 1,
                                  't-rank--bronze': row.position === 3,
                                  't-rank--plain': row.position > 3,
                                }"
                              >
                                {{ row.position }}
                              </div>
                            </td>
                            <td class="t-player">{{ row.player }}</td>
                            <td class="t-right-align t-score">{{ row.score.toLocaleString() }}</td>
                            <td class="t-right-align t-prize">{{ row.prize_label }}</td>
                          </tr>
                        </tbody>
                      </table>

                      <div v-if="standing" class="t-standing">
                        <div class="t-standing__top">
                          <div class="t-standing__label">Your Standing</div>
                          <div v-if="standing.badge_text" class="t-standing__badge">
                            {{ standing.badge_text }}
                          </div>
                        </div>

                        <div class="t-standing__row">
                          <div class="t-standing__pos">{{ standing.position }}</div>
                          <div class="t-standing__user">
                            <div class="t-standing__name">{{ standing.username }}</div>
                            <div v-if="standing.est_prize_label" class="t-standing__est">
                              EST. PRIZE: {{ standing.est_prize_label }}
                            </div>
                          </div>
                          <div class="t-standing__score">
                            <div class="t-standing__scoreLabel">Score</div>
                            <div class="t-standing__scoreValue">
                              {{ standing.score.toLocaleString() }}
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </v-window-item>

                <v-window-item value="prizes">
                  <div class="t-sidebar__scroll t-sidebar__scroll--pad">
                    <div class="t-sidebar__body t-sidebar__body--pad">
                      <div v-if="props.tournament.prizes?.length" class="t-prizes">
                        <div
                          v-for="prize in props.tournament.prizes"
                          :key="prize.id"
                          class="t-prizeRow"
                        >
                          <div>
                            <div class="t-prizeRow__name">{{ prize.prize_name }}</div>
                            <div class="t-prizeRow__meta">
                              <span v-if="prize.prize_type === 'rank'">
                                Rank {{ prize.rank_from
                                }}{{
                                  prize.rank_to && prize.rank_to !== prize.rank_from
                                    ? ` - ${prize.rank_to}`
                                    : ''
                                }}
                              </span>
                              <span v-else>Min points: {{ prize.min_points }}</span>
                            </div>
                          </div>
                          <div class="t-prizeRow__amount">
                            {{ prize.prize_currency || '' }} {{ prize.prize_amount }}
                          </div>
                        </div>
                      </div>

                      <div v-else class="t-muted t-center">No prizes configured.</div>
                    </div>
                  </div>
                </v-window-item>
              </v-window>
            </div>
          </v-card>
        </v-col>

        <v-col v-if="tournamentGames.length" cols="12">
          <section class="t-eligible-games">
            <div class="t-games__eyebrow">Eligible Games</div>
            <div class="t-games__title">Play these games to score</div>
            <container-categoryheadless
              :key="eligibleGamesContainer.id"
              :options="eligibleGamesContainer"
            />
          </section>
        </v-col>
      </v-row>
    </Transition>
  </div>
</template>

<style scoped>
@import url('../../components/container/tournaments/tournaments.css');
</style>
