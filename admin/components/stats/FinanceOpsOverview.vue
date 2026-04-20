<script setup lang="ts"> 
import Money from "../shared/Money.vue";

type Payload = {
  range: { from: string; to: string };
  filters: { currency_code: string; decimals: number; unclaimed_days: number };

  volume: {
    deposits: { count: number; amount_base: string; amount_ui: string };
    withdrawals: { count: number; amount_base: string; amount_ui: string };
  };

  net_cashflow: { amount_base: string; amount_ui: string };
  avg_deposit: { amount_base: string; amount_ui: string };

  wallet_balances_overview: {
    user_wallets: {
      available_base: string;
      reserved_base: string;
      total_base: string;
      available_ui: string;
      reserved_ui: string;
      total_ui: string;
    };
    non_user_wallets: {
      available_base: string;
      reserved_base: string;
      total_base: string;
      available_ui: string;
      reserved_ui: string;
      total_ui: string;
    };
    note?: string;
  };

  unclaimed_balances: {
    never_played: {
      players_count: number;
      available_base: string;
      reserved_base: string;
      total_base: string;
      available_ui: string;
      reserved_ui: string;
      total_ui: string;
    };
    inactive: {
      players_count: number;
      inactive_days: number;
      cutoff: string;
      available_base: string;
      reserved_base: string;
      total_base: string;
      available_ui: string;
      reserved_ui: string;
      total_ui: string;
    };
    note?: string;
  };
};

const props = defineProps<{
  data: Payload | null;
  currency: string;
}>();

function money(v: any) {
  const num = Number(v ?? 0);
  if (Number.isNaN(num)) return "0.00";
  return new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 8,
  }).format(num);
}

function n(v: any) {
  const num = Number(v ?? 0);
  if (Number.isNaN(num)) return "0";
  return new Intl.NumberFormat().format(num);
}

function isNegative(v: string) {
  return String(v).trim().startsWith("-");
}

const userTotal = computed(() =>
  Number(props.data?.wallet_balances_overview.user_wallets.total_ui ?? 0)
);
const casinoTotal = computed(() =>
  Number(props.data?.wallet_balances_overview.non_user_wallets.total_ui ?? 0)
);
const totalAll = computed(() => userTotal.value + casinoTotal.value);

const userPct = computed(() => {
  const total = totalAll.value;
  if (!total) return 0;
  return Math.round((userTotal.value / total) * 100);
});
const casinoPct = computed(() => 100 - userPct.value);

</script>

