<script setup lang="ts">
import { APP_SECTIONS, type ContainerType } from "./setup";

type SectionT = {
  loading?: boolean;
  sections: ContainerType[];
  pageId: number | string | string[];
  isTag: boolean;
};
const props = withDefaults(defineProps<SectionT>(), {
  loading: false,
  isTag: false,
});

const SectionContainerRef = ref("SectionContainerRef");
//composables
const { confirmDelete, axiosErrorAlert } = useAlert();

//models
const newSectionMenu = ref<boolean>(false);
const componentSaved = ref();

//emitters
const emits = defineEmits(["onAddNew", "reloadList"]);

//methods
const handleAddNew = (section: any) => {
  emits("onAddNew", section);
  newSectionMenu.value = false;
  //@ts-ignore
  SectionContainerRef.value.scrollToIndex(props.sections.length - 1);
};
const deleteItem = async (id: string) => {
  confirmDelete(async (_result: any) => {
    const { data, success, error } = await useApiDeleteFetch(
      "/sections/delete",
      {
        id,
      }
    );
    if (success) {
      if (data.data.success) {
        useNuxtApp().$toast.success(data.message);
        emits("reloadList");
      } else {
        useNuxtApp().$toast.error(data.data.msg);
      }

      return;
    }
    if (error) {
      return axiosErrorAlert(error);
    }
  });
};
const save = async (payload: any): Promise<void> => {
  componentSaved.value = payload.id;
  const { data, success, error } = await useApiPostFetch(
    "/sections/save-data",
    {
      ...payload,
    }
  );
  if (success) {
    useNuxtApp().$toast.success(data.message);
    emits("reloadList");
  } else {
    useNuxtApp().$toast.error("Something went wrong");
  }
  componentSaved.value = null;
};

const moveItem = (from: number, to: number) => {
  // remove `from` item and store it
  let lSections = [...props.sections];
  var f = lSections.splice(from, 1)[0];
  // insert stored item into position `to`
  lSections.splice(to, 0, f);

  return lSections.map((el, i) => ({ id: el.id, pageOrder: i }));
};

const changeOrder = async (payload: any): Promise<void> => {
  componentSaved.value = payload.page_id;

  const order = moveItem(payload.from, payload.to);

  const { data, success, error } = await useApiPostFetch(
    "/sections/change-order",
    {
      order,
      page_id: props.pageId,
      tag: props.isTag,
    }
  );
  if (success) {
    useNuxtApp().$toast.success(data.message);
    emits("reloadList");
  } else {
    useNuxtApp().$toast.error("Something went wrong");
  }
  componentSaved.value = null;
};
</script>
<template>
  <v-card
    :loading="props.loading"
    :disabled="props.loading"
    color="transparent"
    elevation="0"
  >
    <v-card-title class="d-flex justify-end ga-1">
      <v-menu
        v-model="newSectionMenu"
        :close-on-content-click="false"
        location="bottom center"
      >
        <template v-slot:activator="{ props }">
          <v-btn color="indigo" v-bind="props" variant="flat">
            Add new section
          </v-btn>
        </template>

        <v-card min-width="300">
          <v-list density="compact">
            <v-list-item
              v-for="(section, i) in APP_SECTIONS"
              :title="section.label"
              :key="`Section${i}`"
            >
              <template v-slot:append>
                <v-btn
                  icon="mdi-plus"
                  variant="flat"
                  density="compact"
                  @click.prevent="handleAddNew(section)"
                ></v-btn>
              </template>
            </v-list-item>
          </v-list>
        </v-card>
      </v-menu>
    </v-card-title>
    <v-card-text class="h-100 overflow-y">
      <v-virtual-scroll
        :items="props.sections"
        height="500"
        ref="SectionContainerRef"
      >
        <template v-slot:default="{ item, index }">
          <SectionContainer
            :key="item.id"
            :item="item"
            :page-order="index"
            :loading="componentSaved === item.id"
            :has-decrese-order="index > 0"
            :has-increase-order="index + 1 < props.sections.length"
            @on-delete="deleteItem"
            @on-save="save"
            @on-order-changed="changeOrder"
          />
        </template>
      </v-virtual-scroll>
    </v-card-text>
  </v-card>
</template>
