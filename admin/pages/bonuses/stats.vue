<script setup lang="ts">
useHead({ title: "Bonus Stats" });

definePageMeta({
  middleware: "auth",
});
</script>

<template>
  <BonusesStatsCards />
</template>
