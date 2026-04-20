<script setup lang="ts">
import type { BonusGrant, BonusGrantEvent } from "~/types/bonuses";

const props = defineProps<{
  modelValue: boolean;
  grant: BonusGrant | null;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
}>();

const bonuses = useBonusesStore();
const { grantEvents } = storeToRefs(bonuses);
const { toastError } = useAlert();

const loading = computed(() => bonuses.loading.events);

const resolvePlayerId = (event: BonusGrantEvent): string => {
  const playerId = event.player_id ?? event.meta?.player_id ?? null;
  return playerId !== null && typeof playerId !== "undefined"
    ? String(playerId)
    : "-";
};

const resolveWalletBonusId = (event: BonusGrantEvent): string => {
  const walletId = event.meta?.wallet_id_bonus;
  return walletId !== null && typeof walletId !== "undefined"
    ? String(walletId)
    : "-";
};

const resolvePrimaryAmount = (event: BonusGrantEvent): string | number => {
  if (
    event.event_type === "bet_debit" &&
    event.wallet_split_total_ui !== null &&
    String(event.wallet_split_total_ui).trim() !== ""
  ) {
    return event.wallet_split_total_ui ?? "-";
  }

  return event.amount_ui ?? event.amount_base ?? "-";
};

const shouldShowSecondaryAmount = (event: BonusGrantEvent): boolean => {
  return (
    event.event_type === "bet_debit" &&
    event.wallet_split_total_ui !== null &&
    String(event.wallet_split_total_ui).trim() !== "" &&
    String(event.wallet_split_total_ui).trim() !== String(event.amount_ui ?? event.amount_base ?? "")
  );
};

const formatMoney = (value: unknown): string => {
  const parsed = Number(String(value ?? 0));
  if (!Number.isFinite(parsed)) {
    return "-";
  }

  return new Intl.NumberFormat("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 8,
  }).format(parsed);
};

type WalletDebugRow = {
  walletLabel: string;
  walletId: string;
  before: string;
  after: string;
  pct: string;
  currency: string;
  walletType: string;
  walletTypeColor: string;
  walletTypeClass: string;
  walletAmountToneClass: string;
};

const formatWalletDebugRows = (event: BonusGrantEvent): WalletDebugRow[] => {
  const items = event.wallet_balance_debug ?? [];
  if (!items.length) {
    return [];
  }

  return items.map((item) => {
    const walletLabel = item.wallet_purpose || "unknown";
    const normalizedPurpose = String(walletLabel).toLowerCase();
    const purpose = normalizedPurpose || "unknown";
    const walletTypeColor = purpose === "real" ? "teal" : purpose === "bonus" ? "indigo" : "grey";
    const walletTypeClass =
      purpose === "real"
        ? "wallet-real"
        : purpose === "bonus"
          ? "wallet-bonus"
          : "wallet-unknown";
    const walletAmountToneClass =
      purpose === "real"
        ? "wallet-amount-real"
        : purpose === "bonus"
          ? "wallet-amount-bonus"
          : "wallet-amount-unknown";
    const walletId = String(item.wallet_id ?? "-");
    const before = item.available_before_ui ?? item.available_before_base ?? "-";
    const after = item.available_after_ui ?? item.available_after_base ?? "-";
    const pct = item.delta_pct ?? "0";
    const currency = String(event.currency_code || "").trim();

    return {
      walletLabel,
      walletId,
      before: formatMoney(before),
      after: formatMoney(after),
      pct: String(pct),
      currency: currency || "N/A",
      walletType: purpose,
      walletTypeColor,
      walletTypeClass,
      walletAmountToneClass,
    };
  });
};

watch(
  () => props.modelValue,
  async (open) => {
    if (!open || !props.grant?.id) {
      return;
    }

    const result = await bonuses.fetchGrantEvents(props.grant.id);
    if (!result.success) {
      toastError(result.message || "Failed to load grant events.");
    }
  },
);
</script>

