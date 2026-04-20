<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { useAppStore } from '~/core/store/app'
import { useAuthStore } from '~/core/store/auth'
import { OverlaysTypes } from '~/core/types/Overlays'

//props
const props = defineProps({
  profile: {
    required: true,
    type: Object,
  },
})

//composables
const { setSnackbar } = useAppStore()
const { isset } = useUtils()
const { t } = useI18n()
const { success: alertSuccess, error: alertError } = useAlerts()
const { logout } = useAuthStore()
const { push } = useRouter()

//models
const settings = ref({
  marketing_emails: 1,
  hide_username: 1,
  two_factor_enabled: 0,
})

const password = ref({
  old_password: '',
  password: '',
  password_confirmation: '',
})

const displayPasswords = ref({
  old_password: false,
  password: false,
  password_confirmation: false,
})

const passInfo = ref(0)
const loading = ref<boolean>(false)
const loadingPass = ref<boolean>(false)
const errors = ref<any>([])
const message = ref<string>('')
//methods
const save = async (): Promise<void> => {
  loading.value = true
  const { data, success, error } = await useApiPostFetch('player/save-settings', settings.value)

  if (!success) {
    alertError(error.message)
  } else {
    alertSuccess(data.message)
    console.log(settings.value.two_factor_enabled, settings.value.two_factor_enabled === 1)

    if (parseInt(`${settings.value.two_factor_enabled}`) === 1) {
      await logout()
      push({ query: { overlay: OverlaysTypes.LOGIN }, path: '/' })
    }
  }
  loading.value = false
}

const savePassword = async (): Promise<void> => {
  loadingPass.value = true
  const { data, success, error } = await useApiPostFetch('player/change-password', password.value)

  if (!success) {
    errors.value = error.errors || []
    message.value = error.message
  } else {
    errors.value = []
    message.value = ''
    setSnackbar(data.message)
  }
  loadingPass.value = false
}

