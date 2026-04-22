<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'
import type { Tournament } from '~/components/container/tournaments/tournaments-config'
import TournamentsList from '~/components/container/tournaments/tournaments-list.vue'

type TournamentsPublicJson = { tournaments: Tournament[] }

const loading = ref(false)
const errorMessage = ref<string | null>(null)

const options = ref<ContainerType>({
  id: 'tournaments-list',
  // container value is not used by the component; keep a safe string.
  container: 'TournamentsListContainer' as any,
  children: [],
  appearance: {
    resolutionConfig: {
      LG: { isVisible: true },
      MD: { isVisible: true },
      SM: { isVisible: true },
      XL: { isVisible: true },
      XS: { isVisible: true },
    },
  },
  data: {
    tournaments: [] as Tournament[],
  },
})

async function loadPublicTournaments() {
  loading.value = true
  errorMessage.value = null

  try {
    const res = await fetch('/tournaments.json')
    if (!res.ok) {
      throw new Error(`Failed to fetch tournaments.json (${res.status})`)
    }

    const json = (await res.json()) as TournamentsPublicJson
    options.value = {
      ...options.value,
      data: {
        ...options.value.data,
        tournaments: Array.isArray(json?.tournaments) ? json.tournaments : [],
      },
    }
  } catch (e: any) {
    errorMessage.value = 'Failed to load tournaments.'
    console.log(e)
  } finally {
    loading.value = false
  }
}

onMounted(loadPublicTournaments)
</script>
<template>
  <div class="flex flex-col gap-6">
    <h2 class="font-headline font-bold text-2xl">Tournaments</h2>
    <div v-if="loading" class="text-neutral-500">Loading...</div>
    <div v-else-if="errorMessage" class="text-red-400">{{ errorMessage }}</div>
    <TournamentsList v-else :options="options" />
  </div>
</template>
