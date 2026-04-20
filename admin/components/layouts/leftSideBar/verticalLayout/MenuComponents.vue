<script lang="ts" setup>
import { computed } from "vue";
import { menuItems } from "@/app/menu";
import { useLayoutStore } from "@/store/app";
import { SIDEBAR_SIZE } from "@/app/config";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "~/store/auth";

const state = useLayoutStore();
const route = useRoute();
const router = useRouter();
const { user } = useAuthStore();

const path = computed(() => route.path);

const isCompactSideBar = computed(() => {
  return state.sideBarSize === SIDEBAR_SIZE.COMPACT;
});

const onClick = (path: string, isSingleLevel?: boolean) => {
  if (isSingleLevel) {
    const openListEle = document.querySelector(".v-list-group--open");
    if (openListEle) {
      const titleEle = document.querySelector(
        ".v-list-group--open .v-list-item--active"
      );
      if (titleEle) {
        const listItemsEle: any = document.querySelector(
          ".v-list-group--open .v-list-group__items"
        );
        if (listItemsEle) {
          const appendIcon = document.querySelector(
            ".v-list-group--open .v-list-item--active .v-list-item__append .ph-caret-up"
          );

          if (appendIcon) {
            appendIcon.classList.remove("ph-caret-up");
            appendIcon.classList.add("ph-caret-down");
            listItemsEle.style.display = "none";
            titleEle.classList.remove("v-list-item--active");
            openListEle.classList.remove("v-list-group--open");
          }
        }
      }
    }
  }
  router.push(path);
};

const USER_RIGHTS = {
  operator: ["HeaderMenu", "sideBarDashboard", "sideBarReports"],
  admin: [],
};
const USER_TYPE = "admin";
</script>
<template>
  <v-container fluid class="py-0 px-3">
    <v-list
      class="navbar-nav h-100 vertical-menu-component pt-0"
      id="navbar-nav"
      open-strategy="single"
    >
      <v-list-group
        v-for="(menuItem, index) in menuItems"
        :key="`${menuItem.label}-${index}`"
        :class="menuItem.isHeader ? 'menu-title' : 'nav-item'"
      >
        <template #activator="{ props }">
          <v-list-item
            v-if="menuItem.isHeader"
            :data-key="`t-${menuItem.label}`"
            prepend-icon=""
            class="px-2"
            variant="text"
            append-icon=""
          >
            <template #title>
              <div class="menu-title">
                {{ menuItem.label }}
              </div>
            </template>
          </v-list-item>
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
            height="45"
            min-height="45"
            @click.prevent="menuItem.link && onClick(menuItem.link, true)"
          >
            <template #title>
              <router-link :to="menuItem.link">
                <div
                  class="nav-link menu-link"
                  :class="isCompactSideBar ? 'pa-2' : 'd-flex align-center'"
                >
                  <i :class="menuItem.icon" class="ph-lg" />
                  <div>{{ menuItem.label }}</div>
                </div>
              </router-link>
            </template>
          </v-list-item>
          <v-list-item
            v-if="menuItem.subMenu && menuItem.subMenu.length"
            :data-key="`t-${menuItem.label}`"
            v-bind="props"
            class="py-0 nav-link ps-5 menu-header-title"
            height="45"
            min-height="45"
          >
            <template #title>
              <span
                class="nav-link menu-link"
                :class="isCompactSideBar ? 'pa-2' : 'd-flex align-center'"
              >
                <i :class="menuItem.icon" class="ph-lg"></i>
                <span>{{ menuItem.label }}</span>
              </span>
            </template>
            <template #append="{ isActive }">
              <i
                v-if="!isCompactSideBar"
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
              class="py-0 nav nav-sm nav-link sub-menu-list-item"
              density="compact"
              v-bind="props"
              :value="subMenu.link"
              :active="subMenu.link === path"
              :to="subMenu.link"
              height="38"
              min-height="38"
              @click.prevent="subMenu.link && onClick(subMenu.link)"
            >
              <template #title>
                <span class="nav-link menu-link py-0">
                  {{ subMenu.label }}
                </span>
              </template>
              <template #append="{ isActive }">
                <i
                  v-if="!isCompactSideBar && subMenu.subMenu?.length"
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
            height="38"
            min-height="38"
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
              <span v-else class="nav-link menu-link py-0">
                {{ nestedItem.label }}
              </span>
            </template>
          </v-list-item>
        </v-list-group>
      </v-list-group>
    </v-list>
  </v-container>
</template>
