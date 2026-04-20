<script setup lang="ts">
import QRCode from 'qrcode'

const props = defineProps<{
  code: string
  setupRequired: boolean
  otpAuthUrl: string
  secret: string
  expiresIn: number | null
  loading?: boolean
  submitLabel: string
}>()

const emit = defineEmits<{
  (e: 'update:code', value: string): void
  (e: 'submit'): void
}>()

const { t } = useI18n({ useScope: 'global' })
const qrCodeDataUrl = ref('')

const generateQrCode = async (otpAuthUrl: string) => {
  qrCodeDataUrl.value = ''

  if (!otpAuthUrl) {
    return
  }

  try {
    qrCodeDataUrl.value = await QRCode.toDataURL(otpAuthUrl, {
      width: 220,
      margin: 1,
    })
  } catch (error) {
    qrCodeDataUrl.value = ''
    console.log(error)
  }
}

watch(
  () => props.otpAuthUrl,
  async (value) => {
    await generateQrCode(value)
  },
  { immediate: true },
)
</script>

<template>
  <v-col cols="12" class="pb-0 pt-0">
    <div v-if="setupRequired" class="text-subtitle-2 text-white mb-2">
      {{ t('auth.twoFactorSetupHint') }}
    </div>
    <div v-if="setupRequired && qrCodeDataUrl" class="d-flex justify-center mb-2">
      <img
        :src="qrCodeDataUrl"
        :alt="t('auth.twoFactorQrAlt')"
        width="220"
        height="220"
        style="border-radius: 8px; background: #fff; padding: 8px"
      />
    </div>
    <div v-if="setupRequired && otpAuthUrl" class="text-body-2 text-white mb-2">
      {{ t('auth.twoFactorOtpAuthUrl') }}:
      <v-text-field :model-value="otpAuthUrl" density="compact" readonly hide-details />
    </div>
    <div v-if="setupRequired && secret" class="text-body-2 text-white mb-2">
      {{ t('auth.twoFactorSecret') }}:
      <strong>{{ secret }}</strong>
    </div>
    <div v-if="setupRequired && expiresIn" class="text-body-2 text-white mb-2">
      {{ t('auth.twoFactorExpiresIn', { seconds: expiresIn }) }}
    </div>
    <div class="text-subtitle-1 text-white">{{ t('auth.twoFactorCode') }}*</div>
    <v-text-field
      :model-value="code"
      :placeholder="`${t('auth.twoFactorCode')}*`"
      density="compact"
      color="primary"
      @update:model-value="emit('update:code', $event)"
    />
  </v-col>
  <v-col cols="12" class="pb-0 pt-0">
    <v-btn
      color="purple"
      class="w-100"
      :disabled="loading || !code"
      :loading="loading"
      @click.prevent="emit('submit')"
    >
      {{ submitLabel }}
    </v-btn>
  </v-col>
</template>
