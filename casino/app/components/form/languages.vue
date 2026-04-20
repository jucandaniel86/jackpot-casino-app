<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
type FormLanguageT = {
  label: string
}
type LanguageItem = {
  name: string
  code: string
}
//props
const props = defineProps<FormLanguageT>()

//models
const loading = ref(false)
const items = ref<LanguageItem[]>([])
const model = defineModel<string>({ default: '' })

const getLanguages = async (): Promise<void> => {
  loading.value = true
  const data: any = await $fetch('/json/responses/languages.json')
  items.value = data
  loading.value = false
}

onMounted(() => {
  getLanguages()
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
      :item-title="'name'"
      :item-value="'code'"
      hide-details
    />
  </div>
</template>
