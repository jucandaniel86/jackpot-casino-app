<script setup lang="ts">
type RewardItem = {
  id: number;
  uid: string;
  title: string;
  slug: string;
  subtitle?: string | null;
  description?: string | null;
  thumbnail?: string | null;
  thumbnailUrl?: string | null;
  type: string;
  rule?: Record<string, unknown> | null;
  page_order: number;
  is_active: boolean;
  starts_at?: string | null;
  ends_at?: string | null;
};

const headers = [
  { title: "Thumbnail", key: "thumbnail", sortable: false, width: 120 },
  { title: "Title", key: "title" },
  { title: "Type", key: "type" },
  { title: "Slug", key: "slug" },
  { title: "Order", key: "page_order", width: 100 },
  { title: "Active", key: "is_active", width: 100 },
  { title: "Actions", key: "actions", sortable: false, width: 120 },
];

const emptyForm = () => ({
  id: null as number | null,
  title: "",
  subtitle: "",
  description: "",
  type: "daily_redeem",
  ruleText: "{}",
  page_order: 0,
  is_active: true,
  starts_at: "",
  ends_at: "",
  thumbnail: null as File | File[] | null,
});

const { axiosErrorAlert, confirmDelete, alertSuccess } = useAlert();

const rewards = ref<RewardItem[]>([]);
const rewardTypes = ref<Array<{ title: string; value: string }>>([]);
const loading = ref(false);
const saving = ref(false);
const dialog = ref(false);
const selectedType = ref<string | null>(null);
const form = ref(emptyForm());
const currentThumbnailUrl = ref<string | null>(null);

const reloadList = async () => {
  loading.value = true;

  const payload = selectedType.value ? { type: selectedType.value } : {};
  const { success, data } = await useAPIFetch("/rewards/list", payload);

  if (success) {
    rewards.value = data.data;
  }

  loading.value = false;
};

const loadTypes = async () => {
  const { success, data } = await useAPIFetch("/rewards/types");

  if (success) {
    rewardTypes.value = data.data;
  }
};

const openCreate = () => {
  form.value = emptyForm();
  currentThumbnailUrl.value = null;
  dialog.value = true;
};

const openEdit = (item: RewardItem) => {
  form.value = {
    id: item.id,
    title: item.title || "",
    subtitle: item.subtitle || "",
    description: item.description || "",
    type: item.type || "daily_redeem",
    ruleText: JSON.stringify(item.rule || {}, null, 2),
    page_order: item.page_order || 0,
    is_active: Boolean(item.is_active),
    starts_at: item.starts_at || "",
    ends_at: item.ends_at || "",
    thumbnail: null,
  };
  currentThumbnailUrl.value = item.thumbnailUrl || null;
  dialog.value = true;
};

const selectedThumbnail = computed(() => {
  const thumbnail = form.value.thumbnail;

  if (Array.isArray(thumbnail)) {
    return thumbnail[0] || null;
  }

  return thumbnail || null;
});

const buildPayload = () => {
  const formData = new FormData();

  if (form.value.id) {
    formData.append("id", String(form.value.id));
  }

  formData.append("title", form.value.title);
  formData.append("subtitle", form.value.subtitle || "");
  formData.append("description", form.value.description || "");
  formData.append("type", form.value.type);
  formData.append("rule", form.value.ruleText || "{}");
  formData.append("page_order", String(form.value.page_order || 0));
  formData.append("is_active", form.value.is_active ? "1" : "0");

  if (form.value.starts_at) {
    formData.append("starts_at", form.value.starts_at);
  }

  if (form.value.ends_at) {
    formData.append("ends_at", form.value.ends_at);
  }

  if (selectedThumbnail.value) {
    formData.append("thumbnail", selectedThumbnail.value);
  }

  return formData;
};

