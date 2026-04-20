import vuetify, { transformAssetUrls } from "vite-plugin-vuetify";
import pck from "./package.json";

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: "2024-04-03",
  devtools: { enabled: true },

  app: {
    head: {
      link: [
        {
          rel: "stylesheet",
          href: "https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap",
        },
      ],
    },
  },

  css: ["@/assets/scss/app.scss"],

  runtimeConfig: {
    public: {
      baseURL: process.env.API_URL || "https://api.example.com/",
      passport: {
        clientID: process.env.PASSPORT_CLIENT_ID,
        clientSecret: process.env.PASSPORT_CLIENT_SECRET,
        grantType: process.env.PASSPORT_CLIENT_GRANT_TYPE,
      },
      appName: process.env.APP_NAME,
      version: pck.version,
    },
  },

  build: {
    transpile: ["vuetify"],
  },

  ssr: false,

  plugins: [
    { src: "~/plugins/simplebar.ts", mode: "client" },
    { src: "~/plugins/datepicker.ts", mode: "client" },
  ],

  modules: [
    (_options, nuxt) => {
      nuxt.hooks.hook("vite:extendConfig", (config) => {
        // @ts-expect-error
        config.plugins.push(vuetify({ autoImport: true }));
      });
    },
    "@pinia/nuxt",
    "@pinia-plugin-persistedstate/nuxt",
  ],
  vite: {
    vue: {
      template: {
        transformAssetUrls,
      },
    },
    css: {
      preprocessorOptions: {
        scss: {
          additionalData: '@import "@/assets/scss/_variables.scss";',
          api: "modern",
          silenceDeprecations: ["legacy-js-api"],
        },
      },
    },
  },
});
