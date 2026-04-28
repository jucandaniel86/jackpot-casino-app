<script setup lang="ts">
const props = defineProps<{
  currency: string; // "PEP"
  period: [string, string]; // [from, to] ISO strings from SelectDatetimepicker
}>();

defineEmits<{
  (e: "open", status: "pending" | "failed"): void;
}>();

const metrics = ref({
  pending: { count: 0, amount_ui: "0" },
  failed: { count: 0, amount_ui: "0" },
});
const symbol = ref(useDefaultCurrency());
const pendingLoading = ref(false);

const periodLabel = computed(() => {
  const [from, to] = props.period;
  return `${new Date(from).toLocaleDateString()} → ${new Date(
    to,
  ).toLocaleDateString()}`;
});

function fmt(v: string) {
  const n = Number(v || "0");
  return n.toLocaleString(undefined, { maximumFractionDigits: 2 });
}

async function refresh() {
  pendingLoading.value = true;
  try {
    const { data } = await useAPIFetch("/stats/sweeps", {
      currency_code: props.currency,
      from: props.period[0],
      to: props.period[1],
    });

    const res: any = data?.result;
    metrics.value = res.metrics;
    symbol.value = res.filters.currency_symbol;
  } finally {
    pendingLoading.value = false;
  }
}

watch(() => [props.currency, props.period], refresh, {
  deep: true,
  immediate: true,
});
</script>

<template>
  <v-card class="pa-4">
    <div class="d-flex align-center justify-space-between">
      <div>
        <div class="text-subtitle-1 font-weight-medium">Sweeps</div>
        <div class="text-caption text-medium-emphasis">
          {{ periodLabel }} • {{ symbol }}
        </div>
      </div>

      <v-btn
        variant="text"
        icon="mdi-refresh"
        @click="refresh"
        :loading="pendingLoading"
      />
    </div>

    <v-divider class="my-3" />

    <div class="d-flex flex-wrap ga-3">
      <v-chip color="warning" variant="tonal" class="px-3">
        Pending: <b class="ml-2">{{ metrics.pending.count }}</b>
        <span class="ml-2 text-medium-emphasis"
          >({{ fmt(metrics.pending.amount_ui) }} {{ symbol }})</span
        >
      </v-chip>

      <v-chip color="error" variant="tonal" class="px-3">
        Failed: <b class="ml-2">{{ metrics.failed.count }}</b>
        <span class="ml-2 text-medium-emphasis"
          >({{ fmt(metrics.failed.amount_ui) }} {{ symbol }})</span
        >
      </v-chip>
    </div>

    <div class="mt-3 d-flex ga-2">
      <v-btn size="small" variant="tonal" @click="$emit('open', 'pending')">
        View pending
      </v-btn>
      <v-btn size="small" variant="tonal" @click="$emit('open', 'failed')">
        View failed
      </v-btn>
    </div>
  </v-card>
</template>
