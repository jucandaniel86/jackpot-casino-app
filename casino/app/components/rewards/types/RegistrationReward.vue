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
  'redeem-action': [payload: { reward: Reward }]
}>()

const buttonLabel = computed(() => props.reward.rule?.claim_button_label || 'Register')
</script>

<template>
  <section class="reward-card reward-card--registration">
    <div class="reward-card__media">
      <img
        :src="reward.thumbnailUrl || 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80'"
        :alt="reward.title"
      />
    </div>

    <div class="reward-card__content">
      <span class="reward-card__eyebrow">{{ reward.subtitle || 'Registration Reward' }}</span>
      <h2>{{ reward.title }}</h2>
      <p v-if="reward.description">{{ reward.description }}</p>

      <v-btn
        color="purple"
        prepend-icon="mdi-account-plus-outline"
        :loading="loading"
        @click.prevent="emit('redeem-action', { reward })"
      >
        {{ buttonLabel }}
      </v-btn>
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
    linear-gradient(135deg, rgba(255, 255, 255, 0.04), transparent 40%), var(--surface-mid);
  box-shadow: var(--premium-shadow);
}

.reward-card__content {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-width: 0;
  padding: 40px 40px 40px 12px;
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

.reward-card__media {
  min-height: 300px;
  overflow: hidden;
  background: var(--surface-low);
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

  .reward-card__media {
    min-height: 240px;
  }

  .reward-card__content {
    padding: 0 22px 28px;
  }
}
</style>
