<script setup lang="ts">
import { APP_BREADCRUMBS } from "~/app/breadcrumbs";

const title = "Casino Games";

useHead({
  title,
});
</script>
<template>
  <SharedBreadcrumb :items="APP_BREADCRUMBS.GAMES" :title="title" />
  <Games />
</template>
