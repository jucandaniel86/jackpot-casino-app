<script setup lang="ts">
import { CASINO_TAGS_TABLE } from "./headers";

//composables
const { confirmDelete, alertSuccess, axiosErrorAlert } = useAlert();
const router = useRouter();

//models
const totalItems = ref(0);
const items = ref<any[]>();
const loading = ref(true);
const searchText = ref("");
const options = ref<any>({});
const dialog = ref<boolean>(false);

//methods
const reloadList = async () => {
  loading.value = true;
  const { page, itemsPerPage } = options.value;

  const { success, data } = await useAPIFetch("/tags/list", {
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
    const { data, success, error } = await useApiDeleteFetch("/tags/delete", {
      id,
    });
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

const wait = (duration: number) => {
  return new Promise<void>((resolve) => {
    setTimeout(() => resolve(), duration);
  });
};

const save = async (payload: any) => {
  const { success, data, error } = await useApiPostFetch(`/tags/save`, payload);

  if (success) {
    useNuxtApp().$toast.success(data.message);

    await wait(600);
    router.push({ name: "casino-tags-edit-id", params: { id: data.data.id } });
    return;
  }

  if (!success && error) {
    axiosErrorAlert(error);
  }
};

const add = () => (dialog.value = true);

const edit = (item: any) =>
  router.push({ name: "casino-tags-edit-id", params: { id: item.id } });

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
        <span>Add new tag</span>
      </v-btn>
    </v-card-title>
    <v-card-text>
      <v-data-table
        :headers="CASINO_TAGS_TABLE"
        :items="items"
        :options.sync="options"
        :server-items-length="totalItems"
        :loading="loading"
        :search="searchText"
        :group-by="[{ key: 'pageName', order: 'asc' }]"
        class="elevation-1"
        :height="400"
        density="compact"
      >
        <template v-slot:item="{ item }">
          <tr>
            <td>{{ item.name }}</td>
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
    <v-dialog v-model="dialog" max-width="500">
      <TagsForm @tags:close-modal="dialog = false" @tags:save="save" />
    </v-dialog>
  </v-card>
</template>
