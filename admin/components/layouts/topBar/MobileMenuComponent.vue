<script lang="ts" setup>
import { computed } from "vue";

import { menuItems } from "@/app/menu";
import { useRoute, useRouter } from "vue-router";
import { useLayoutStore } from "@/store/app";

const route = useRoute();
const router = useRouter();

const path = computed(() => route.path);
const mode = computed(() => state.mode);

const state = useLayoutStore();

const topBarColor = computed(() => state.topBarColor);

const getTheme = computed(() => {
  const isDark = topBarColor.value === "dark" || mode.value === "dark";
  const layoutTheme = state.layoutTheme;
  if (isDark) {
    return layoutTheme === "default"
      ? "defaultThemeDark"
      : layoutTheme + "Dark";
  }

  return layoutTheme === "default" ? "defaultTheme" : layoutTheme;
});

const onClick = (path: string) => {
  router.push(path);
};
</script>
<template>
  <v-card>
    <v-list
      height="350px"
      color="primary"
      :theme="getTheme"
      open-strategy="single"
    >
      <v-list-group
        v-for="(menuItem, index) in menuItems"
        :key="`${menuItem.label}-${index}`"
        class="nav-item"
      >
        <template #activator="{ props }">
          <v-list-item
            v-if="
              !(menuItem.subMenu && menuItem.subMenu.length) && menuItem.link
            "
            :data-key="`t-${menuItem.label}`"
            append-icon=""
            class="py-0 ps-5"
            :value="menuItem.link"
            :active="menuItem.link === path"
            :to="menuItem.link"
            @click.prevent="menuItem.link && onClick(menuItem.link)"
          >
            <template #title>
              <span class="nav-link menu-link d-flex align-center">
                <i :class="menuItem.icon" class="ph-lg me-2"></i>
                <span>{{ menuItem.label }}</span>
              </span>
            </template>
          </v-list-item>
          <v-list-item
            v-if="menuItem.subMenu && menuItem.subMenu.length"
            :data-key="`t-${menuItem.label}`"
            v-bind="props"
            class="py-0 nav-link ps-5"
          >
            <template #title>
              <span class="nav-link menu-link d-flex align-center">
                <i :class="menuItem.icon" class="ph-lg me-2"></i>
                <span>{{ menuItem.label }}</span>
              </span>
            </template>
            <template #append="{ isActive }">
              <i
                :class="isActive ? 'ph ph-caret-up' : 'ph ph-caret-down'"
                class="ms-2"
              ></i>
            </template>
          </v-list-item>
        </template>
        <v-list-group
          v-for="(subMenu, index) in menuItem.subMenu"
          :key="`submenu-${subMenu.label}-${index}`"
          :value="subMenu.link"
          :active="subMenu.link === path"
          :to="subMenu.link"
        >
          <template #activator="{ props }">
            <v-list-item
              class="py-0 nav nav-sm nav-link rail-navigation-list"
              density="compact"
              v-bind="props"
              :value="subMenu.link"
              :active="subMenu.link === path"
              :to="subMenu.link"
              @click.prevent="subMenu.link && onClick(subMenu.link)"
            >
              <template #title>
                <span class="nav-link menu-link py-0">
                  {{ subMenu.label }}
                </span>
              </template>
              <template #append="{ isActive }">
                <i
                  v-if="subMenu.subMenu?.length"
                  :class="isActive ? 'ph ph-caret-up' : 'ph ph-caret-down'"
                ></i>
              </template>
            </v-list-item>
          </template>

          <v-list-item
            v-for="(nestedItem, index) in subMenu.subMenu"
            :key="index"
            class="py-0 nav nav-sm rail-navigation-list"
            density="compact"
            :to="nestedItem.link"
          >
            <template #title>
              <router-link
                v-if="nestedItem.link"
                :to="{ path: nestedItem.link }"
              >
                <span class="nav-link menu-link py-0">
                  {{ nestedItem.label }}
                </span>
              </router-link>
            </template>
          </v-list-item>
        </v-list-group>
      </v-list-group>
    </v-list>
  </v-card>
</template>
