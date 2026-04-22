// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  app: {
    head: {
      title: 'Pepagy San Casino',
      link: [{ rel: 'icon', type: 'image/png', href: '/favicon.png' }],
    },
  },

  css: ['~/assets/css/app.css'],

  runtimeConfig: {
    public: {
      baseURL: process.env.API_URL || 'https://api.example.com/',
      casinoID: process.env.CASINO_ID || 'jackpot-ro',
      seoTitle: process.env.SEO_TITLE || 'Jackpot Casino',
      seoDescription:
        process.env.SEO_DESCRIPTION || 'Jackpot Casino - Crypto casino and sportsbook.',
      seoDisplayTitle: process.env.SEO_DISPLAY_TITLE || 'Jackpot Casino',
      seoDisplayDescription:
        process.env.SEO_DISPLAY_DESCRIPTION || 'Play with crypto at Jackpot Casino.',
      payloadCryptoKey: process.env.NUXT_PUBLIC_PAYLOAD_CRYPTO_KEY,
      encrypted: process.env.NUXT_PUBLIC_ENCRYPTED || false,
      favoriteOption: process.env.ENABLE_FAVORITE_OPTION || 1,
    },
  },

  plugins: [
    { src: './plugins/i18n', mode: 'client' },
    { src: './plugins/walletconnect', mode: 'client' },
    { src: './plugins/alerts', mode: 'client' },
    { src: './plugins/wagmi', mode: 'client' },
  ],

  imports: {
    presets: [
      {
        from: 'vue-i18n',
        imports: ['useI18n'],
      },
    ],
  },

  modules: [
    '@nuxt/eslint',
    '@nuxt/icon',
    '@nuxt/image',
    'vuetify-nuxt-module',
    '@pinia/nuxt',
    'pinia-plugin-persistedstate/nuxt',
  ],
  typescript: {
    typeCheck: true,
  },
  build: {
    transpile: ['vuetify'],
  },

  vuetify: {
    vuetifyOptions: './vuetify.config.ts',
  },

  pinia: {
    storesDirs: ['./core/store/**'],
  },

  ssr: false,
})
