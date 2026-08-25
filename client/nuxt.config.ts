export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',

  devtools: { enabled: true },

  modules: [
    '@nuxtjs/tailwindcss',
    '@pinia/nuxt',
    'nuxt-auth-utils',
    '@nuxt/icon',
  ],
  // runtimeConfig: {
  //   public: {
  //     backendApi: 'http://localhost:8000',
  //     xenditPublicKey: ''
  //   }
  // },
  runtimeConfig: {
    public: {
      backendApi: '',
      xenditPublicKey: ''
    }
  },
  nitro: {
    devProxy: {
      '/api': {
        target: `${process.env.NUXT_PUBLIC_BACKEND_API}/api`,
        changeOrigin: true,
      },
      '/sanctum': {
        target: `${process.env.NUXT_PUBLIC_BACKEND_API}/sanctum`,
        changeOrigin: true,
      },
    },
  },
  // routeRules: {
  //   '/api/**': {
  //     proxy: 'http://127.0.0.1:8000/api/**'
  //   }
  // },

  devServer: {
    host: '0.0.0.0',
    port: 3000,
    https: {
      key: './certs/192.168.1.2+3-key.pem',
      cert: './certs/192.168.1.2+3.pem',
    },
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