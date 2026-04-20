<script setup lang="ts">
import { ContainerSection, ContainerStatus, type ContainerType } from "./setup";
type SectionContainerT = {
  item: ContainerType;
  loading?: boolean;
  pageOrder: number;
  hasIncreaseOrder: boolean;
  hasDecreseOrder: boolean;
};
const props = defineProps<SectionContainerT>();
const statusColor = computed(() => {
  switch (props.item.status) {
    case ContainerStatus.DRAFT:
      return "orange";
    case ContainerStatus.PUBLISHED:
      return "green";
    case ContainerStatus.PRIVATE:
      return "red";
    default:
      "blue";
  }
});

const dialog = ref<boolean>(false);
const emits = defineEmits(["onDelete", "onSave", "onOrderChanged"]);
const dataRef = ref<any>(props.item.data);
const name = ref(props.item.name);
const handleDataChange = (payload: any) => (dataRef.value = payload);
const handleDelete = () => emits("onDelete", props.item.id);
const handleOrderChange = (to: number) => {
  emits("onOrderChanged", {
    from: props.pageOrder,
    to,
    section_id: props.item.id,
  });
};
const save = async () =>
  emits("onSave", {
    id: props.item.id,
    data: dataRef.value,
    name: name.value,
  });
</script>
<template>
  <div>
    <v-card class="mb-1" :loading="props.loading">
      <v-card-title class="d-flex justify-space-between">
        <div class="w-100">
          <h3 class="text-h6 d-flex ga-1">
            <v-chip density="compact" color="orange">{{
              props.pageOrder
            }}</v-chip>
            {{ props.item.name || "Untitled Section" }}
          </h3>
          <v-chip size="x-small"
            >Section ID:
            <span class="font-weight-bold">{{ props.item.id }}</span></v-chip
          >
          <v-chip size="x-small"
            >Type:
            <span class="font-weight-bold">{{
              props.item.container
            }}</span></v-chip
          >
          <v-chip size="x-small" :color="statusColor"
            >Status:
            <span class="font-weight-bold">{{
              props.item.status
            }}</span></v-chip
          >
        </div>

        <v-spacer></v-spacer>
        <v-btn icon="mdi-dots-vertical" variant="text" v-bind="props"></v-btn>
      </v-card-title>

      <v-card-actions class="d-flex justify-space-between">
        <div class="d-flex">
          <v-btn
            v-if="props.hasDecreseOrder"
            icon="mdi-arrow-up"
            density="compact"
            @click.prevent="handleOrderChange(props.pageOrder - 1)"
          />
          <v-btn
            v-if="props.hasIncreaseOrder"
            icon="mdi-arrow-down"
            density="compact"
            @click.prevent="handleOrderChange(props.pageOrder + 1)"
          />
        </div>
        <div class="d-flex ga-2">
          <v-btn
            density="compact"
            color="blue"
            prepend-icon="mdi-cog-sync"
            flat
            @click.prevent="dialog = true"
          >
            Settings
          </v-btn>

          <v-btn
            density="compact"
            color="red"
            flat
            prepend-icon="mdi-delete-outline"
            @click.prevent="handleDelete"
            >Delete</v-btn
          >
        </div>
      </v-card-actions>
    </v-card>
    <v-dialog v-model="dialog" max-width="900">
      <v-card class="w-100">
        <v-card-title class="d-flex justify-space-between ga-1 align-center">
          <div>
            <div class="d-flex w-100 align-center mb-1">
              <v-text-field
                v-model="name"
                density="compact"
                label="Section Name"
                hide-details
              />
            </div>
            <v-chip size="x-small"
              >Section ID:
              <span class="font-weight-bold">{{ props.item.id }}</span></v-chip
            >

            <v-chip size="x-small"
              >Type:
              <span class="font-weight-bold">{{
                props.item.container
              }}</span></v-chip
            >
            <v-chip size="x-small" :color="statusColor"
              >Status:
              <span class="font-weight-bold">{{
                props.item.status
              }}</span></v-chip
            >
          </div>
          <v-btn
            density="compact"
            icon="mdi-close"
            @click.prevent="dialog = false"
          />
        </v-card-title>
        <v-card-text class="SectionContainer" ref="SectionContainer">
          <SectionHtml
            v-if="props.item.container === ContainerSection.HTML"
            :item="props.item"
            @on-update="handleDataChange"
          />
          <SectionSeo
            v-if="props.item.container === ContainerSection.SEO"
            :item="props.item"
            @on-update="handleDataChange"
          />
          <SectionSearch
            v-if="props.item.container === ContainerSection.SEARCH"
            :item="props.item"
            @on-update="handleDataChange"
          />
          <SectionAccordion
            v-if="props.item.container === ContainerSection.ACCORDION"
            :item="props.item"
            @on-update="handleDataChange"
          />
          <SectionProviders
            v-if="props.item.container === ContainerSection.PROVIDER_LOGOS"
            :item="props.item"
            @on-update="handleDataChange"
          />
          <SectionCategory
            v-if="props.item.container === ContainerSection.GAMES_CATEGORY"
            :item="props.item"
            @on-update="handleDataChange"
          />
          <SectionGames
            v-if="props.item.container === ContainerSection.CATEGORY_HEADLESS"
            :item="props.item"
            @on-update="handleDataChange"
          />
          <SectionTabs
            v-if="props.item.container === ContainerSection.TABS"
            :item="props.item"
            @on-update="handleDataChange"
          />
          <SectionPromotions
            v-if="props.item.container === ContainerSection.PROMOTIONS"
            :item="props.item"
            @on-update="handleDataChange"
          />
          <SectionSlider
            v-if="props.item.container === ContainerSection.SLIDER"
            :item="props.item"
            @on-update="handleDataChange"
          />
          <SectionOffer
            v-if="props.item.container === ContainerSection.OFFER"
            :item="props.item"
            @on-update="handleDataChange"
          />
          <SectionFavourites
            v-if="props.item.container === ContainerSection.PLAYER_FAVORITES"
            :item="props.item"
            @on-update="handleDataChange"
          />
        </v-card-text>
        <v-card-actions>
          <div class="d-flex justify-end align-center">
            <v-btn
              density="compact"
              color="blue"
              flat
              :disabled="props.loading"
              :prepend-icon="props.loading ? 'mdi-reload' : 'mdi-content-save'"
              @click.prevent="save"
              >Save</v-btn
            >
          </div>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
<style lang="css" scoped>
.SectionContainer {
  max-height: 600px;
  height: 100%;
  overflow-x: hidden;
  overflow-y: scroll;
}
</style>
