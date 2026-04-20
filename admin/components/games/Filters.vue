<script setup lang="ts">
import { YESNO_SELECT } from "./config";

//props
type GameFiltersT = {
  loading: boolean;
};
const props = defineProps<GameFiltersT>();

//models
const filters = ref({
  soon: null,
  is_recomended: null,
  is_fun: null,
  active_on_site: null,
  name: "",
});

//emitters
const emitters = defineEmits(["onReload"]);

//methods
const handleReloadList = () =>
  emitters("onReload", {
    ...filters.value,
    page: 1,
    itemsPerPage: 10,
  });
</script>
<template>
  <v-row>
    <v-col cols="3" md="3">
      <v-text-field
        v-model="filters.name"
        label="Search by name or game ID"
        chips
        density="compact"
        hide-details
      ></v-text-field>
    </v-col>
    <v-col cols="2" md="2">
      <v-select
        v-model="filters.active_on_site"
        :items="YESNO_SELECT"
        clearable
        label="Active"
        item-title="name"
        item-value="id"
        density="compact"
        hide-details
      >
      </v-select>
    </v-col>
    <v-col cols="2" md="2">
      <v-select
        v-model="filters.soon"
        :items="YESNO_SELECT"
        clearable
        label="Soon"
        item-title="name"
        item-value="id"
        density="compact"
        hide-details
      >
      </v-select>
    </v-col>
    <v-col cols="2" md="2">
      <v-select
        v-model="filters.is_fun"
        :items="YESNO_SELECT"
        clearable
        label="Fun"
        item-title="name"
        item-value="id"
        density="compact"
        hide-details
      >
      </v-select>
    </v-col>
    <v-col cols="2" md="2" class="align-self-center">
      <v-btn
        :disabled="props.loading"
        color="success"
        class="btn btn-primary"
        @click.prevent="handleReloadList"
        variant="flat"
        hide-details
        prepend-icon="mdi-search-web"
      >
        Search
      </v-btn>
    </v-col>
  </v-row>
</template>
