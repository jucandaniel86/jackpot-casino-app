<script setup lang="ts">
import { APP_BREADCRUMBS } from "~/app/breadcrumbs";

const title = "Promotions";

useHead({
  title,
});

//composables
const { alertSuccess, axiosErrorAlert } = useAlert();

//models
const loading = ref<boolean>(false);

//methods
const save = async (options: any): Promise<void> => {
  loading.value = true;

  var formData = new FormData();
  console.log(options.seo, JSON.stringify(options.seo));
  formData.append("title", options.title);
  formData.append("subtitle", options.subtitle);
  formData.append("description", options.description);
  formData.append("smallDescription", options.smallDescription);
  formData.append("howItWorks", options.howItWorks);
  formData.append("terms", options.terms);
  formData.append("primaryAction", JSON.stringify(options.primaryAction));
  formData.append("seo", JSON.stringify(options.seo));
  formData.append("active", options.active);
  formData.append("id", options.id);

  if (options.thumbnail_file) {
    formData.append("thumbnail", options.thumbnail_file);
  }

  if (options.banner_file) {
    formData.append("banner", options.banner_file);
  }

  const { data, error } = await useApiPostFetch("/promotions/save", formData);
  if (data) {
    alertSuccess(data.message);
    loading.value = false;
    return;
  }
  loading.value = false;
  axiosErrorAlert(error, true);
};
</script>
<template>
  <SharedBreadcrumb :items="APP_BREADCRUMBS.PROMOTIONS_SAVE" :title="title" />
  <PromotionsForm @onSave="save" :saveLoading="loading" />
</template>
