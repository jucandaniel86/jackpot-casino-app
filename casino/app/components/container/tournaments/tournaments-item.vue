<script setup lang="ts">
import type { Tournament } from './tournaments-config'

const props = defineProps<{ tournament: Tournament }>()

const panel = ref<'leaderboard' | 'prizes'>('leaderboard')

const statusLabel = computed(() => {
  if (props.tournament.status === 'active') return 'LIVE NOW'
  if (props.tournament.status === 'upcoming') return 'UPCOMING'
  if (props.tournament.status === 'finished') return 'FINISHED'
  return String(props.tournament.status || '').toUpperCase()
})

const statusColor = computed(() => {
  if (props.tournament.status === 'active') return 'success'
  if (props.tournament.status === 'upcoming') return 'info'
  if (props.tournament.status === 'finished') return 'secondary'
  return 'warning'
})

const subtitle = computed(() => props.tournament.ui?.subtitle ?? null)
const playersCount = computed(() => props.tournament.ui?.players_count ?? null)
const endsInLabel = computed(() => props.tournament.ui?.ends_in_label ?? null)
const prizePoolLabel = computed(() => props.tournament.ui?.prize_pool_label ?? null)
const progressPercent = computed(() => {
  const v = props.tournament.ui?.progress_percent
  const n = typeof v === 'number' ? v : null
  if (n === null) return 0
  return Math.max(0, Math.min(100, n))
})

const protocols = computed(() => props.tournament.ui?.protocols ?? [])
const description = computed(() => props.tournament.ui?.description ?? null)
const rulesNote = computed(() => props.tournament.ui?.rules_note ?? null)

const leaderboard = computed(() => props.tournament.ui?.leaderboard ?? [])
const standing = computed(() => props.tournament.ui?.user_standing ?? null)

