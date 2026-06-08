<script setup lang="ts">
import type { BuyBundle } from '~/core/store/buy'

type BundleItemSize = 'default' | 'xs'

const props = withDefaults(
  defineProps<{
    bundle: BuyBundle
    compact?: boolean
    size?: BundleItemSize
    showBuyButton?: boolean
    buyDisabled?: boolean
  }>(),
  {
    compact: false,
    size: 'default',
    showBuyButton: true,
    buyDisabled: false,
  },
)
const emit = defineEmits<{ buy: [bundle: BuyBundle] }>()

const coinsLabel = computed(() => Number(props.bundle.coins || 0).toLocaleString())
const isHero = computed(() => props.bundle.tier === 'featured' || Boolean(props.bundle.featured))
const isExtraSmall = computed(() => props.size === 'xs')
const imageUrl = computed(() => props.bundle.thumbnail || props.bundle.image_url)
const ctaLabel = computed(() => {
  if (props.bundle.cta_text) return props.bundle.cta_text

  return props.compact || isExtraSmall.value ? 'Buy' : 'Buy Now'
})
const currencyLabel = computed(() => props.bundle.price_currency || '-')
const realMoneyLabel = computed(() => {
  if (props.bundle.priceLabel) return props.bundle.priceLabel
  if (props.bundle.price_amount === null || props.bundle.price_amount === undefined) return '-'

  return `${currencyLabel.value} ${Number(props.bundle.price_amount).toFixed(2)}`
})
</script>

<template>
  <v-card
    variant="flat"
    class="bb-card"
    :class="{
      'bb-card--hero': isHero && !isExtraSmall,
      'bb-card--compact': props.compact,
      'bb-card--xs': isExtraSmall,
    }"
  >
    <div v-if="bundle.badge && !isExtraSmall" class="bb-card__badge">
      {{ bundle.badge }}
    </div>

    <div v-if="!props.compact && !isExtraSmall" class="bb-card__shimmer" />

    <div class="bb-card__visual" :class="{ 'bb-card__visual--image': imageUrl }">
      <v-img
        v-if="imageUrl"
        :src="imageUrl"
        :alt="bundle.name"
        :width="isExtraSmall ? 90 : '100%'"
        :height="isExtraSmall ? 90 : undefined"
        aspect-ratio="1"
        class="bb-card__image mb-2"
        cover
      />
      <v-icon
        v-else
        :icon="bundle.icon"
        :size="isExtraSmall ? 42 : isHero ? 88 : props.compact ? 28 : 72"
      />
    </div>

    <div class="bb-card__content">
      <div class="bb-card__name">{{ bundle.name }}</div>

      <template v-if="isExtraSmall">
        <div class="bb-card__xs-line">
          <span>Coins</span>
          <strong>{{ coinsLabel }}</strong>
        </div>
        <div class="bb-card__xs-line">
          <span>Currency</span>
          <strong>{{ currencyLabel }}</strong>
        </div>
        <div class="bb-card__xs-line">
          <span>Real money</span>
          <strong>{{ realMoneyLabel }}</strong>
        </div>
      </template>

      <div v-else class="bb-card__coins">
        {{ coinsLabel }}
        <span>Coins</span>
      </div>

      <div v-if="bundle.bonusLabel && !isExtraSmall" class="bb-card__bonus">
        {{ bundle.bonusLabel }}
      </div>
    </div>

    <div v-if="props.showBuyButton" class="bb-card__footer">
      <div class="bb-card__price">{{ bundle.priceLabel }}</div>
      <v-btn
        class="bb-gold-btn"
        variant="flat"
        :class="{ 'bb-gold-btn--compact': props.compact }"
        :disabled="props.buyDisabled"
        @click="emit('buy', bundle)"
      >
        {{ ctaLabel }}
      </v-btn>
    </div>
  </v-card>
</template>

<style scoped>
.bb-card {
  --bb-gold: var(--base-color, #f2cf8e);
  --bb-gold-strong: #f5c542;
  --bb-surface-low: #1c1b1b;
  --bb-text: #e5e2e1;
  --bb-muted: #d1c5ae;

  position: relative;
  display: flex;
  min-height: 420px;
  flex-direction: column;
  align-items: center;
  overflow: hidden;
  border-radius: 14px !important;
  padding: 32px;
  text-align: center;
  background: rgba(53, 53, 52, 0.42) !important;
  backdrop-filter: blur(24px);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
  transition:
    transform 0.35s ease,
    box-shadow 0.35s ease;
}

.bb-card:hover {
  transform: translateY(-4px);
  box-shadow:
    inset 0 0 0 1px rgba(245, 197, 66, 0.14),
    0 24px 60px rgba(0, 0, 0, 0.25);
}

.bb-card--hero {
  min-height: 456px;
  transform: scale(1.04);
  background: rgba(53, 53, 52, 0.58) !important;
  box-shadow:
    inset 0 0 0 2px rgba(245, 197, 66, 0.22),
    0 0 54px rgba(245, 197, 66, 0.14);
  z-index: 1;
}

.bb-card--hero:hover {
  transform: scale(1.04) translateY(-4px);
}

.bb-card--compact {
  min-height: 196px;
  align-items: stretch;
  padding: 22px;
  text-align: left;
  background: var(--bb-surface-low) !important;
}

.bb-card--xs {
  display: grid;
  grid-template-columns: 90px minmax(0, 1fr);
  gap: 14px;
  min-height: 0;
  align-items: center;
  padding: 12px;
  text-align: left;
  background: var(--bb-surface-low, rgba(28, 27, 27, 0.92)) !important;
}

.bb-card--xs:hover {
  transform: none;
}

.bb-card__shimmer {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent, rgba(245, 197, 66, 0.1), transparent);
  background-size: 200% 100%;
  opacity: 0;
  transition: opacity 0.35s ease;
}

