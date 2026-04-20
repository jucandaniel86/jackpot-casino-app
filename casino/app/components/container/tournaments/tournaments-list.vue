<script setup lang="ts">
import type { ContainerType } from '~/core/types/Container'
import TournamentsItem from './tournaments-item.vue'
import type { Tournament } from './tournaments-config'

const { options } = defineProps<{ options: ContainerType }>()
const { display, styles } = useContainerOptions(options)

const tournaments: Tournament[] = options.data.tournaments

//composables
const activeTournaments = tournaments.filter((t) => t.status === 'active')
const upcomingTournaments = tournaments.filter((t) => t.status === 'upcoming')
const finishedTournaments = tournaments.filter((t) => t.status === 'finished')

const tab = ref<'active' | 'upcoming' | 'finished'>('active')
</script>

<template>
  <div v-if="display" :id="options.id" :style="styles">
    <v-btn-toggle v-model="tab" class="t-tabs" mandatory>
      <v-btn class="t-tab" :class="{ 't-tab--active': tab === 'active' }" value="active">
        Active
      </v-btn>
      <v-btn class="t-tab" :class="{ 't-tab--active': tab === 'upcoming' }" value="upcoming">
        Upcoming
      </v-btn>
      <v-btn class="t-tab" :class="{ 't-tab--active': tab === 'finished' }" value="finished">
        Finished
      </v-btn>
    </v-btn-toggle>

    <v-window v-model="tab" class="t-tab-content">
      <v-window-item value="active">
        <!-- active tournaments -->
        <div v-if="activeTournaments.length === 0" class="t-empty">No active tournaments.</div>
        <tournaments-item
          v-for="tournament in activeTournaments"
          v-else
          :key="tournament.id"
          :tournament="tournament"
        />
      </v-window-item>

      <v-window-item value="upcoming">
        <!-- upcoming tournaments -->
        <div v-if="upcomingTournaments.length === 0" class="t-empty">No upcoming tournaments.</div>
        <tournaments-item
          v-for="tournament in upcomingTournaments"
          v-else
          :key="tournament.id"
          :tournament="tournament"
        />
      </v-window-item>

      <v-window-item value="finished">
        <!-- finished tournaments -->
        <div v-if="finishedTournaments.length === 0" class="t-empty">No finished tournaments.</div>
        <tournaments-item
          v-for="tournament in finishedTournaments"
          v-else
          :key="tournament.id"
          :tournament="tournament"
        />
      </v-window-item>
    </v-window>
  </div>
</template>

<style scoped>
.t-tabs {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 6px;
  border-radius: 9999px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  background: rgba(255, 255, 255, 0.04);
  width: fit-content;
}

.t-tab {
  border-radius: 9999px;
  padding: 8px 32px;
  min-height: unset;
  height: auto;
  font-size: 14px;
  font-weight: 800;
  letter-spacing: 0.2px;
  text-transform: none;
  color: rgba(163, 163, 163, 1);
  background: transparent;
}

.t-tab--active {
  background: var(--base-color, #f2cf8e);
  color: #0b0b0b;
  box-shadow: 0 6px 18px rgba(212, 175, 55, 0.35);
}

.t-tab-content {
  margin-top: 24px;
}

.t-empty {
  color: rgba(163, 163, 163, 1);
}
</style>
