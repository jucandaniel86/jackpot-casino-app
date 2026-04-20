<script setup lang="ts">
import { useAPIFetch } from '~/composables/useApiFetch'

type AutocompleteItem = Record<string, any>

const props = withDefaults(
  defineProps<{
    modelValue?: any
    label?: string
    placeholder?: string
    itemTitle?: string
    itemValue?: string
    minChars?: number
    returnObject?: boolean
    noDataText?: string
  }>(),
  {
    label: 'Search games',
    placeholder: 'Type to search',
    itemTitle: 'name',
    itemValue: 'id',
    minChars: 2,
    returnObject: false,
    noDataText: 'No results',
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: any): void
}>()

const search = ref<string>('')
const items = ref<AutocompleteItem[]>([])
const loading = ref<boolean>(false)

const selected = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

const fetchItems = async () => {
  const query = search.value.trim()

  if (query.length < props.minChars) {
    items.value = []
    return
  }

  loading.value = true
  const response = await useAPIFetch('/search/game', { search: query })
  const results = Array.isArray(response) ? response : (response?.games ?? response?.data ?? [])

  if (query === search.value.trim()) {
    items.value = results
  }

  loading.value = false
}

const escapeHtml = (value: string) => {
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;')
}

const highlightMatch = (value: unknown) => {
  const label = String(value ?? '')
  const query = search.value.trim()

  if (!query) {
    return escapeHtml(label)
  }

  const escapedLabel = escapeHtml(label)
  const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  const pattern = new RegExp(`(${escapedQuery})`, 'ig')

  return escapedLabel.replace(pattern, '<mark class="autocomplete-match">$1</mark>')
}

watch(
  () => search.value,
  () => {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(fetchItems, 300)
  },
)
</script>

<template>
  <v-autocomplete
    v-model="selected"
    v-model:search="search"
    :items="items"
    :loading="loading"
    :placeholder="placeholder"
    :item-title="itemTitle"
    :item-value="itemValue"
    :return-object="returnObject"
    :no-data-text="noDataText"
    clearable
    density="compact"
    class="activity-select"
  >
    <template #item="{ props: itemProps, item }">
      <v-list-item v-bind="itemProps">
        <template #title>
          <span v-html="highlightMatch(item.raw?.[itemTitle] ?? item.title)" />
        </template>
      </v-list-item>
    </template>
  </v-autocomplete>
</template>

<style>
:deep(.autocomplete-match) {
  background: #ffd54f;
  color: #16181f;
  font-weight: 800;
  border-radius: 4px;
  padding: 0 3px;
}

.activity-select .v-field__clearable .v-icon {
  color: #fff !important;
  fill: #fff !important;
  opacity: 1;
}
</style>
