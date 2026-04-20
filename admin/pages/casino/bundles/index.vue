<script setup lang="ts">
import { APP_BREADCRUMBS } from "~/app/breadcrumbs";

const title = "Bundles";

useHead({
  title,
});
</script>

<template>
  <SharedBreadcrumb :items="APP_BREADCRUMBS.BUNDLES" :title="title" />
  <Bundles />
</template>

