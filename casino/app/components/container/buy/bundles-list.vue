<script setup lang="ts">
import { useAuthStore } from '~/core/store/auth'
import { OverlaysTypes } from '~/core/types/Overlays'
import BuyBundleCard from './bundle-item.vue'
import {
  buyBundleFilters,
  featuredBuyBundles,
  standardBuyBundles,
  type BuyBundle,
  type BuyBundleFilter,
} from './buy-bundles-config'
import type { ContainerType } from '~/core/types/Container'

const { options } = defineProps<{ options: ContainerType }>()
const { display, styles } = useContainerOptions(options)

const router = useRouter()
const { isLogged } = storeToRefs(useAuthStore())

const selectedFilter = ref<BuyBundleFilter>('all')

const matchesFilter = (bundle: BuyBundle) => {
  if (selectedFilter.value === 'featured') return Boolean(bundle.featured)
  if (selectedFilter.value === 'popular') return Boolean(bundle.popular)
  return true
}

const visibleFeaturedBundles = computed(() => featuredBuyBundles.filter(matchesFilter))
const visibleStandardBundles = computed(() => standardBuyBundles.filter(matchesFilter))

const openPurchaseFlow = () => {
  router.replace({
    query: {
      overlay: isLogged.value ? OverlaysTypes.WALLET : OverlaysTypes.REGISTER,
    },
  })
}
</script>

<template>
  <div v-if="display" :id="options.id" :style="styles" class="bb-page">
    <section class="bb-hero">
      <div class="bb-hero__copy">
        <div class="bb-eyebrow">Sovereign Vault</div>
        <h1>Buy Bundles</h1>
        <p>
          Elevate your play with exclusive coin packages. Precision crafted for the discerning high
          roller.
        </p>
      </div>

      <v-btn-toggle v-model="selectedFilter" class="bb-filters" mandatory>
        <v-btn
          v-for="filter in buyBundleFilters"
          :key="filter.value"
          class="bb-filter"
          :class="{ 'bb-filter--active': selectedFilter === filter.value }"
          :value="filter.value"
          variant="flat"
        >
          {{ filter.label }}
        </v-btn>
      </v-btn-toggle>
    </section>

    <section v-if="visibleFeaturedBundles.length" class="bb-featured">
      <BuyBundleCard
        v-for="bundle in visibleFeaturedBundles"
        :key="bundle.id"
        :bundle="bundle"
        @buy="openPurchaseFlow"
      />
    </section>

    <section class="bb-standard">
      <h2 class="bb-section-title">
        <span />
        Standard Packages
      </h2>

      <v-row v-if="visibleStandardBundles.length" class="bb-standard__grid">
        <v-col
          v-for="bundle in visibleStandardBundles"
          :key="bundle.id"
          cols="12"
          sm="6"
          md="4"
          lg="3"
        >
          <BuyBundleCard compact :bundle="bundle" @buy="openPurchaseFlow" />
        </v-col>
      </v-row>

      <v-card v-else class="bb-empty" variant="flat"> No bundles match this filter yet. </v-card>
    </section>

    <section class="bb-vip">
      <div class="bb-vip__content">
        <div class="bb-eyebrow">Daily drops and private access</div>
        <h2>Exclusive VIP Rewards</h2>
        <p>Join the Sovereign Circle for daily coin drops and early access to high-limit tables.</p>
        <v-btn class="bb-secondary-btn" variant="flat" @click="openPurchaseFlow">
          Learn More
        </v-btn>
      </div>
    </section>
  </div>
</template>

