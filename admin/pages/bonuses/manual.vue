<script setup lang="ts">
useHead({ title: "Manual Bonus Grants" });

definePageMeta({
  middleware: "auth",
});
</script>

<template>
  <BonusesManualGrantForm />
</template>
