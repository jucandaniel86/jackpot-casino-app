<script setup lang="ts">
//props
type IconT = {
  icon?: string;
};
type IconItemT = {
  label: string;
  svg: string;
};
const props = defineProps<IconT>();

//models
const selectedIcon = ref<string>(props.icon || "");
const loading = ref<boolean>(false);
const search = ref("");
const items = ref<IconItemT[]>([]);
const allIcons = ref("");
const dialog = ref<boolean>(false);
const model = defineModel();
const addMode = ref<boolean>(false);
const addForm = ref({
  label: "",
  icon: "",
});

const selectIcon = (icon: string) => (selectedIcon.value = icon);

//computed
const icons = computed(() => {
  return items.value.filter((item) =>
    item.label.toLowerCase().includes(search.value.toLowerCase())
  );
});

//methods
const getIcons = async (): Promise<void> => {
  loading.value = true;
  const { data } = await useAPIFetch("/icons/list");
  if (data) {
    items.value = data.data;
    allIcons.value = Array.from(data.data)
      .map((icon: any) => icon.icon)
      .join(" ");
  }
  loading.value = false;
};

const resetAddForm = () => {
  addForm.value = {
    icon: "",
    label: "",
  };
};

const saveIcon = async (): Promise<any> => {
  const { data } = await useApiPostFetch("/icons/save", addForm.value);
  if (data) {
    await getIcons();
    resetAddForm();
    addMode.value = false;
    return useNuxtApp().$toast.success(data.message);
  }

  return useNuxtApp().$toast.error(data.message);
};

const selectAndClose = () => {
  if (selectedIcon.value === "") {
    return useNuxtApp().$toast.warning("Plase select a icon");
  }
  model.value = selectedIcon.value;
  dialog.value = false;
};

const unSelect = () => {
  model.value = "";
  selectedIcon.value = "";
};

//watchers
watch(
  props,
  () => {
    if (props.icon) {
      selectedIcon.value = props.icon;
    }
  },
  { deep: true }
);

//onMounted
onMounted(() => {
  getIcons();
  if (props.icon) {
    selectIcon(props.icon);
  }
});
</script>
<template>
  <div class="d-none" v-html="allIcons" v-memo="allIcons" />

  <v-dialog v-model="dialog" max-width="500">
    <v-card class="mx-auto w-100" max-width="500" :loading="loading">
      <v-sheet class="pa-4" color="surface-variant">
        <div class="d-flex justify-space-between ga-1 align-center">
          <v-text-field
            v-model="search"
            hide-details
            placeholder="Search in available icons"
            density="compact"
            clearable
            :disabled="addMode"
          />
        </div>
      </v-sheet>

      <v-card-text class="position-relative">
        <v-progress-linear v-if="loading" />
        <div
          class="d-flex justify-start align-center flex-wrap ga-1 scrollable"
        >
          <v-btn
            flat
            :variant="item.label === selectedIcon ? 'flat' : 'tonal'"
            color="blue"
            border="1"
            v-for="(item, i) in icons"
            :key="`Item${i}`"
            @click.prevent="selectIcon(item.label)"
            class="icon-btn"
          >
            <v-tooltip activator="parent" location="top">{{
              item.label
            }}</v-tooltip>
            <svg
              viewBox="0 0 24 24"
              class="icon"
              aria-describedby="v-tooltip-v-0-4"
            >
              <use :xlink:href="`#${item.label}`"></use>
            </svg>
          </v-btn>
        </div>

        <v-sheet
          v-if="addMode"
          class="position-absolute top-0 left-0 w-100 h-100"
        >
          <v-text-field
            v-model="addForm.label"
            label="Label"
            density="compact"
            hide-details
          />
          <v-textarea
            v-model="addForm.icon"
            label="Icon"
            density="compact"
            hide-details
          />
          <div class="d-flex justify-space-between pa-1">
            <v-btn
              prepend-icon="mdi-content-save-alert-outline"
              size="small"
              variant="flat"
              color="blue"
              @click.prevent="saveIcon"
              >Save</v-btn
            >

            <v-btn
              size="small"
              variant="flat"
              color="red"
              prepend-icon="mdi-close"
              @click.prevent="addMode = false"
              >Close</v-btn
            >
          </div>
        </v-sheet>
      </v-card-text>
      <v-card-actions class="d-flex justify-space-between ga-1">
        <div class="d-flex ga-1">
          <v-btn
            size="small"
            variant="flat"
            color="blue"
            prepend-icon="mdi-plus"
            @click.prevent="addMode = true"
            >Add new Icon</v-btn
          >
          <v-btn
            size="small"
            variant="flat"
            color="blue"
            prepend-icon="mdi-form-select"
            @click.prevent="selectAndClose"
            >Select Icon</v-btn
          >
          <v-btn
            v-if="selectedIcon"
            size="small"
            variant="flat"
            color="blue"
            prepend-icon="mdi-form-select"
            @click.prevent="unSelect"
            >Unselect</v-btn
          >
        </div>

        <v-btn
          size="small"
          variant="flat"
          color="red"
          prepend-icon="mdi-close"
          @click.prevent="dialog = false"
          >Close</v-btn
        >
      </v-card-actions>
    </v-card>
    <template v-slot:activator="{ props: activatorProps }">
      <v-text-field
        v-bind="activatorProps"
        density="compact"
        v-model="model"
        hide-details
        label="Icon"
        readonly
      />
    </template>
  </v-dialog>
</template>
<style>
.icon {
  fill: currentColor;
  width: 20px;
  height: 20px;
  color: currentColor;
  display: inline-block;
  flex-shrink: 0;
  font-size: 24px;
  vertical-align: middle;
  box-sizing: border-box;
}
.v-btn--variant-flat .icon {
  fill: #fff;
  color: #fff;
}
.icon-btn {
  max-width: 40px;
  width: 40px;
  height: 40px !important;
  max-height: auto;
}
.cls-18880 {
  fill-rule: evenodd;
  stroke-width: 0px;
}
.cls-110656 {
  fill-rule: evenodd;
}
.cls-17326 {
  fill-rule: evenodd;
}
.cls-1777 {
  fill-rule: evenodd;
}
.scrollable {
  overflow-x: hidden;
  overflow-y: scroll;
  max-height: 300px;
  height: 100%;
}
</style>