onMounted(() => {
  if (props.profile) {
    settings.value = {
      ...settings.value,
      ...props.profile.profile,
    }
  }
})
</script>
<template>
  <div>
    <v-row>
      <v-col cols="12" md="6">
        <v-row>
          <v-col cols="12">
            <h3 class="settings-title">{{ t('settings.general') }}</h3>
          </v-col>

          <v-col cols="12">
            <h3 class="settings-title mt-5">{{ t('settings.communication') }}</h3>
          </v-col>
          <v-col cols="12">
            <v-checkbox
              v-model="settings.marketing_emails"
              :true-value="1"
              :false-value="0"
              :label="t('settings.marketingMails')"
              hide-details
            />
          </v-col>
          <v-col cols="12">
            <h3 class="settings-title mt-5">{{ t('settings.privacy') }}</h3>
          </v-col>
          <v-col cols="12">
            <v-checkbox
              v-model="settings.two_factor_enabled"
              :true-value="'1'"
              :false-value="'0'"
              :label="t('settings.twoFactorAuthLabel')"
              :messages="[
                '*after you hit Save changes, you will be automaticaly logged out. On your next login you will have to set your Google Authenticator.',
              ]"
              :hide-details="false"
              class="custom-select"
            />
          </v-col>
          <v-col cols="12">
            <v-checkbox
              v-model="settings.hide_username"
              :true-value="1"
              :false-value="0"
              :label="t('settings.hideUsername')"
              hide-details
              class="custom-select"
            />
          </v-col>
          <v-col cols="12" class="pt-0">
            <v-btn
              color="purple"
              class="w-100"
              max-width="200"
              :disabled="loading"
              @click.prevent="save"
              >{{ t('settings.save') }}</v-btn
            >
          </v-col>
        </v-row>
      </v-col>
      <!-- end left side -->

      <!-- start right side -->
      <v-col cols="12" md="6">
        <h3 class="settings-title mb-5">{{ t('settings.security') }}</h3>
        <v-row>
          <v-col cols="12" class="pb-0">
            <v-expansion-panels v-model="passInfo" class="mb-6">
              <v-expansion-panel
                style="
                  border: 1px solid var(--accordion-header-background-color) !important;
                  border-top-left-radius: var(--accordion-border-radius) !important;
                  border-top-right-radius: var(--accordion-border-radius) !important;
                  padding-bottom: 0 !important;
                "
              >
                <v-expansion-panel-title>{{
                  t('settings.changePassword')
                }}</v-expansion-panel-title>
                <v-expansion-panel-text class="password-wrapper-expansion ml-0 pr-2 pl-0 mb-3">
                  <v-row>
                    <v-col cols="12" class="pb-0">
                      <div class="text-subtitle-1 text-white">{{ t('settings.oldPassword') }}*</div>
                      <div class="password-wrapper">
                        <v-text-field
                          v-model="password.old_password"
                          :type="displayPasswords.old_password ? 'text' : 'password'"
                          :placeholder="t('settings.oldPassword')"
                          hide-details="auto"
                          density="compact"
                          color="primary"
                          :error="isset(errors.old_password)"
                          :error-messages="
                            isset(errors.old_password) ? errors.old_password[0] : null
                          "
                        />
                        <button
                          @click.prevent="
                            displayPasswords.old_password = !displayPasswords.old_password
                          "
                        >
                          <icon-eye v-if="!displayPasswords.old_password" />
                          <icon-eye-off v-if="displayPasswords.old_password" />
                        </button>
                      </div>
                    </v-col>
                    <v-col cols="12" class="pb-0">
                      <div class="text-subtitle-1 text-white">{{ t('settings.newPassword') }}*</div>
                      <div class="password-wrapper">
                        <v-text-field
                          v-model="password.password"
                          :type="displayPasswords.password ? 'text' : 'password'"
                          :placeholder="t('settings.newPassword')"
                          hide-details="auto"
                          density="compact"
                          color="primary"
                          :error="isset(errors.password)"
                          :error-messages="isset(errors.password) ? errors.password[0] : null"
                        />
                        <button
                          @click.prevent="displayPasswords.password = !displayPasswords.password"
                        >
                          <icon-eye v-if="!displayPasswords.password" />
                          <icon-eye-off v-if="displayPasswords.password" />
                        </button>
                      </div>
                    </v-col>
                    <v-col cols="12" class="pb-0">
                      <div class="text-subtitle-1 text-white">
                        {{ t('settings.confirmPassword') }}*
                      </div>
                      <div class="password-wrapper">
                        <v-text-field
                          v-model="password.password_confirmation"
                          :type="displayPasswords.password_confirmation ? 'text' : 'password'"
                          placeholder="Confirm Password"
                          hide-details="auto"
                          density="compact"
                          color="primary"
                          :error="isset(errors.password_confirmation)"
                          :error-messages="
                            isset(errors.password_confirmation)
                              ? errors.password_confirmation[0]
                              : null
                          "
                        />
                        <button
                          @click.prevent="
                            displayPasswords.password_confirmation =
                              !displayPasswords.password_confirmation
                          "
                        >
                          <icon-eye v-if="!displayPasswords.password_confirmation" />
                          <icon-eye-off v-if="displayPasswords.password_confirmation" />
                        </button>
                      </div>
                    </v-col>

                    <v-col cols="12" class="pb-0">
                      <v-btn
                        color="purple"
                        class="w-100 mt-3"
                        max-width="200"
                        :disabled="loadingPass"
                        @click.prevent="savePassword"
                        >{{ t('settings.savePassword') }}</v-btn
                      >
                    </v-col>
                  </v-row>
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>
          </v-col>
        </v-row>
      </v-col>
      <!-- end right side -->
    </v-row>
  </div>
</template>
<style lang="css" scoped>
@import '../../assets/css/components/Auth.css';
.password-wrapper .svg-icon {
  fill: #fff;
}
</style>
<style>
.v-messages__message,
.v-messages {
  color: #fff;
}
</style>
