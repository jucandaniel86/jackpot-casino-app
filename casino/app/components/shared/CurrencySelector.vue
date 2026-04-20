<script setup lang="ts">
import { useWalletStore } from '~/core/store/wallet'

const { wallets } = storeToRefs(useWalletStore())

const model = defineModel({ default: '' })

const items = computed(() => {
  const currencies: any[] = []
  wallets.value.forEach((element) => {
    if (currencies.indexOf(element.code) === -1) {
      currencies.push(element.code)
    }
  })
  return currencies
})
</script>
<template>
  <v-select v-model="model" :items="items" hide-details density="compact" class="activity-select" />
</template>
