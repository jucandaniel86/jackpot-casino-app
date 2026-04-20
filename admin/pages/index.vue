<script setup lang="ts">
import { APP_BREADCRUMBS } from "~/app/breadcrumbs";
useHead({
  title: "Dashboard",
});
</script>

<template>
  <SharedBreadcrumb :title="'Dashboard'" :items="APP_BREADCRUMBS.DASHBOARD" />
  <Dashboard />
</template>
