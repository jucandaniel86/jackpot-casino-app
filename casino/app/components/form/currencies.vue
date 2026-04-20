<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
type FormCurrenciesT = {
  label: string
}
type CurrencyItem = {
  symbol: string
  code: string
}
//props
const props = defineProps<FormCurrenciesT>()

//models
const loading = ref(false)
const items = ref<CurrencyItem[]>([])
const model = defineModel<string>({ default: '' })

const getCurrencies = async (): Promise<void> => {
  loading.value = true
  const date: any = await $fetch('/json/responses/currencies.json')
  items.value = date
  loading.value = false
}

onMounted(() => {
  getCurrencies()
})
</script>
<template>
  <div>
    <div class="text-subtitle-1 text-white">{{ props.label }}</div>
    <v-select
      v-model="model"
      :loading="loading"
      :items="items"
      density="compact"
      :item-title="'code'"
      :item-value="'code'"
      hide-details
    />
  </div>
</template>
