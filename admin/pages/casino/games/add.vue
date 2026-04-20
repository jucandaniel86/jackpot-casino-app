<script setup lang="ts">
import { APP_BREADCRUMBS } from "~/app/breadcrumbs";

const title = "Casino Games :: Add new Game";

//composables
const router = useRouter();
const { axiosErrorAlert } = useAlert();
const { alertSuccess } = useAlert();

//models
const loading = ref<boolean>(false);

//methods
const backToGames = () => router.push({ name: "casino-games" });
const save = async (options: any) => {
  loading.value = true;

  var formData = new FormData();

  formData.append("name", options.name);
  formData.append("game_id", options.game_id);
  formData.append("categories", JSON.stringify(Array.from(options.categories)));
  formData.append("casinos", JSON.stringify(Array.from(options.casinos)));
  formData.append("provider_id", options.provider_id);
  formData.append("description", options.description);
  formData.append("iframe_url", options.iframe_url);
  formData.append("is_fullpage", options.is_fullpage);
  formData.append("active_on_site", options.active_on_site);
  formData.append("is_recommended", options.is_recomended);
  formData.append("soon", options.soon);
  formData.append("is_fun", options.is_fun);

  if (options.thumbnail_file) {
    formData.append("thumbnail", options.thumbnail_file);
  }

  const { data, error } = await useApiPostFetch("/games/save", formData);
  if (data) {
    alertSuccess(data.message);
    backToGames();
    loading.value = false;
    return;
  }
  loading.value = false;
  return axiosErrorAlert(error, true);
};

useHead({
  title,
});
</script>
<template>
  <SharedBreadcrumb :items="APP_BREADCRUMBS.GAMES_ADD" :title="title" />
  <v-card>
    <v-card-title class="d-flex justify-end">
      <v-btn
        value="nearby"
        @click.prevent="backToGames"
        prepend-icon="mdi-arrow-left"
      >
        <span>Back to games</span>
      </v-btn>
    </v-card-title>
    <v-card-text>
      <GamesForm :loading="loading" @onSave="save" />
    </v-card-text>
  </v-card>
</template>
