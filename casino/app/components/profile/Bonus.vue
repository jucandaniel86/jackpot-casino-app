<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { ProfileBonusFilters } from '~/config/Profile.config'

//composables
const { convertCurrency, convertDate } = useUtils()

//models
const activities = ProfileBonusFilters.map((el) => ({ label: el.label, value: el.id }))
const filterVisible = ref<boolean>(false)
const activityType = ref(activities[0]?.value)
const filters = ref<any>({})
const results = ref<any[]>([])
const loading = ref<boolean>(false)

//computed
const currentFilters = computed(() => {
  return ProfileBonusFilters.find((el) => el.id === activityType.value)?.items
})

//methods
const fetchResults = async (): Promise<void> => {
  const currentFetchURL = ProfileBonusFilters.find((el) => el.id === activityType.value)
  if (currentFetchURL) {
    loading.value = true
    const data: any = await $fetch(currentFetchURL.fetchUrl)
    results.value = data
    loading.value = false
  }
}

//watchers
watch(activityType, () => {
  filterVisible.value = false
  fetchResults()
})

//mounted
onMounted(() => {
  fetchResults()
})
</script>
<template>
  <div>
    <v-row>
      <v-col cols="4">
        <v-select
          v-model="activityType"
          :items="activities"
          hide-details
          density="compact"
          :item-title="'label'"
          :item-value="'value'"
        />
      </v-col>
      <v-col cols="4">
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
        <v-col cols="8">
          <v-row>
            <v-col
              v-for="(filter, i) in currentFilters"
              :key="`Filter${activityType}_${i}`"
              class="pb-0 pt-0"
              :cols="filter.cols"
            >
              <div v-if="filter.label" class="text-subtitle-1 text-white">{{ filter.label }}</div>
              <v-select
                v-if="filter.visible && filter.type === 'SELECT'"
                v-model="filters[filter.id]"
                density="compact"
                :items="filter.values"
                :item-title="'label'"
                :item-value="'value'"
              />
              <form-datetime
                v-if="filter.visible && filter.type === 'TIME'"
                v-model="filters[filter.id]"
              />
            </v-col>
          </v-row>
          <v-row>
            <v-col cols="12">
              <v-btn color="primary" class="w-100" max-width="200">Apply</v-btn>
            </v-col>
          </v-row>
        </v-col>
      </v-row>
    </div>

    <v-progress-circular v-if="loading" color="purple" />

    <div v-if="results.length === 0" class="no-results-wrapper mt-5 mb-5">No Results</div>

    <div v-else>
      <table class="transactions-table mt-8">
        <thead>
          <tr>
            <th>Date received</th>
            <th>Expiry date</th>
            <th>Amount</th>
            <th>Bonus name</th>
            <th>Type</th>
            <th>Status</th>
            <th>Currency</th>
            <th>Remaining wagering</th>
            <th>CBID</th>
            <th>Deducted by cap</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(result, i) in results" :key="`${activityType}_${i}`">
            <td>{{ convertDate(result.bonusCreateTime) }}</td>
            <td>{{ convertDate(result.expiryDate) }}</td>
            <td>
              {{ result.freeSpinCount }}x{{ convertCurrency(result.freeSpinValue, 2) }}
              {{ result.freeSpinCurrency }}
            </td>
            <td>{{ result.bonusName }}</td>
            <td>{{ result.type }}</td>
            <td>{{ result.status }}</td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
