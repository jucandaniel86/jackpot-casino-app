<script setup lang="ts">
const currentTab = ref()
const { replace } = useRouter()
const tabs = ref([
  {
    label: 'Deposit',
    id: 'deposit',
  },
  {
    label: 'Withdraw',
    id: 'withdraw',
  },
])
const closeModal = () => replace({ query: {} })
</script>
<template>
  <v-card class="wallet-card mx-auto w-100" max-width="550" max-height="100%" min-height="620">
    <v-card-title>
      <v-tabs v-model="currentTab" align-tabs="start" class="mb-5">
        <v-tab v-for="(tab, i) in tabs" :key="`Tab${i}`" :value="tab.id">
          {{ tab.label }}
        </v-tab>
      </v-tabs>
      <v-btn size="x-small" class="overlay-close" @click.prevent="closeModal"><IconClose /></v-btn>
    </v-card-title>
    <v-card-text class="pt-0">
      <v-tabs-window v-model="currentTab">
        <v-tabs-window-item value="deposit">
          <wallet-deposit />
        </v-tabs-window-item>
        <v-tabs-window-item value="withdraw">
          <wallet-withdraw />
        </v-tabs-window-item>
      </v-tabs-window>
    </v-card-text>
  </v-card>
</template>
