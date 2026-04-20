<script setup lang="ts">
import type { PromotionT } from "~/core/types/Promotions";

//composables
const { wait } = useUtils();
const { axiosErrorAlert, confirmDelete, alertSuccess } = useAlert();

//models
const search = ref("");
const promotions = ref<PromotionT[]>([]);
const router = useRouter();
const modal = ref<boolean>(false);
const loading = ref<boolean>(false);

//methods
const openModal = () => (modal.value = true);

const edit = (id: number) =>
  router.push({ name: "casino-promotions-edit-id", params: { id } });

const reloadList = async () => {
  loading.value = true;

  const { success, data } = await useAPIFetch("/promotions/list", {
    search: search.value,
  });
  if (success) {
    promotions.value = data.data;
  }

  loading.value = false;
};

const deleteItem = async (id: number) => {
  confirmDelete(async (_result: any) => {
    const { data, success, error } = await useApiDeleteFetch(
      "/promotions/delete",
      {
        id,
      }
    );
    if (success) {
      alertSuccess(data.message);
      reloadList();
      return;
    }
    if (error) {
      return axiosErrorAlert(error);
    }
  });
};

const save = async (payload: any) => {
  const { success, data, error } = await useApiPostFetch(
    `/promotions/save-draft`,
    payload
  );

  if (success) {
    useNuxtApp().$toast.success(data.message);

    await wait(600);
    edit(data.data.id);
    return;
  }

  if (!success && error) {
    axiosErrorAlert(error);
  }
};

//mounted
onMounted(() => {
  reloadList();
});
</script>
<template>
  <v-card>
    <v-data-iterator
      :loading="loading"
      :items="promotions"
      :items-per-page="9"
      :search="search"
    >
      <template v-slot:header>
        <div class="px-2 d-flex pt-2 justify-space-between align-center">
          <v-text-field
            v-model="search"
            density="compact"
            placeholder="Search"
            prepend-inner-icon="mdi-magnify"
            style="max-width: 300px"
            variant="solo"
            clearable
            hide-details
          ></v-text-field>
          <v-btn color="blue" flat @click.prevent="openModal">
            <v-icon icon="mdi-plus"></v-icon>
            Add new promotion
          </v-btn>
        </div>
      </template>

      <template v-slot:default="{ items }: any">
        <v-container class="pa-2" fluid>
          <v-row dense v-if="items.length">
            <v-col v-for="item in items" :key="item.title" cols="auto" md="4">
              <PromotionsItem
                :item="item.raw"
                @onEdit="edit"
                @onDelete="deleteItem"
              />
            </v-col>
          </v-row>
        </v-container>
      </template>

      <template v-slot:footer="{ page, pageCount, prevPage, nextPage }">
        <div class="d-flex align-center justify-center pa-4">
          <v-btn
            :disabled="page === 1"
            density="comfortable"
            icon="mdi-arrow-left"
            variant="tonal"
            rounded
            @click="prevPage"
          ></v-btn>

          <div class="mx-2 text-caption">
            Page {{ page }} of {{ pageCount }}
          </div>

          <v-btn
            :disabled="page >= pageCount"
            density="comfortable"
            icon="mdi-arrow-right"
            variant="tonal"
            rounded
            @click="nextPage"
          ></v-btn>
        </div>
      </template>
    </v-data-iterator>
  </v-card>
  <v-dialog v-model="modal" max-width="500">
    <PromotionsModal
      @promotions:close-modal="modal = false"
      @promotions:save="save"
    />
  </v-dialog>
</template>