<template>
  <div v-if="!data">
    <v-skeleton-loader type="card, card, card, card, table" />
  </div>

  <div v-else class="d-flex flex-column ga-4">
    <!-- KPI Cards -->
    <v-row dense>
      <v-col cols="12" md="3">
        <v-card variant="outlined" color="primary">
          <v-card-text>
            <div class="text-caption text-medium-emphasis">Deposits</div>
            <div class="d-flex align-center justify-space-between">
              <div class="text-h5 font-weight-bold">
                <Money
                  :ui="data.volume.deposits.amount_ui"
                  :base="data.volume.deposits.amount_base"
                  :decimals="data.filters.decimals"
                  :currency="currency"
                  suffixCurrency
                  showPlus
                  :precision="2"
                />
              </div>
              <v-chip size="small" variant="tonal" color="success">
                {{ n(data.volume.deposits.count) }} tx
              </v-chip>
            </div>
            <div class="text-caption text-medium-emphasis mt-1">
              {{ currency }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3">
        <v-card variant="outlined" color="primary">
          <v-card-text>
            <div class="text-caption text-medium-emphasis">Withdrawals</div>
            <div class="d-flex align-center justify-space-between">
              <div class="text-h5 font-weight-bold">
                <Money
                  :ui="data.volume.withdrawals.amount_ui"
                  :base="data.volume.withdrawals.amount_base"
                  :decimals="data.filters.decimals"
                  :currency="currency"
                  showPlus
                  suffixCurrency
                  :precision="2"
                />
              </div>
              <v-chip size="small" variant="tonal" color="info">
                {{ n(data.volume.withdrawals.count) }} tx
              </v-chip>
            </div>
            <div class="text-caption text-medium-emphasis mt-1">
              {{ currency }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3">
        <v-card
          variant="tonal"
          :color="isNegative(data.net_cashflow.amount_ui) ? 'error' : 'success'"
        >
          <v-card-text>
            <div class="text-caption">Net cashflow</div>
            <div class="text-h5 font-weight-bold">
              <Money
                :ui="data.net_cashflow.amount_ui"
                :base="data.net_cashflow.amount_base"
                :decimals="data.filters.decimals"
                :currency="currency"
                showPlus
                suffixCurrency
                :precision="2"
              />
            </div>
            <div class="text-caption mt-1">{{ currency }}</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3">
        <v-card variant="outlined" color="primary">
          <v-card-text>
            <div class="text-caption text-medium-emphasis">
              Avg deposit size
            </div>
            <div class="text-h5 font-weight-bold">
              <Money
                :ui="data.avg_deposit.amount_ui"
                :base="data.avg_deposit.amount_base"
                :decimals="data.filters.decimals"
                :currency="currency"
                showPlus
                suffixCurrency
                :precision="2"
              />
            </div>
            <div class="text-caption text-medium-emphasis mt-1">
              {{ currency }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Wallet balances overview -->
    <v-card variant="outlined" color="primary">
      <v-card-title class="d-flex align-center justify-space-between">
        <div class="text-subtitle-1 font-weight-bold">
          Wallet balances overview
        </div>
        <v-chip size="small" variant="tonal">
          Total: {{ money(totalAll) }} {{ currency }}
        </v-chip>
      </v-card-title>

      <v-divider />

      <v-card-text class="d-flex flex-column ga-3">
        <!-- User wallets -->
        <div class="d-flex align-center justify-space-between">
          <div class="d-flex align-center ga-2">
            <v-chip size="small" variant="tonal" color="primary"
              >User wallets</v-chip
            >
            <div class="text-body-2">
              <Money
                :ui="data.wallet_balances_overview.user_wallets.total_ui"
                :base="data.wallet_balances_overview.user_wallets.total_base"
                :decimals="data.filters.decimals"
                :currency="currency"
                suffixCurrency
                :precision="2"
              />
            </div>
          </div>
          <div class="text-caption text-medium-emphasis">{{ userPct }}%</div>
        </div>

        <v-progress-linear
          :model-value="userPct"
          height="12"
          rounded
          color="primary"
        />

        <div class="text-caption text-medium-emphasis">
          Available:
          {{ money(data.wallet_balances_overview.user_wallets.available_ui) }} •
          Reserved:
          {{ money(data.wallet_balances_overview.user_wallets.reserved_ui) }}
        </div>

        <!-- Non user wallets (Treasury/Casino) -->
        <div class="d-flex align-center justify-space-between mt-2">
          <div class="d-flex align-center ga-2">
            <v-chip size="small" variant="tonal" color="grey"
              >Casino/Treasury</v-chip
            >
            <div class="text-body-2">
              {{
                money(data.wallet_balances_overview.non_user_wallets.total_ui)
              }}
              {{ currency }}
            </div>
          </div>
          <div class="text-caption text-medium-emphasis">{{ casinoPct }}%</div>
        </div>

        <v-progress-linear
          :model-value="casinoPct"
          height="12"
          rounded
          color="grey"
        />

        <div class="text-caption text-medium-emphasis">
          Available:
          {{
            money(data.wallet_balances_overview.non_user_wallets.available_ui)
          }}
          • Reserved:
          {{
            money(data.wallet_balances_overview.non_user_wallets.reserved_ui)
          }}
        </div>

        <div class="text-caption text-medium-emphasis">
          {{ data.wallet_balances_overview.note }}
        </div>

        <v-alert
          v-if="
            Number(data.wallet_balances_overview.non_user_wallets.total_ui) ===
            0
          "
          type="info"
          variant="tonal"
        >
          Treasury wallet is not implemented yet. “Casino/Treasury” totals
          represent non-player wallet holders.
        </v-alert>
      </v-card-text>
    </v-card>

    <!-- Unclaimed balances -->
    <v-row dense>
      <v-col cols="12" md="6">
        <v-card variant="outlined" color="primary">
          <v-card-title class="d-flex align-center justify-space-between">
            <div class="text-subtitle-1 font-weight-bold">
              Unclaimed: never played
            </div>
            <v-chip size="small" variant="tonal" color="warning">
              {{ n(data.unclaimed_balances.never_played.players_count) }}
              players
            </v-chip>
          </v-card-title>
          <v-divider />
          <v-card-text class="d-flex flex-column ga-2">
            <div class="text-body-2">
              Total:
              <strong
                >{{ money(data.unclaimed_balances.never_played.total_ui) }}
                {{ currency }}</strong
              >
            </div>
            <div class="text-caption text-medium-emphasis">
              Available:
              {{ money(data.unclaimed_balances.never_played.available_ui) }} •
              Reserved:
              {{ money(data.unclaimed_balances.never_played.reserved_ui) }}
            </div>
            <div class="text-caption text-medium-emphasis">
              Users who deposited but have 0 bets ever.
            </div>

            <div class="d-flex ga-2 mt-2">
              <v-btn
                variant="outlined"
                size="small"
                prepend-icon="mdi-account-search"
              >
                View players
              </v-btn>
              <v-btn
                variant="outlined"
                size="small"
                color="primary"
                prepend-icon="mdi-email"
              >
                Send retention campaign
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card variant="outlined" color="primary">
          <v-card-title class="d-flex align-center justify-space-between">
            <div class="text-subtitle-1 font-weight-bold">
              Unclaimed: inactive
            </div>
            <v-chip size="small" variant="tonal" color="info">
              {{ n(data.unclaimed_balances.inactive.players_count) }} players
            </v-chip>
          </v-card-title>
          <v-divider />
          <v-card-text class="d-flex flex-column ga-2">
            <div class="text-body-2">
              Total:
              <strong
                >{{ money(data.unclaimed_balances.inactive.total_ui) }}
                {{ currency }}</strong
              >
            </div>
            <div class="text-caption text-medium-emphasis">
              Available:
              {{ money(data.unclaimed_balances.inactive.available_ui) }} •
              Reserved:
              {{ money(data.unclaimed_balances.inactive.reserved_ui) }}
            </div>
            <div class="text-caption text-medium-emphasis">
              Deposited, but no bets in last
              {{ data.unclaimed_balances.inactive.inactive_days }} days. Cutoff:
              {{ data.unclaimed_balances.inactive.cutoff }}
            </div>

            <div class="d-flex ga-2 mt-2">
              <v-btn
                variant="outlined"
                size="small"
                prepend-icon="mdi-account-search"
              >
                View players
              </v-btn>
              <v-btn
                variant="outlined"
                size="small"
                color="primary"
                prepend-icon="mdi-bell"
              >
                Trigger reactivation
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <div class="text-caption text-medium-emphasis">
      {{ data.unclaimed_balances.note }}
    </div>
  </div>
</template>
