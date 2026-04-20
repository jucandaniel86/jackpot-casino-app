<script setup lang="ts">
import { VTreeview } from "vuetify/labs/VTreeview";
import { type CategoryType } from "./types";
//models
const saveLoading = ref<boolean>(false);
const listLoading = ref<boolean>(false);
const displayModal = ref<boolean>(false);
const selectedCategory = ref<CategoryType | null>();
const items = ref<CategoryType[]>();
const search = ref("");

//composables
const { alertSuccess, axiosErrorAlert, confirmDelete } = useAlert();

//computed
const modalKey = computed(() => {
  if (selectedCategory.value) {
    return selectedCategory.value.id;
  }
  return "add-modal";
});

//methods
const toggleModalDisplay = (payload: any) => {
  displayModal.value = payload;
  if (payload === false) {
    selectedCategory.value = null;
  }
};

const onAdd = () => {
  selectedCategory.value = null;
  toggleModalDisplay(true);
};

const onEdit = (payload: CategoryType) => {
  selectedCategory.value = payload;
  toggleModalDisplay(true);
};

const handleSave = async (payload: any) => {
  saveLoading.value = true;

  const { data, error } = await useApiPostFetch("/categories/save", payload);

  if (error) {
    axiosErrorAlert(error);
    saveLoading.value = false;
    return;
  }

  if (data) {
    alertSuccess(data.message);
    loadList();
    toggleModalDisplay(false);
  }

  saveLoading.value = false;
};

const deleteItem = async (id: number) => {
  confirmDelete(async (_result: any) => {
    const { data, success, error } = await useApiDeleteFetch(
      "/categories/delete",
      {
        id,
      }
    );
    if (success) {
      alertSuccess(data.message);
      loadList();
      return;
    }
    if (error) {
      return axiosErrorAlert(error);
    }
  });
};

const loadList = async (search = "") => {
  listLoading.value = true;

  const { success, data } = await useAPIFetch("/categories/list", {
    search,
  });
  if (success) {
    items.value = data.data;
  }

  listLoading.value = false;
};

watch(search, () => {
  loadList(search.value);
});

onMounted(() => {
  loadList();
});
</script>
<template>
  <div>
    <v-sheet class="pa-4" color="surface-variant">
      <div class="d-flex justify-space-between ga-1">
        <v-text-field
          v-model="search"
          label="Search Category"
          density="compact"
          clearable
          flat
          hide-details
          variant="solo"
        />

        <v-btn @click.prevent="onAdd" variants="flat" color="blue">
          <v-icon icon="mdi-plus" />
          Add
        </v-btn>
      </div>
    </v-sheet>
    <v-treeview
      :items="items"
      item-value="id"
      item-title="name"
      item-children="descendants"
      collapse-icon="mdi-chevron-down"
      density="compact"
      expand-icon="mdi-chevron-right"
      fluid
      selectable
      class="flex-1-0"
      open-all
    >
      <template v-slot:append="{ item }">
        <div class="d-flex ga-2 align-center justify-center">
          <v-btn color="blue" size="small" @click.prevent="onEdit(item)">
            <v-icon icon="mdi-file-edit-outline" variants="flat" />
            Edit
          </v-btn>
          <v-btn color="red" size="small" @click.prevent="deleteItem(item.id)">
            <v-icon icon="mdi-delete-outline" variants="flat" />
            Delete
          </v-btn>
        </div>
      </template>
    </v-treeview>

    <v-dialog v-model="displayModal">
      <CategoriesModal
        :loading="saveLoading"
        @onClose="toggleModalDisplay(false)"
        @onSave="handleSave"
        :item="selectedCategory"
      />
    </v-dialog>
  </div>
</template>
