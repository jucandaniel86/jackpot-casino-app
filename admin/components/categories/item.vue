<script setup lang="ts">
import ListItem from "./item.vue";
import type { CategoryType } from "./types";

type CategoryItemT = {
  item: CategoryType;
  level: number;
  hasActions: boolean;
};

const props = defineProps<CategoryItemT>();
const currentLevel = props.level + 1;

//emitters
const emitters = defineEmits(["onAdd", "onEdit", "onDelete"]);

//methods
const handleEdit = () => emitters("onEdit", props.item);
const handleDelete = () => emitters("onDelete", props.item);
</script>

<template>
  <tr>
    <td :class="`pl-${currentLevel * 2}`">{{ item.name }}</td>
    <td v-if="props.hasActions">
      <div class="d-flex ga-2 align-center justify-center">
        <v-btn color="blue" size="small" @click.prevent="handleEdit">
          <v-icon icon="mdi-file-edit-outline" variants="flat" />
          Edit
        </v-btn>
        <v-btn color="red" size="small" @click.prevent="handleDelete">
          <v-icon icon="mdi-delete-outline" variants="flat" />
          Delete
        </v-btn>
      </div>
    </td>
  </tr>
  <ListItem
    v-for="child in item.descendants"
    :key="child.id"
    :item="child"
    :level="currentLevel"
    :has-actions="props.hasActions"
    @onDelete="handleDelete"
    @onEdit="handleEdit"
  />
</template>
