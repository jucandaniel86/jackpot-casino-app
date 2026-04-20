<script setup lang="ts">
type Row = {
  game_id: number;
  game_name: string;
  provider_id: number;
  players_count: number;
  wagered: string;
  won: string;
  refunded: string;
  rtp_percent: string;
  refund_rate_percent: string;
  flag: "ok" | "warning" | "critical";
};
type Payload = { items: Row[] };
const props = defineProps<{ data: Payload | null }>();
</script>

<template>
  <div v-if="!data">
    <v-skeleton-loader type="table" />
  </div>

  <v-data-table
    v-else
    :items="data.items"
    density="compact"
    class="elevation-0"
    :headers="[
      { title: 'Game', key: 'game_name' },
      { title: 'Players', key: 'players_count', align: 'end' },
      { title: 'Wagered', key: 'wagered', align: 'end' },
      { title: 'Won', key: 'won', align: 'end' },
      { title: 'RTP', key: 'rtp_percent', align: 'end' },
      { title: 'Refund %', key: 'refund_rate_percent', align: 'end' },
      { title: 'Flag', key: 'flag' },
    ]"
  >
    <template #item.flag="{ item }">
      <v-chip
        :color="
          item.flag === 'critical'
            ? 'error'
            : item.flag === 'warning'
            ? 'warning'
            : 'success'
        "
        variant="tonal"
        size="small"
      >
        {{ item.flag.toUpperCase() }}
      </v-chip>
    </template>
  </v-data-table>
</template>
