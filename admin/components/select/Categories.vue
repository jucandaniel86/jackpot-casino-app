<script setup lang="ts">
import type { CategoryType } from "../categories/types";
import { VTreeview } from "vuetify/labs/VTreeview";

type SelectCategoriesT = {
  multiple?: boolean;
};
const props = withDefaults(defineProps<SelectCategoriesT>(), {
  multiple: true,
});

//composables
const { flatten } = useUtils();

//models
const dialog = ref(false);
const items = ref<CategoryType[]>([]);
const model = defineModel<any>();
const search = ref("");

const getCategories = async () => {
  const { data } = await useAPIFetch("/categories/list");
  if (data) {
    items.value = data.data;
  }
};

const onClickClose = (selection: any) => {
  model.value = model.value?.filter((item: any) => item !== selection.id);
};

const filter = (value: any, search: string, item: any) => {
  return value.toLowerCase().includes(search.toLowerCase());
};

const selectedItems = computed(() => {
  let desc: any[] = [];
  flatten(desc, items.value);
  return desc.filter((item) => model.value?.indexOf(item.id as any) !== -1);
});

//onMounted
onMounted(() => {
  getCategories();
});
</script>
<template>
  <div class="pt-1 pb-1">
    <v-dialog v-model="dialog" width="500">
      <template #activator="{ props: activatorProps }">
        <div class="d-flex ga-2 align-center">
          <v-btn color="primary" v-bind="activatorProps">
            <span> Choose a category </span>
          </v-btn>
          <div
            v-if="model && model.length > 0"
            class="d-flex flex-wrap ga-1 align-center"
          >
            <span>Selected Categories: </span>
            <v-scroll-x-transition group hide-on-leave>
              <v-chip
                v-for="selection in selectedItems"
                :key="selection.id"
                :text="selection.name"
                color="grey"
                size="small"
                border
                closable
                label
                @click:close="onClickClose(selection)"
              ></v-chip>
            </v-scroll-x-transition>
          </div>
        </div>
      </template>

      <v-card>
        <v-sheet class="pa-4" color="surface-variant">
          <v-text-field
            v-model="search"
            label="Search Category"
            density="compact"
            clearable
            flat
            hide-details
            variant="solo"
          />
        </v-sheet>

        <v-card-text class="pa-0">
          <v-treeview
            v-model:selected="model"
            :search="search"
            :custom-filter="filter"
            :items="items"
            activatable
            item-value="id"
            item-title="name"
            collapse-icon="mdi-chevron-down"
            density="compact"
            item-children="descendants"
            expand-icon="mdi-chevron-right"
            :select-strategy="
              props.multiple ? 'independent' : 'single-independent'
            "
            fluid
            selectable
            class="flex-1-0"
          >
          </v-treeview>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-btn color="blue" flat @click="dialog = false"> Close </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
