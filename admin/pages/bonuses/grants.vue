<script setup lang="ts">
useHead({ title: "Bonus Grants" });

definePageMeta({
  middleware: "auth",
});
</script>

<template>
  <BonusesGrantsTable />
</template>
