import { defineVuetifyConfiguration } from 'vuetify-nuxt-module/custom-configuration'

export default defineVuetifyConfiguration({
  defaults: {
    VBtnPrimary: {
      class: ['v-btn--primary', 'text-none'],
    },
    VBtn: {
      style: {
        'min-width': '64px',
        outline: 0,
      },
    },
    display: {
      mobileBreakpoint: 'sm',
      thresholds: {
        xs: 0,
        sm: 340,
        md: 540,
        lg: 800,
        xl: 1280,
      },
    },
  },
})
