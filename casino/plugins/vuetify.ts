import { defineNuxtPlugin } from 'nuxt/app'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'

export default defineNuxtPlugin((nuxtApp) => {
  const vuetify = createVuetify({
    components,
    theme: {
      themes: {
        light: {
          dark: false,
          colors: {
            purple: '#9F36F1',
          },
        },
      },
    },
  })

  nuxtApp.vueApp.use(vuetify)
})
