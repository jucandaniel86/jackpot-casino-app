<script setup lang="ts">
import { useAppStore } from '~/core/store/app'
import { useAuthStore } from '~/core/store/auth'
import { OverlaysTypes } from '~/core/types/Overlays'
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

const rewards = ref<Reward[]>([])
const isLoading = ref(true)
const redeemingUid = ref<string | null>(null)

const router = useRouter()
const { success: alertSuccess, error: alertError } = useAlerts()
const { setLoadWallets } = useAppStore()
const { isLogged } = storeToRefs(useAuthStore())

const visibleRewards = computed(() =>
  rewards.value.filter((reward) => reward.type !== 'registration_reward' || !isLogged.value),
)

const openOverlay = (overlay: OverlaysTypes) => {
  router.replace({ query: { overlay } })
}

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

const redeemEmailReward = async (reward: Reward, email?: string) => {
  if (!email) {
    alertError('Please enter your email address.')
    return
  }

  redeemingUid.value = reward.uid

  const { data, success, error } = await useApiPostFetch('/redeem', {
    email,
    reward_uid: reward.uid,
  })

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
}

const redeemDailyReward = async (reward: Reward) => {
  if (!isLogged.value) {
    openOverlay(OverlaysTypes.LOGIN)
    return
  }

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
}

const redeemRegistrationReward = () => {
  openOverlay(OverlaysTypes.REGISTER)
}

const redeemReward = async (payload: { reward: Reward; email?: string }) => {
  switch (payload.reward.type) {
    case 'email_subscription_reward':
      await redeemEmailReward(payload.reward, payload.email)
      return
    case 'registration_reward':
      redeemRegistrationReward()
      return
    case 'daily_redeem':
    default:
      await redeemDailyReward(payload.reward)
  }
}

onMounted(() => {
  loadRewards()
})

watch(isLogged, () => {
  loadRewards()
})
</script>

<template>
  <v-container class="redeem-page">
    <div v-if="isLoading" class="redeem-page__loading">
      <v-progress-circular indeterminate color="purple" />
    </div>

    <template v-else>
      <RewardCard
        v-for="reward in visibleRewards"
        :key="reward.uid"
        :reward="reward"
        :loading="redeemingUid === reward.uid"
        @redeem-action="redeemReward"
      />

      <v-alert v-if="!visibleRewards.length" type="info" variant="tonal">
        No rewards are available right now.
      </v-alert>
    </template>
  </v-container>
</template>

<style scoped>
.redeem-page {
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding-top: 24px;
  padding-bottom: 24px;
}

.redeem-page__loading {
  display: flex;
  min-height: 320px;
  align-items: center;
  justify-content: center;
}

@media screen and (max-width: 560px) {
  .redeem-page {
    padding-top: 16px;
  }
}
</style>
