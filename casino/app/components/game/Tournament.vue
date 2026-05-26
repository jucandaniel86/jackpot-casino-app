<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import type { Tournament as TournamentConfig } from '~/components/container/tournaments/tournaments-config'
import { useAuthStore } from '~/core/store/auth'
import { useFeApi } from '~/composables/useFeApi'

const props = withDefaults(
  defineProps<{
    tournament: (TournamentConfig & {
      thumbnail_url?: string | null
      user_standing?: any
    }) | null
    compact?: boolean
    active?: boolean
  }>(),
  {
    compact: false,
    active: true,
  },
)

const { isLogged } = storeToRefs(useAuthStore())
const { onGameEvent } = useFeApi()

const now = ref(Date.now())
const standing = ref<any>(null)
const standingUpdated = ref(false)

let timer: ReturnType<typeof setInterval> | null = null
let standingAnimationTimer: ReturnType<typeof setTimeout> | null = null
let standingRefreshTimer: ReturnType<typeof setTimeout> | null = null
let standingRefreshLoading = false
let unsubscribeRoundEnd: (() => void) | null = null

const thumbnailUrl = computed(() => props.tournament?.thumbnail_url || props.tournament?.thumbnail || '')

const endsLabel = computed(() => {
  if (!props.tournament?.ended_at) {
    return ''
  }

  const endTime = new Date(props.tournament.ended_at).getTime()
  if (Number.isNaN(endTime)) {
    return ''
  }

  const diffInMinutes = Math.max(0, Math.ceil((endTime - now.value) / 60000))
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

const setStanding = (nextStanding: any, animate = false): void => {
  const previousRank = standing.value?.rank ?? null
  const previousPoints = standing.value?.points ?? null
  const nextRank = nextStanding?.rank ?? nextStanding?.position ?? null
  const nextPoints = nextStanding?.points ?? nextStanding?.score ?? null

  standing.value = nextStanding

  if (!animate || (previousRank === nextRank && previousPoints === nextPoints)) {
    return
  }

  standingUpdated.value = false
  requestAnimationFrame(() => {
    standingUpdated.value = true
  })

  if (standingAnimationTimer) {
    clearTimeout(standingAnimationTimer)
  }

  standingAnimationTimer = setTimeout(() => {
    standingUpdated.value = false
  }, 900)
}

const syncStanding = (): void => {
  setStanding(props.tournament?.user_standing ? { ...props.tournament.user_standing } : null)
}

const refreshStanding = async (): Promise<void> => {
  if (!isLogged.value || !props.tournament?.id || standingRefreshLoading) {
    return
  }

  standingRefreshLoading = true

  try {
    const response = await useAPIFetch(`/player/tournaments/${props.tournament.id}/standing`)
    const nextStanding = response?.data ?? null

    if (!nextStanding) {
      return
    }

    setStanding(
      {
        rank: nextStanding.rank ?? nextStanding.standing?.position ?? null,
        points: nextStanding.points ?? nextStanding.standing?.score ?? 0,
      },
      true,
    )
  } catch (error) {
    console.warn('Tournament standing refresh failed:', error)
  } finally {
    standingRefreshLoading = false
  }
}

const scheduleStandingRefresh = (): void => {
  if (standingRefreshTimer) {
    clearTimeout(standingRefreshTimer)
  }

  standingRefreshTimer = setTimeout(() => {
    refreshStanding()
  }, 800)
}

const unsubscribeRoundEndListener = (): void => {
  unsubscribeRoundEnd?.()
  unsubscribeRoundEnd = null

  if (standingRefreshTimer) {
    clearTimeout(standingRefreshTimer)
    standingRefreshTimer = null
  }
}

const subscribeRoundEndListener = (): void => {
  if (unsubscribeRoundEnd || !props.active) {
    return
  }

  unsubscribeRoundEnd = onGameEvent('notifyRoundEnd', async (_event, payload) => {
    console.log('Game round ended', payload)
    scheduleStandingRefresh()
  })
}

onMounted(() => {
  syncStanding()
  timer = setInterval(() => {
    now.value = Date.now()
  }, 60000)

  subscribeRoundEndListener()
})

onUnmounted(() => {
  if (timer) {
    clearInterval(timer)
  }
  if (standingAnimationTimer) {
    clearTimeout(standingAnimationTimer)
  }
  if (standingRefreshTimer) {
    clearTimeout(standingRefreshTimer)
  }
  unsubscribeRoundEndListener()
})

watch(
  () => props.tournament?.id,
  () => {
    syncStanding()
  },
)

watch(isLogged, () => {
  syncStanding()
})

watch(
  () => props.active,
  (active) => {
    if (active) {
      syncStanding()
      subscribeRoundEndListener()
      return
    }

    unsubscribeRoundEndListener()
  },
)
</script>

<template>
  <div
    v-if="tournament"
    class="game-tournament-strip"
    :class="{ 'game-tournament-strip--compact': compact }"
  >
    <div class="game-tournament-strip__glow" aria-hidden="true" />
    <div class="game-tournament-strip__thumbnail">
      <img v-if="thumbnailUrl" :src="thumbnailUrl" :alt="tournament.name" />
      <v-icon v-else icon="mdi-trophy" size="30" />
    </div>

    <div class="game-tournament-strip__content">
      <div class="game-tournament-strip__eyebrow">
        <v-icon color="#f32424" icon="mdi-fire" size="16" />
        Live tournament
      </div>
      <div class="game-tournament-strip__title">{{ tournament.name }}</div>
      <div v-if="endsLabel" class="game-tournament-strip__timer">
        <v-icon icon="mdi-timer-sand" size="16" />
        {{ endsLabel }}
      </div>
    </div>

    <div
      v-if="standing"
      class="game-tournament-strip__standing"
      :class="{ 'game-tournament-strip__standing--updated': standingUpdated }"
    >
      <span>#{{ standing.rank || standing.position || '-' }}</span>
      <small>{{ standing.points || standing.score || 0 }} pts</small>
    </div>
  </div>
</template>
