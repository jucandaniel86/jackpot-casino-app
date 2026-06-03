<script setup lang="ts">
import { type BuyBundle, buyStore } from '~/core/store/buy'
import BundleItem from '../../container/buy/bundle-item.vue'

const store = buyStore()
const { currentBundle, bundles } = storeToRefs(store)
const { resetBuyState, setBundles, setCurrentBundle, startBuying } = store
const { replace } = useRouter()

const loadingBundles = ref(false)
const bundlesError = ref('')
const testName = ref('')
const testEmail = ref('')

const availableBundles = computed(() => bundles.value ?? [])

function close() {
  resetBuyState()
  replace({ query: {} })
}

async function loadBundles() {
  if (currentBundle.value) return
  if (availableBundles.value.length > 0) return

  loadingBundles.value = true
  bundlesError.value = ''

  try {
    const response = await useAPIFetch('/bundles/active')
    setBundles(response?.data ?? [])
  } catch {
    bundlesError.value = 'Could not load bundles. Please try again.'
  } finally {
    loadingBundles.value = false
  }
}

function selectBundle(bundle: BuyBundle) {
  setCurrentBundle(bundle)
  startBuying()
}

function buy() {
  if (!currentBundle.value) return

  console.log('Buy bundle test payload', {
    bundle_id: currentBundle.value.id,
    name: testName.value,
    email: testEmail.value,
  })
}

onMounted(loadBundles)
</script>

<template>
  <v-card class="wallet-card mx-auto w-100" max-width="550" max-height="100%" min-height="620">
    <v-card-title>
      <h2>Buy Coins</h2>
      <v-btn size="x-small" class="overlay-close" @click.prevent="close"><IconClose /></v-btn>
    </v-card-title>
    <v-card-text class="pt-0">
      {{ currentBundle ? 'You are purchasing:' : 'Select a bundle to purchase:' }}
      <div v-if="currentBundle" class="my-4">
        <BundleItem :bundle="currentBundle" size="xs" :show-buy-button="false" />
      </div>

      <div v-else class="my-4">
        <v-progress-linear v-if="loadingBundles" indeterminate color="primary" class="mb-3" />

        <div v-if="loadingBundles" class="buy-bundles-picker">
          <v-skeleton-loader
            v-for="index in 3"
            :key="index"
            type="list-item-avatar-three-line"
            class="buy-bundles-picker__skeleton"
          />
        </div>

        <v-alert v-if="bundlesError" type="error" variant="tonal" density="compact" class="mb-3">
          {{ bundlesError }}
        </v-alert>

        <div v-if="!loadingBundles && availableBundles.length" class="buy-bundles-picker">
          <button
            v-for="bundle in availableBundles"
            :key="bundle.id"
            type="button"
            class="buy-bundles-picker__item"
            @click="selectBundle(bundle)"
          >
            <BundleItem :bundle="bundle" size="xs" :show-buy-button="false" />
          </button>
        </div>

        <v-card v-else-if="!loadingBundles && !bundlesError" variant="tonal" class="pa-4">
          No bundles available yet.
        </v-card>
      </div>

      <div class="buy-test-fields">
        <v-text-field
          v-model="testName"
          label="Name"
          variant="outlined"
          density="comfortable"
          hide-details
        />
        <v-text-field
          v-model="testEmail"
          label="Email"
          type="email"
          variant="outlined"
          density="comfortable"
          hide-details
        />
        <v-btn color="primary" variant="flat" :disabled="!currentBundle" @click="buy"> Buy </v-btn>
      </div>
    </v-card-text>
  </v-card>
</template>

<style scoped>
.buy-bundles-picker {
  display: grid;
  gap: 10px;
  max-height: 320px;
  overflow-y: auto;
  padding-right: 4px;
}

.buy-bundles-picker__item {
  display: block;
  width: 100%;
  border: 0;
  padding: 0;
  text-align: inherit;
}

.buy-bundles-picker__skeleton {
  border-radius: 8px;
}

.buy-test-fields {
  display: grid;
  gap: 12px;
  margin-top: 18px;
}
</style>
