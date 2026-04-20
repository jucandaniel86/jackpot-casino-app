<script setup lang="ts">
//composables
const { required } = useRules();

//models
const loading = ref<boolean>(false);
const form = ref({
  name: "",
});

//emitters
const emitters = defineEmits(["tags:close-modal", "tags:save"]);
const save = () => emitters("tags:save", form.value);
</script>
<template>
  <v-card title="Save TAG">
    <v-card-text>
      <v-text-field
        v-model="form.name"
        label="Name"
        required
        :rules="[required]"
        density="compact"
      ></v-text-field>
    </v-card-text>
    <v-card-actions class="d-flex justify-space-between align-center">
      <v-btn
        :disabled="loading"
        @click="save"
        density="compact"
        variant="flat"
        color="blue"
        >Save</v-btn
      >
      <v-btn
        @click="emitters('tags:close-modal')"
        color="red"
        density="compact"
        variant="flat"
        :disabled="loading"
        >Close</v-btn
      >
    </v-card-actions>
  </v-card>
</template>
