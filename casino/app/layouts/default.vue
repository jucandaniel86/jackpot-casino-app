<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '~/core/store/app'

const { name } = useDisplay()

const isMobile = computed(() => ['xs', 'sm'].indexOf(name.value) !== -1)
const { snackbar, snackbarMessage } = storeToRefs(useAppStore())
const { toggleSnackbar } = useAppStore()
</script>
<template>
  <v-layout>
    <icons />
    <currencies />
    <v-snackbar
      v-model="snackbar"
      :timeout="3500"
      border="start"
      density="compact"
      color="purple"
      location-strategy="connected"
      location="top right"
      position="absolute"
      style="top: 1rem"
    >
      {{ snackbarMessage }}
      <template #actions>
        <v-btn
          color="white"
          icon="mdi-close"
          size="small"
          elevation="0"
          @click="toggleSnackbar(false)"
        />
      </template>
    </v-snackbar>
    <base-overlays />
    <base-sidebar />
    <base-header />
    <v-main>
      <v-container>
        <slot />
        <v-spacer v-if="isMobile" style="height: var(--footer-menu-height)" />
      </v-container>
    </v-main>
    <Teleport to="body">
      <base-footer-menu v-if="isMobile" />
    </Teleport>
  </v-layout>
</template>