const save = async () => {
  if (!form.value.title.trim()) {
    useNuxtApp().$toast.error("Title is required");
    return;
  }

  try {
    JSON.parse(form.value.ruleText || "{}");
  } catch (_error) {
    useNuxtApp().$toast.error("Rule must be valid JSON");
    return;
  }

  saving.value = true;
  const endpoint = form.value.id ? "/rewards/update" : "/rewards/insert";
  const { success, data, error } = await useApiPostFetch(endpoint, buildPayload());
  saving.value = false;

  if (success) {
    alertSuccess(data.message);
    dialog.value = false;
    await reloadList();
    return;
  }

  if (error) {
    axiosErrorAlert(error);
  }
};

const deleteItem = async (id: number) => {
  confirmDelete(async () => {
    const { data, success, error } = await useApiDeleteFetch("/rewards/delete", { id });

    if (success) {
      alertSuccess(data.message);
      await reloadList();
      return;
    }

    if (error) {
      axiosErrorAlert(error);
    }
  });
};

watch(selectedType, () => {
  reloadList();
});

onMounted(() => {
  loadTypes();
  reloadList();
});
</script>

<template>
  <v-card>
    <v-card-title class="d-flex align-center justify-space-between ga-3">
      <v-select
        v-model="selectedType"
        :items="rewardTypes"
        label="Filter by type"
        density="compact"
        variant="solo"
        clearable
        hide-details
        style="max-width: 320px"
      />

      <v-btn color="blue" flat prepend-icon="mdi-plus" @click.prevent="openCreate">
        Add reward
      </v-btn>
    </v-card-title>

    <v-card-text>
      <v-data-table
        :headers="headers"
        :items="rewards"
        :loading="loading"
        density="compact"
        class="elevation-1"
      >
        <template #item.thumbnail="{ item }">
          <v-avatar rounded size="56" color="grey-lighten-3">
            <v-img v-if="item.thumbnailUrl" :src="item.thumbnailUrl" cover />
            <v-icon v-else icon="mdi-image-off-outline" />
          </v-avatar>
        </template>

        <template #item.type="{ item }">
          <v-chip size="small" color="purple">{{ item.type }}</v-chip>
        </template>

        <template #item.is_active="{ item }">
          <v-chip size="small" :color="item.is_active ? 'green' : 'grey'">
            {{ item.is_active ? "Active" : "Inactive" }}
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-btn density="compact" icon @click.prevent="openEdit(item)">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn density="compact" icon @click.prevent="deleteItem(item.id)">
            <v-icon>mdi-delete-forever</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card-text>
  </v-card>

  <v-dialog v-model="dialog" max-width="760">
    <v-card>
      <v-card-title>{{ form.id ? "Edit reward" : "Add reward" }}</v-card-title>

      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="8">
            <v-text-field
              v-model="form.title"
              label="Title"
              density="compact"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model.number="form.page_order"
              type="number"
              label="Page order"
              density="compact"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-select
              v-model="form.type"
              :items="rewardTypes"
              label="Type"
              density="compact"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-switch
              v-model="form.is_active"
              label="Active"
              color="green"
              hide-details
              density="compact"
            />
          </v-col>

          <v-col cols="12">
            <v-text-field
              v-model="form.subtitle"
              label="Subtitle"
              density="compact"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12">
            <v-textarea
              v-model="form.description"
              label="Description"
              rows="3"
              density="compact"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.starts_at"
              label="Starts at"
              type="datetime-local"
              density="compact"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.ends_at"
              label="Ends at"
              type="datetime-local"
              density="compact"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12">
            <v-img
              v-if="currentThumbnailUrl"
              :src="currentThumbnailUrl"
              max-height="160"
              cover
              class="mb-3 rounded"
            />
            <v-file-input
              v-model="form.thumbnail"
              label="Thumbnail"
              accept="image/*"
              show-size
              density="compact"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12">
            <v-textarea
              v-model="form.ruleText"
              label="Rule JSON"
              rows="8"
              density="compact"
              variant="outlined"
              spellcheck="false"
            />
          </v-col>
        </v-row>
      </v-card-text>

      <v-card-actions class="justify-end">
        <v-btn variant="text" @click.prevent="dialog = false">Cancel</v-btn>
        <v-btn color="blue" :loading="saving" variant="flat" @click.prevent="save">
          Save
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
