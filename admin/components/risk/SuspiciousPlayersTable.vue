<script setup lang="ts">
import RiskBadge from "../shared/RiskBadge.vue";

type Row = {
  user_id: number;
  username: string;
  bets_count: number;
  wagered: string;
  profit: string;
  win_rate_percent: string;
  refund_rate_percent: string;
  risk_score: number;
  risk_reasons: string[];
};

type Payload = { players: Row[] };
const props = defineProps<{ data: Payload | null }>();
</script>

<template>
  <div v-if="!data">
    <v-skeleton-loader type="table" />
  </div>

  <v-data-table
    v-else
    :items="data.players"
    density="compact"
    class="elevation-0"
    :headers="[
      { title: 'Risk', key: 'risk', sortable: false },
      { title: 'Player', key: 'username' },
      { title: 'Bets', key: 'bets_count', align: 'end' },
      { title: 'Wagered', key: 'wagered', align: 'end' },
      { title: 'Profit', key: 'profit', align: 'end' },
      { title: 'RTP', key: 'win_rate_percent', align: 'end' },
      { title: 'Refund %', key: 'refund_rate_percent', align: 'end' },
      { title: 'Reasons', key: 'reasons', sortable: false },
    ]"
  >
    <template #item.risk="{ item }">
      <RiskBadge
        :score="item.risk_score"
        :reasons="item.risk_reasons"
        compact
      />
    </template>

    <template #item.profit="{ item }">
      <span
        :class="
          String(item.profit).startsWith('-') ? 'text-error' : 'text-success'
        "
      >
        {{ item.profit }}
      </span>
    </template>

    <template #item.reasons="{ item }">
      <span class="text-medium-emphasis">{{
        item.risk_reasons?.join(", ") || "—"
      }}</span>
    </template>
  </v-data-table>
</template>
