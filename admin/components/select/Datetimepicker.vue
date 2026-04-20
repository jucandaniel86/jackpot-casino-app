<script setup lang="ts">
import moment from 'moment';

let fromDate = moment().subtract(1, "week");
let toDate = moment();

const date = ref([fromDate, toDate]);
</script>
<template>
	<VueDateTimePicker v-model="date" :range="{ partialRange: false }" :teleport="true" />
</template>