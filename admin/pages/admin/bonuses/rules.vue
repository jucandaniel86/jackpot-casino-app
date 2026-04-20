<script setup lang="ts">
useHead({ title: "Bonus Rules" });

definePageMeta({
  middleware: "auth",
});
</script>

<template>
  <BonusesRulesTable />
</template>
