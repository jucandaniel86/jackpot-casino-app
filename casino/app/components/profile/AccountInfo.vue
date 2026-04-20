<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { useAuthStore } from "~/core/store/auth";

//props
const props = defineProps({
  profile: {
    required: true,
    type: Object,
  },
});

//models
const personalInfo = ref({
  first_name: "",
  last_name: "",
  birthday: "",
  country: "",
  postal_code: "",
  address: "",
  city: "",
  phone: "",
});

const accountInfoForm = useTemplateRef<HTMLElement>("accountInfoForm");

//composables
const { user } = storeToRefs(useAuthStore());
const { t } = useI18n();

onMounted(() => {
  if (props.profile) {
    personalInfo.value = {
      ...personalInfo.value,
      ...props.profile.profile,
    };
  }
});
</script>
<template>
  <div ref="accountInfoForm">
    <v-row>
      <v-col cols="12" md="4" class="pb-0">
        <v-row v-if="user" no-gutters>
          <v-col cols="12">
            <div class="text-subtitle-1 text-white">{{ t("settings.email") }}</div>
            <v-text-field
              :value="user.email"
              :placeholder="t('settings.email')"
              density="compact"
              color="primary"
              :disabled="true"
            />
          </v-col>
          <v-col cols="12">
            <div class="text-subtitle-1 text-white">{{ t("settings.username") }}</div>
            <v-text-field
              :value="user.username"
              :placeholder="t('settings.username')"
              density="compact"
              color="primary"
              :disabled="true"
            />
          </v-col>
        </v-row>
      </v-col>
      <v-col cols="12" class="pb-0 pt-0">
        <v-row no-gutters>
          <v-col cols="12" md="8">
            <hr class="desp" />
          </v-col>
        </v-row>
      </v-col>
    </v-row>
  </div>
</template>