const standingColor = computed(() => {
  const v = standing.value?.badge_variant ?? null
  if (v === 'success') return 'success'
  if (v === 'info') return 'info'
  if (v === 'warning') return 'warning'
  if (v === 'error') return 'error'
  return 'primary'
})
</script>
<template>
  <div class="t-root mb-2">
    <!-- Tournament Item: Featured -->
    <v-card class="t-feature" variant="flat">
      <v-row class="t-feature__row" no-gutters>
        <v-col class="t-feature__thumb" cols="12" md="auto">
          <v-img
            class="t-feature__img"
            :src="props.tournament.thumbnail || undefined"
            cover
            alt="Tournament Thumbnail"
          />
        </v-col>

        <v-col class="t-feature__content" cols="12" md="auto">
          <div class="t-feature__inner">
            <div class="t-feature__top">
              <div>
                <div class="t-badges">
                  <span class="t-badge t-badge--live">{{ statusLabel }}</span>
                  <span v-if="playersCount !== null" class="t-badge t-badge--players">
                    <v-icon class="t-badge__icon" icon="mdi-account-group" size="14" />
                    {{ playersCount }} players
                  </span>
                </div>

                <h3 class="t-title">
                  {{ props.tournament.name }}
                </h3>
                <p v-if="subtitle" class="t-subtitle">
                  {{ subtitle }}
                </p>
              </div>

              <div class="t-ends">
                <div class="t-ends__label">Ends in</div>
                <div class="t-ends__value">
                  <v-icon icon="mdi-timer-outline" size="16" />
                  {{ endsInLabel || '—' }}
                </div>
              </div>
            </div>

            <div class="t-feature__bottom">
              <div>
                <div class="t-prize__label">Prize Pool</div>
                <div class="t-prize__value">
                  {{ prizePoolLabel || '—' }}
                </div>
              </div>

              <v-btn class="t-cta" variant="flat"> JOIN TOURNAMENT </v-btn>
            </div>
          </div>
        </v-col>
      </v-row>

      <div class="t-progress">
        <div class="t-progress__bar" :style="{ width: `${progressPercent}%` }" />
      </div>
    </v-card>

    <v-row class="t-below" no-gutters>
      <!-- Rules Section (left) -->
      <v-col class="t-left" cols="12" md="7">
        <v-card class="t-rules" variant="flat">
          <div class="t-rules__header">
            <v-icon icon="mdi-gavel" class="t-rules__icon" />
            <div class="t-rules__title">Tournament Protocols</div>
          </div>

          <div class="t-rules__scroll">
            <div v-if="protocols.length" class="t-rules__grid">
              <div v-for="(p, i) in protocols" :key="`p-${i}`" class="t-rule-card">
                <div class="t-rule-card__label">{{ p.label }}</div>
                <div class="t-rule-card__value">{{ p.value }}</div>
              </div>
            </div>
            <div v-else class="t-muted">No rules/protocols available.</div>

            <div class="t-rules__text">
              <p v-if="description" class="t-desc">{{ description }}</p>
              <p v-if="rulesNote" class="t-note">{{ rulesNote }}</p>
            </div>
          </div>
        </v-card>
      </v-col>

      <!-- Right Sidebar: Leaderboard & Prizes -->
      <v-col class="t-right" cols="12" md="5">
        <v-card class="t-sidebar" variant="flat">
          <div class="t-sidebar__tabs">
            <v-btn
              class="t-sidebar__tab"
              variant="text"
              :class="{ 't-sidebar__tab--active': panel === 'leaderboard' }"
              @click="panel = 'leaderboard'"
            >
              Leaderboard
            </v-btn>
            <v-btn
              class="t-sidebar__tab"
              variant="text"
              :class="{ 't-sidebar__tab--active': panel === 'prizes' }"
              @click="panel = 'prizes'"
            >
              Prizes
            </v-btn>
          </div>

          <div class="t-sidebar__content">
            <v-window v-model="panel" class="t-sidebar__window">
              <v-window-item value="leaderboard">
                <div class="t-sidebar__scroll">
                  <div class="t-sidebar__body">
                    <table class="t-table">
                      <thead>
                        <tr>
                          <th>Position</th>
                          <th>Player</th>
                          <th class="t-right-align">Score</th>
                          <th class="t-right-align">Prizes</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-if="leaderboard.length === 0">
                          <td class="t-muted t-center" colspan="4">No leaderboard data.</td>
                        </tr>
                        <tr v-for="row in leaderboard" :key="`lb-${row.position}-${row.player}`">
                          <td>
                            <div
                              class="t-rank"
                              :class="{
                                't-rank--gold': row.position === 1,
                                't-rank--bronze': row.position === 3,
                                't-rank--plain': row.position > 3,
                              }"
                            >
                              {{ row.position }}
                            </div>
                          </td>
                          <td class="t-player">{{ row.player }}</td>
                          <td class="t-right-align t-score">{{ row.score.toLocaleString() }}</td>
                          <td class="t-right-align t-prize">{{ row.prize_label }}</td>
                        </tr>
                      </tbody>
                    </table>

                    <div v-if="standing" class="t-standing">
                      <div class="t-standing__top">
                        <div class="t-standing__label">Your Standing</div>
                        <div v-if="standing.badge_text" class="t-standing__badge">
                          {{ standing.badge_text }}
                        </div>
                      </div>

                      <div class="t-standing__row">
                        <div class="t-standing__pos">{{ standing.position }}</div>
                        <div class="t-standing__user">
                          <div class="t-standing__name">{{ standing.username }}</div>
                          <div v-if="standing.est_prize_label" class="t-standing__est">
                            EST. PRIZE: {{ standing.est_prize_label }}
                          </div>
                        </div>
                        <div class="t-standing__score">
                          <div class="t-standing__scoreLabel">Score</div>
                          <div class="t-standing__scoreValue">
                            {{ standing.score.toLocaleString() }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </v-window-item>

              <v-window-item value="prizes">
                <div class="t-sidebar__scroll t-sidebar__scroll--pad">
                  <div class="t-sidebar__body t-sidebar__body--pad">
                    <div v-if="props.tournament.prizes?.length" class="t-prizes">
                      <div
                        v-for="prize in props.tournament.prizes"
                        :key="prize.id"
                        class="t-prizeRow"
                      >
                        <div>
                          <div class="t-prizeRow__name">{{ prize.prize_name }}</div>
                          <div class="t-prizeRow__meta">
                            <span v-if="prize.prize_type === 'rank'">
                              Rank {{ prize.rank_from
                              }}{{
                                prize.rank_to && prize.rank_to !== prize.rank_from
                                  ? ` - ${prize.rank_to}`
                                  : ''
                              }}
                            </span>
                            <span v-else>Min points: {{ prize.min_points }}</span>
                          </div>
                        </div>
                        <div class="t-prizeRow__amount">
                          {{ prize.prize_currency || '' }} {{ prize.prize_amount }}
                        </div>
                      </div>
                    </div>

                    <div v-else class="t-muted t-center">No prizes configured.</div>
                  </div>
                </div>
              </v-window-item>
            </v-window>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<style scoped>
.t-root {
  display: flex;
  flex-direction: column;
  gap: 24px;
  color: #e2e2e2;
}

.t-muted {
  color: rgba(163, 163, 163, 1);
}
.t-center {
  text-align: center;
}
.t-right-align {
  text-align: right;
}

.t-feature {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.05);
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.45);
  transition: border-color 0.3s ease;
}
.t-feature:hover {
  border-color: rgba(242, 207, 142, 0.2);
}

