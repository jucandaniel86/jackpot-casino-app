<script setup lang="ts">
import { APP_BREADCRUMBS } from "~/app/breadcrumbs";

const title = "Tournaments";

useHead({
  title,
});
</script>

<template>
  <SharedBreadcrumb :items="APP_BREADCRUMBS.TOURNAMENTS" :title="title" />
  <Tournaments />
</template>

