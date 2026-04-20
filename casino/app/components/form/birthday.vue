<script setup lang="ts">
//models
const menu = ref<boolean>(false)
const date = ref<string>('')
</script>
<template>
  <v-menu
    v-model="menu"
    :close-on-content-click="false"
    transition="scale-transition"
    offset-y
    full-width
    min-width="290px"
    location="bottom center"
  >
    <template #activator="{ props }">
      <v-text-field
        v-model="date"
        append-inner-icon="mdi-calendar"
        readonly
        :props
        density="compact"
        color="primary"
        placeholder="dd/mm/yyyy"
      />
    </template>
    <v-date-picker
      v-model="date"
      clearable
      hide-details="auto"
      color="primary"
      label="Date"
      view-mode="year"
      :min="'1950-01-01'"
    />
  </v-menu>
</template>
