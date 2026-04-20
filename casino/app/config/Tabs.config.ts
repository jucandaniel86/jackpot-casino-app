import type { TabType } from '~/core/types/Game'

export const TEST_TABS: TabType[] = [
  {
    label: 'Slots',
    icon: 'brand-ico-slots2',
    file: 'json/tags/slots.json',
    id: 'slots',
  },
  {
    label: 'New',
    icon: '',
    file: 'json/tags/new.json',
    id: 'new',
  },
  {
    label: 'Live Casino',
    icon: 'brand-ico-live-casino2',
    file: 'json/tags/live-casino.json',
    id: 'live-casino',
  },
  {
    label: 'Table Games',
    icon: '#brand-ico-casino2',
    file: 'json/tags/table-games.json',
    id: 'table-games',
  },
  {
    label: 'Casual Games',
    icon: 'brand-ico-crashgames2',
    file: 'json/tags/casual-games.json',
    id: 'casual-games',
  },
  {
    label: 'Providers',
    icon: 'brand-ico-blackjack',
    file: 'json/tags/providers.json',
    id: 'providers',
  },
  {
    label: 'Promotions',
    icon: 'brand-ico-promotions',
    file: 'json/tags/promo-games.json',
    id: 'promotions',
  },
]
