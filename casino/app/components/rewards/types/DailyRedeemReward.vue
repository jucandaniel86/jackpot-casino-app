<script setup lang="ts">
type Reward = {
  title: string
  subtitle?: string | null
  description?: string | null
  thumbnailUrl?: string | null
  rule?: Record<string, any> | null
  claim_state?: {
    is_claimed: boolean
    message?: string | null
    next_claim_at?: string | null
    seconds_until_next?: number | null
  } | null
}

const props = defineProps<{
  reward: Reward
  loading?: boolean
}>()

const emit = defineEmits<{
  'redeem-action': [payload: { reward: Reward }]
}>()

const resolveRemainingSeconds = () => {
  const state = props.reward.claim_state

  if (!state) return 0

  if (typeof state.seconds_until_next === 'number' && state.seconds_until_next > 0) {
    return Math.ceil(state.seconds_until_next)
  }

  if (!state.next_claim_at) return 0

  return Math.max(0, Math.ceil((new Date(state.next_claim_at).getTime() - Date.now()) / 1000))
}

const remainingSeconds = ref(resolveRemainingSeconds())
let timer: ReturnType<typeof setInterval> | null = null

const isClaimed = computed(() => Boolean(props.reward.claim_state?.is_claimed))
const buttonLabel = computed(() => (isClaimed.value ? 'Claimed' : props.reward.rule?.claim_button_label || 'Redeem'))
const claimMessage = computed(() => props.reward.claim_state?.message || 'You already claimed today\'s reward.')
const formattedCountdown = computed(() => {
  const total = Math.max(0, remainingSeconds.value)
  const hours = Math.floor(total / 3600)
  const minutes = Math.floor((total % 3600) / 60)
  const seconds = total % 60

  return [hours, minutes, seconds].map((value) => String(value).padStart(2, '0')).join(':')
})

const startCountdown = () => {
  if (timer) clearInterval(timer)

  if (!isClaimed.value || remainingSeconds.value <= 0) return

  timer = setInterval(() => {
    remainingSeconds.value = Math.max(0, remainingSeconds.value - 1)

    if (remainingSeconds.value === 0 && timer) {
      clearInterval(timer)
      timer = null
    }
  }, 1000)
}

watch(
  () => props.reward.claim_state,
  () => {
    remainingSeconds.value = resolveRemainingSeconds()
    startCountdown()
  },
  { deep: true },
)

onMounted(startCountdown)

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})
</script>

<template>
  <section class="reward-card reward-card--daily">
    <div class="reward-card__content">
      <span class="reward-card__eyebrow">{{ reward.subtitle || 'Daily Reward' }}</span>
      <h2>{{ reward.title }}</h2>
      <p v-if="reward.description">{{ reward.description }}</p>

      <v-alert v-if="isClaimed" class="reward-card__status" type="success" variant="tonal">
        <strong>{{ claimMessage }}</strong>
        <span v-if="remainingSeconds > 0">Next reward in {{ formattedCountdown }}</span>
      </v-alert>

      <v-btn
        color="purple"
        prepend-icon="mdi-gift-outline"
        :loading="loading"
        :disabled="isClaimed"
        @click.prevent="emit('redeem-action', { reward })"
      >
        {{ buttonLabel }}
      </v-btn>
    </div>

    <div class="reward-card__media">
      <img
        :src="reward.thumbnailUrl || 'https://images.unsplash.com/photo-1606167668584-78701c57f13d?auto=format&fit=crop&w=900&q=80'"
        :alt="reward.title"
      />
    </div>
  </section>
</template>

<style scoped>
.reward-card {
  display: grid;
  grid-template-columns: minmax(320px, 1.05fr) minmax(0, 0.95fr);
  gap: 28px;
  align-items: stretch;
  overflow: hidden;
  border: 1px solid var(--surface-border);
  border-radius: var(--premium-radius);
  background:
    radial-gradient(circle at 88% 18%, var(--base-color-soft), transparent 28%),
    linear-gradient(135deg, rgba(255, 255, 255, 0.035), transparent 44%), var(--surface-low);
  box-shadow: var(--premium-shadow);
}

.reward-card__content {
  min-width: 0;
  padding: 40px;
  color: #fff;
}

.reward-card__eyebrow {
  display: inline-flex;
  width: fit-content;
  margin-bottom: 14px;
  padding: 7px 12px;
  border: 1px solid color-mix(in srgb, var(--base-color) 42%, transparent);
  border-radius: 999px;
  background: var(--base-color-soft);
  color: color-mix(in srgb, var(--base-color) 55%, #ffffff);
  font-size: 13px;
  font-weight: 800;
}

.reward-card__content h2 {
  margin: 0;
  font-size: 34px;
  line-height: 1.16;
  font-weight: 800;
}

.reward-card__content p {
  max-width: 560px;
  margin: 14px 0 26px;
  color: var(--text-color);
  font-size: 16px;
  line-height: 1.6;
}

.reward-card__status {
  max-width: 560px;
  margin-bottom: 22px;
}

.reward-card__status :deep(.v-alert__content) {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.reward-card__media {
  min-height: 300px;
  overflow: hidden;
  background: var(--surface-mid);
}

.reward-card__media img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: saturate(0.94) contrast(1.05);
}

@media screen and (max-width: 900px) {
  .reward-card {
    grid-template-columns: 1fr;
  }

  .reward-card__content {
    padding: 28px 22px 0;
  }

  .reward-card__media {
    min-height: 240px;
  }
}
</style>
