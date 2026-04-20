<script lang="ts" setup>
import { menuItems } from "@/app/menu";
import { type MenuItemType } from "@/app/types";
import { onMounted, ref, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useLayoutStore } from "@/store/app";
const state = useLayoutStore();

const router = useRouter();
const route = useRoute();

onMounted(() => {
  setTimeout(() => {
    setupHorizontalMenu();
  }, 100);
  window.addEventListener("resize", setupHorizontalMenu);
});

const navMenus = ref<MenuItemType[]>(menuItems);

const path = computed(() => route.path);
const topBarColor = computed(() => state.topBarColor);
const mode = computed(() => state.mode);

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

const setupHorizontalMenu = () => {
  let isMoreMenu = false;
  const navData = menuItems.filter((menuItem) => !menuItem.isHeader);

  const horizontalMenuEle = document.getElementById("horizontal-menu");
  if (horizontalMenuEle) {
    const fullMenuWidth = horizontalMenuEle.parentElement?.clientWidth || 0;
    let totalItemsWidth = 0;
    let visibleItems = [];
    let hiddenItems = [];

    for (let i = 0; i < navData.length; i++) {
      const element: HTMLElement = horizontalMenuEle.children[i] as HTMLElement;
      const itemWidth = element?.offsetWidth || 0;
      totalItemsWidth += itemWidth;

      if (totalItemsWidth + 180 <= fullMenuWidth) {
        visibleItems.push(navData[i]);
      } else {
        hiddenItems.push(navData[i]);
      }
    }

    const moreMenuItem = {
      id: "more",
      label: "more",
      icon: "ph-briefcase",
      subMenu: hiddenItems,
      stateVariables: isMoreMenu,
      click: (e: any) => {
        e.preventDefault();
        isMoreMenu = !isMoreMenu;
      },
    };

    let updatedMenuItems = [...visibleItems];
    if (hiddenItems.length) {
      updatedMenuItems.push(moreMenuItem);
    }
    navMenus.value = updatedMenuItems;
  }
};

const onClick = (path: string) => {
  router.push(path);
};

// @ts-ignore
const selectedMenuItem: MenuItemType[] = computed(() => {
  return menuItems.find((data) => {
    if (data.link === path.value) {
      return data;
    } else {
      return data.subMenu?.find((subItem) => {
        if (subItem.link === path.value) {
          return subItem;
        } else {
          const data = subItem.subMenu?.find((nestedItem) => {
            return nestedItem.link === path.value;
          });
          return data;
        }
      });
    }
  });
});

const isActive = (item: MenuItemType) => {
  // @ts-ignore
  return item.id === selectedMenuItem.value.id || item.link === path.value;
};
</script>
<template>
  <div class="py-1 navbar-nav" id="horizontal-menu">
    <v-menu
      v-for="(menuItem, index) in navMenus"
      :key="`${menuItem.label}-${index}`"
      open-on-hover
      open-on-click
    >
      <template #activator="{ props }">
        <v-btn
          v-if="!menuItem.isHeader"
          variant="plain"
          v-bind="props"
          :to="menuItem.link"
          :active="isActive(menuItem)"
          class="nav-link menu-link cyan"
          :ripple="false"
        >  
          <i class="ph ph-lg" :class="menuItem.icon"></i>
          <span class="py-0 ms-2 me-1">
            {{ menuItem.label }}
          </span>
          <i v-if="menuItem.subMenu?.length" class="ph ph-sm ph-caret-down"></i>
        </v-btn>
      </template>
      <v-list
        v-if="menuItem.subMenu?.length"
        density="compact"
        class="navbar-nav"
        width="180"
        color="primary"
        :theme="getTheme"
      >
        <v-list-group
          v-for="(subMenu, index) in menuItem.subMenu"
          :key="`submenu-${subMenu.label}-${index}`"
          color="primary"
          class="nav-item"
          min-height="35"
          :to="subMenu.link"
          :value="subMenu.link"
          :active="subMenu.link === path"
        >
          <template #activator="{ props }">
            <v-list-item
              v-if="!subMenu.subMenu?.length"
              v-bind="props"
              class="nav nav-link menu-link"
              append-icon=""
              :to="subMenu.link"
              :value="subMenu.link"
              :active="subMenu.link === path"
              @click.prevent="subMenu.link && onClick(subMenu.link)"
            >
              <template #title>
                <span class="py-0">
                  {{ subMenu.label }}
                </span>
              </template>
            </v-list-item>
            <v-menu open-on-hover open-on-click location="end">
              <template #activator="{ props }">
                <v-list-item
                  v-if="subMenu.subMenu?.length"
                  v-bind="props"
                  class="nav nav-link menu-link"
                >
                  <template #title>
                    <span class="py-0">
                      {{ subMenu.label }}
                    </span>
                  </template>
                </v-list-item>
              </template>
              <v-list
                location="end"
                width="180"
                density="compact"
                color="primary"
                class="navbar-nav"
                :theme="getTheme"
              >
                <v-list-item
                  v-for="nestedItem in subMenu.subMenu"
                  :key="'nested-sub-menu-' + nestedItem.id"
                  class="nav-item nav-link menu-link"
                  :to="nestedItem.link"
                  :value="nestedItem.link"
                  :active="nestedItem.link == path"
                >
                  <template #title>
                    <span class="py-0">
                      {{ nestedItem.label }}
                    </span>
                  </template>
                </v-list-item>
              </v-list>
            </v-menu>
          </template>
        </v-list-group>
      </v-list>
    </v-menu>
  </div>
</template>
