<script setup lang="ts">
//composables
const { t } = useI18n()
const { isset, wait } = useUtils()
const { replace } = useRouter()

//models
const email = ref('')
const save = ref<boolean>(false)
const errors = ref<any>({})
const message = ref<string>('')

//methods
const handleForgotPassword = async () => {
  save.value = true
  const { data, error } = await useApiPostFetch('/forgot-password', {
    email: email.value,
  })
  errors.value = []
  if (error && error.errors) {
    errors.value = error.errors
  }

  if (error && error.success === false && isset(error.message)) {
    errors.value = { email: [error.message] }
  }

  if (data && data.success) {
    message.value = data.message
    await wait(1000)
    replace({ query: {} })
  }

  save.value = false
}
</script>
<template>
  <v-card max-width="414" width="100%" class="wallet-card ma-auto">
    <v-card-title>{{ t('forgot.title') }}</v-card-title>
    <v-card-text>
      <v-text-field
        v-model="email"
        type="email"
        :label="t('forgot.email')"
        hide-details="auto"
        :error="isset(errors.email)"
        :error-messages="isset(errors.email) ? errors.email[0] : null"
        density="compact"
      />

      <div v-if="message" class="text-green text-subtitle-2 mt-2">{{ message }}</div>
    </v-card-text>
    <v-card-actions>
      <v-btn
        color="primary"
        variant="flat"
        :loading="save"
        :disabled="save"
        @click.prevent="handleForgotPassword"
        >{{ t('forgot.reset') }}</v-btn
      >
    </v-card-actions>
  </v-card>
</template>