.t-feature__thumb {
  width: 100%;
}
@media (min-width: 540px) {
  .t-feature__thumb {
    width: 224px;
    flex: 0 0 224px;
  }
}
.t-feature__img {
  height: 192px;
  transition: transform 0.7s ease;
}
@media (min-width: 540px) {
  .t-feature__img {
    height: 100%;
    min-height: 220px;
  }
}
.t-feature:hover .t-feature__img {
  transform: scale(1.1);
}

.t-feature__content {
  flex: 1 1 auto;
}
.t-feature__inner {
  padding: 24px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 220px;
}

.t-feature__top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
}

.t-badges {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}
.t-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 10px;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 6px;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  border: 1px solid rgba(255, 255, 255, 0.08);
}
.t-badge--live {
  background: rgba(242, 207, 142, 0.12);
  color: var(--base-color, #f2cf8e);
  border-color: rgba(242, 207, 142, 0.2);
}
.t-badge--players {
  background: rgba(255, 255, 255, 0.05);
  color: rgba(212, 212, 212, 1);
  border-color: rgba(255, 255, 255, 0.06);
}
.t-badge__icon {
  opacity: 0.9;
}

.t-title {
  font-size: 24px;
  font-weight: 900;
  margin: 0;
  color: #fff;
  transition: color 0.3s ease;
}
.t-feature:hover .t-title {
  color: var(--base-color, #f2cf8e);
}
.t-subtitle {
  margin: 2px 0 0;
  font-size: 14px;
  font-weight: 600;
  color: rgba(115, 115, 115, 1);
}

.t-ends {
  text-align: right;
}
.t-ends__label {
  font-size: 12px;
  color: rgba(115, 115, 115, 1);
  text-transform: uppercase;
  letter-spacing: 0.12em;
  margin-bottom: 4px;
}
.t-ends__value {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 800;
  color: var(--base-color, #f2cf8e);
}

.t-feature__bottom {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 16px;
  margin-top: 24px;
}
.t-prize__label {
  font-size: 12px;
  color: rgba(115, 115, 115, 1);
  text-transform: uppercase;
  letter-spacing: 0.12em;
  margin-bottom: 4px;
}
.t-prize__value {
  font-size: 36px;
  font-weight: 900;
  color: #fff;
  text-shadow: 0 0 15px rgba(212, 175, 55, 0.5);
}

.t-cta {
  background: var(--base-color, #f2cf8e) !important;
  color: #0b0b0b !important;
  font-weight: 900;
  border-radius: 8px;
  padding: 12px 32px;
  letter-spacing: 0.02em;
  box-shadow: 0 6px 18px rgba(212, 175, 55, 0.25);
}
.t-cta:active {
  transform: scale(0.98);
}

.t-progress {
  position: absolute;
  left: 0;
  bottom: 0;
  height: 4px;
  width: 100%;
  background: rgba(255, 255, 255, 0.08);
}
.t-progress__bar {
  height: 100%;
  background: var(--base-color, #f2cf8e);
}

.t-left {
  padding-right: 12px;
}
.t-right {
  padding-left: 12px;
}
@media (max-width: 539px) {
  .t-left,
  .t-right {
    padding-left: 0;
    padding-right: 0;
  }
}
@media (min-width: 960px) {
  .t-left,
  .t-right {
    display: flex;
  }
  .t-below {
    --t-below-height: clamp(520px, 64vh, 640px);
  }
}

.t-rules {
  background: rgba(255, 255, 255, 0.04);
  border-radius: 12px;
  padding: 32px;
  border-left: 4px solid var(--base-color, #f2cf8e);
  display: flex;
  flex-direction: column;
}
.t-rules__scroll {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  padding-right: 6px;
}
@media (min-width: 960px) {
  .t-rules {
    height: var(--t-below-height);
  }
}
.t-rules__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
}
.t-rules__icon {
  color: var(--base-color, #f2cf8e);
}
.t-rules__title {
  font-size: 20px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  color: #fff;
}
.t-rules__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
  margin-bottom: 32px;
}
@media (min-width: 768px) {
  .t-rules__grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
.t-rule-card {
  background: rgba(0, 0, 0, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  padding: 16px;
}
.t-rule-card__label {
  font-size: 10px;
  text-transform: uppercase;
  font-weight: 900;
  letter-spacing: 0.12em;
  color: rgba(115, 115, 115, 1);
  margin-bottom: 4px;
}
.t-rule-card__value {
  font-weight: 800;
  color: #fff;
}
.t-rules__text {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.t-desc {
  margin: 0;
  font-size: 14px;
  line-height: 1.6;
  color: rgba(163, 163, 163, 1);
}
.t-note {
  margin: 0;
  font-size: 12px;
  line-height: 1.6;
  opacity: 0.6;
  color: rgba(163, 163, 163, 1);
}

.t-sidebar {
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(53, 53, 53, 0.6);
  backdrop-filter: blur(12px);
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.55);
  display: flex;
  flex-direction: column;
}
@media (min-width: 960px) {
  .t-sidebar {
    height: var(--t-below-height);
  }
}
.t-sidebar__tabs {
  display: flex;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.t-sidebar__tab {
  flex: 1 1 0;
  padding: 16px 0;
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: rgba(115, 115, 115, 1) !important;
  border-bottom: 2px solid transparent;
  border-radius: 0;
}
.t-sidebar__tab--active {
  color: var(--base-color, #f2cf8e) !important;
  border-bottom-color: var(--base-color, #f2cf8e);
  background: rgba(242, 207, 142, 0.06);
}
.t-sidebar__body {
  padding: 4px;
}
.t-sidebar__body--pad {
  padding: 24px;
}
.t-sidebar__content {
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
}
.t-sidebar__scroll {
  height: 100%;
  overflow-y: auto;
  overflow-x: hidden;
}
.t-sidebar__scroll--pad {
  padding-right: 6px;
}
.t-sidebar__window {
  height: 100%;
  min-height: 0;
}
.t-sidebar__window :deep(.v-window__container) {
  height: 100%;
}
.t-sidebar__window :deep(.v-window-item),
.t-sidebar__window :deep(.v-window-item > *) {
  height: 100%;
}

.t-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
.t-table thead th {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-weight: 900;
  color: rgba(115, 115, 115, 1);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  padding: 16px 24px;
  position: sticky;
  top: 0;
  z-index: 2;
  background: rgba(35, 35, 35, 0.92);
  backdrop-filter: blur(10px);
}
.t-table tbody td {
  padding: 16px 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.02);
}
.t-table tbody tr:hover {
  background: rgba(255, 255, 255, 0.05);
}

.t-rank {
  width: 24px;
  height: 24px;
  border-radius: 9999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: rgba(212, 212, 212, 1);
  background: rgba(115, 115, 115, 0.2);
}
.t-rank--gold {
  background: rgba(245, 158, 11, 0.2);
  border-color: rgba(242, 207, 142, 0.3);
  color: var(--base-color, #f2cf8e);
}
.t-rank--bronze {
  background: rgba(124, 45, 18, 0.2);
  border-color: rgba(251, 146, 60, 0.3);
  color: rgba(251, 146, 60, 1);
}
.t-rank--plain {
  background: transparent;
  border-color: transparent;
  color: rgba(115, 115, 115, 1);
}
.t-player {
  font-weight: 900;
  color: #fff;
}
.t-score {
  color: rgba(212, 212, 212, 1);
}
.t-prize {
  color: var(--base-color, #f2cf8e);
  font-weight: 900;
}

.t-standing {
  margin-top: 16px;
  padding: 24px;
  background: rgba(242, 207, 142, 0.1);
  border-top: 1px solid rgba(242, 207, 142, 0.2);
}
.t-standing__top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.t-standing__label {
  font-size: 12px;
  text-transform: uppercase;
  font-weight: 900;
  letter-spacing: 0.12em;
  color: rgba(212, 212, 212, 1);
}
.t-standing__badge {
  background: var(--base-color, #f2cf8e);
  color: #0b0b0b;
  font-size: 10px;
  font-weight: 900;
  padding: 2px 8px;
  border-radius: 6px;
}
.t-standing__row {
  display: flex;
  align-items: center;
  gap: 16px;
}
.t-standing__pos {
  width: 40px;
  height: 40px;
  border-radius: 9999px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #fff;
  font-weight: 900;
}
.t-standing__name {
  font-weight: 900;
  color: #fff;
}
.t-standing__est {
  font-size: 12px;
  font-weight: 900;
  color: var(--base-color, #f2cf8e);
}
.t-standing__score {
  margin-left: auto;
  text-align: right;
}
.t-standing__scoreLabel {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: rgba(115, 115, 115, 1);
  font-weight: 900;
}
.t-standing__scoreValue {
  font-weight: 900;
  color: #fff;
}

.t-prizes {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.t-prizeRow {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(0, 0, 0, 0.2);
}
.t-prizeRow__name {
  font-weight: 900;
  color: #fff;
}
.t-prizeRow__meta {
  margin-top: 2px;
  font-size: 12px;
  color: rgba(115, 115, 115, 1);
}
.t-prizeRow__amount {
  font-weight: 900;
  color: var(--base-color, #f2cf8e);
}
</style>
