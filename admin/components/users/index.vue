<script setup lang="ts">
import moment from "moment";
import { type UserType } from "./Utils";
import { useAuthStore } from "~/store/auth";

//components
import UserForm from "./Form.vue";
import UserPasswordForm from "./Password.vue";

//composables
const { confirmDelete } = useAlert();

//models
const { user } = storeToRefs(useAuthStore());
const { alertSuccess, axiosErrorAlert } = useAlert();
const totalItems = ref<number>(0);
const items = ref<UserType[]>([]);
const loading = ref<boolean>(true);
const searchText = ref<string>("");
const options = ref<any>({});
const selectedItem = ref<UserType | undefined>();
const modal = ref<boolean>(false);
const modalPassword = ref<boolean>(false);
const headers = ref<any[]>([
  {
    title: "Name",
    align: "start",
    sortable: false,
    value: "name",
    width: "30%",
  },
  {
    title: "Created at",
    align: "start",
    sortable: false,
    value: "created_at",
    width: "15%",
  },
  {
    title: "Actions",
    value: "iron",
    sortable: false,
    width: "20%",
  },
]);

//watchers
watch(options, () => ({
  handler() {
    reloadList();
  },
  deep: true,
}));

//methods
const add = () => {
  selectedItem.value = undefined;
  modal.value = true;
};

const edit = (_item: UserType) => {
  selectedItem.value = _item;
  modal.value = true;
};

const openPasswordEdit = (_item: UserType) => {
  selectedItem.value = _item;
  modalPassword.value = true;
};

const convertDate = (date: string) => moment(date).format("MM/DD/YYYY H:mm");

const reloadList = async () => {
  loading.value = true;
  const { page, itemsPerPage } = options.value;

  const { success, data } = await useAPIFetch("/users/list", {
    start: page,
    length: itemsPerPage,
    search: searchText.value,
  });
  if (success) {
    items.value = data.data.items;
    totalItems.value = data.total;
  }

  loading.value = false;
};

const deleteItem = async (id: number) => {
  confirmDelete(async (_result: any) => {
    const { data, success, error } = await useApiDeleteFetch("/users/delete", {
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

const testAPIRequest = async () => {
  const { success, data } = await useAPIFetch("/users/test");
  if (success) {
    console.log(data);
  }
};

//mounted
onMounted(() => {
  reloadList();
});
</script>
<template>
  <!-- <v-btn @click="testAPIRequest">Test API</v-btn> -->
  <v-data-table
    :headers="headers as any"
    :items="items"
    :options.sync="options"
    :server-items-length="totalItems"
    :loading="loading"
    :search="searchText"
    class="elevation-1 pa-2"
    :height="550"
  >
    <template v-slot:top>
      <v-row justify="end" class="align-center">
        <v-col cols="12" md="6" lg="3">
          <v-text-field
            v-model="searchText"
            label="Search"
            hide-details
            variant="solo"
            density="compact"
            class="filter-search text-field-component"
          ></v-text-field>
        </v-col>
        <v-col cols="auto">
          <v-btn color="secondary" @click="add">
            <i class="ph-plus-circle" /> Add user
          </v-btn>
        </v-col>
      </v-row>
    </template>
    <template v-slot:item="{ item }">
      <tr
        :class="{
          'active-user': user && user.id === item.id,
        }"
      >
        <td class="custom-class">
          {{ item.name }} <br />
          {{ item.email }}
        </td>
        <td class="custom-class">{{ convertDate(item.created_at) }}</td>
        <td class="custom-class">
          <v-btn
            icon="ph-pencil ph-sm"
            color="secondary"
            density="compact"
            elevation="0"
            :variant="'elevated'"
            rounded
            class="mx-1"
            @click.prevent="edit(item)"
          />
          <v-btn
            icon="ph-key ph-sm"
            color="warning"
            density="compact"
            elevation="0"
            :variant="'elevated'"
            rounded
            class="mx-1"
            @click.prevent="openPasswordEdit(item)"
          />
          <v-btn
            icon="ph-trash ph-sm"
            color="danger"
            density="compact"
            elevation="0"
            :variant="'elevated'"
            rounded
            @click.prevent="deleteItem(item.id)"
          />
        </td>
      </tr>
    </template>
  </v-data-table>
  <v-dialog
    name="form"
    v-model="modal"
    :height="'auto'"
    :maxWidth="600"
    width="100%"
    :scrollable="false"
    :adaptive="true"
    :styles="{ overflow: 'visible' }"
  >
    <user-form
      :item="selectedItem"
      @users:close-modal="modal = false"
      @users:reload-list="reloadList"
    />
  </v-dialog>
  <v-dialog
    name="password"
    v-model="modalPassword"
    :height="'auto'"
    :maxWidth="600"
    width="100%"
    :scrollable="false"
    :adaptive="true"
    :styles="{ overflow: 'visible' }"
  >
    <user-password-form
      :item="selectedItem"
      @users:close-password-modal="modalPassword = false"
    />
  </v-dialog>
</template>

<style>
.text-start {
  background: transparent;
}

.active-user td {
  position: relative;
}

.active-user td:first-child::after {
  position: absolute;
  content: "Logged user";
  padding: 0.5rem;
  background: green;
  width: fit-content;
  color: #fff;
  right: 0;
  top: 0;
  height: 100%;
  display: flex;
  align-items: center;
  border-left: 3px solid #005400;
}
</style>
