<script setup lang="ts">
import { type CategoryType, CategoryViewType } from "./types";

type CategoryListT = {
  hasSearch?: boolean;
  viewType: CategoryViewType;
  loading?: boolean;
  items: CategoryType[] | undefined;
  multiselect?: boolean;
};
//props
const props = withDefaults(defineProps<CategoryListT>(), {
  hasSearch: true,
  loading: false,
  multiselect: false,
});

//models
const search = ref("");
const selected = ref<CategoryType>();

//emitters
const emitters = defineEmits(["onEdit", "onDelete", "onAdd", "onSearch"]);

//methods
const handleEdit = (_category: CategoryType) => emitters("onEdit", _category);
const handleDelete = (_category: CategoryType) =>
  emitters("onDelete", _category.id);
const handleAdd = (_category?: CategoryType) => {
  return emitters("onAdd");
};

watch(search, () => {
  emitters("onSearch", search.value);
});
</script>
<template>
  <v-card>
    <v-card-title>
      <div
        v-if="props.hasSearch || props.viewType === CategoryViewType.EDITABLE"
        class="d-flex justify-space-between align-center ga-2"
      >
        <v-text-field
          v-model="search"
          v-if="props.hasSearch"
          hide-details
          clearable
          prepend-inner-icon="mdi-magnify"
          placeholder="Search category by name"
          variant="solo"
          density="compact"
        />
        <div
          class="d-flex justify-center align-center"
          v-if="props.viewType === CategoryViewType.EDITABLE"
        >
          <v-btn @click.prevent="handleAdd" variants="flat" color="blue">
            <v-icon icon="mdi-plus" />
            Add
          </v-btn>
        </div>
      </div>
    </v-card-title>
    <v-card-text>
      <v-table density="compact">
        <thead>
          <tr>
            <th width="80%">Name</th>
            <th v-if="props.viewType === CategoryViewType.EDITABLE">Actions</th>
          </tr>
        </thead>
        <tbody>
          <CategoriesItem
            v-for="(category, i) in props.items"
            :key="category.id"
            :item="category"
            :level="0"
            :has-actions="props.viewType === CategoryViewType.EDITABLE"
            @onEdit="handleEdit"
            @onDelete="handleDelete"
            @onAdd="handleAdd"
          />
        </tbody>
      </v-table>
    </v-card-text>
  </v-card>
</template>
