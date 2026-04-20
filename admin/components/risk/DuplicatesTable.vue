<script setup lang="ts">
type Row = {
  transaction_type: string;
  operator_transaction_id: string;
  count: number;
  first_seen: string;
  last_seen: string;
};
type Payload = { duplicates: Row[] };
const props = defineProps<{ data: Payload | null }>();
</script>

<template>
  <div v-if="!data">
    <v-skeleton-loader type="table" />
  </div>

  <v-data-table
    v-else
    :items="data.duplicates"
    density="compact"
    class="elevation-0"
    :headers="[
      { title: 'Type', key: 'transaction_type' },
      { title: 'Operator Tx ID', key: 'operator_transaction_id' },
      { title: 'Count', key: 'count', align: 'end' },
      { title: 'First', key: 'first_seen' },
      { title: 'Last', key: 'last_seen' },
    ]"
  >
    <template #item.operator_transaction_id="{ item }">
      <span class="font-mono text-caption">{{
        item.operator_transaction_id
      }}</span>
    </template>

    <template #item.count="{ item }">
      <span :class="item.count > 3 ? 'text-error font-weight-bold' : ''">
        {{ item.count }}
      </span>
    </template>
  </v-data-table>
</template>
