<script setup lang="ts">
import { useAuthStore } from '~/core/store/auth'
import { OverlaysTypes } from '~/core/types/Overlays'
import BuyBundleCard from './bundle-item.vue'
import type { ContainerType } from '~/core/types/Container'
import { type BuyBundle, buyStore } from '~/core/store/buy'

const { options } = defineProps<{ options: ContainerType }>()
const { display, styles } = useContainerOptions(options)
const { setBundles } = buyStore()

const router = useRouter()
const { isLogged } = storeToRefs(useAuthStore())
const { startBuying, setCurrentBundle } = buyStore()

const allBundles = computed<BuyBundle[]>(() => options.data?.bundles ?? [])

const featuredBundles = computed<BuyBundle[]>(() => {
  const bundles =
    options.data?.featuredBundles ?? allBundles.value.filter((bundle) => bundle.featured)

  return bundles
})

const standardBundles = computed<BuyBundle[]>(() => {
  const bundles =
    options.data?.standardBundles ??
    allBundles.value.filter((bundle) => bundle.tier !== 'featured' && !bundle.featured)

  return bundles
})

const hasBundles = computed(
  () => featuredBundles.value.length > 0 || standardBundles.value.length > 0,
)

const openPurchaseFlow = (bundle: BuyBundle) => {
  setCurrentBundle(bundle)
  startBuying()
  router.replace({
    query: {
      overlay: isLogged.value ? OverlaysTypes.BUY : OverlaysTypes.LOGIN,
    },
  })
}

onMounted(() => {
  setBundles(options.data?.bundles ?? [])
})
</script>

<template>
  <div v-if="display" :id="options.id" :style="styles" class="bb-page">
    <section class="bb-hero">
      <div class="bb-hero__copy">
        <h1>Buy Bundles</h1>
      </div>
    </section>

    <section v-if="featuredBundles.length" class="bb-featured">
      <BuyBundleCard
        v-for="bundle in featuredBundles"
        :key="bundle.id"
        :bundle="bundle"
        :buy-disabled="true"
        @buy="openPurchaseFlow"
      />
    </section>

    <section class="bb-standard">
      <h2 v-if="standardBundles.length" class="bb-section-title">
        <span />
        Bundles
      </h2>

      <v-row v-if="standardBundles.length" class="bb-standard__grid">
        <v-col v-for="bundle in standardBundles" :key="bundle.id" cols="12" sm="6" md="4" lg="4">
          <BuyBundleCard
            :compact="false"
            :bundle="bundle"
            :buy-disabled="true"
            @buy="openPurchaseFlow"
          />
        </v-col>
      </v-row>

      <v-card v-if="!hasBundles" class="bb-empty" variant="flat">
        No bundles available yet.
      </v-card>
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

.bb-hero h1 {
  color: var(--bb-text);
  font-size: clamp(44px, 7vw, 72px);
  font-weight: 950;
  letter-spacing: -0.06em;
  line-height: 0.95;
  margin: 0 0 18px;
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

@media (max-width: 959px) {
  .bb-hero {
    align-items: flex-start;
    flex-direction: column;
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
}
</style>