<style scoped>
.bb-page {
  --bb-gold: var(--base-color, #f2cf8e);
  --bb-gold-strong: #f5c542;
  --bb-surface: #131313;
  --bb-surface-low: #1c1b1b;
  --bb-surface-high: #2a2a2a;
  --bb-surface-highest: #353534;
  --bb-text: #e5e2e1;
  --bb-muted: #d1c5ae;

  display: flex;
  flex-direction: column;
  gap: 48px;
  color: var(--bb-text);
  padding-bottom: 48px;
}

.bb-hero {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 24px;
  padding-top: 24px;
}

.bb-hero__copy {
  max-width: 620px;
}

.bb-eyebrow {
  color: var(--bb-gold-strong);
  font-size: 11px;
  font-weight: 900;
  letter-spacing: 0.2em;
  margin-bottom: 10px;
  text-transform: uppercase;
}

.bb-hero h1 {
  color: var(--bb-text);
  font-size: clamp(44px, 7vw, 72px);
  font-weight: 950;
  letter-spacing: -0.06em;
  line-height: 0.95;
  margin: 0 0 18px;
}

.bb-hero p,
.bb-vip p {
  color: var(--bb-muted);
  font-size: 17px;
  font-weight: 300;
  line-height: 1.65;
  margin: 0;
}

.bb-filters {
  flex: 0 0 auto;
  gap: 4px;
  padding: 4px;
  border-radius: 12px;
  background: var(--bb-surface-low);
  box-shadow: none;
}

.bb-filter {
  background: transparent !important;
  border-radius: 8px !important;
  box-shadow: none !important;
  color: var(--bb-muted) !important;
  font-weight: 800 !important;
  padding: 0 24px !important;
}

.bb-filter--active {
  background: var(--bb-surface-highest) !important;
  color: var(--bb-gold) !important;
}

.bb-featured {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 28px;
  align-items: stretch;
}

.bb-standard {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.bb-section-title {
  display: flex;
  align-items: center;
  gap: 14px;
  color: var(--bb-text);
  font-size: 26px;
  font-weight: 900;
  margin: 0;
  letter-spacing: -0.02em;
}

.bb-section-title span {
  width: 34px;
  height: 4px;
  border-radius: 999px;
  background: var(--bb-gold-strong);
}

.bb-standard__grid {
  margin: -8px;
}

.bb-empty {
  background: var(--bb-surface-low);
  color: var(--bb-muted);
  padding: 24px;
  border-radius: 12px;
}

.bb-vip {
  position: relative;
  display: flex;
  min-height: 264px;
  align-items: center;
  overflow: hidden;
  border-radius: 18px;
  padding: 42px;
  isolation: isolate;
  background:
    linear-gradient(90deg, rgba(0, 0, 0, 0.88), rgba(0, 0, 0, 0.28)),
    radial-gradient(circle at 88% 26%, rgba(245, 197, 66, 0.28), transparent 32%),
    radial-gradient(circle at 70% 88%, rgba(242, 207, 142, 0.14), transparent 28%),
    linear-gradient(135deg, #191817, var(--bb-surface-high));
  box-shadow: inset 0 0 0 1px rgba(245, 197, 66, 0.14);
}

.bb-vip::after {
  content: '';
  position: absolute;
  inset: auto -8% -35% auto;
  width: 420px;
  height: 420px;
  border-radius: 999px;
  background: rgba(245, 197, 66, 0.08);
  filter: blur(8px);
  z-index: -1;
}

.bb-vip__content {
  max-width: 540px;
}

.bb-vip h2 {
  color: var(--bb-gold);
  font-size: clamp(30px, 4vw, 42px);
  font-weight: 950;
  letter-spacing: -0.04em;
  line-height: 1;
  margin: 0 0 12px;
}

.bb-secondary-btn {
  background: var(--bb-gold-strong) !important;
  border-radius: 10px !important;
  color: #251a00 !important;
  font-weight: 950 !important;
  letter-spacing: 0.12em !important;
  margin-top: 26px;
  padding: 0 30px !important;
  text-transform: uppercase !important;
}

@media (max-width: 959px) {
  .bb-hero {
    align-items: flex-start;
    flex-direction: column;
  }

  .bb-filters {
    width: 100%;
  }

  .bb-filter {
    flex: 1 1 0;
  }

  .bb-featured {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 539px) {
  .bb-page {
    gap: 36px;
  }

  .bb-hero h1 {
    font-size: 46px;
  }

  .bb-hero p,
  .bb-vip p {
    font-size: 15px;
  }

  .bb-vip {
    padding: 28px;
  }
}
</style>
