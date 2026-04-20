<script setup lang="ts">
import type { ItemMenuType } from "./Config";

type MenuItemComponent = {
  item: ItemMenuType;
};
const props = defineProps<MenuItemComponent>();

//emits
const emitters = defineEmits(["onEdit", "onDelete"]);

//methods
const handleEdit = () => emitters("onEdit", props.item);
const handleDelete = () => emitters("onDelete", props.item.id);
</script>
<template>
  <v-card class="mb-2" style="cursor: move">
    <v-card-title class="d-flex justify-space-between ga-1 align-center">
      <div class="d-flex justify-space-between ga-1 align-center">
        <v-chip density="compact">{{ props.item.item_order }}</v-chip>
        <span>{{ props.item.title }}</span>
      </div>

      <div class="d-flex justify-space-between ga-1 align-center">
        <v-btn
          @click.prevent="handleEdit"
          color="blue"
          text="Edit"
          variant="tonal"
          prepend-icon="mdi-content-save-outline"
        />
        <v-btn
          @click.prevent="handleDelete"
          color="red"
          text="Delete"
          variant="tonal"
          prepend-icon="mdi-delete-outline"
        />
      </div>
    </v-card-title>
  </v-card>
</template>
