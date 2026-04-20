<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import {
  ProfileActivityFilters,
  ProfileActivityItemType,
  type BetTransactonType,
} from '~/config/Profile.config'
import CurrencySelector from '../shared/CurrencySelector.vue'
import ActivityBetCard from './ActivityBetCard.vue'
import ActivityTransactionCard from './ActivityTransactionCard.vue'

//types
enum TransactionType {
  WIN = 'win',
  BET = 'bet',
}

//composables
const { name } = useDisplay()
const { convertCurrency, convertDate, isset } = useUtils()
const { t } = useI18n()

//models
const activities = ProfileActivityFilters.map((el) => ({
  label: el.label,
  value: el.id,
}))
const filterVisible = ref<boolean>(false)
const activityType = ref(activities[0]?.value)
const filters = ref<any>({})
const results = ref<any[]>([])
const loading = ref<boolean>(false)
const page = ref<number>(1)
const length = ref<number>(15)
const total = ref<number>(0)

//computed
const currentFilters = computed(() => {
  return ProfileActivityFilters.find((el) => el.id === activityType.value)?.items
})
const isMobile = computed(() => ['xs', 'sm'].includes(name.value))

//methods
const fetchResults = async (): Promise<void> => {
  const currentFetchURL = ProfileActivityFilters.find((el) => el.id === activityType.value)
  if (currentFetchURL) {
    loading.value = true
    try {
      const { data, meta }: any = await useAPIFetch(currentFetchURL.fetchUrl, {
        page: page.value,
        length: length.value,
        currency: isset(filters.value.currency) ? filters.value.currency : undefined,
        from: isset(filters.value.time) ? filters.value.time.from : undefined,
        to: isset(filters.value.time) ? filters.value.time.to : undefined,
        game: isset(filters.value.game) ? filters.value.game : undefined,
        type: isset(filters.value.type) ? filters.value.type : undefined,
      })

      if (data) {
        switch (activityType.value) {
          case 'bets':
            results.value = data.data
            total.value = data.total
            break
          case 'transactions':
            results.value = data
            total.value = meta.total
            break
        }
      }
    } catch (err) {
      console.warn('err', err)
    }
    loading.value = false
  }
}

const handleBetType = (transactionType: string, payout: number) => {
  const t = String(transactionType).toLowerCase()
  switch (t) {
    case TransactionType.WIN:
      if (payout > 0) return 'win'
      return 'loss'
      break
    case TransactionType.BET:
      return 'bet'
    default:
      return t
  }
}

const handleBetTypeColor = (transactionType: string, payout: number) => {
  switch (handleBetType(transactionType, payout)) {
    case 'win':
      return 'green'
    case 'bet':
      return 'grey'
    case 'loss':
      return 'yellow'
    default:
      return 'grey'
  }
}

const handleAmount = (transition: BetTransactonType) => {
  let value: any = 0
  switch (transition.transaction_type) {
    case 'bet':
      value = transition.stake
      break
    case 'win':
      value = transition.payout
      break
    case 'refund':
      value = transition.refund
      break
  }
  return convertCurrency(Number(value), transition.ui_decimals ?? 8)
}

const handleTransactionAmount = (transaction: {
  amount: number | string
  ui_decimals?: number
}) => {
  return convertCurrency(Number(transaction.amount), transaction.ui_decimals ?? 8)
}

const handleFilterSearch = async () => {
  page.value = 1
  await fetchResults()
}

//watchers
watch(activityType, async () => {
  filterVisible.value = false
  results.value = []
  await fetchResults()
})

watch(page, async () => {
  await fetchResults()
})

