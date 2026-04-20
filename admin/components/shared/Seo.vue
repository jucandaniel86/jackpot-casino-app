<script setup lang="ts">
type SharedSeoComponentT = {
  title: string;
  description: string;
  displayTitle: string;
  displayDescription: string;
  indexed: number;
};

let form = defineModel<SharedSeoComponentT>({
  default: {
    title: "",
    description: "",
    displayTitle: "",
    displayDescription: "",
    indexed: 1,
  },
});
</script>
<template>
  <v-card density="compact" elevation="0">
    <v-card-title>SEO Options</v-card-title>
    <v-card-text>
      <v-text-field
        v-model="form.title"
        hide-details
        density="compact"
        label="Title"
      />
      <v-text-field
        v-model="form.displayTitle"
        hide-details
        density="compact"
        label="Display Title"
      />
      <v-textarea
        v-model="form.description"
        hide-details
        density="compact"
        label="Description"
      />

      <v-textarea
        v-model="form.displayDescription"
        hide-details
        density="compact"
        label="Display Description"
      />

      <v-checkbox
        v-model="form.indexed"
        :true-value="1"
        :false-value="0"
        label="Indexed"
        hide-details
      />
    </v-card-text>
  </v-card>
</template>
