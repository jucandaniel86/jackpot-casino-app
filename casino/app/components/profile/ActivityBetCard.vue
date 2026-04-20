<script setup lang="ts">
const { t } = useI18n()

defineProps<{
  item: any
  typeLabel: string
  typeColor: string
  amount: string
  formattedDate: string
}>()
</script>

<template>
  <div class="activity-card">
    <div class="activity-card__header">
      <div class="activity-card__game">{{ item.game?.name || '-' }}</div>
      <v-chip :color="typeColor" variant="tonal" size="small">
        {{ typeLabel }}
      </v-chip>
    </div>

    <div class="activity-card__grid">
      <div class="activity-card__row">
        <span class="activity-card__label">{{ t('settings.amount') }}</span>
        <span class="activity-card__value">{{ amount }}</span>
      </div>
      <div class="activity-card__row">
        <span class="activity-card__label">{{ t('settings.currency') }}</span>
        <span class="activity-card__value">{{ item.currency }}</span>
      </div>
      <div class="activity-card__row">
        <span class="activity-card__label">{{ t('settings.timestamp') }}</span>
        <span class="activity-card__value">{{ formattedDate }}</span>
      </div>
      <div class="activity-card__row">
        <span class="activity-card__label">{{ t('settings.status') }}</span>
        <span class="activity-card__value">Completed</span>
      </div>
      <div class="activity-card__row activity-card__row--stacked">
        <span class="activity-card__label">{{ t('settings.transID') }}</span>
        <span class="activity-card__value activity-card__value--break">{{ item.transaction_id }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.activity-card {
  background: #181b22;
  border: 1px solid #2b3040;
  border-radius: 12px;
  padding: 14px;
  color: #fff;
}

.activity-card__header {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
  margin-bottom: 14px;
}

.activity-card__game {
  font-size: 15px;
  font-weight: 700;
  line-height: 1.35;
}

.activity-card__grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.activity-card__row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.activity-card__row--stacked {
  flex-direction: column;
}

.activity-card__label {
  color: #98a0b3;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.activity-card__value {
  text-align: right;
  font-size: 14px;
  font-weight: 600;
}

.activity-card__value--break {
  text-align: left;
  word-break: break-all;
}
</style>