//mounted
onMounted(() => {
  fetchResults()
})
</script>
<template>
  <div>
    <v-row>
      <v-col cols="10" md="4">
        <v-select
          v-model="activityType"
          :items="activities"
          hide-details
          density="compact"
          :item-title="'label'"
          :item-value="'value'"
          class="activity-select"
        />
      </v-col>
      <v-col cols="2" md="4">
        <v-btn
          :color="filterVisible ? 'purple' : 'primary'"
          @click.prevent="filterVisible = !filterVisible"
        >
          <v-icon icon="mdi-filter" />
        </v-btn>
      </v-col>
    </v-row>

    <div v-if="filterVisible" class="filter-wrapper mt-3 mb-3">
      <v-row>
        <v-col cols="12" md="8">
          <v-row>
            <v-col
              v-for="(filter, i) in currentFilters"
              :key="`Filter${activityType}_${i}`"
              class="pb-0 pt-0"
              :cols="filter.cols"
            >
              <div v-if="filter.label" class="text-subtitle-1 text-white">
                {{ filter.label }}
              </div>
              <SharedGameSearchAutocomplete
                v-if="filter.visible && filter.type === ProfileActivityItemType.AUTOCOMPLETE"
                v-model="filters[filter.id]"
              />
              <v-select
                v-if="filter.visible && filter.type === ProfileActivityItemType.SELECT"
                v-model="filters[filter.id]"
                density="compact"
                :items="filter.values"
                :item-title="'label'"
                :item-value="'value'"
                class="activity-select"
              />
              <CurrencySelector
                v-if="filter.visible && filter.type === ProfileActivityItemType.CURRENCY"
                v-model="filters[filter.id]"
              />
              <form-datetime
                v-if="filter.visible && filter.type === ProfileActivityItemType.TIME"
                v-model="filters[filter.id]"
              />
            </v-col>
          </v-row>
          <v-row>
            <v-col cols="12">
              <v-btn
                color="primary"
                class="w-100"
                max-width="200"
                :disabled="loading"
                @click.prevent="handleFilterSearch"
                >{{ t('settings.apply') }}</v-btn
              >
            </v-col>
          </v-row>
        </v-col>
      </v-row>
    </div>

    <div v-if="results.length === 0 && !loading" class="no-results-wrapper mt-5 mb-5">
      {{ t('settings.noResults') }}
    </div>

    <div v-else class="transitions_wrapper">
      <v-progress-circular
        v-if="loading"
        color="yellow"
        indeterminate
        class="transactions_loader"
      />
      <div v-if="isMobile && activityType === 'bets'" class="activity-mobile-list mt-6">
        <ActivityBetCard
          v-for="(result, i) in results"
          :key="`${activityType}_mobile_${i}`"
          :item="result"
          :type-label="handleBetType(result.transaction_type, Number(result.payout))"
          :type-color="handleBetTypeColor(result.transaction_type, Number(result.payout))"
          :amount="handleAmount(result)"
          :formatted-date="convertDate(result.when_placed)"
        />
      </div>

      <v-table
        v-else-if="activityType === 'bets'"
        color="dark"
        class="transactions-table mt-8"
      >
        <thead>
          <tr>
            <th>{{ t('settings.game') }}</th>
            <th>{{ t('settings.type') }}</th>
            <th>{{ t('settings.currency') }}</th>
            <th>{{ t('settings.amount') }}</th>
            <th>{{ t('settings.timestamp') }}</th>
            <th>{{ t('settings.status') }}</th>
            <th>{{ t('settings.transID') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(result, i) in results" :key="`${activityType}_${i}`">
            <td>{{ isset(result.game) ? result.game.name : '-' }}</td>
            <td>
              <v-chip
                :color="handleBetTypeColor(result.transaction_type, result.payout)"
                variant="tonal"
                size="small"
              >
                {{ handleBetType(result.transaction_type, result.payout) }}
              </v-chip>
            </td>
            <td>{{ result.currency }}</td>
            <td>{{ handleAmount(result) }}</td>
            <td>{{ convertDate(result.when_placed) }}</td>
            <td>{{ result.round_finished ? 'Completed' : 'Completed' }}</td>
            <td>
              <span class="transaction-text-overlay">{{ result.transaction_id }}</span>
            </td>
          </tr>
        </tbody>
      </v-table>

      <div
        v-if="isMobile && activityType === 'transactions'"
        class="activity-mobile-list activity-mobile-list--scroll mt-6"
      >
        <ActivityTransactionCard
          v-for="(result, i) in results"
          :key="`Transaction_mobile_${activityType}_${i}`"
          :item="result"
          :amount="handleTransactionAmount(result)"
          :formatted-date="convertDate(result.created_at)"
        />
      </div>

      <v-table v-else-if="activityType === 'transactions'" class="transactions-table mt-8">
        <thead>
          <tr>
            <th>{{ t('settings.type') }}</th>
            <th>{{ t('settings.currency') }}</th>
            <th>{{ t('settings.amount') }}</th>
            <th>{{ t('settings.timestamp') }}</th>
            <th>{{ t('settings.status') }}</th>
            <th>{{ t('settings.transID') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(result, i) in results" :key="`Transaction_${activityType}_${i}`">
            <td>{{ result.type }}</td>
            <td>{{ result.currency }}</td>
            <td>{{ handleTransactionAmount(result) }}</td>
            <td>{{ convertDate(result.created_at) }}</td>
            <td>{{ result.status }}</td>
            <td>
              <span class="transaction-text-overlay">{{ result.txid }}</span>
            </td>
          </tr>
        </tbody>
      </v-table>
      <v-pagination
        v-model="page"
        :total-visible="7"
        :length="Math.floor(total / length)"
        class="activity-pagination"
      />
    </div>
  </div>
</template>
<style>
.v-table {
  background-color: inherit !important;
  color: white !important;
}

.transactions-table th,
.transactions-table td {
  text-align: left !important;
}
.activity-select .v-field {
  background: #21242e;
  border-radius: 6px;
  overflow: hidden;
}

.activity-select .v-field__input,
.activity-select .v-field__append-inner {
  background: transparent !important;
}

.activity-select .v-field__append-inner {
  margin-left: 0 !important;
  padding-right: 10px;
}

.activity-mobile-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.activity-mobile-list--scroll {
  max-height: 60vh;
  overflow-y: auto;
  padding-right: 4px;
}
</style>
