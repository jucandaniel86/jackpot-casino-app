<script setup lang="ts">
import moment from 'moment'

const model = defineModel<{ from: string; to: string }>({
  default: {
    from: '',
    to: '',
  },
})

const handleFromValue = (value: string | unknown) => {
  if (value) {
    model.value = {
      ...model.value,
      from: moment(value).format('YYYY-MM-DD'),
    }
  }
}
const handleToValue = (value: string | unknown) => {
  if (value) {
    model.value = {
      ...model.value,
      to: moment(value).format('YYYY-MM-DD'),
    }
  }
}
</script>
<template>
  <v-row>
    <v-col cols="6">
      <div class="text-subtitle-1 text-white">From</div>
      <v-date-input
        density="compact"
        color="primary"
        append-inner-icon="mdi-calendar"
        prepend-icon=""
        class="activity-select"
        @update:model-value="handleFromValue"
      />
    </v-col>
    <v-col cols="6">
      <div class="text-subtitle-1 text-white">To</div>
      <v-date-input
        density="compact"
        color="primary"
        append-inner-icon="mdi-calendar"
        prepend-icon=""
        class="activity-select"
        @update:model-value="handleToValue"
      />
    </v-col>
  </v-row>
</template>
