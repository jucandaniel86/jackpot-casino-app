<script setup lang="ts">
import { useAuthStore } from "~/store/auth";
import { useRouter } from "vue-router";
const { user, authenticated } = storeToRefs(useAuthStore());
const store = useAuthStore();
const router = useRouter();

const logOut = async () => {
  await store.logout();
};
</script>
<template>
  <v-menu width="175">
    <template v-slot:activator="{ props }">
      <a dark v-bind="props" class="d-flex align-center mx-3">
        <v-avatar size="small" class="user-profile">
          <v-img
            class="header-profile-user"
            src="@/assets/images/users/user-dummy-img.jpg"
            alt="Header Avatar"
          />
        </v-avatar>
        <span class="text-start ms-xl-3" v-if="authenticated">
          <h4
            class="d-none d-xl-inline-block user-name-text font-weight-medium"
          >
            {{ user?.name }}  
          </h4>
          <span class="d-none d-xl-block user-name-sub-text">Founder</span>
        </span>
      </a>
    </template>
    <v-list density="compact" :lines="false" class="profile-list" nav>
      <h6 class="dropdown-header" v-if="authenticated">
        {{ user?.name }}  
      </h6>
      <v-divider class="my-2" />

      <v-list-item class="dropdown-item" @click="logOut">
        <i class="mdi mdi-logout text-muted" />
        <span class="align-middle" data-key="t-logout">Logout </span>
      </v-list-item>
    </v-list>
  </v-menu>
</template>
