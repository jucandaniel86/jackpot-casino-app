<script setup lang="ts">
import moment from "moment";

type BiggestWin = {
  username?: string;
  game?: string;
  amount?: number | string;
  currency?: string;
  at?: string;
};

type TodayStatsPayload = {
  active_players_today?: number | string;
  biggest_win_today?: BiggestWin;
  deposit_count_today?: number | string;
  deposits_today?:
    | number
    | string
    | { amount?: number | string; currency?: string };
  ggr_today?: number | string;
  withdraw_count_today?: number | string;
  withdrawals_today?:
    | number
    | string
    | { amount?: number | string; currency?: string };
};

const props = defineProps<{
  stats: TodayStatsPayload;
  range?: { from?: string | Date; to?: string | Date };
  currency?: string;
  loading?: boolean;
}>();

const emit = defineEmits<{
  (event: "date-change", value: string): void;
}>();

const dateMenu = ref(false);
const selectedDate = ref<Date | null>(null);

const formatNumber = (value: number | string | undefined) => {
  if (value === null || value === undefined || value === "") return "-";
  const num =
    typeof value === "string" ? Number(value.replace(/,/g, "")) : Number(value);
  if (!Number.isFinite(num)) return String(value);
  return num.toLocaleString();
};

const formatMoney = (
  value?: number | string | { amount?: number | string; currency?: string },
) => {
  if (value === null || value === undefined || value === "") return "-";
  if (typeof value === "object") {
    const amount = formatNumber(value.amount);
    if (value.currency) return `${amount} ${value.currency}`;
    return props.currency ? `${amount} ${props.currency}` : amount;
  }
  const amount = formatNumber(value);
  return props.currency ? `${amount} ${props.currency}` : amount;
};

const formatDate = (value?: string) => {
  if (!value) return "-";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString();
};

const formatRangeDate = (value: string | Date) => {
  if (value instanceof Date) return value.toLocaleDateString();
  const parsed = moment(value);
  return parsed.isValid() ? parsed.format("LL") : String(value);
};

const normalizeDate = (value: string | Date) => {
  const parsed = moment(value);
  if (!parsed.isValid()) return null;
  return parsed.toDate();
};

const rangeLabel = computed(() => {
  const fromValue = props.range?.from ?? selectedDate.value;
  const toValue = props.range?.to ?? selectedDate.value;
  if (!fromValue || !toValue) return "Today";
  const fromLabel = formatRangeDate(fromValue);
  const toLabel = formatRangeDate(toValue);
  if (fromLabel === toLabel) return fromLabel;
  return `${fromLabel} - ${toLabel}`;
});

const biggestWin = computed(() => props.stats.biggest_win_today || {});

watch(
  () => props.range?.from,
  (value) => {
    if (!value) return;
    selectedDate.value = normalizeDate(value);
  },
  { immediate: true },
);

const handleDateChange = (value: Date | null) => {
  if (!value) return;
  selectedDate.value = value;
  dateMenu.value = false;
  emit("date-change", moment(value).format("YYYY-MM-DD"));
};
</script>

<template>
  <v-card>
    <v-card-title class="d-flex align-center justify-space-between">
      <div class="text-h6">Stats</div>
      <div class="d-flex flex-column align-end ga-1">
        <v-chip
          size="small"
          color="indigo"
          variant="tonal"
          class="cursor-pointer"
          @click="dateMenu = true"
        >
          {{ rangeLabel }}
        </v-chip>
        <v-dialog v-model="dateMenu" width="auto">
          <v-card>
            <v-card-text class="pa-0">
              <v-date-picker
                v-model="selectedDate"
                :max="new Date()"
                color="indigo"
                @update:modelValue="handleDateChange"
              />
            </v-card-text>
          </v-card>
        </v-dialog>
        <div class="text-caption text-medium-emphasis">
          Click the date to change the range
        </div>
      </div>
    </v-card-title>

    <v-card-text>
      <v-progress-linear v-if="loading" indeterminate class="mb-4" />
      <v-row dense class="flex-wrap">
        <v-col cols="12" sm="6" md="4" lg="3">
          <v-sheet class="pa-4" rounded border>
            <div
              class="d-flex align-center ga-2 text-caption text-medium-emphasis"
            >
              <v-icon size="18">mdi-account-group</v-icon>
              <span>Active Players Today</span>
            </div>
            <div class="text-h4">
              {{ formatNumber(stats.active_players_today) }}
            </div>
          </v-sheet>
        </v-col>

        <v-col cols="12" sm="6" md="4" lg="3">
          <v-sheet class="pa-4" rounded border>
            <div
              class="d-flex align-center ga-2 text-caption text-medium-emphasis"
            >
              <v-icon size="18">mdi-counter</v-icon>
              <span>Deposit Count Today</span>
            </div>
            <div class="text-h4">
              {{ formatNumber(stats.deposit_count_today) }}
            </div>
          </v-sheet>
        </v-col>

        <v-col cols="12" sm="6" md="4" lg="3">
          <v-sheet class="pa-4" rounded border>
            <div
              class="d-flex align-center ga-2 text-caption text-medium-emphasis"
            >
              <v-icon size="18">mdi-cash-minus</v-icon>
              <span>Withdraw Count Today</span>
            </div>
            <div class="text-h4">
              {{ formatNumber(stats.withdraw_count_today) }}
            </div>
          </v-sheet>
        </v-col>

        <v-col cols="12" sm="6" md="4" lg="3">
          <v-sheet class="pa-4" rounded border>
            <div
              class="d-flex align-center ga-2 text-caption text-medium-emphasis"
            >
              <v-icon size="18">mdi-cash-plus</v-icon>
              <span>Deposits Today</span>
            </div>
            <div class="text-h4">{{ formatMoney(stats.deposits_today) }}</div>
          </v-sheet>
        </v-col>

        <v-col cols="12" sm="6" md="4" lg="3">
          <v-sheet class="pa-4" rounded border>
            <div
              class="d-flex align-center ga-2 text-caption text-medium-emphasis"
            >
              <v-icon size="18">mdi-cash</v-icon>
              <span>Withdrawals Today</span>
            </div>
            <div class="text-h4">
              {{ formatMoney(stats.withdrawals_today) }}
            </div>
          </v-sheet>
        </v-col>

        <v-col cols="12" sm="6" md="4" lg="3">
          <v-sheet class="pa-4" rounded border>
            <div
              class="d-flex align-center ga-2 text-caption text-medium-emphasis"
            >
              <v-icon size="18">mdi-cash-multiple</v-icon>
              <span>GGR Today</span>
            </div>
            <div class="text-h4">{{ formatMoney(stats.ggr_today) }}</div>
          </v-sheet>
        </v-col>

        <v-col cols="12" sm="12" md="8" lg="6">
          <v-sheet class="pa-4" rounded border>
            <div
              class="d-flex align-center ga-2 text-caption text-medium-emphasis"
            >
              <v-icon size="18">mdi-trophy</v-icon>
              <span>Biggest Win Today</span>
              <v-chip size="x-small" variant="outlined" class="ml-auto">
                {{ formatDate(biggestWin.at) }}
              </v-chip>
            </div>
            <div class="text-subtitle-1">
              {{ biggestWin.username || "-" }}
            </div>
            <div class="text-body-2 text-medium-emphasis">
              {{ biggestWin.game || "-" }}
            </div>
            <div class="d-flex align-center ga-2 mt-2">
              <div class="text-h4">
                {{
                  formatMoney({
                    amount: biggestWin.amount,
                    currency: biggestWin.currency,
                  })
                }}
              </div>
            </div>
          </v-sheet>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>
