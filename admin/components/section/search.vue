<script setup lang="ts">
import { type ContainerType } from "./setup";

type SectionContainerT = {
  item: ContainerType;
};
const props = defineProps<SectionContainerT>();

//models
const form = ref({
  focusWidthPercent: "",
  iconColor: "",
  textColor: "",
  widthPercent: "",
});

const resolutionConfig = ref({
  LG: {
    panelWidthPercent: 0,
    itemsPerRow: 0,
    isVisible: 1,
  },
  XL: {
    panelWidthPercent: 0,
    itemsPerRow: 0,
    isVisible: 1,
  },
  MD: {
    panelWidthPercent: 0,
    itemsPerRow: 0,
    isVisible: 1,
  },
  SM: {
    panelWidthPercent: 0,
    itemsPerRow: 0,
    isVisible: 1,
  },
  XS: {
    panelWidthPercent: 0,
    itemsPerRow: 0,
    isVisible: 1,
  },
});

const emits = defineEmits(["onUpdate"]);

watch(
  form,
  () => {
    emits("onUpdate", {
      resolutionConfig: resolutionConfig.value,
      focusWidthPercent: form.value.focusWidthPercent,
      iconColor: form.value.iconColor,
      textColor: form.value.textColor,
      widthPercent: form.value.widthPercent,
    });
  },
  { deep: true }
);

watch(
  resolutionConfig,
  () => {
    emits("onUpdate", {
      resolutionConfig: resolutionConfig.value,
      focusWidthPercent: form.value.focusWidthPercent,
      iconColor: form.value.iconColor,
      textColor: form.value.textColor,
      widthPercent: form.value.widthPercent,
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
  if (props.item.data?.focusWidthPercent) {
    form.value.focusWidthPercent = props.item.data.focusWidthPercent;
  }
  if (props.item.data?.iconColor) {
    form.value.iconColor = props.item.data.iconColor;
  }
  if (props.item.data?.textColor) {
    form.value.textColor = props.item.data.textColor;
  }
  if (props.item.data?.widthPercent) {
    form.value.widthPercent = props.item.data.widthPercent;
  }
});
</script>
<template>
  <v-row>
    <v-col cols="6">
      <span class="text-h6">Input Settings</span>
      <v-text-field
        v-model="form.widthPercent"
        label="Width Percent"
        hide-details
        density="compact"
      />
      <v-text-field
        v-model="form.focusWidthPercent"
        label="Focus Width Percent"
        hide-details
        density="compact"
      />
      <v-text-field
        v-model="form.iconColor"
        label="Icon Color"
        hide-details
        density="compact"
      />
      <v-text-field
        v-model="form.textColor"
        label="Text Color"
        hide-details
        density="compact"
      />
    </v-col>
    <v-col cols="6">
      <SharedResolutionConfig>
        <template #XL>
          <v-text-field
            v-model="resolutionConfig.XL.itemsPerRow"
            label="Items per row"
            hide-details
            density="compact"
          />
          <v-text-field
            v-model="resolutionConfig.XL.panelWidthPercent"
            label="Panel width percent"
            hide-details
            density="compact"
          />
          <v-checkbox
            v-model="resolutionConfig.XL.isVisible"
            label="Visible"
            hide-details
            density="compact"
            :false-value="0"
            :true-value="1"
          />
        </template>
        <template #LG>
          <v-text-field
            v-model="resolutionConfig.LG.itemsPerRow"
            label="Items per row"
            hide-details
            density="compact"
          />
          <v-text-field
            v-model="resolutionConfig.LG.panelWidthPercent"
            label="Panel width percent"
            hide-details
            density="compact"
          />
          <v-checkbox
            v-model="resolutionConfig.LG.isVisible"
            label="Visible"
            hide-details
            density="compact"
            :false-value="0"
            :true-value="1"
          />
        </template>
        <template #MD>
          <v-text-field
            v-model="resolutionConfig.MD.itemsPerRow"
            label="Items per row"
            hide-details
            density="compact"
          />
          <v-text-field
            v-model="resolutionConfig.MD.panelWidthPercent"
            label="Panel width percent"
            hide-details
            density="compact"
          />
          <v-checkbox
            v-model="resolutionConfig.MD.isVisible"
            label="Visible"
            hide-details
            density="compact"
            :false-value="0"
            :true-value="1"
          />
        </template>
        <template #SM>
          <v-text-field
            v-model="resolutionConfig.SM.itemsPerRow"
            label="Items per row"
            hide-details
            density="compact"
          />
          <v-text-field
            v-model="resolutionConfig.SM.panelWidthPercent"
            label="Panel width percent"
            hide-details
            density="compact"
          />
          <v-checkbox
            v-model="resolutionConfig.SM.isVisible"
            label="Visible"
            hide-details
            density="compact"
            :false-value="0"
            :true-value="1"
          />
        </template>
        <template #XS>
          <v-text-field
            v-model="resolutionConfig.XS.itemsPerRow"
            label="Items per row"
            hide-details
            density="compact"
          />
          <v-text-field
            v-model="resolutionConfig.XS.panelWidthPercent"
            label="Panel width percent"
            hide-details
            density="compact"
          />
          <v-checkbox
            v-model="resolutionConfig.XS.isVisible"
            label="Visible"
            hide-details
            density="compact"
            :false-value="0"
            :true-value="1"
          />
        </template>
      </SharedResolutionConfig>
    </v-col>
  </v-row>
</template>
