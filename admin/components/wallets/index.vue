<script setup lang="ts">
import type { WalletType } from "./types";

const search = ref<string>("");
const loading = ref<boolean>(false);
const dialog = ref<boolean>(false);
const items = ref<WalletType[]>([]);
const currentItem = ref<WalletType>();

//composables
const { confirmDelete, alertSuccess, axiosErrorAlert } = useAlert();

//methods
const reloadList = async () => {
  loading.value = true;
  const { success, data } = await useAPIFetch("/wallet/list", {
    search: search.value,
  });
  if (success) {
    items.value = data.data;
  }
  loading.value = false;
};
const deleteItem = async (id: number) => {
  confirmDelete(async (_result: any) => {
    const { data, success, error } = await useApiDeleteFetch("/wallet/delete", {
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

const save = async (payload: any) => {
  const { success, data, error } = await useApiPostFetch(
    `/wallet/save`,
    payload
  );

  if (success) {
    useNuxtApp().$toast.success(data.message);
    dialog.value = false;
    reloadList();
    return;
  }

  if (!success && error) {
    axiosErrorAlert(error);
  }
};

const createUserWallets = async () => {
  const { success, data, error } = await useAPIFetch(
    `/wallet/create-user-wallets`
  );

  if (success) {
    useNuxtApp().$toast.success("Success");
    dialog.value = false;
    return;
  }
};

const add = () => {
  dialog.value = true;
  currentItem.value = undefined;
};

const edit = (item: WalletType) => {
  dialog.value = true;
  currentItem.value = item;
};

//onMounted
onMounted(() => {
  reloadList();
});
</script>
<template>
  <v-card>
    <v-card-title class="d-flex align-center ga-1">
      <div class="d-flex ga-1 align-center justify-space-between w-100">
        <v-text-field
          color="blue"
          hide-details
          density="compact"
          v-model="search"
          label="Search Wallet"
        />
        <v-btn
          prepend-icon="mdi-magnify"
          text="Search"
          color="blue"
          variant="tonal"
          @click.prevent="reloadList"
        />
      </div>
      <v-btn prepend-icon="mdi-plus" @click.prevent="createUserWallets"
        >Create missed wallets for users</v-btn
      >
      <v-btn
        prepend-icon="mdi-plus"
        text="Add new wallet"
        color="blue"
        @click.prevent="add"
      />
    </v-card-title>
    <v-card-text>
      <v-progress-linear color="blue" indeterminate v-if="loading" />
      <v-table fixed-header height="500" striped="even">
        <thead class="bg-blue">
          <tr>
            <td>ID</td>
            <td>CODE</td>
            <td>NAME</td>
            <td>SYMBOL</td>
            <td>PRECISION</td>
            <td>MIN. AMOUNT</td>
            <td>FIAT</td>
            <td>ACTIVE</td>
            <td>ACTIONS</td>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, i) in items" :key="item.wallet_uuid">
            <td>{{ item.id }}</td>
            <td>{{ item.code }}</td>
            <td>
              {{ item.name }} <br />
              <v-chip
                :text="item.wallet_uuid"
                size="x-small"
                density="compact"
              />
            </td>
            <td>{{ item.symbol }}</td>
            <td>{{ item.precision }}</td>
            <td>{{ item.min_amount }}</td>
            <td>
              <v-chip size="small" :color="item.is_fiat ? 'green' : 'orange'">{{
                item.is_fiat ? "YES" : "NO"
              }}</v-chip>
            </td>
            <td>
              <v-chip size="small" :color="item.active ? 'green' : 'orange'">{{
                item.active ? "YES" : "NO"
              }}</v-chip>
            </td>
            <td>
              <div class="d-flex align-center ga-1">
                <v-btn
                  text="Edit"
                  prepend-icon="mdi-pencil-outline"
                  @click.prevent="edit(item)"
                  color="blue"
                  density="compact"
                  variant="tonal"
                />

                <v-btn
                  text="Delete"
                  prepend-icon="mdi-delete-outline"
                  @click.prevent="deleteItem(item.id)"
                  color="red"
                  density="compact"
                  variant="tonal"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card-text>
  </v-card>
  <v-dialog v-model="dialog" max-width="600">
    <WalletsForm
      :item="currentItem"
      @wallet:close-modal="dialog = false"
      @wallet:save="save"
    />
  </v-dialog>
</template>