.bb-card:hover .bb-card__shimmer,
.bb-card--hero .bb-card__shimmer {
  opacity: 1;
}

.bb-card__badge {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 2;
  border-radius: 999px;
  background: rgba(245, 197, 66, 0.95);
  color: #251a00;
  font-size: 10px;
  font-weight: 950;
  letter-spacing: 0.12em;
  padding: 5px 10px;
  text-transform: uppercase;
}

.bb-card__visual {
  position: relative;
  z-index: 1;
  display: flex;
  height: 180px;
  width: 100%;
  align-items: center;
  justify-content: center;
  color: var(--bb-gold);
  opacity: 0.95;
}

.bb-card--compact .bb-card__visual {
  height: auto;
  justify-content: flex-start;
  margin-bottom: 14px;
  color: var(--bb-muted);
}

.bb-card__visual--image {
  height: auto;
}

.bb-card--xs .bb-card__visual {
  width: 90px;
  height: 90px;
  margin: 0;
}

.bb-card__image {
  width: 100%;
  aspect-ratio: 1;
  border-radius: 10px;
}

.bb-card--compact .bb-card__image {
  flex: 0 0 100%;
}

.bb-card--xs .bb-card__image {
  width: 90px;
  height: 90px;
  flex: 0 0 90px;
  margin-bottom: 0 !important;
}

.bb-card__content {
  position: relative;
  z-index: 1;
}

.bb-card--xs .bb-card__content {
  min-width: 0;
}

.bb-card__name {
  color: var(--bb-text);
  font-size: 22px;
  font-weight: 900;
  letter-spacing: -0.02em;
  margin-bottom: 12px;
}

.bb-card--hero .bb-card__name {
  color: var(--bb-gold);
  font-size: 32px;
  font-weight: 950;
}

.bb-card--compact .bb-card__name {
  color: var(--bb-muted);
  font-size: 12px;
  font-weight: 900;
  letter-spacing: 0.02em;
  margin-bottom: 4px;
  text-transform: uppercase;
}

.bb-card--xs .bb-card__name {
  color: var(--bb-text);
  font-size: 15px;
  font-weight: 900;
  letter-spacing: 0;
  margin-bottom: 8px;
  overflow-wrap: anywhere;
  text-transform: none;
}

.bb-card__xs-line {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  color: var(--bb-muted);
  font-size: 12px;
  line-height: 1.35;
}

.bb-card__xs-line strong {
  color: var(--bb-text);
  font-size: 13px;
  font-weight: 900;
  text-align: right;
}

.bb-card__coins {
  color: #fff;
  font-size: 42px;
  font-weight: 950;
  letter-spacing: -0.04em;
  line-height: 1;
}

.bb-card--hero .bb-card__coins {
  font-size: 52px;
}

.bb-card--compact .bb-card__coins {
  font-size: 28px;
}

.bb-card__coins span {
  display: block;
  color: var(--bb-muted);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.18em;
  margin-top: 8px;
  text-transform: uppercase;
}

.bb-card--compact .bb-card__coins span {
  display: inline;
  font-size: 10px;
  letter-spacing: 0;
  margin-left: 4px;
  text-transform: none;
}

.bb-card__bonus {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  color: var(--bb-gold-strong);
  background: rgba(245, 197, 66, 0.09);
  box-shadow: inset 0 0 0 1px rgba(245, 197, 66, 0.18);
  font-size: 12px;
  font-weight: 900;
  margin-top: 16px;
  padding: 6px 12px;
}

.bb-card__footer {
  position: relative;
  z-index: 1;
  display: flex;
  width: 100%;
  flex-direction: column;
  gap: 18px;
  margin-top: auto;
  padding-top: 32px;
}

.bb-card--compact .bb-card__footer {
  align-items: center;
  flex-direction: row;
  justify-content: space-between;
  gap: 12px;
  padding-top: 18px;
}

.bb-card--xs .bb-card__footer {
  grid-column: 2;
  gap: 10px;
  padding-top: 10px;
}

.bb-card__price {
  color: var(--bb-text);
  font-size: 28px;
  font-weight: 900;
}

.bb-card--compact .bb-card__price {
  font-size: 19px;
}

.bb-gold-btn {
  width: 100%;
  height: 52px !important;
  border-radius: 12px !important;
  background: linear-gradient(135deg, #ffe5aa 0%, #f5c542 100%) !important;
  color: #3e2e00 !important;
  font-size: 13px !important;
  font-weight: 950 !important;
  letter-spacing: 0.14em !important;
  text-transform: uppercase !important;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.35),
    0 18px 30px rgba(245, 197, 66, 0.18) !important;
  transition:
    transform 0.25s ease,
    box-shadow 0.25s ease;
}

.bb-gold-btn:hover {
  transform: scale(1.02);
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.35),
    0 20px 36px rgba(245, 197, 66, 0.26) !important;
}

.bb-gold-btn--compact {
  width: auto;
  height: 36px !important;
  min-width: 72px !important;
  border-radius: 9px !important;
  font-size: 10px !important;
  padding: 0 16px !important;
}

@media (max-width: 959px) {
  .bb-card--hero {
    transform: none;
  }

  .bb-card--hero:hover {
    transform: translateY(-4px);
  }
}

@media (max-width: 539px) {
  .bb-card {
    min-height: 380px;
    padding: 26px;
  }

  .bb-card--compact {
    min-height: 188px;
    padding: 20px;
  }
}
</style>
