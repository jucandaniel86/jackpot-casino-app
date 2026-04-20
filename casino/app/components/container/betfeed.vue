<!-- eslint-disable vue/no-v-html -->
<script setup lang="ts">
import { useEventSource } from '@vueuse/core'
import moment from 'moment'
import type { ContainerType } from '~/core/types/Container'

type BetFeedTab = {
  id: string
  label: string
}

type BetFeedItem = {
  _id: number
  currency: string
  game: string
  payout: string
  stake: string
  username: string
  when_placed: string
  game_slug: string
}
const TABS: BetFeedTab[] = [
  { id: 'allBets', label: 'All Bets' },
  { id: 'highRollers', label: 'High Rollers' },
]

const { name } = useDisplay()
const { options } = defineProps<{ options: ContainerType }>()
const { display } = useContainerOptions(options)
const DEFAULT_TAB = 'allBets'
const items = ref<BetFeedItem[]>([])
let feedSequence = 0

const currentTab = ref<string>(DEFAULT_TAB)
const isDesktop = computed(() => ['lg', 'md', 'xl'].indexOf(name.value) !== -1)

//composables
const { t } = useI18n()
const router = useRouter()

//methods
const convertDate = (date: string) => moment(date).format('HH:mm')
const calcMultiplier = (stake: number, payout: number) => {
  if (stake === 0) return '0'
  return (payout / stake).toFixed(2)
}

const playForFun = (slug: string) => {
  router.push({
    name: 'game-slug',
    params: { slug },
    query: { demo: 1 },
  })
}

const listenServerEvents = () => {
  const config = useRuntimeConfig()
  const { data } = useEventSource(`${config.public.baseURL}wins/stream`, ['win'], {
    autoReconnect: {
      retries: 3,
      delay: 1000,
      onFailed() {
        console.log('Failed to connect EventSource after 3 retries')
      },
    },
  })

  watch(data, (raw) => {
    if (!raw) return
    try {
      const msg = JSON.parse(raw)
      items.value.unshift({ _id: feedSequence++, ...msg })
      if (items.value.length > 10) items.value.splice(10)
    } catch (e: any) {
      console.warn('EventStream Err:', e)
    }
  })
}

const displayedItems = computed(() => {
  switch (currentTab.value) {
    case 'allBets':
      return items.value
    case 'highRollers':
      return items.value.filter((item) => parseInt(item.stake) >= 500)
    default:
      return items.value
  }
})

onMounted(() => {
  listenServerEvents()
})
</script>
<template>
  <div v-if="display" :id="options.id">
    <v-tabs v-model="currentTab" align-tabs="start" class="mb-5">
      <v-tab v-for="(tab, i) in TABS" :key="`Tab${i}`" :value="tab.id">
        {{ tab.label }}
      </v-tab>
    </v-tabs>

    <table class="betfeed__table">
      <thead>
        <tr>
          <td>{{ t('betfeed.game') }}</td>
          <td v-if="isDesktop">{{ t('betfeed.user') }}</td>
          <td v-if="isDesktop">{{ t('betfeed.time') }}</td>
          <td v-if="isDesktop">{{ t('betfeed.betAmount') }}</td>
          <td v-if="isDesktop">{{ t('betfeed.multiplier') }}</td>
          <td>{{ t('betfeed.payout') }}</td>
        </tr>
      </thead>
      <transition-group name="betfeed" tag="tbody">
        <tr v-for="feed in displayedItems" :key="`${options.id}_feed${feed._id}`">
          <td>
            <a href="#" class="betfeed-link" @click.prevent="playForFun(feed.game_slug)">
              {{ feed.game }}
            </a>
          </td>
          <td v-if="isDesktop">{{ feed.username }}</td>
          <td v-if="isDesktop">{{ convertDate(feed.when_placed) }}</td>
          <td v-if="isDesktop">{{ feed.stake }} {{ feed.currency }}</td>
          <td v-if="isDesktop">
            {{ calcMultiplier(parseFloat(feed.stake), parseFloat(feed.payout)) }}x
          </td>
          <td>{{ feed.payout }} {{ feed.currency }}</td>
        </tr>
      </transition-group>
    </table>
  </div>
</template>

<style scoped>
.betfeed-enter-active {
  transition:
    transform 260ms ease-out,
    opacity 260ms ease-out;
}
.betfeed-enter-from {
  opacity: 0;
  transform: translateY(-12px);
}
.betfeed-enter-to {
  opacity: 1;
  transform: translateY(0);
}
.betfeed-leave-active {
  transition: opacity 180ms ease;
}
.betfeed-leave-to {
  opacity: 0;
}
.betfeed-move {
  transition: transform 220ms ease;
}
.betfeed-link {
  color: #fff;
  text-transform: none;
}
</style>
