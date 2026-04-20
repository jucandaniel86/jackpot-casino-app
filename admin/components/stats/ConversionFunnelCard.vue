<script setup lang="ts">
import moment from "moment";
type FunnelStep = {
  key: string;
  label: string;
  count: number;
  step_percent: number; // % vs step anterior
  overall_percent: number; // % vs registered
};

type Payload = {
  range: { from: string; to: string };
  filters?: Record<string, any>;
  steps: FunnelStep[];
};

const props = defineProps<{
  data: Payload | null;
  title?: string;
  currencyCode?: string | null;
}>();

const titleText = computed(() => props.title ?? "Conversion Funnel");
const rangeLabel = computed(() => {
  if (!props.data?.range?.from || !props.data?.range?.to) return "";
  const fromLabel = moment(props.data.range.from).format("LL");
  const toLabel = moment(props.data.range.to).format("LL");
  return `${fromLabel} → ${toLabel}`;
});

const maxCount = computed(() => {
  if (!props.data?.steps?.length) return 0;
  return Math.max(...props.data.steps.map((s) => Number(s.count || 0)));
});

function barWidth(count: number) {
  const max = maxCount.value;
  if (!max) return 0;
  return Math.max(2, Math.round((count / max) * 100)); // min 2% să se vadă
}

function colorByIndex(i: number) {
  // primele 2-3 pași verde, apoi warning când drop mare
  if (i === 0) return "primary";
  if (i === 1) return "success";
  if (i === 2) return "success";
  if (i === 3) return "warning";
  return "error";
}

function formatNumber(n: number) {
  return new Intl.NumberFormat().format(n);
}

function pct(n: number) {
  return `${Number(n || 0).toFixed(2)}%`;
}
</script>

<template>
  <v-card>
    <v-card-title class="d-flex align-center justify-space-between">
      <div class="d-flex flex-column">
        <div class="text-subtitle-1 font-weight-bold">{{ titleText }}</div>
        <div v-if="rangeLabel" class="text-caption text-medium-emphasis">
          {{ rangeLabel }}
          <span v-if="currencyCode" class="ml-2">• {{ currencyCode }}</span>
        </div>
      </div>
      <v-icon icon="mdi-filter-variant" class="text-medium-emphasis" />
    </v-card-title>

    <v-divider />

    <v-card-text>
      <div v-if="!data">
        <v-skeleton-loader
          type="list-item-three-line, list-item-three-line, list-item-three-line"
        />
      </div>

      <div v-else class="d-flex flex-column ga-3">
        <div
          v-for="(s, i) in data.steps"
          :key="s.key"
          class="d-flex align-center ga-3"
        >
          <!-- Left: label -->
          <div style="min-width: 170px">
            <div class="font-weight-medium">{{ s.label }}</div>
            <div class="text-caption text-medium-emphasis">
              Step: {{ pct(s.step_percent) }} • Overall:
              {{ pct(s.overall_percent) }}
            </div>
          </div>

          <!-- Middle: bar -->
          <div class="flex-grow-1">
            <v-progress-linear
              :model-value="barWidth(s.count)"
              :color="colorByIndex(i)"
              height="14"
              rounded
            >
              <template #default>
                <div class="px-2 text-caption font-weight-medium">
                  {{ formatNumber(s.count) }}
                </div>
              </template>
            </v-progress-linear>
          </div>

          <!-- Right: count -->
          <div class="text-right" style="min-width: 90px">
            <div class="font-weight-bold">{{ formatNumber(s.count) }}</div>
            <div class="text-caption text-medium-emphasis">users</div>
          </div>
        </div>

        <!-- Optional: Big drop highlight -->
        <v-alert
          v-if="data.steps.length >= 2"
          type="warning"
          variant="tonal"
          class="mt-4"
        >
          <div class="font-weight-medium">Quick insight</div>
          <div class="text-body-2">
            Biggest drop is usually between
            <strong>{{ data.steps[0].label }}</strong> →
            <strong>{{ data.steps[1].label }}</strong> if your deposit
            conversion is low. Check payment UX & onboarding.
          </div>
        </v-alert>
      </div>
    </v-card-text>
  </v-card>
</template>
