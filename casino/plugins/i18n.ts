import { watch } from 'vue'
import { createI18n } from 'vue-i18n'
import { useAppStore } from '~/core/store/app'
import en from '../locales/en.json'
import ro from '../locales/ro.json'

const messages = { en, ro }
const defaultLocale = import.meta.env.VITE_DEFAULT_LOCALE || 'en'
const fallbackLocale = import.meta.env.VITE_FALLBACK_LOCALE || 'en'

export default defineNuxtPlugin((nuxtApp) => {
  const appStore = useAppStore()

  const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    warnHtmlMessage: false,
    locale: appStore.currentLanguage || defaultLocale,
    fallbackLocale,
    availableLocales: Object.keys(messages),
    messages,
  })

  nuxtApp.vueApp.use(i18n)

  watch(
    () => appStore.currentLanguage,
    (locale) => {
      if (locale) {
        i18n.global.locale.value = locale
      }
    },
    { immediate: true },
  )
})
