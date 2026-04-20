<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script setup lang="ts">
import { IconEye, IconEyeOff } from '#components'
import TwoFactorSetupStep from '~/components/auth/TwoFactorSetupStep.vue'
import { useAppStore } from '~/core/store/app'
import { useAuthStore } from '~/core/store/auth'
import { useI18n } from 'vue-i18n'

//models
const email = ref('')
const username = ref('')
const password = ref('')

const errors = ref<any>([])
const loading = ref<boolean>(false)
const showPassword = ref<boolean>(false)
const step = ref<number>(1)
const message = ref('')
const loginToken = ref('')
const twoFactorCode = ref('')
const twoFactorSetupRequired = ref<boolean>(false)
const twoFactorOtpAuthUrl = ref<string>('')
const twoFactorSecret = ref<string>('')
const twoFactorExpiresIn = ref<number | null>(null)

const validatePassword = (value: string): string[] => {
  const rules = [
    { regex: /[A-Z]/, message: 'Password must contain at least one uppercase letter' },
    { regex: /[a-z]/, message: 'Password must contain at least one lowercase letter' },
    { regex: /\d/, message: 'Password must contain at least one number' },
    {
      regex: /[^A-Za-z0-9]/,
      message: 'Password must contain at least one special character',
    },
  ]

  return rules.filter(({ regex }) => !regex.test(value)).map(({ message }) => message)
}

//composables
const { isset } = useUtils()
const { setToken, setUser } = useAuthStore()
const { replace } = useRouter()
const { setSnackbar } = useAppStore()
const { connectedWallet } = storeToRefs(useAuthStore())
const { t } = useI18n()
const { getTwoFactorPayload } = useTwoFactorAuth()

//emitters
const emitters = defineEmits(['changeView'])

//methids
const onChangeView = () => emitters('changeView', 'login')

const resetTwoFactorState = () => {
  loginToken.value = ''
  twoFactorCode.value = ''
  twoFactorSetupRequired.value = false
  twoFactorOtpAuthUrl.value = ''
  twoFactorSecret.value = ''
  twoFactorExpiresIn.value = null
}

const startTwoFactorLoginFlow = async () => {
  const { data, success, error } = await useApiPostFetch('/player/login', {
    email: email.value,
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
    return true
  }

  if (!success) {
    errors.value = error.errors || []
    message.value = error.message || ''
    return false
  }

  if (data.authorization) {
    setToken(data.authorization.token)
    setUser(data.user)
    replace({})
    setSnackbar(`Welcome ${data.user.username}`)
    return true
  }

  return false
}

const handleRegistrationAction = async () => {
  loading.value = true
  message.value = ''
  errors.value = []
  resetTwoFactorState()

  const passwordValidationErrors = validatePassword(password.value)
  if (passwordValidationErrors.length) {
    errors.value = {
      ...errors.value,
      password: passwordValidationErrors,
    }
    loading.value = false
    return
  }

  const { data, success, error } = await useApiPostFetch('/registration', {
    username: username.value,
    password: password.value,
    email: email.value,
    legalAge: 1,
    agree: 1,
    wallet: connectedWallet.value,
  })

  if (!success) {
    errors.value = error.errors
  } else {
    errors.value = []
    if (data.authorization) {
      setToken(data.authorization.token)
      setUser(data.user)
      replace({})
      setSnackbar(`Welcome ${data.user.username}`)
    } else {
      await startTwoFactorLoginFlow()
    }
  }

  loading.value = false
}

const completeTwoFactorLogin = async () => {
  loading.value = true
  errors.value = []

  const { data, success, error } = await useApiPostFetch('/player/login/verify', {
    login_token: loginToken.value,
    setup_token: loginToken.value,
    code: twoFactorCode.value,
  })

  if (!success) {
    errors.value = error.errors || []
    message.value = error.message || ''
    loading.value = false
    return
  }

  setToken(data.authorization.token)
  setUser(data.user)
  replace({})
  setSnackbar(`Welcome ${data.user.username}`)
  loading.value = false
}

watch(password, () => {
  const passwordValidationErrors = validatePassword(password.value)
  if (passwordValidationErrors.length) {
    errors.value = {
      ...errors.value,
      password: passwordValidationErrors,
    }
    return
  }
  if (errors.value?.password) {
    const { password: _password, ...rest } = errors.value
    errors.value = rest
  }
})
</script>
<template>
  <v-row>
    <v-col v-if="message" cols="12" class="pb-0 pt-0">
      <v-alert class="mb-1" border="start" density="compact" color="purple" variant="tonal">
        {{ message }}
      </v-alert>
    </v-col>
    <v-col v-if="connectedWallet" cols="12" class="pb-0 pt-0">
      <span class="text-purple d-flex font-weight-bold mb-2">{{
        t('auth.walletConnectDisclaimer')
      }}</span>
    </v-col>
    <v-col v-if="step === 1" cols="12" class="pb-0 pt-0">
      <div class="text-subtitle-1 text-white">{{ t('auth.email') }}*</div>
      <v-text-field
        v-model="email"
        :placeholder="`${t('auth.email')}*`"
        density="compact"
        color="primary"
        :error="isset(errors.email)"
        :error-messages="isset(errors.email) ? errors.email[0] : null"
      />
    </v-col>
    <v-col v-if="step === 1" cols="12" class="pb-0 pt-0">
      <div class="text-subtitle-1 text-white">{{ t('auth.username') }}*</div>
      <v-text-field
        v-model="username"
        :placeholder="`${t('auth.username')}*`"
        density="compact"
        :error="isset(errors.username)"
        :error-messages="isset(errors.username) ? errors.username[0] : null"
      />
    </v-col>
    <v-col v-if="step === 1" cols="12" class="pb-0 pt-0">
      <div class="text-subtitle-1 text-white">{{ t('auth.password') }}*</div>
      <div class="password-wrapper">
        <v-text-field
          v-model="password"
          placeholder="Password*"
          :type="showPassword ? 'text' : 'password'"
          density="compact"
          :error="isset(errors.password)"
          :error-messages="isset(errors.password) ? errors.password[0] : null"
        />
        <button @click.prevent="showPassword = !showPassword">
          <icon-eye v-if="!showPassword" />
          <icon-eye-off v-if="showPassword" />
        </button>
      </div>
    </v-col>

    <v-col v-if="step === 1" cols="12" class="pb-0 pt-0 mt-2">
      <v-btn color="purple" class="w-100" @click.prevent="handleRegistrationAction">{{
        t('auth.signPlay')
      }}</v-btn>
    </v-col>
    <two-factor-setup-step
      v-if="step === 2"
      :code="twoFactorCode"
      :setup-required="twoFactorSetupRequired"
      :otp-auth-url="twoFactorOtpAuthUrl"
      :secret="twoFactorSecret"
      :expires-in="twoFactorExpiresIn"
      :loading="loading"
      :submit-label="t('auth.verifyCode')"
      @update:code="twoFactorCode = $event"
      @submit="completeTwoFactorLogin"
    />
    <v-col cols="12" class="pb-0">
      <p class="register-disclaimer">
        {{ t('auth.loginDisclaimer') }}
        <a href="#" class="purple" @click.prevent="onChangeView">{{ t('auth.signIn') }}</a>
      </p>
    </v-col>
  </v-row>
</template>
<style lang="css" scoped>
@import '../../assets/css/components/Auth.css';
</style>
