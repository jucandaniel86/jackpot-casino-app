<script setup lang="ts">
import type { GameT } from "../games/config";
import { type ContainerType } from "./setup";

type SectionContainerT = {
  item: ContainerType;
};
const props = defineProps<SectionContainerT>();

//models
const form = ref({
  aspectRatio: 0,
});
const resolutionConfig = ref({
  LG: {
    aspectRatio: 0,
    itemsPerRow: 0,
  },
  XL: {
    aspectRatio: 0,
    itemsPerRow: 0,
  },
  MD: {
    aspectRatio: 0,
    itemsPerRow: 0,
  },
  SM: {
    aspectRatio: 0,
    itemsPerRow: 0,
  },
  XS: {
    aspectRatio: 0,
    itemsPerRow: 0,
  },
});
const category = ref([]);
const games = ref<GameT[]>([]);

//emits
const emits = defineEmits(["onUpdate"]);

watch(
  resolutionConfig,
  () => {
    emits("onUpdate", {
      resolutionConfig: resolutionConfig.value,
      aspectRatio: form.value.aspectRatio,
    });
  },
  { deep: true }
);

watch(
  form,
  () => {
    emits("onUpdate", {
      resolutionConfig: resolutionConfig.value,
      aspectRatio: form.value.aspectRatio,
      category: category.value,
    });
  },
  { deep: true }
);

onMounted(() => {
  if (typeof props.item.data?.resolutionConfig !== "undefined") {
    let resConfig = props.item.data.resolutionConfig;
    if (typeof props.item.data.resolutionConfig === "string") {
      resConfig = JSON.parse(props.item.data.resolutionConfig);
    }
    resolutionConfig.value = { ...resConfig };
  }
  if (props.item.data?.aspectRatio) {
    form.value.aspectRatio = props.item.data.aspectRatio;
  }
});
</script>
<template>
  <div>
    <v-row>
      <v-col cols="12" class="position-relative">
        <div class="position-sticky top-0 left-0">
          <span class="d-flex ga-1 align-center mb-1">
            <v-icon icon="mdi-cog-sync" /> Settings
          </span>
          <v-text-field
            v-model="form.aspectRatio"
            label="Aspect Ratio Percentage"
            density="compact"
            hide-details
          />
          <SharedResolutionConfig>
            <template #XL>
              <v-text-field
                label="Items per row"
                hide-details
                density="compact"
                v-model="resolutionConfig.XL.itemsPerRow"
              />
              <v-text-field
                label="Aspect Ratio Percentage"
                hide-details
                density="compact"
                v-model="resolutionConfig.XL.aspectRatio"
              />
            </template>
            <template #LG>
              <v-text-field
                label="Items per row"
                hide-details
                density="compact"
                v-model="resolutionConfig.LG.itemsPerRow"
              />
              <v-text-field
                label="Aspect Ratio Percentage"
                hide-details
                density="compact"
                v-model="resolutionConfig.LG.aspectRatio"
              />
            </template>
            <template #MD>
              <v-text-field
                label="Items per row"
                hide-details
                density="compact"
                v-model="resolutionConfig.MD.itemsPerRow"
              />
              <v-text-field
                label="Aspect Ratio Percentage"
                hide-details
                density="compact"
                v-model="resolutionConfig.MD.aspectRatio"
              />
            </template>
            <template #SM>
              <v-text-field
                label="Items per row"
                hide-details
                density="compact"
                v-model="resolutionConfig.SM.itemsPerRow"
              />
              <v-text-field
                label="Aspect Ratio Percentage"
                hide-details
                density="compact"
                v-model="resolutionConfig.SM.aspectRatio"
              />
            </template>
            <template #XS>
              <v-text-field
                label="Items per row"
                hide-details
                density="compact"
                v-model="resolutionConfig.XS.itemsPerRow"
              />
              <v-text-field
                label="Aspect Ratio Percentage"
                hide-details
                density="compact"
                v-model="resolutionConfig.XS.aspectRatio"
              />
            </template>
          </SharedResolutionConfig>
        </div>
      </v-col>
    </v-row>
  </div>
</template>
