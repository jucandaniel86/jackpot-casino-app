<script setup lang="ts">
type TreasuryDistributionItem = {
  label: string;
  amount: number;
  color?: string;
};

const props = withDefaults(
  defineProps<{
    items: TreasuryDistributionItem[];
    label?: string;
    error?: string;
  }>(),
  {
    items: () => [],
    label: "",
  }
);

const series = computed(() =>
  props.items.map((item) => {
    const raw = (item as any).amount ?? (item as any).value ?? 0;
    const num =
      typeof raw === "string" ? Number(raw.replace(/,/g, "")) : Number(raw);
    return Number.isFinite(num) ? num : 0;
  })
);
const labels = computed(() => props.items.map((item) => item.label));
const hasData = computed(() => series.value.some((value) => value > 0));

const hashString = (value: string) => {
  let hash = 0;
  for (let i = 0; i < value.length; i += 1) {
    hash = (hash << 5) - hash + value.charCodeAt(i);
    hash |= 0;
  }
  return Math.abs(hash);
};

const randomColorFrom = (seed: string, index: number) => {
  const hash = hashString(`${seed}-${index}`);
  const hue = hash % 360;
  const saturation = 60 + (hash % 20);
  const lightness = 45 + (hash % 10);
  return `hsl(${hue}, ${saturation}%, ${lightness}%)`;
};

const colors = computed(() => {
  const base = props.items.map((item) => item.color).filter(Boolean) as string[];
  if (base.length === props.items.length) {
    return base;
  }

  return props.items.map(
    (item, index) =>
      item.color || randomColorFrom(item.label || "slice", index)
  );
});

const chartOptions = computed(() => ({
  chart: {
    type: "pie",
    toolbar: { show: false },
  },
  labels: labels.value,
  colors: colors.value.length ? colors.value : undefined,
  legend: {
    position: "bottom",
  },
  dataLabels: {
    enabled: true,
    formatter: (val: number) => `${val.toFixed(1)}%`,
  },
  tooltip: {
    y: {
      formatter: (val: number) => val.toLocaleString(),
    },
  },
  stroke: {
    colors: ["#fff"],
  },
}));
</script>

<template>
  <v-card>
    <v-card-title v-if="label">{{ label }}</v-card-title>

    <v-alert v-if="error" type="error" variant="tonal">{{
      props.error
    }}</v-alert>

    <v-card-text v-else>
      <div
        v-if="!items.length || !hasData"
        class="text-body-2 text-medium-emphasis"
      >
        No distribution data.
      </div>
      <ClientOnly v-else>
        <apexchart
          type="pie"
          height="280"
          :options="chartOptions"
          :series="series"
        />
      </ClientOnly>
    </v-card-text>
  </v-card>
</template>
