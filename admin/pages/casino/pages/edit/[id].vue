<script setup lang="ts">
import { APP_BREADCRUMBS } from "~/app/breadcrumbs";

const title = "Page Administration";

useHead({
  title,
});

//composables
const { axiosErrorAlert, alertSuccess } = useAlert();

const sections = ref<any>([]);
const loading = ref<boolean>(false);
const route = useRoute();
const pageID = route.params.id;
const form = ref({
  name: "",
  restricted: 0,
  id: 0,
  seo: {
    title: "",
    description: "",
    displayTitle: "",
    displayDescription: "",
    indexed: 1,
  },
});

//methods
const addNew = async (payload: any): Promise<void> => {
  loading.value = true;
  const { data, success, error } = await useApiPostFetch(
    "/sections/save-draft",
    {
      ...payload,
      page: route.params.id,
    },
  );
  if (success) {
    useNuxtApp().$toast.success(data.message);
    sections.value.push(data.data);
  } else {
    useNuxtApp().$toast.error("Something went wrong");
  }
  loading.value = false;
};

const getPage = async (): Promise<void> => {
  loading.value = true;
  const { data } = await useAPIFetch("/pages/get", {
    id: route.params.id,
  });
  if (data) {
    sections.value = data.data.sections;
    form.value.name = data.data.name;
    if (typeof data.data.seo === "string") {
      try {
        form.value.seo = JSON.parse(data.data.seo);
      } catch {
        form.value.seo = form.value.seo;
      }
    } else if (data.data.seo) {
      form.value.seo = data.data.seo;
    }
    form.value.id = data.data.id;
  }
  loading.value = false;
};

const save = async (payload: any) => {
  console.log("saving", payload);
  const { success, data, error } = await useApiPostFetch(`/pages/save`, {
    ...payload,
    seo: JSON.stringify(payload?.seo ?? {}),
  });

  if (success) {
    alertSuccess(data.message);
    return;
  }

  if (!success && error) {
    axiosErrorAlert(error);
  }
};

//mounted
onMounted(() => {
  getPage();
});
</script>
<template>
  <SharedBreadcrumb :items="APP_BREADCRUMBS.PAGE_ADD" :title="title" />

  <v-row no-gutters>
    <v-col cols="9">
      <Section
        :page-id="pageID"
        :sections="sections"
        @onAddNew="addNew"
        @reload-list="getPage"
        :is-tag="false"
      />
    </v-col>
    <v-col cols="3">
      <v-card class="pa-4">
        <h3 class="mb-4">Page Settings</h3>
        <v-text-field
          v-model="form.name"
          label="SEO Title"
          hide-details
          density="compact"
        />
        <SharedSeo v-model="form.seo" />
        <v-btn color="primary" class="mt-4" @click="save(form)"
          >Save Settings</v-btn
        >
      </v-card>
    </v-col>
  </v-row>
</template>
