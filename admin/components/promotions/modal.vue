<script setup lang="ts">
//composables
const { required } = useRules();

//models
const loading = ref<boolean>(false);
const form = ref({
  title: "",
  active: 1,
});

//emitters
const emitters = defineEmits(["promotions:close-modal", "promotions:save"]);
const save = () => emitters("promotions:save", form.value);
</script>
<template>
  <v-card title="Save Promotion">
    <v-card-text>
      <v-text-field
        v-model="form.title"
        label="Name"
        required
        :rules="[required]"
        density="compact"
      ></v-text-field>
      <v-checkbox
        v-model="form.active"
        hide-details
        density="compact"
        :false-value="0"
        :true-value="1"
        label="Active"
      />
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
        @click="emitters('promotions:close-modal')"
        color="red"
        density="compact"
        variant="flat"
        :disabled="loading"
        >Close</v-btn
      >
    </v-card-actions>
  </v-card>
</template>
