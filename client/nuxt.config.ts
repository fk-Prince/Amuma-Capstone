// Runs before first paint: applies Light/Dark/Device mode and the saved dashboard palette.
// Stored palette CSS is only injected if it matches the rigid `--var:R G B;` shape that
// utils/colorScale.ts produces (keep PALETTE_CSS_PATTERN in sync with the regex below).
const THEME_BOOT_SCRIPT = String.raw`(function(){try{var d=document.documentElement,t=localStorage.getItem('theme');var dark=t==='light'?false:t==='system'?matchMedia('(prefers-color-scheme: dark)').matches:true;if(dark)d.classList.add('dark');var p=JSON.parse(localStorage.getItem('palette')||'null');if(p&&typeof p.css==='string'&&/^html\[data-palette-scope\]\{(--[a-z0-9-]+:(\d{1,3}|\d{1,3} \d{1,3} \d{1,3});)+\}$/.test(p.css)){var e=document.createElement('style');e.id='amuma-palette';e.textContent=p.css;document.head.appendChild(e)}}catch(e){}})()`;

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
      reverbKey: '',
      reverbHost: '',
      reverbPort: '',
      reverbScheme: '',
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
          innerHTML: `html{background:rgb(var(--page-bg,238 243 251))}html.dark{background:rgb(var(--secondary-800,31 38 52))}`,
        },
      ],
      script: [
        {
          innerHTML: THEME_BOOT_SCRIPT,
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