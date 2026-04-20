<script setup lang="ts">
const props = defineProps<{
  ui: string | number;
  base?: string | number | null;
  decimals?: number | null;
  currency?: string | null;

  precision?: number;
  showPlus?: boolean;
  colored?: boolean;
  monospace?: boolean;
  compact?: boolean;
  suffixCurrency?: boolean;
}>();

const precision = computed(() => props.precision ?? 2);

function toNumber(v: any): number {
  const n = Number(v ?? 0);
  return Number.isNaN(n) ? 0 : n;
}

const uiNum = computed(() => toNumber(props.ui));
const isNeg = computed(() => uiNum.value < 0);
const isPos = computed(() => uiNum.value > 0);

const textClass = computed(() => {
  const classes: string[] = [];
  if (props.monospace) classes.push("font-mono");
  if (props.compact) classes.push("text-caption");

  if (props.colored !== false) {
    if (isNeg.value) classes.push("text-error");
    else if (isPos.value) classes.push("text-success");
  }

  return classes.join(" ");
});

const formatted = computed(() => {
  const v = uiNum.value;
  const abs = Math.abs(v);

  const s = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: precision.value,
    maximumFractionDigits: precision.value,
  }).format(abs);

  const sign = isNeg.value ? "-" : props.showPlus && isPos.value ? "+" : "";
  const cur =
    props.suffixCurrency && props.currency ? ` ${props.currency}` : "";
  return `${sign}${s}${cur}`;
});

const tooltip = computed(() => {
  const parts: string[] = [];
  parts.push(`UI: ${props.ui}${props.currency ? ` ${props.currency}` : ""}`);

  if (props.base != null && props.base !== "") {
    parts.push(`Base: ${props.base}`);
  }
  if (props.decimals != null) {
    parts.push(`Decimals: ${props.decimals}`);
  }

  return parts.join("\n");
});

const showTooltip = computed(() => {
  return props.base != null || props.decimals != null;
});
</script>

<template>
  <v-tooltip v-if="showTooltip" :text="tooltip" location="top">
    <template #activator="{ props: tp }">
      <span v-bind="tp" :class="textClass">
        {{ formatted }}
      </span>
    </template>
  </v-tooltip>

  <span v-else :class="textClass">
    {{ formatted }}
  </span>
</template>

<style scoped>
.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
    "Liberation Mono", "Courier New", monospace;
}
</style>
