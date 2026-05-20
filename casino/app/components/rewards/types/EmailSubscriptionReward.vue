<script setup lang="ts">
type Reward = {
  title: string
  subtitle?: string | null
  description?: string | null
  thumbnailUrl?: string | null
  rule?: Record<string, any> | null
}

const props = defineProps<{
  reward: Reward
  loading?: boolean
}>()

const emit = defineEmits<{
  'redeem-action': [payload: { reward: Reward; email: string }]
}>()

const email = ref('')
const buttonLabel = computed(() => props.reward.rule?.claim_button_label || 'Subscribe')

const submit = () => {
  emit('redeem-action', {
    reward: props.reward,
    email: email.value,
  })
}
</script>

<template>
  <section class="reward-card reward-card--email">
    <div class="reward-card__media">
      <img
        :src="reward.thumbnailUrl || 'https://images.unsplash.com/photo-1606167668584-78701c57f13d?auto=format&fit=crop&w=900&q=80'"
        :alt="reward.title"
      />
    </div>

    <div class="reward-card__content">
      <span class="reward-card__eyebrow">{{ reward.subtitle || 'Limited reward' }}</span>
      <h1>{{ reward.title }}</h1>
      <p v-if="reward.description">{{ reward.description }}</p>

      <form class="reward-card__form" @submit.prevent="submit">
        <v-text-field
          v-model="email"
          type="email"
          required
          placeholder="Enter your email address"
          variant="outlined"
          density="comfortable"
          hide-details
        />
        <v-btn color="purple" type="submit" :loading="loading">{{ buttonLabel }}</v-btn>
      </form>
    </div>
  </section>
</template>

<style scoped>
.reward-card {
  display: grid;
  grid-template-columns: minmax(0, 0.95fr) minmax(320px, 1.05fr);
  gap: 28px;
  align-items: stretch;
  overflow: hidden;
  border: 1px solid var(--surface-border);
  border-radius: var(--premium-radius);
  background:
    linear-gradient(135deg, rgba(255, 255, 255, 0.04), transparent 38%), var(--surface-mid);
  box-shadow: var(--premium-shadow);
}

.reward-card__media {
  position: relative;
  min-height: 360px;
  overflow: hidden;
  background: var(--surface-low);
}

.reward-card__media::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent 58%, rgba(24, 27, 34, 0.72));
}

.reward-card__media img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: saturate(0.96) contrast(1.04);
}

.reward-card__content {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-width: 0;
  padding: 48px 40px 48px 12px;
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

.reward-card__content h1 {
  margin: 0;
  font-size: 42px;
  line-height: 1.08;
  font-weight: 800;
}

.reward-card__content p {
  max-width: 520px;
  margin: 14px 0 28px;
  color: var(--text-color);
  font-size: 16px;
  line-height: 1.6;
}

.reward-card__form {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 12px;
  max-width: 560px;
}

.reward-card__form :deep(.v-btn) {
  height: 48px !important;
  padding: 0 26px !important;
}

@media screen and (max-width: 900px) {
  .reward-card {
    grid-template-columns: 1fr;
  }

  .reward-card__media {
    min-height: 240px;
  }

  .reward-card__media::after {
    background: linear-gradient(180deg, transparent 58%, rgba(24, 27, 34, 0.76));
  }

  .reward-card__content {
    padding: 0 22px 28px;
  }
}

@media screen and (max-width: 560px) {
  .reward-card__content h1 {
    font-size: 32px;
  }

  .reward-card__form {
    grid-template-columns: 1fr;
  }
}
</style>
