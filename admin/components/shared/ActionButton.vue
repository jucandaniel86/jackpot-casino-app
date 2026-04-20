<script setup lang="ts">
import {
  ButtonActionTypesEnum,
  type ActionButtonType,
} from "~/core/types/ActionButton";
import type { PageType } from "~/core/types/Page";

type ActionButtonComponentType = {
  label: string;
  color?: string;
  defaultOptions?: any;
};

const props = withDefaults(defineProps<ActionButtonComponentType>(), {
  color: "primary",
});

//models
const pages = ref<PageType[]>([]);
const model = ref<ActionButtonType>({
  action: {
    type: ButtonActionTypesEnum.OPEN_INTERNAL_PAGE,
    slug: "",
    url: "",
    overlay: "",
    noFollow: false,
    isSameTab: false,
  },
  color: "primary",
  title: "",
});
const menuOpen = ref<boolean>(false);
const ACTION_TYPES = [
  { id: ButtonActionTypesEnum.OPEN_INTERNAL_PAGE, label: "Open Internal Page" },
  { id: ButtonActionTypesEnum.OPEN_EXTERNAL_PAGE, label: "Open External Page" },
  { id: ButtonActionTypesEnum.OPEN_OVERLAY, label: "Open Overlay" },
];
const availableColors = [
  "primary",
  "secondary",
  "purple",
  "blue",
  "yellow",
  "red",
  "pink",
  "black",
];
const availableOverlays = [
  { id: "wallet", label: "Wallet" },
  { id: "login", label: "Login" },
  { id: "register", label: "Register" },
];
const { label } = props;

//emits
const emits = defineEmits(["model:update"]);

//methods
const getPages = async (): Promise<void> => {
  const { data } = await useAPIFetch("/pages/list");
  pages.value = data.data;
};

//mounted
onMounted(async () => {
  await getPages();
  if (props.defaultOptions) {
    model.value = {
      ...model.value,
      ...props.defaultOptions,
    };
  }
});

watch(
  model,
  () => {
    emits("model:update", model.value);
  },
  { deep: true }
);
</script>
<template>
  <v-menu v-model="menuOpen" :close-on-content-click="false">
    <template #activator="{ props }">
      <div class="d-flex flex-column" style="max-width: 200px">
        <label v-if="label">{{ label }}</label>
        <v-btn v-bind="props" :color="model.color" flat>{{
          model.title || "Undefined"
        }}</v-btn>
      </div>
    </template>
    <v-card width="500" v-if="menuOpen">
      <v-card-text>
        <v-text-field
          v-model="model.title"
          hide-details
          density="compact"
          label="Button Label"
        />
        <v-select
          label="Color"
          v-model="model.color"
          :items="availableColors"
          hide-details
          density="compact"
        />
        <v-select
          label="Choose Action Type"
          v-model="model.action.type"
          :items="ACTION_TYPES"
          item-title="label"
          item-value="id"
          hide-details
          density="compact"
        ></v-select>

        <v-select
          v-if="model.action.type === ButtonActionTypesEnum.OPEN_INTERNAL_PAGE"
          v-model="model.action.slug"
          label="Choose internal page"
          :items="pages"
          item-title="name"
          item-value="slug"
          hide-details
          density="compact"
        />

        <v-select
          v-if="model.action.type === ButtonActionTypesEnum.OPEN_OVERLAY"
          v-model="model.action.overlay"
          label="Overlay"
          :items="availableOverlays"
          item-title="label"
          item-value="id"
          hide-details
          density="compact"
        />

        <div
          class="d-flex w-100 flex-column"
          v-if="model.action.type === ButtonActionTypesEnum.OPEN_EXTERNAL_PAGE"
        >
          <v-textarea
            v-model="model.action.url"
            label="External URL Page"
            hide-details
            density="compact"
          />
          <div class="d-flex ga-1">
            <v-checkbox
              v-model="model.action.isSameTab"
              label="Same Tab?"
              hide-details
              density="compact"
            />
            <v-checkbox
              v-model="model.action.noFollow"
              label="No Follow?"
              hide-details
              density="compact"
            />
          </div>
        </div>
        <div class="d-flex w-100 justify-end">
          <v-btn
            density="compact"
            color="primary"
            flat
            @click.prevent="menuOpen = false"
            >Save</v-btn
          >
        </div>
      </v-card-text>
    </v-card>
  </v-menu>
</template>
