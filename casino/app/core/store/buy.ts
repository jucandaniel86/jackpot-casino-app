import { defineStore } from 'pinia'
import { ref } from 'vue'

export type BuyBundle = {
  id: string
  name: string
  coins: number
  priceLabel: string
  icon: string
  tier: 'featured' | 'standard'
  badge?: string
  bonusLabel?: string
  featured?: boolean
  popular?: boolean
  thumbnail?: string | null
  image_url?: string | null
  slug?: string | null
  short_description?: string | null
  description?: string | null
  price_amount?: number | string | null
  price_currency?: string | null
  gc_amount?: number | string | null
  coin_amount?: number | string | null
  cta_text?: string | null
  metadata?: Record<string, unknown> | null
}

export const buyStore = defineStore('buy', () => {
  const buyingStarted = ref(false)
  const currentBundle = ref<BuyBundle | null>(null)
  const bundles = ref<BuyBundle[] | null>(null)

  const startBuying = () => {
    buyingStarted.value = true
  }

  const setCurrentBundle = (bundle: BuyBundle) => {
    currentBundle.value = bundle
  }

  const resetBuyState = () => {
    buyingStarted.value = false
    currentBundle.value = null
  }

  const setBundles = (bundlesData: BuyBundle[]) => {
    bundles.value = bundlesData
  }

  return {
    buyingStarted,
    startBuying,
    currentBundle,
    setCurrentBundle,
    resetBuyState,
    bundles,
    setBundles,
  }
})
