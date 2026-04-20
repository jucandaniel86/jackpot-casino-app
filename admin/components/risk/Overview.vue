<script setup lang="ts">
type Overview = {
  range: { from: string; to: string };
  kpis: {
    high_risk_players: number;
    medium_risk_players: number;
    duplicate_transactions_24h: number;
    top_profit_user: null | {
      user_id: number;
      username: string;
      profit: string;
      wagered: string;
    };
    highest_rtp_player: null | {
      user_id: number;
      username: string;
      rtp_percent: string;
    };
  };
};

const props = defineProps<{ data: Overview | null }>();
</script>

<template>
  <div v-if="!data">
    <v-skeleton-loader type="card, card, card" />
  </div>

  <v-row v-else dense>
    <v-col cols="12" md="3">
      <v-card variant="tonal" color="error">
        <v-card-text>
          <div class="text-body-2">High Risk Players</div>
          <div class="text-h4 font-weight-bold">
            {{ data.kpis.high_risk_players }}
          </div>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col cols="12" md="3">
      <v-card variant="tonal" color="warning">
        <v-card-text>
          <div class="text-body-2">Medium Risk Players</div>
          <div class="text-h4 font-weight-bold">
            {{ data.kpis.medium_risk_players }}
          </div>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col cols="12" md="3">
      <v-card variant="outlined">
        <v-card-text>
          <div class="text-body-2 text-medium-emphasis">Duplicate Tx (24h)</div>
          <div class="text-h4 font-weight-bold">
            {{ data.kpis.duplicate_transactions_24h }}
          </div>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col cols="12" md="3">
      <v-card variant="outlined">
        <v-card-text>
          <div class="text-body-2 text-medium-emphasis">Top Profit User</div>
          <div v-if="data.kpis.top_profit_user" class="mt-1">
            <div class="font-weight-medium">
              {{ data.kpis.top_profit_user.username }}
            </div>
            <div class="text-success font-weight-bold">
              +{{ data.kpis.top_profit_user.profit }}
            </div>
          </div>
          <div v-else class="text-medium-emphasis">—</div>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col cols="12" md="6">
      <v-card variant="outlined">
        <v-card-text>
          <div class="text-body-2 text-medium-emphasis">Highest RTP Player</div>
          <div v-if="data.kpis.highest_rtp_player" class="mt-1">
            <div class="font-weight-medium">
              {{ data.kpis.highest_rtp_player.username }}
            </div>
            <div class="text-primary font-weight-bold">
              {{ data.kpis.highest_rtp_player.rtp_percent }}%
            </div>
          </div>
          <div v-else class="text-medium-emphasis">—</div>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>
