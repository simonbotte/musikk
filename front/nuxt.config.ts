// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2024-11-01',
  devtools: { enabled: true },
  modules: [
    '@nuxt/image',
    '@nuxt/fonts',
    '@nuxt/icon',
    '@nuxtjs/tailwindcss',
  ],
  app: {
    head: {
      script: [
        {
          src: 'https://js-cdn.music.apple.com/musickit/v3/musickit.js',
          'data-web-components': '',
        }
      ]
    }
  },
  runtimeConfig: {
    public: {
      apiBase: '/api'
    }
  },

})