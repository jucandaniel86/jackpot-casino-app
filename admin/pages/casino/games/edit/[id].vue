<script setup lang="ts">
import { APP_BREADCRUMBS } from "~/app/breadcrumbs";
import type { GameT } from "~/components/games/config";

const title = "Casino Games :: Add new Game";

//composables
const router = useRouter();
const route = useRoute();
const { axiosErrorAlert, toastError, alertSuccess } = useAlert();

//models
const loading = ref<boolean>(false);
const item = ref<GameT>();

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
  formData.append("id", options.id);

  if (options.thumbnail_file) {
    formData.append("thumbnail", options.thumbnail_file);
  }

  const { data, error } = await useApiPostFetch("/games/save", formData);
  if (data) {
    alertSuccess(data.message);
    getGame();
    loading.value = false;
    return;
  }
  loading.value = false;
  return axiosErrorAlert(error, true);
};

const getGame = async (): Promise<void> => {
  loading.value = true;
  const { data, success } = await useAPIFetch("/games/get", {
    id: route.params.id,
  });

  if (!success) {
    toastError("Something went wrong. Please try again");
    // backToGames();
    return;
  }

  item.value = data.data.item;

  loading.value = false;
};

useHead({
  title,
});

onMounted(() => {
  getGame();
});
</script>
<template>
  <SharedBreadcrumb :items="APP_BREADCRUMBS.GAMES_EDIT" :title="title" />
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
      <GamesForm :loading="loading" @onSave="save" :item="item" />
    </v-card-text>
  </v-card>
</template>
