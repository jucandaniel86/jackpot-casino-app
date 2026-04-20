<script lang="ts" setup>
import { menuItems } from "@/app/menu";
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { type MenuItemType } from "@/app/types";

const route = useRoute();
const router = useRouter();

const path = computed(() => route.path);
const onClick = (path: string) => {
  router.push(path);
};

const isActive = (item: MenuItemType) => {
  if (item.prefix && item.prefix === "/dashboard" && path.value === "/") {
    return true;
  } else if (item.link === path.value) {
    return true;
  } else if (item.prefix && path.value.includes(item.prefix)) {
    return true;
  }
  return false;
};
</script>
<template>
  <div class="rail-menu-wrapper">
    <v-menu
      v-for="menuItem in menuItems"
      :key="menuItem.id"
      open-on-hover
      open-on-click
      location="start top"
      width="212"
      class="rail-menu"
      permanent
    >
      <template v-if="!menuItem.isHeader" v-slot:activator="{ props }">
        <v-btn
          variant="text"
          dark
          v-bind="props"
          class="rail-main-menu rail-main-menu-icon"
          height="45"
          width="45"
          :to="menuItem.link"
          :active="isActive(menuItem)"
          :value="menuItem.link"
          @click.prevent="menuItem.link && onClick(menuItem.link)"
        >
          <i class="ph ph-xl" :class="menuItem.icon" />
        </v-btn>
      </template>
      <div v-if="menuItem.subMenu" class="navbar-menu">
        <v-list
          class="navbar-nav"
          id="navbar-nav"
          density="compact"
          theme="dark"
        >
          <v-list-item>
            <span class="rail-menu-title py-0">
              {{ $t(`t-${menuItem.label}`) }}
            </span>
          </v-list-item>
          AA
          <v-list-group
            v-for="(subMenu, index) in menuItem.subMenu"
            :key="`submenu-${subMenu.label}-${index}`"
            class="py-0 nav nav-sm"
            height="37"
            :to="subMenu.link"
            :active="subMenu.link === path"
            :value="subMenu.link"
          >
            <template #activator="{ props }">
              <v-list-item
                v-if="!subMenu.subMenu?.length"
                append-icon=""
                :to="subMenu.link"
                :active="subMenu.link === path"
                :value="subMenu.link"
                @click.prevent="subMenu.link && onClick(subMenu.link)"
              >
                <template #title>
                  <span class="nav-link menu-link py-0">
                    {{ subMenu.label }}
                  </span>
                </template>
              </v-list-item>
              <v-menu
                open-on-hover
                open-on-click
                location="end top"
                class="rail-menu"
                width="210"
              >
                <template #activator="{ props }">
                  <v-list-item
                    v-if="subMenu.subMenu?.length"
                    v-bind="props"
                    class="nav nav-link menu-link"
                  >
                    <template #title>
                      <span class="py-0 ps-4">
                        {{ $t(`t-${subMenu.label}`) }}
                      </span>
                    </template>
                  </v-list-item>
                </template>
                <div class="navbar-menu">
                  <v-list
                    location="end"
                    width="180"
                    density="compact"
                    class="navbar-nav"
                    theme="dark"
                  >
                    <v-list-item
                      v-for="nestedItem in subMenu.subMenu"
                      :key="'nested-sub-menu-' + nestedItem.id"
                      class="nav-item nav-link menu-link"
                      :to="nestedItem.link"
                      :value="nestedItem.link"
                      :active="nestedItem.link == path"
                      @click.prevent="
                        nestedItem.link && onClick(nestedItem.link)
                      "
                    >
                      <template #title>
                        <span class="py-0">
                          {{ $t(`t-${nestedItem.label}`) }}
                        </span>
                      </template>
                    </v-list-item>
                  </v-list>
                </div>
              </v-menu>
            </template>
          </v-list-group>
        </v-list>
      </div>
    </v-menu>
  </div>
</template>
