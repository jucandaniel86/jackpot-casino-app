<script setup lang="ts">
import { useAppStore } from '~/core/store/app'
import { useAuthStore } from '~/core/store/auth'
import RewardCard from '~/components/rewards/RewardCard.vue'

type Reward = {
  id: number
  uid: string
  title: string
  subtitle?: string | null
  description?: string | null
  thumbnailUrl?: string | null
  type: string
  rule?: Record<string, any> | null
  claim_state?: {
    is_claimed: boolean
    message?: string | null
    next_claim_at?: string | null
    seconds_until_next?: number | null
  } | null
}

const emit = defineEmits<{
  'on-close': []
}>()

const rewards = ref<Reward[]>([])
const isLoading = ref(true)
const redeemingUid = ref<string | null>(null)

const authStore = useAuthStore()
const { setUser } = authStore
const { setLoadWallets } = useAppStore()
const { success: alertSuccess, error: alertError } = useAlerts()

const dailyReward = computed(() =>
  rewards.value.find((reward) => reward.type === 'daily_redeem' && !reward.claim_state?.is_claimed),
)

const loadRewards = async () => {
  isLoading.value = true

  try {
    const response = await useAPIFetch('/rewards')
    rewards.value = response?.data || []
  } catch (_error) {
    alertError('We could not load rewards.')
  } finally {
    isLoading.value = false
  }
}

const refreshPlayerProfile = async () => {
  try {
    const profile = await useAPIFetch('/player/profile')
    if (profile?.user) {
      setUser(profile.user)
    }
  } catch (_error) {
    // Claim state is refreshed from rewards even when the profile badge cannot update.
  }
}

const redeemDailyReward = async (reward: Reward) => {
  redeemingUid.value = reward.uid

  const { data, success, error } = await useApiPostFetch(`/player/rewards/${reward.uid}/claim`)

  redeemingUid.value = null

  if (!success) {
    alertError(error?.message || 'We could not redeem this reward.')
    return
  }

  if (!data?.success) {
    alertError(data?.message || 'We could not redeem this reward.')
    return
  }

  alertSuccess(data.message || 'Reward claimed.')
  setLoadWallets(true)
  await loadRewards()
  await refreshPlayerProfile()
}

onMounted(() => {
  loadRewards()
})
</script>

<template>
  <v-card class="redeem-overlay" variant="flat">
    <div class="redeem-overlay__header">
      <div>
        <span>{{ $t('profile.dailyRedeemTitle') }}</span>
        <h2>{{ dailyReward?.title || $t('profile.dailyRedeemTitle') }}</h2>
      </div>
      <v-btn icon="mdi-close" variant="text" color="white" @click.prevent="emit('on-close')" />
    </div>

    <div v-if="isLoading" class="redeem-overlay__loading">
      <v-progress-circular indeterminate color="purple" />
    </div>

    <template v-else>
      <RewardCard
        v-if="dailyReward"
        :reward="dailyReward"
        :loading="redeemingUid === dailyReward.uid"
        @redeem-action="redeemDailyReward($event.reward)"
      />

      <v-alert v-else type="success" variant="tonal">
        {{ $t('profile.dailyRedeemClaimed') }}
      </v-alert>
    </template>
  </v-card>
</template>

<style scoped>
.redeem-overlay {
  width: min(920px, calc(100vw - 32px));
  margin: 0 auto;
  overflow: hidden;
  border: 1px solid var(--surface-border);
  border-radius: 8px !important;
  background: var(--surface-high) !important;
  box-shadow: var(--premium-shadow);
}

.redeem-overlay__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 20px;
  border-bottom: 1px solid var(--surface-border);
  color: #fff;
}

.redeem-overlay__header span {
  color: color-mix(in srgb, var(--base-color) 65%, #ffffff);
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
}

.redeem-overlay__header h2 {
  margin: 4px 0 0;
  font-size: 22px;
  line-height: 1.2;
}

.redeem-overlay__loading {
  display: flex;
  min-height: 360px;
  align-items: center;
  justify-content: center;
}

.redeem-overlay :deep(.reward-card) {
  border: 0;
  border-radius: 0;
  box-shadow: none;
}

@media screen and (max-width: 760px) {
  .redeem-overlay {
    width: calc(100vw - 20px);
  }

  .redeem-overlay :deep(.reward-card) {
    grid-template-columns: 1fr;
  }
}
</style>
