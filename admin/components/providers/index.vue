<script setup lang="ts">
import { CASINO_PROVIDERS_TABLE_HEADERS } from "./headers";
import type { ProviderT } from "./types";

//composables
const { confirmDelete, alertSuccess, axiosErrorAlert } = useAlert();

//models
const totalItems = ref(0);
const items = ref<ProviderT[]>();
const loading = ref(true);
const searchText = ref("");
const options = ref<any>({});
const selectedItem = ref<ProviderT>();
const modal = ref<boolean>(false);

//methods
const reloadList = async () => {
  loading.value = true;
  const { page, itemsPerPage } = options.value;

  const { success, data } = await useAPIFetch("/providers/list", {
    start: page,
    length: itemsPerPage,
    search: searchText.value,
  });
  if (success) {
    items.value = data.data;
    totalItems.value = data.total;
  }

  loading.value = false;
};
const deleteItem = async (id: number) => {
  confirmDelete(async (_result: any) => {
    const { data, success, error } = await useApiDeleteFetch(
      "/providers/delete",
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

const toggleModal = (_payload: boolean) => (modal.value = _payload);

const add = () => {
  selectedItem.value = undefined;
  toggleModal(true);
};

const edit = (item: ProviderT) => {
  selectedItem.value = item;
  toggleModal(true);
};

//watchers
watch(
  options,
  () => {
    reloadList();
  },
  { deep: true }
);

watch(searchText, () => {
  if (searchText.value && searchText.value.length > 2) {
    return reloadList();
  }
  if (searchText.value.length <= 1) reloadList();
});

//onMounted
onMounted(() => {
  reloadList();
});
</script>
<template>
  <v-card>
    <v-card-title class="d-flex justify-space-between align-center ga-2">
      <v-text-field
        v-model="searchText"
        label="Search"
        chips
        hide-details
        density="compact"
      ></v-text-field>
      <v-btn value="nearby" @click.prevent="add" flat color="blue">
        <v-icon>mdi-plus</v-icon>
        <span>Add new provider</span>
      </v-btn>
    </v-card-title>
    <v-card-text>
      <v-data-table
        :headers="CASINO_PROVIDERS_TABLE_HEADERS"
        :items="items"
        :options.sync="options"
        :server-items-length="totalItems"
        :loading="loading"
        :search="searchText"
        class="elevation-1"
        :height="400"
        density="compact"
      >
        <template v-slot:item="{ item }">
          <tr>
            <td>
              <v-img
                v-if="item.thumbnail"
                :max-width="100"
                aspect-ratio="16/9"
                cover
                :src="item.thumbnail_url"
                style="background: #c9c9c9"
              >
                <template v-slot:placeholder>
                  <div class="d-flex align-center justify-center fill-height">
                    <v-progress-circular
                      color="grey-lighten-4"
                      indeterminate
                    ></v-progress-circular>
                  </div>
                </template>
              </v-img>
            </td>
            <td>{{ item.name }}</td>
            <td>
              <v-chip
                :color="item.active ? 'green' : 'red'"
                pill
                size="x-small"
              >
                {{ item.active ? "YES" : "NO" }}
              </v-chip>
            </td>
            <td>
              <v-btn density="compact" :icon="true" @click.prevent="edit(item)"
                ><v-icon>mdi-pencil</v-icon></v-btn
              >
              <v-btn
                density="compact"
                :icon="true"
                @click.prevent="deleteItem(item.id)"
                ><v-icon>mdi-delete-forever</v-icon></v-btn
              >
            </td>
          </tr>
        </template>
      </v-data-table>
    </v-card-text>
  </v-card>

  <v-dialog v-model="modal" :maxWidth="600" width="100%">
    <providers-form
      :selected-provider="selectedItem"
      @providers:close-modal="toggleModal(false)"
      @providers:reload-list="reloadList"
    />
  </v-dialog>
</template>
