export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',

  devtools: { enabled: false },

  modules: [
    '@nuxtjs/tailwindcss',
    '@pinia/nuxt',
    'nuxt-auth-utils',
    '@nuxt/icon',
  ],

  tailwindcss: {
    cssPath: '~/assets/css/main.css',
  },

  postcss: {
    plugins: {
      '~~/postcss/px-to-rem': {},
    },
  },
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
  routeRules: {
    '/auth/family/signin': { redirect: '/auth/client/signin' },
  },

  devServer: {
    host: '0.0.0.0',
    port: 3000,
    https: {
      key: './certs/dev-key.pem',
      cert: './certs/dev.pem',
    },
  },

  app: {
    head: {
      style: [
        {
          innerHTML: `html{background:#EEF3FB}html.dark{background:#1f2634}`,
        },
      ],
      script: [
        {
          innerHTML: `(function(){try{var t=localStorage.getItem('theme');if(t?t==='dark':true)document.documentElement.classList.add('dark')}catch(e){}})()`,
        },
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
        },
        {
          rel: 'preconnect',
          href: 'https://fonts.googleapis.com'
        },
        {
          rel: 'preconnect',
          href: 'https://fonts.gstatic.com',
          crossorigin: ''
        },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap'
        }
      ]
    }
  },
})