<script setup lang="ts">
import { useTheme } from "vuetify";
import { setAttributes } from "./app/menu";
import { useLayoutStore } from "./store/app";

const state = useLayoutStore();
const theme = useTheme();

const scrollTop = ref(document.documentElement.scrollTop);

onMounted(() => {
  const {
    layoutType,
    layoutTheme,
    layoutWidth,
    mode,
    position,
    topBarColor,
    sideBarSize,
    sideBarColor,
    dir,
  } = state;
  const initialThemeSetup: { [key: string]: string } = {
    ["data-layout"]: layoutType,
    ["data-theme"]: layoutTheme,
    ["data-layout-width"]: layoutWidth,
    ["data-bs-theme"]: mode,
    ["data-layout-position"]: position,
    ["data-topbar"]: topBarColor,
    ["data-sidebar-size"]: sideBarSize,
    ["data-sidebar"]: sideBarColor,
    ["dir"]: dir,
  };

  for (const key in initialThemeSetup) {
    setAttributes(key, initialThemeSetup[key]);
  }

  if (mode === "dark") {
    theme.global.name.value =
      layoutTheme === "default" ? "defaultThemeDark" : layoutTheme + "Dark";
  } else {
    theme.global.name.value =
      layoutTheme === "default" ? "defaultTheme" : layoutTheme;
  }

  addScrollEventListener();
});

const addScrollEventListener = () => {
  document.addEventListener("scroll", () => {
    scrollTop.value = document.documentElement.scrollTop;
  });
};

const onScrollTop = () => {
  window.scrollTo({
    top: 0,
    behavior: "smooth",
  });
};

onBeforeUnmount(() => {
  document.removeEventListener("scroll", () => {
    scrollTop.value = 0;
  });
});
</script>
<template>
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
</template>
