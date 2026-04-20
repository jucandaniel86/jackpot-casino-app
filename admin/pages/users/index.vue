<script setup lang="ts">
import UsersList from "~/components/users/index.vue";

useSeoMeta({
  title: "Users",
  ogTitle: "Users",
});

definePageMeta({
  middleware: "auth",
});
</script>
<template>
  <UsersList />
</template>
