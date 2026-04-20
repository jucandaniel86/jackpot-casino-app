<script setup lang="ts">
// 	LG
// :
// {itemsPerRow: 6, panelWidthPercent: 100}
// MD
// :
// {itemsPerRow: 5, panelWidthPercent: 100}
// SM
// :
// {itemsPerRow: 4, panelWidthPercent: 100}
// XL
// :
// {itemsPerRow: 7, panelWidthPercent: 100}
// XS
// :
// {itemsPerRow: 2, panelWidthPercent: 100}

const tabs = ref([
  {
    label: "XL",
    content: "1920px > < 2560px",
    icon: "mdi-monitor",
    subtitle: "1080p to 1440p desktop",
  },
  {
    label: "LG",
    content: "1280px > < 1920px",
    icon: "mdi-laptop",
    subtitle: "Laptop to desktop",
  },
  {
    label: "MD",
    content: "960px > < 1280px",
    icon: "mdi-tablet",
    subtitle: "Large tablet to laptop",
  },
  {
    label: "SM",
    content: "600px > < 960px",
    icon: "mdi-crop-landscape",
    subtitle: "Small to medium tablet",
  },
  {
    label: "XS",
    content: "< 600px",
    icon: "mdi-cellphone",
    subtitle: "Small to large phone",
  },
]);

const tab = ref(tabs.value[0].label);
</script>
<template>
  <v-tabs v-model="tab" align-tabs="center" color="deep-purple-accent-4">
    <v-tab
      v-for="(tab, i) in tabs"
      :value="tab.label"
      :key="`tab${i}`"
      :prepend-icon="tab.icon"
      :text="tab.label"
    />
  </v-tabs>

  <v-tabs-window v-model="tab">
    <v-tabs-window-item v-for="(t, i) in tabs" :value="t.label">
      <v-container fluid>
        <v-chip size="x-small" label :prepend-icon="t.icon" class="mb-1">
          {{ t.subtitle }}
          ({{ t.content }})
        </v-chip>
        <slot :name="t.label" />
      </v-container>
    </v-tabs-window-item>
  </v-tabs-window>
</template>
