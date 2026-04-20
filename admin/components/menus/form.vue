<script setup lang="ts">
import {
  ActionTypes,
  MenusPositions,
  Overlays,
  type ItemMenuType,
} from "./Config";

//types
type MenuFormT = {
  pages: any[];
  promotions: any[];
  item?: ItemMenuType;
};

//props
const props = defineProps<MenuFormT>();

//models
const form = ref<any>({
  title: "",
  icon: "",
  action_type: ActionTypes[1].id,
  position: MenusPositions[1].id,
  is_same_tab: 1,
  overlay: "",
  external_link: "",
  page_id: "",
  promotion_id: "",
  game_id: "",
});
const pageType = ref("page");
const games = ref<any>([]);

//emitters
const emitters = defineEmits(["onClose", "onSave"]);

//methods
const handleSave = () => {
  emitters("onSave", {
    ...form.value,
    page_id: pageType.value === "page" ? form.value.page_id : 0,
    promotion_id: pageType.value === "promotion" ? form.value.promotion_id : 0,
    game_id: pageType.value === "game" ? form.value.game_id : 0,
  });
};
const searchGame = async (search: string): Promise<void> => {
  if (String(search).length <= 1) return;
  const { data, success } = await useAPIFetch("/games/list", {
    name: search,
  });
  if (success && data) {
    games.value = data.data.items;
  }
};

const populateForm = () => {
  form.value = {
    ...form.value,
    ...props.item,
  };
  if (form.value.page_id > 0) {
    pageType.value = "page";
  } else if (form.value.game_id) {
    pageType.value = "game";
  } else if (form.value.promotion_id) {
    pageType.value = "promotion";
  }
};

watch(
  props,
  () => {
    if (props.item) {
      populateForm();
    }
  },
  { deep: true }
);

onMounted(() => {
  if (props.item) {
    populateForm();
  }
});
</script>
<template>
  <v-card elevation="1" variant="elevated" color="white">
    <v-card-title class="d-flex justify-space-between ga-2 align-center">
      <span class="text-h6">Menu Item</span>
      <v-btn
        text="Close"
        prepend-icon="mdi-close"
        flat
        @click.prevent="emitters('onClose')"
      />
    </v-card-title>
    <v-card-text>
      <v-text-field
        v-model="form.title"
        hide-details
        density="compact"
        label="Title"
      />
      <SharedIcons v-model="form.icon" :icon="form.icon" />
      <v-select
        v-model="form.position"
        :items="MenusPositions"
        hide-details
        density="compact"
        label="Position"
        item-title="label"
        item-value="id"
      />
      <v-select
        v-model="form.action_type"
        :items="ActionTypes"
        hide-details
        density="compact"
        label="Action Type"
        item-title="label"
        item-value="id"
      />

      <v-radio-group
        v-if="form.action_type === 'OPEN_OVERLAY'"
        v-model="form.overlay"
        width="100%"
        inline
      >
        <v-radio
          v-for="(item, i) in Overlays"
          :key="`Overlays${i}`"
          :value="item.id"
          :label="item.label"
          density="compact"
        />
      </v-radio-group>
      <v-textarea
        v-if="form.action_type === 'OPEN_EXTERNAL_PAGE'"
        v-model="form.external_link"
        hide-details
        density="compact"
        label="External Link"
      />
      <v-checkbox
        v-if="form.action_type === 'OPEN_EXTERNAL_PAGE'"
        v-model="form.is_same_tab"
        :false-value="0"
        :true-value="1"
        hide-details
        label="Open in same tab"
        density="compact"
      />
      <v-row
        v-if="form.action_type === 'OPEN_INTERNAL_PAGE'"
        no-gutters
        align-center
      >
        <v-col cols="3">
          <v-checkbox
            v-model="pageType"
            :value="'page'"
            hide-details
            label="Page"
          />
        </v-col>
        <v-col cols="9">
          <v-select
            v-model="form.page_id"
            :items="props.pages"
            label="Page"
            hide-details
            density="compact"
            item-title="name"
            item-value="id"
            class="w-100"
            :disabled="pageType !== 'page'"
          />
        </v-col>

        <v-col cols="3">
          <v-checkbox
            v-model="pageType"
            :value="'promotion'"
            hide-details
            label="Promotion"
          />
        </v-col>
        <v-col cols="9">
          <v-select
            v-model="form.promotion_id"
            :items="props.promotions"
            label="Promotion"
            hide-details
            density="compact"
            item-title="title"
            item-value="id"
            class="w-100"
            :disabled="pageType !== 'promotion'"
          />
        </v-col>
        <v-col cols="3">
          <v-checkbox
            v-model="pageType"
            :value="'game'"
            hide-details
            label="Game"
          />
        </v-col>
        <v-col cols="9">
          <v-autocomplete
            v-model="form.game_id"
            :disabled="pageType !== 'game'"
            hide-details
            density="compact"
            label="Game"
            :items="games"
            @update:search="searchGame"
            item-title="name"
            item-value="id"
          ></v-autocomplete>
        </v-col>
      </v-row>
    </v-card-text>
    <v-card-actions>
      <v-btn
        text="Save"
        prepend-icon="mdi-content-save-outline"
        flat
        @click.prevent="handleSave"
      />
    </v-card-actions>
  </v-card>
</template>
