<script setup lang="ts">
const props = defineProps<{ data: any }>();

const deltaNeg = computed(() => Number(props.data.mismatch.delta_base) < 0);
</script>

<template>
  <v-row dense>
    <!-- 16) Mismatch -->
    <v-col cols="12" md="4">
      <v-card>
        <v-card-title class="d-flex justify-space-between">
          Ledger vs On-chain
          <v-chip
            :color="deltaNeg ? 'error' : 'success'"
            size="small"
            variant="tonal"
          >
            {{ deltaNeg ? "Mismatch" : "OK" }}
          </v-chip>
        </v-card-title>

        <v-card-text class="d-flex flex-column ga-2">
          <div>
            Treasury on-chain:

            <SharedMoney
              :ui="props.data.mismatch.treasury_onchain_ui"
              :base="props.data.mismatch.treasury_onchain_base"
              :decimals="props.data.decimals"
              currency="PEP"
              :precision="2"
            />
          </div>

          <div>
            Ledger liabilities:
            <SharedMoney
              :ui="props.data.mismatch.ledger_liabilities_ui"
              :base="props.data.mismatch.ledger_liabilities_base"
              :decimals="props.data.decimals"
              currency="PEP"
              :precision="2"
            />
          </div>

          <div class="font-weight-bold">
            Delta:

            <SharedMoney
              :ui="props.data.mismatch.delta_ui"
              :base="props.data.mismatch.delta_base"
              :decimals="props.data.decimals"
              currency="PEP"
              :precision="2"
              colored
            />
          </div>

          <v-alert
            v-if="deltaNeg"
            type="error"
            variant="tonal"
            density="compact"
          >
            ⚠ Ledger liabilities exceed on-chain funds.
          </v-alert>
        </v-card-text>
      </v-card>
    </v-col>

    <!-- 17) Deposit latency -->
    <v-col cols="12" md="4">
      <v-card>
        <v-card-title>Deposit latency</v-card-title>
        <v-card-text>
          <v-list density="compact">
            <v-list-item title="P50">
              <template #append
                >{{ props.data.deposit_latency.p50_sec }} sec</template
              >
            </v-list-item>
            <v-list-item title="P95">
              <template #append
                >{{ props.data.deposit_latency.p95_sec }} sec</template
              >
            </v-list-item>
            <v-list-item title="Max">
              <template #append
                >{{ props.data.deposit_latency.max_sec }} sec</template
              >
            </v-list-item>
          </v-list>
        </v-card-text>
      </v-card>
    </v-col>

    <!-- 18) Fees -->
    <v-col cols="12" md="4">
      <v-card>
        <v-card-title>Fees (SOL)</v-card-title>
        <v-card-text class="d-flex flex-column ga-2">
          <div>
            Withdraw fees:
            <SharedMoney
              :ui="props.data.fees.withdraw_fee_sol"
              :precision="6"
            />
          </div>
          <div>
            Sweep fees:
            <SharedMoney :ui="props.data.fees.sweep_fee_sol" :precision="6" />
          </div>
          <div class="font-weight-bold">
            Cost / withdraw:
            <SharedMoney
              :ui="props.data.fees.cost_per_withdraw_sol"
              :precision="6"
            />
          </div>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>
