<script setup lang="ts">
import type { BonusWalletBackfillResponse } from "~/types/bonuses";

useHead({ title: "Bonus Maintenance" });

definePageMeta({
  middleware: "auth",
});

const { toastSuccess, toastError, confirm } = useAlert();
const api = useBonusesApi();
const router = useRouter();

const running = ref(false);
const result = ref<BonusWalletBackfillResponse | null>(null);

const runBackfill = async () => {
  if (running.value) {
    return;
  }

  running.value = true;
  const response = await api.createMissingWallets();
  running.value = false;

  if (!response.success) {
    toastError(response.message || "Backfill failed.");
    return;
  }

  result.value = response.data;
  const newWallets = Number(response.data?.newWallets ?? 0);
  toastSuccess(`Backfill completed. Created ${newWallets} new wallets.`);
};

const confirmBackfill = () => {
  confirm({
    text: "Run wallet backfill for all players now?",
    onClick: async () => {
      await runBackfill();
    },
  });
};

const goToManualGrant = () => {
  router.push("/bonuses/manual");
};

const formattedResult = computed(() => JSON.stringify(result.value, null, 2));
const newWalletsCount = computed(() => Number(result.value?.newWallets ?? 0));
</script>

<template>
  <v-card>
    <v-card-title class="d-flex align-center justify-space-between">
      <span>Backfill Bonus Wallets</span>
      <v-btn variant="tonal" color="info" @click="goToManualGrant">
        Go to Manual Grant
      </v-btn>
    </v-card-title>
    <v-divider />
    <v-card-text>
      <p class="mb-2">
        Creates missing wallets for existing players based on active wallet
        types.
      </p>
      <p class="mb-4">
        Use this when manual bonus grants return skip reason:
        <code>missing_bonus_wallet_type_or_wallet</code>.
      </p>

      <v-alert type="info" variant="tonal" class="mb-4">
        Safe to run multiple times. Idempotent behavior is expected.
      </v-alert>

      <v-btn
        color="primary"
        :loading="running"
        :disabled="running"
        @click="confirmBackfill"
      >
        Create Missing Wallets
      </v-btn>

      <v-card v-if="result" class="mt-6" variant="outlined">
        <v-card-title class="text-subtitle-1">
          Backfill completed. Created {{ newWalletsCount }} new wallets.
        </v-card-title>
        <v-divider />
        <v-card-text>
          <pre class="result-json">{{ formattedResult }}</pre>
        </v-card-text>
      </v-card>

      <v-card class="mt-6" variant="outlined">
        <v-card-title class="text-subtitle-2">Troubleshooting</v-card-title>
        <v-divider />
        <v-card-text>
          If you still see skipped users, verify bonus wallet_type is active and
          matches <code>currency_id</code>.
        </v-card-text>
      </v-card>
    </v-card-text>
  </v-card>
</template>

<style scoped>
.result-json {
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
  font-family: Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  font-size: 12px;
}
</style>
