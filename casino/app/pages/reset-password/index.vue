<script setup lang="ts">
import { useAuthStore } from '~/core/store/auth'

//models
const isValid = ref(false)
const isSuccess = ref(false)
const loading = ref(true)
const loadingPass = ref<boolean>(false)
const errors = ref<any>([])
const message = ref<string>('')
const password = ref({
  password: '',
  password_confirmation: '',
})

const displayPasswords = ref({
  password: false,
  password_confirmation: false,
})

//composables
const route = useRoute()
const router = useRouter()
const { isset, wait } = useUtils()
const { t } = useI18n()
const { success: alertSuccess } = useAlerts()
const { setToken, setUser } = useAuthStore()

//methods
const validateEmailReset = async () => {
  const { error } = await useApiPostFetch('/forgot-password/validate', {
    token: route.query.token,
    email: route.query.email,
  })
  if (error) {
    isValid.value = false
  } else {
    isValid.value = true
  }
  loading.value = false
}

const onLogin = async () => {
  loading.value = true
  const { data, success, error } = await useApiPostFetch('/player/login', {
    email: route.query.email,
    password: password.value.password,
  })

  if (!success) {
    errors.value = error.errors
    message.value = error.message
  } else {
    errors.value = []
    message.value = ''
    setToken(data.authorization.token)
    setUser(data.user)

    router.push('/')
    await wait(1000)
    alertSuccess(`Welcome ${data.user.username}`)
  }

  loading.value = false
}

const savePassword = async (): Promise<void> => {
  loadingPass.value = true
  const { data, success, error } = await useApiPostFetch('forgot-password/reset', {
    ...password.value,
    token: route.query.token,
    email: route.query.email,
  })

  if (!success) {
    errors.value = error.errors || []
    message.value = !isset(error.errors) ? error.message : ''
  } else {
    errors.value = []
    message.value = ''
    alertSuccess(data.message)
    onLogin()
    isSuccess.value = true
  }
  loadingPass.value = false
}

//mounted
onMounted(() => {
  validateEmailReset()
})
</script>

<template>
  <div>
    <div v-if="loading" class="d-flex justify-center align-center h-screen">
      <v-progress-circular indeterminate color="purple" />
    </div>
    <v-container v-if="!loading" class="py-12">
      <v-row>
        <v-col cols="12">
          <h1 class="text-center text-white mb-4">Reset Password</h1>
        </v-col>
      </v-row>
      <v-row>
        <v-col cols="12" md="6">
          <div v-if="isValid && !isSuccess" class="column-left">
            <v-row>
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
                  <button @click.prevent="displayPasswords.password = !displayPasswords.password">
                    <icon-eye v-if="!displayPasswords.password" />
                    <icon-eye-off v-if="displayPasswords.password" />
                  </button>
                </div>
              </v-col>
              <v-col cols="12" class="pb-0">
                <div class="text-subtitle-1 text-white">{{ t('settings.confirmPassword') }}*</div>
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
                      isset(errors.password_confirmation) ? errors.password_confirmation[0] : null
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
                <v-alert v-if="message">{{ message }}</v-alert>
                <v-btn
                  color="purple"
                  class="w-100 mt-3"
                  max-width="200"
                  :disabled="loadingPass"
                  :loading="loadingPass"
                  @click.prevent="savePassword"
                  >{{ t('settings.savePassword') }}</v-btn
                >
              </v-col>
            </v-row>
          </div>
          <div v-else>
            <v-alert type="error" variant="outlined" class="mb-4">
              Invalid or expired reset link. Please request a new password reset.
            </v-alert>
          </div>
        </v-col>
        <v-col cols="12" md="6">
          <v-img src="@/assets/imgs/reset-password.png" />
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>
<style lang="css" scoped>
@import '../../assets/css/components/Auth.css';
.password-wrapper .svg-icon {
  fill: #fff;
}
</style>
