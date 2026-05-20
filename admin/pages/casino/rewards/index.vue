<script setup lang="ts">
import { APP_BREADCRUMBS } from "~/app/breadcrumbs";

const title = "Rewards Administration";

useHead({
  title,
});
</script>

<template>
  <SharedBreadcrumb :items="APP_BREADCRUMBS.REWARDS" :title="title" />
  <Rewards />
</template>
