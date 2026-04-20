<script setup lang="ts">
import { toastContainers } from "vue3-toastify";
import type { GameT } from "./config";

//models
const loading = ref<boolean>(false);
const items = ref<GameT[]>();
const totalItems = ref<number>(0);

//composables
const router = useRouter();
const { confirmDelete, toastSuccess, toastError, axiosErrorAlert } = useAlert();

//methods
const add = () => router.push({ name: "casino-games-add" });
const edit = (id: number) =>
  router.push({ name: "casino-games-edit-id", params: { id } });
const reloadList = async (options: any) => {
  loading.value = true;
  let { page, itemsPerPage } = options;

  const { success, data } = await useAPIFetch("/games/list", {
    start: page,
    length: itemsPerPage,
    ...options,
  });
  if (success) {
    items.value = data.data.items;
    totalItems.value = data.data.total;
  }

  loading.value = false;
};

const deleteItem = async (id: number) => {
  confirmDelete(async (_result: any) => {
    const { data, success, error } = await useApiDeleteFetch("/games/delete", {
      id,
    });
    if (success) {
      if (data.data.success) {
        toastSuccess(data.data.msg);
        reloadList({});
      } else {
        toastError(data.data.msg);
      }

      return;
    }
    if (error) {
      return axiosErrorAlert(error);
    }
  });
};
</script>
<template>
  <v-card>
    <v-card-title class="d-flex justify-space-between">
      <GamesFilters :loading="loading" @onReload="reloadList" />
      <v-btn
        @click.prevent="add"
        prepend-icon="mdi-plus"
        variant="flat"
        color="blue"
        >Add Game</v-btn
      >
    </v-card-title>
    <v-card-text>
      <GamesList
        :items="items"
        :loading="loading"
        :total-items="totalItems"
        @onReload="reloadList"
        @onDelete="deleteItem"
        @onEdit="edit"
      />
    </v-card-text>
  </v-card>
</template>
