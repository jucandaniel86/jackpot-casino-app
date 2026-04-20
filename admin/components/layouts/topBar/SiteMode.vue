<script lang="ts" setup>
import { useLayoutStore } from "@/store/app";
import { useTheme } from "vuetify";
import { SITE_THEME } from "@/app/config";

const state = useLayoutStore();
const theme = useTheme();
const { LIGHT, DARK } = SITE_THEME;

const modes = [
  { title: "Default (light mode)", icon: "ph-sun", value: LIGHT },
  { title: "Dark", icon: "ph-moon", value: DARK },
  {
    title: "Auto (system default)",
    icon: "ph-moon-stars",
    value: LIGHT,
  },
];
const onModeChange = (mode: { [key: string]: string }) => {
  const element = document.getElementById("side-mode-icon");
  if (element) {
    element.className = "ph-2x ph";
    element.classList.add(mode.icon);
    state.changeMode(mode.value);

    if (state.mode === DARK) {
      theme.global.name.value =
        state.layoutTheme === "default"
          ? "defaultThemeDark"
          : state.layoutTheme + "Dark";
    } else {
      theme.global.name.value =
        state.layoutTheme === "default" ? "defaultTheme" : state.layoutTheme;
    }
  }
};
</script>
<template>
  <v-menu>
    <template v-slot:activator="{ props }">
      <v-btn
        variant="text"
        dark
        v-bind="props"
        icon
        class="mode-layout btn-ghost-dark"
      >
        <i class="ph ph-sun ph-2x" id="side-mode-icon"></i>
      </v-btn>
    </template>
    <v-list class="px-1" density="compact" :lines="false">
      <v-list-item
        class="dropdown-item"
        v-for="(mode, index) in modes"
        :key="index"
        @click="onModeChange(mode)"
      >
        <i :class="mode.icon" />
        {{ mode.title }}
      </v-list-item>
    </v-list>
  </v-menu>
</template>
