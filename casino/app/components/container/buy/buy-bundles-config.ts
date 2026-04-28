export type BuyBundleFilter = 'all' | 'featured' | 'popular'

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
}

export const buyBundleFilters: { label: string; value: BuyBundleFilter }[] = [
  { label: 'All', value: 'all' },
  { label: 'Featured', value: 'featured' },
  { label: 'Popular', value: 'popular' },
]

export const featuredBuyBundles: BuyBundle[] = [
  {
    id: 'starter-pack',
    name: 'Starter Pack',
    coins: 8000,
    priceLabel: '$9.99',
    icon: 'mdi-database',
    tier: 'featured',
    featured: true,
  },
  {
    id: 'high-roller-pack',
    name: 'High Roller Pack',
    coins: 100000,
    priceLabel: '$99.99',
    icon: 'mdi-medal',
    tier: 'featured',
    badge: 'Best Value',
    bonusLabel: '+25% Bonus Included',
    featured: true,
    popular: true,
  },
  {
    id: 'pro-pack',
    name: 'Pro Pack',
    coins: 45000,
    priceLabel: '$49.99',
    icon: 'mdi-star',
    tier: 'featured',
    badge: 'Popular',
    bonusLabel: '+15% Bonus',
    popular: true,
  },
]

export const standardBuyBundles: BuyBundle[] = [
  {
    id: 'micro-pack',
    name: 'Micro Pack',
    coins: 1500,
    priceLabel: '$1.99',
    icon: 'mdi-coins',
    tier: 'standard',
  },
  {
    id: 'small-pack',
    name: 'Small Pack',
    coins: 4000,
    priceLabel: '$4.99',
    icon: 'mdi-coins',
    tier: 'standard',
  },
  {
    id: 'classic-pack',
    name: 'Classic Pack',
    coins: 15000,
    priceLabel: '$19.99',
    icon: 'mdi-cards-diamond',
    tier: 'standard',
  },
  {
    id: 'gamer-pack',
    name: 'Gamer Pack',
    coins: 25000,
    priceLabel: '$29.99',
    icon: 'mdi-controller-classic',
    tier: 'standard',
    popular: true,
  },
  {
    id: 'vault-pack',
    name: 'Vault Pack',
    coins: 60000,
    priceLabel: '$59.99',
    icon: 'mdi-safe-square',
    tier: 'standard',
    featured: true,
  },
  {
    id: 'executive-pack',
    name: 'Executive Pack',
    coins: 80000,
    priceLabel: '$79.99',
    icon: 'mdi-briefcase',
    tier: 'standard',
  },
  {
    id: 'whale-pack',
    name: 'Whale Pack',
    coins: 150000,
    priceLabel: '$149.99',
    icon: 'mdi-chart-waterfall',
    tier: 'standard',
    popular: true,
  },
  {
    id: 'sovereign-pack',
    name: 'Sovereign Pack',
    coins: 500000,
    priceLabel: '$499.99',
    icon: 'mdi-crown',
    tier: 'standard',
    featured: true,
  },
]
