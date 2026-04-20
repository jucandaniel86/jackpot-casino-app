<script setup lang="ts">
type RiskLevel = "ok" | "low" | "medium" | "high" | "critical";

const props = defineProps<{
  score: number;
  reasons?: string[];
  compact?: boolean;
  showScore?: boolean;
}>();

const level = computed<RiskLevel>(() => {
  if (props.score >= 95) return "critical";
  if (props.score >= 80) return "high";
  if (props.score >= 50) return "medium";
  if (props.score >= 30) return "low";
  return "ok";
});

const config = computed(() => {
  switch (level.value) {
    case "critical":
      return { color: "error", icon: "mdi-alert-octagon", label: "CRITICAL" };
    case "high":
      return { color: "error", icon: "mdi-alert", label: "HIGH" };
    case "medium":
      return { color: "warning", icon: "mdi-alert-circle", label: "MED" };
    case "low":
      return { color: "info", icon: "mdi-shield-outline", label: "LOW" };
    default:
      return { color: "success", icon: "mdi-check-circle", label: "OK" };
  }
});

const tooltipText = computed(() => {
  if (!props.reasons?.length) return `Risk score: ${props.score}`;
  return (
    `Risk score: ${props.score}\n` +
    props.reasons.map((r) => `• ${r}`).join("\n")
  );
});
</script>

<template>
  <v-tooltip :text="tooltipText" location="top">
    <template #activator="{ props: tp }">
      <!-- Compact badge (pentru tabel) -->
      <v-chip
        v-if="compact"
        v-bind="tp"
        :color="config.color"
        variant="tonal"
        size="small"
        class="font-weight-medium"
      >
        <v-icon :icon="config.icon" start size="16" />
        <span>{{ config.label }}</span>
        <span v-if="showScore !== false" class="ml-2">({{ score }})</span>
      </v-chip>

      <!-- Normal badge (pentru profile / header) -->
      <v-chip
        v-else
        v-bind="tp"
        :color="config.color"
        variant="tonal"
        size="default"
        class="font-weight-medium"
      >
        <v-icon :icon="config.icon" start />
        <span>{{ config.label }}</span>
        <span v-if="showScore !== false" class="ml-2">Score: {{ score }}</span>
      </v-chip>
    </template>
  </v-tooltip>
</template>
