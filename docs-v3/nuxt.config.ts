const docsRoutes = [
  '/',
  '/docs',
  '/docs/getting-started/quickstart',
  '/docs/auth/authentication',
  '/docs/auth/authorization',
  '/docs/auth/profile',
  '/docs/api/repositories-basic',
  '/docs/api/repositories',
  '/docs/api/repositories-advanced',
  '/docs/api/repository-generation',
  '/docs/api/fields',
  '/docs/api/relations',
  '/docs/api/rest-methods',
  '/docs/api/validation-methods',
  '/docs/api/actions',
  '/docs/api/getters',
  '/docs/api/serializer',
  '/docs/search/basic-filters',
  '/docs/search/advanced-filters',
  '/docs/search/sorting',
  '/docs/graphql/graphql',
  '/docs/graphql/graphql-generation',
  '/docs/mcp/mcp',
  '/docs/mcp/repositories',
  '/docs/mcp/fields',
  '/docs/mcp/getters',
  '/docs/mcp/json-schema-converter',
  '/docs/mcp/actions',
  '/docs/performance/performance',
  '/docs/performance/solutions',
  '/docs/boost/boost',
  '/docs/testing/testing',
  '/playground',
  '/case-studies',
  '/templates',
  '/community'
]

export default defineNuxtConfig({
  compatibilityDate: '2025-11-18',
  devtools: { enabled: true },
  modules: [
    '@nuxt/ui',
    '@nuxt/content',
    '@vueuse/nuxt'
  ],

  // Site URL used for canonical links and absolute URLs
  runtimeConfig: {
    public: {
      siteUrl: 'https://laravel-restify.com'
    }
  },

  // Nuxt Content configuration
  content: {
    build: {
      markdown: {
        highlight: {
          langs: [
            'php',
            'javascript',
            'typescript',
            'json',
            'bash',
            'shell',
            'yaml',
            'vue',
            'html',
            'css',
            'graphql',
            'sql',
            'diff'
          ]
        }
      }
    }
  },

  // Nuxt UI configuration for content
  ui: {
    content: true,
    fonts: true
  },

  // Icon configuration - only bundle the collections we actually use
  icon: {
    serverBundle: {
      collections: ['heroicons', 'lucide']
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
        { name: 'description', content: 'Laravel Restify Documentation - Build amazing REST APIs with Laravel' }
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

  // 301 redirects from the legacy docs-v2 URL structure to the new /docs/* paths.
  // Removes the duplicate soft-404 pages Ahrefs flagged and preserves old backlinks.
  // Note: bare exact-match legacy paths (/quickstart, /repositories, /search) are
  // handled via public/_redirects with forced (301!) rules. Exact-match redirect
  // routeRules get prerendered as meta-refresh stub HTML files, which Netlify then
  // serves with a 200 (shadowing the redirect) — so they must NOT live here.
  routeRules: {
    '/api/**': { redirect: { to: '/docs/api/**', statusCode: 301 } },
    '/auth/**': { redirect: { to: '/docs/auth/**', statusCode: 301 } },
    '/search/**': { redirect: { to: '/docs/search/**', statusCode: 301 } },
    '/graphql/**': { redirect: { to: '/docs/graphql/**', statusCode: 301 } },
    '/mcp/**': { redirect: { to: '/docs/mcp/**', statusCode: 301 } },
    '/performance/**': { redirect: { to: '/docs/performance/**', statusCode: 301 } },
    '/boost/**': { redirect: { to: '/docs/boost/**', statusCode: 301 } },
    '/testing/**': { redirect: { to: '/docs/testing/**', statusCode: 301 } }
  },

  // TypeScript configuration
  typescript: {
    typeCheck: false
  },

  // Static site generation for Netlify
  ssr: true,
  nitro: {
    preset: 'netlify-static',
    prerender: {
      failOnError: false,
      crawlLinks: true,
      routes: docsRoutes
    }
  }
})
