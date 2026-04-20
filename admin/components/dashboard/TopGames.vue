<script setup lang="ts">
export type TopGameItem = {
  bets_count?: number | string;
  game_id?: number | string;
  game_name?: string;
  players_count?: number | string;
  provider_id?: number | string;
  wagered?: number | string;
};

const props = defineProps<{
  items: TopGameItem[];
}>();

const formatNumber = (value: number | string | undefined) => {
  if (value === null || value === undefined || value === "") return "-";
  const num =
    typeof value === "string" ? Number(value.replace(/,/g, "")) : Number(value);
  if (!Number.isFinite(num)) return String(value);
  return num.toLocaleString();
};

const formatWagered = (value: number | string | undefined) => {
  if (value === null || value === undefined || value === "") return "-";
  const num = typeof value === "string" ? Number(value) : Number(value);
  if (!Number.isFinite(num)) return String(value);
  return num.toLocaleString(undefined, { minimumFractionDigits: 2 });
};
</script>

<template>
  <v-card>
    <v-card-title class="d-flex align-center ga-2">
      <v-icon size="20">mdi-gamepad-variant</v-icon>
      <span>Top Games</span>
    </v-card-title>
    <v-card-text>
      <div v-if="!items?.length" class="text-body-2 text-medium-emphasis">
        No games data.
      </div>
      <v-table v-else density="compact">
        <thead>
          <tr>
            <th class="text-left">Game</th>
            <th class="text-left">Bets</th>
            <th class="text-left">Players</th>
            <th class="text-left">Wagered</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in items"
            :key="`${item.game_id}-${item.provider_id}`"
          >
            <td>
              <div class="text-body-2">{{ item.game_name || "-" }}</div>
              <div class="text-caption text-medium-emphasis">
                ID {{ item.game_id ?? "-" }}
              </div>
            </td>
            <td>{{ formatNumber(item.bets_count) }}</td>
            <td>{{ formatNumber(item.players_count) }}</td>
            <td>{{ formatWagered(item.wagered) }}</td>
          </tr>
        </tbody>
      </v-table>
    </v-card-text>
  </v-card>
</template>
