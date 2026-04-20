<script setup lang="ts">
import { MenusPositions, type ItemMenuType } from "./Config";

//composables
const { axiosErrorAlert, confirmDelete, alertSuccess } = useAlert();

//models
const position = ref(MenusPositions[1].id);
const menus = ref<ItemMenuType[]>([]);
const modal = ref<boolean>(false);
const loading = ref<boolean>(false);
const pages = ref<any>([]);
const promotions = ref<any>([]);
const currentItem = ref<ItemMenuType>();

//methods
const getPage = async (): Promise<void> => {
  const { success, data } = await useAPIFetch("/pages/list");
  if (success) {
    pages.value = data.data;
  }
};
const getPromotions = async (): Promise<void> => {
  const { success, data } = await useAPIFetch("/promotions/list");
  if (success) {
    promotions.value = data.data;
  }
};

const reloadList = async () => {
  const { success, data } = await useAPIFetch("/menu/list", {
    position: position.value,
  });
  if (success) {
    menus.value = data.data;
  }
};

const handleReload = async (): Promise<void> => {
  loading.value = true;
  await reloadList();
  loading.value = false;
};

const handleEdit = (_payload: ItemMenuType) => {
  currentItem.value = _payload;
  modal.value = true;
};

const handleAdd = () => {
  currentItem.value = undefined;
  modal.value = true;
};

const deleteItem = async (id: number) => {
  confirmDelete(async (_result: any) => {
    const { data, success, error } = await useApiDeleteFetch("/menu/delete", {
      id,
    });
    if (success) {
      alertSuccess(data.message);
      handleReload();
      modal.value = false;
      return;
    }
    if (error) {
      return axiosErrorAlert(error);
    }
  });
};

const save = async (payload: any) => {
  const { success, data, error } = await useApiPostFetch(`/menu/save`, payload);

  if (success) {
    useNuxtApp().$toast.success(data.message);
    modal.value = false;
    handleReload();
    return;
  }

  if (!success && error) {
    axiosErrorAlert(error);
  }
};

//mounted
onMounted(async () => {
  loading.value = true;
  await Promise.all([reloadList(), getPromotions(), getPage()]);
  loading.value = false;
});

watch(position, () => handleReload());
</script>
<template>
  <v-card>
    <v-card-title class="d-flex ga-2 justify-space-between align-center">
      <v-select
        v-model="position"
        :items="MenusPositions"
        :item-title="'label'"
        :item-value="'id'"
        hide-details
        density="compact"
        class="w-50"
      ></v-select>
      <v-btn
        flat
        color="blue"
        prepend-icon="mdi-plus"
        text="Add Menu Item"
        @click.prevent="handleAdd"
        :disabled="loading"
      />
    </v-card-title>
    <v-card-text>
      <v-progress-linear v-if="loading" color="blue" indeterminate />
      <div v-if="!loading">
        <MenusItem
          v-for="(menu, i) in menus"
          :key="menu.menu_id"
          :item="menu"
          @onDelete="deleteItem"
          @onEdit="handleEdit"
        />
        <v-alert
          v-if="menus.length === 0"
          border="start"
          color="deep-purple-accent-4"
          title="Alert"
          variant="tonal"
          >No items</v-alert
        >
      </div>
    </v-card-text>
  </v-card>
  <v-dialog v-model="modal" max-width="700">
    <MenusForm
      v-if="!loading"
      :pages="pages"
      :promotions="promotions"
      :item="currentItem"
      :key="`${currentItem ? currentItem.menu_id : 'add'}`"
      @onClose="modal = false"
      @onSave="save"
    />
  </v-dialog>
</template>
