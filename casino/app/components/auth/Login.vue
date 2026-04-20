<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import TwoFactorSetupStep from '~/components/auth/TwoFactorSetupStep.vue'
import { useAuthStore } from '~/core/store/auth'
import { OverlaysTypes } from '~/core/types/Overlays'

//models
const username = ref('')
const password = ref('')
const message = ref('')
const errors = ref<any>([])
const loading = ref<boolean>(false)
const showPassword = ref<boolean>(false)
const step = ref<number>(1)
const validLogin = ref<boolean>(false)
const code = ref<string>('')
const loginToken = ref<string>('')
const twoFactorSetupRequired = ref<boolean>(false)
const twoFactorOtpAuthUrl = ref<string>('')
const twoFactorSecret = ref<string>('')
const twoFactorExpiresIn = ref<number | null>(null)

//composables
const { emailRules } = useValidation()
const { getTwoFactorPayload } = useTwoFactorAuth()
const { setToken, setUser } = useAuthStore()
const { replace } = useRouter()
const { t } = useI18n({ useScope: 'global' })
const { success: alertSuccess } = useAlerts()

//emitters
const emitters = defineEmits(['changeView'])

//computed
const noContent = computed(() => {
  if (step.value === 2) {
    return !code.value
  }

  return !username.value || !password.value
})

//methids
const forgotPassword = () => replace({ query: { overlay: OverlaysTypes.FORGOT } })
const onChangeView = () => emitters('changeView', 'register')

const onLogin = async () => {
  loading.value = true
  message.value = ''
  errors.value = []
  twoFactorSetupRequired.value = false
  twoFactorOtpAuthUrl.value = ''
  twoFactorSecret.value = ''
  twoFactorExpiresIn.value = null

  const { data, success, error } = await useApiPostFetch('/player/login', {
    email: username.value,
    password: password.value,
  })

  const twoFactorFromData = getTwoFactorPayload(data)
  const twoFactorFromError = getTwoFactorPayload(error)
  const twoFactorResponse =
    twoFactorFromData.requiresTwoFactor || twoFactorFromError.requiresTwoFactor
      ? twoFactorFromData.requiresTwoFactor
        ? twoFactorFromData
        : twoFactorFromError
      : null

  if (twoFactorResponse) {
    step.value = 2
    loginToken.value = twoFactorResponse.loginToken
    message.value = twoFactorResponse.message || t('auth.twoFactorPrompt')
    twoFactorSetupRequired.value = twoFactorResponse.setupRequired
    twoFactorOtpAuthUrl.value = twoFactorResponse.otpauthUrl
    twoFactorSecret.value = twoFactorResponse.secret
    twoFactorExpiresIn.value = twoFactorResponse.expiresIn
    validLogin.value = true
    loading.value = false
    return
  }

  if (!success) {
    errors.value = error.errors
    message.value = error.message
    validLogin.value = false
  } else {
    setToken(data.authorization.token)
    setUser(data.user)
    replace({ query: {} })
    setTimeout(() => {
      alertSuccess(`Welcome ${data.user.username}`, {
        autoClose: 3000,
      })
    }, 100)
  }

  loading.value = false
}

const loginWithCode = async () => {
  loading.value = true
  const { data, success, error } = await useApiPostFetch('/player/login/verify', {
    login_token: loginToken.value,
    setup_token: loginToken.value,
    code: code.value,
  })

  if (!success) {
    errors.value = error.errors
    message.value = error.message
    validLogin.value = false
  } else {
    errors.value = []
    message.value = data.message
    validLogin.value = true

    setToken(data.authorization.token)
    setUser(data.user)
    replace({ query: {} })
    setTimeout(() => {
      alertSuccess(`Welcome ${data.user.username}`, {
        autoClose: 3000,
      })
    }, 100)
  }

  loading.value = false
}
</script>
<template>
  <v-row>
    <v-col v-if="message" cols="12" class="pb-0 pt-0">
      <v-alert
        class="mb-1"
        border="start"
        density="compact"
        :color="validLogin ? 'success' : 'purple'"
        variant="tonal"
      >
        {{ message }}
      </v-alert>
    </v-col>

    <v-col v-if="step === 1" cols="12" class="pb-0 pt-0">
      <div class="text-subtitle-1 text-white">{{ t('auth.emailUsername') }}*</div>
      <v-text-field
        v-model="username"
        placeholder="Email or Username*"
        :rules="emailRules()"
        density="compact"
        color="primary"
      />
    </v-col>
    <v-col v-if="step === 1" cols="12" class="pb-0 pt-0">
      <div class="text-subtitle-1 text-white">{{ t('auth.password') }}*</div>
      <div class="password-wrapper">
        <v-text-field
          v-model="password"
          :placeholder="`${t('auth.password')}*`"
          :type="showPassword ? 'text' : 'password'"
          density="compact"
          autocomplete="false"
        />
        <button @click.prevent="showPassword = !showPassword">
          <icon-eye v-if="!showPassword" />
          <icon-eye-off v-if="showPassword" />
        </button>
      </div>
    </v-col>
    <two-factor-setup-step
      v-if="step === 2"
      :code="code"
      :setup-required="twoFactorSetupRequired"
      :otp-auth-url="twoFactorOtpAuthUrl"
      :secret="twoFactorSecret"
      :expires-in="twoFactorExpiresIn"
      :loading="loading"
      :submit-label="t('auth.verifyCode')"
      @update:code="code = $event"
      @submit="loginWithCode"
    />
    <v-col v-if="step === 1" cols="12" class="pb-0 pt-0">
      <v-btn
        color="purple"
        class="w-100"
        :disabled="loading || noContent"
        :loading="loading"
        @click.prevent="onLogin"
        >{{ t('auth.login') }}</v-btn
      >
    </v-col>
    <v-col cols="12" class="pb-0">
      <p class="register-disclaimer">
        {{ t('auth.forgot') }} <br />
        <a href="#" class="purple" @click.prevent="forgotPassword">{{ t('auth.reset') }}</a>
      </p>
    </v-col>
    <v-col cols="12" class="pb-0">
      <p class="register-disclaimer">
        {{ t('auth.signDisclaimer') }} <br />
        <a href="#" class="purple" @click.prevent="onChangeView">{{ t('auth.create') }}</a>
      </p>
    </v-col>
  </v-row>
</template>
<style lang="css" scoped>
@import '../../assets/css/components/Auth.css';
</style>
