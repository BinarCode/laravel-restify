export default defineNuxtConfig({
  devtools: { enabled: true },
  modules: [
    '@nuxt/content',
    '@nuxtjs/tailwindcss',
    '@nuxtjs/color-mode',
    '@vueuse/nuxt'
  ],
  
  // Content configuration
  content: {
    documentDriven: true,
    navigation: {
      fields: ['title', 'description', 'icon', 'category']
    },
    highlight: {
      theme: {
        default: 'github-light',
        dark: 'github-dark'
      },
      preload: ['php', 'bash', 'javascript', 'typescript', 'vue', 'json']
    },
    markdown: {
      anchorLinks: false,
      remarkPlugins: [],
      rehypePlugins: []
    }
  },

  // CSS configuration
  css: ['~/assets/css/main.css'],


  // App configuration
  app: {
    head: {
      title: 'Laravel Restify',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { hid: 'description', name: 'description', content: 'Laravel Restify Documentation - Build amazing REST APIs with Laravel' }
      ],
      link: [
        { rel: 'icon', type: 'image/png', href: '/icon.png' }
      ],
      script: [
        {
          src: '//code.tidio.co/pgx3d8jlrufene0cnyv274lgege4u5c1.js',
          async: true
        },
        {
          src: 'https://www.googletagmanager.com/gtag/js?id=G-R8WWHZK13N',
          async: true
        },
        {
          innerHTML: `
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'G-R8WWHZK13N');
          `
        }
      ]
    }
  },

  // Color mode configuration
  colorMode: {
    classSuffix: ''
  },

  // Tailwind configuration
  tailwindcss: {
    cssPath: '~/assets/css/main.css',
    configPath: 'tailwind.config.js'
  },

  // TypeScript configuration
  typescript: {
    typeCheck: false
  },

  // Static site generation
  ssr: true,
  nitro: {
    preset: 'static',
    prerender: {
      failOnError: false,
      crawlLinks: true,
      routes: ['/'],
      ignore: [
        '/api/_content/cache.**',
        '/api/_content/query/**'
      ]
    }
  }
})