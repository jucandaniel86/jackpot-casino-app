<script setup lang="ts">
import DailyRedeemReward from './types/DailyRedeemReward.vue'
import EmailSubscriptionReward from './types/EmailSubscriptionReward.vue'
import RegistrationReward from './types/RegistrationReward.vue'

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

const props = defineProps<{
  reward: Reward
  loading?: boolean
}>()

const emit = defineEmits<{
  'redeem-action': [payload: { reward: Reward; email?: string }]
}>()

const rewardComponent = computed(() => {
  switch (props.reward.type) {
    case 'email_subscription_reward':
      return EmailSubscriptionReward
    case 'registration_reward':
      return RegistrationReward
    case 'daily_redeem':
    default:
      return DailyRedeemReward
  }
})
</script>

<template>
  <component
    :is="rewardComponent"
    :reward="reward"
    :loading="loading"
    @redeem-action="emit('redeem-action', $event)"
  />
</template>