<template>
  <v-dialog
    :model-value="modelValue"
    class="grant-events-dialog"
    width="90vw"
    max-width="90vw"
    scrollable
    @update:model-value="emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span>Grant Events #{{ grant?.id }}</span>
        <v-btn icon="ph-x" variant="text" @click="emit('update:modelValue', false)" />
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-skeleton-loader v-if="loading" type="table" />
        <v-table v-else class="grant-events-table" density="comfortable">
          <thead>
            <tr>
              <th>Player</th>
              <th>Event</th>
              <th>Effect</th>
              <th>Amount</th>
              <th>Reference</th>
              <th>Description</th>
              <th>Created at</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(event, index) in grantEvents" :key="event.id || index">
              <td>
                {{ resolvePlayerId(event) }}
                <div class="text-caption text-medium-emphasis">
                  Bonus wallet: {{ resolveWalletBonusId(event) }}
                </div>
              </td>
              <td>
                <div>{{ event.event_label || event.event_type || "-" }}</div>
                <div class="text-caption text-medium-emphasis">
                  {{ event.idempotency_key || "-" }}
                </div>
                <div
                  v-if="
                    Array.isArray(event.wallet_balance_debug) &&
                    event.wallet_balance_debug.length
                  "
                  class="mt-2 d-flex flex-column ga-2"
                >
                  <span
                    v-for="(line, idx) in formatWalletDebugRows(event)"
                    :key="`event-dbg-${event.id || index}-${idx}`"
                    class="wallet-debug-row d-flex flex-column pa-2 rounded"
                    :class="line.walletTypeClass"
                  >
                    <div class="d-flex align-center mb-1">
                      <v-chip
                        size="x-small"
                        :color="line.walletTypeColor"
                        variant="flat"
                        label
                        class="mr-2 font-weight-bold"
                      >
                        {{ line.walletType.toUpperCase() }}
                      </v-chip>
                      <span class="text-subtitle-2 font-weight-bold">
                        {{ line.walletLabel }} #{{ line.walletId }}
                      </span>
                    </div>
                    <div class="d-flex flex-wrap align-center ga-2">
                      <span class="text-caption text-medium-emphasis">before</span>
                      <span class="wallet-debug-value" :class="line.walletAmountToneClass">
                        {{ line.before }}
                      </span>
                      <span class="text-caption font-weight-bold">{{ line.currency }}</span>
                      <span class="text-caption text-medium-emphasis">→</span>
                      <span class="text-caption text-medium-emphasis">after</span>
                      <span class="wallet-debug-value" :class="line.walletAmountToneClass">
                        {{ line.after }}
                      </span>
                      <span class="text-caption font-weight-bold">{{ line.currency }}</span>
                      <span>
                        <v-chip size="x-small" variant="tonal" color="primary" class="ml-1 font-weight-bold">
                          {{ line.pct }}%
                        </v-chip>
                      </span>
                    </div>
                  </span>
                </div>
              </td>
              <td>
                <v-chip
                  size="small"
                  variant="tonal"
                  :color="event.event_effect === 'debit' ? 'error' : 'success'"
                >
                  {{
                    event.event_type === "bet_debit"
                      ? "debit split"
                      : event.event_effect || "-"
                  }}
                </v-chip>
              </td>
              <td>
                <div class="font-weight-bold">
                  {{ resolvePrimaryAmount(event) }} {{ event.currency_code || "" }}
                </div>
                <div
                  v-if="shouldShowSecondaryAmount(event)"
                  class="text-caption text-medium-emphasis"
                >
                  Grant amount: {{ event.amount_ui ?? event.amount_base ?? "-" }}
                  {{ event.currency_code || "" }}
                </div>
              </td>
              <td>
                {{ event.reference_type || "-" }} / {{ event.reference_id || "-" }}
              </td>
              <td>{{ event.event_description || event.message || "-" }}</td>
              <td>{{ event.created_at || "-" }}</td>
            </tr>
            <tr v-if="!grantEvents.length">
              <td colspan="7" class="text-center text-medium-emphasis py-4">
                No events found.
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.wallet-debug-row {
  border-left: 4px solid #1976d2;
  background: #e8f0fe;
}

.wallet-debug-value {
  font-size: 0.95rem;
  font-weight: 800;
  color: #0d47a1;
}

.wallet-real {
  border-left-color: #00897b !important;
  background: #e0f2f1;
}

.wallet-bonus {
  border-left-color: #3949ab !important;
  background: #e8eaf6;
}

.wallet-unknown {
  border-left-color: #757575 !important;
  background: #eceff1;
}

.wallet-amount-real {
  color: #004d40 !important;
}

.wallet-amount-bonus {
  color: #1a237e !important;
}

.wallet-amount-unknown {
  color: #37474f !important;
}

.grant-events-table .v-table__wrapper {
  max-height: 65vh;
  overflow-y: auto;
}

.grant-events-table thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: rgb(var(--v-theme-surface));
}

.grant-events-dialog .v-card {
  max-height: 90vh;
}
</style>
