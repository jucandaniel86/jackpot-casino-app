<script setup lang="ts">
import type { GameT } from "./config";

type GameFormT = {
  loading: boolean;
  item?: GameT;
};
const props = defineProps<GameFormT>();

//composables
const { required } = useRules();

//models
const form = ref<any>({
  categories: [],
  provider_id: 0,
  game_id: "",
  name: "",
  description: "",
  iframe_url: "",
  is_fullpage: 0,
  active_on_site: 0,
  is_recomended: 0,
  soon: 0,
  is_fun: 0,
  thumbnail_file: [],
  id: 0,
  thumbnail_url: "",
  casinos: [],
});

//emitters
const emitters = defineEmits(["onSave"]);

//methods
const handleOnSave = () =>
  emitters("onSave", {
    ...form.value,
  });

//onMounted
onMounted(() => {
  if (props.item) {
    form.value = {
      ...form.value,
      ...props.item,
    };
  }
});
watch(
  props,
  () => {
    if (props.item) {
      form.value = {
        ...form.value,
        ...props.item,
      };
    }
  },
  {
    deep: true,
  },
);
</script>
<template>
  <div>
    <v-text-field
      v-model="form.name"
      label="Name"
      required
      :rules="[required]"
      hide-details
      density="compact"
    ></v-text-field>

    <v-text-field
      v-model="form.game_id"
      label="Game ID"
      required
      :rules="[required]"
      hide-details
      density="compact"
    ></v-text-field>

    <SelectCategories v-model="form.categories" class="mt-1 mb-1" />

    <SelectProvider v-model="form.provider_id" />

    <SharedCasinos v-model="form.casinos" :multiple="true" />

    <v-textarea
      v-model="form.description"
      label="Description"
      required
      hide-details
      density="compact"
    ></v-textarea>

    <v-row>
      <v-col cols="2" md="2">
        <v-checkbox
          v-model="form.is_fullpage"
          :label="`Fullpage`"
          :true-value="1"
          :false-value="0"
          hide-details
        ></v-checkbox>
      </v-col>
      <v-col cols="2" md="2">
        <v-checkbox
          v-model="form.active_on_site"
          :label="`Active`"
          :true-value="1"
          :false-value="0"
          hide-details
        ></v-checkbox>
      </v-col>
      <v-col cols="2" md="2">
        <v-checkbox
          v-model="form.is_recomended"
          :label="`Recommended`"
          :true-value="1"
          :false-value="0"
          hide-details
        ></v-checkbox>
      </v-col>
      <v-col cols="2" md="2">
        <v-checkbox
          v-model="form.soon"
          :label="`Soon`"
          :true-value="1"
          :false-value="0"
          hide-details
        ></v-checkbox>
      </v-col>
      <v-col cols="2" md="2">
        <v-checkbox
          v-model="form.is_fun"
          :label="`Fun`"
          :true-value="1"
          :false-value="0"
          hide-details
        ></v-checkbox>
      </v-col>
    </v-row>

    <v-file-input
      v-model="form.thumbnail_file"
      label="Logo"
      accept="image/*"
      show-size
      hide-details
      density="compact"
    ></v-file-input>

    <v-btn
      :disabled="props.loading"
      class="mt-4"
      color="blue"
      @click.prevent="handleOnSave"
    >
      Save
    </v-btn>
  </div>
</template>
