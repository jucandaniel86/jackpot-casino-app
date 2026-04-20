<script setup lang="ts">
import { APP_BREADCRUMBS } from "~/app/breadcrumbs";

const title = "Page Administration";

useHead({
  title,
});
</script>
<template>
  <SharedBreadcrumb :items="APP_BREADCRUMBS.PAGES" :title="title" />
  <Pages />
</template>
