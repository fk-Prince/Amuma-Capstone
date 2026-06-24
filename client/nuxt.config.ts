export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',

  devtools: { enabled: true },

  modules: [
    '@nuxtjs/tailwindcss',
    '@pinia/nuxt',
    'nuxt-auth-utils',
    '@nuxt/icon',
  ],
  runtimeConfig: {
    public: {
      backendApi: 'http://localhost:8000',
      xenditPublicKey: ''
    }
  },

  routeRules: {
    '/api/**': {
      proxy: 'http://127.0.0.1:8000/api/**'
    }
  },

  app: {
    head: {
      script: [
        {
          src: 'https://js.xendit.co/v1/xendit.min.js',
          defer: true
        }
      ],
      link: [
        {
          rel: 'icon',
          type: 'image/png',
          href: '/logo.png'
        }
      ]
    }
  },
})