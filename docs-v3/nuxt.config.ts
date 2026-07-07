import { fileURLToPath } from 'node:url'

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

  // 301 redirects from the legacy docs-v2 URL structure to the new /docs/* paths
  // (preserves old backlinks and removes the duplicate soft-404 pages Ahrefs flagged)
  // live in public/_redirects, authored in Cloudflare Pages' native syntax (:splat,
  // no forced-'!' suffix). They are intentionally NOT expressed as Nuxt routeRules:
  // Nitro emits routeRule redirects into _redirects using '**' placeholders, which
  // Cloudflare Pages does not understand, and exact-match rules would additionally be
  // prerendered as meta-refresh stub HTML that shadows the redirect with a 200.

  // Emit Forge-style raw markdown twins and llms.txt indexes into the static
  // output at build time. Runs after Nitro has staged public assets, so the files
  // land in the final publicDir (dist/) alongside the prerendered pages.
  hooks: {
    async 'nitro:build:public-assets'(nitro) {
      const { generateLlms } = await import('./scripts/generate-llms.mjs')
      await generateLlms({
        contentDir: fileURLToPath(new URL('./content/docs', import.meta.url)),
        outputDir: nitro.options.output.publicDir,
        siteUrl: 'https://laravel-restify.com'
      })
    }
  },

  // TypeScript configuration
  typescript: {
    typeCheck: false
  },

  // Static site generation for Cloudflare Pages
  ssr: true,
  nitro: {
    preset: 'cloudflare_pages',
    // Collapse the per-file static excludes into a handful of wildcards so the
    // generated dist/_routes.json stays well under Cloudflare Pages' hard limit
    // of 100 rules. Nitro seeds `exclude` from this config first, converts each
    // trailing `/*` into a `/**` glob to skip matching files during its own
    // asset walk, then appends the remaining root assets. Without this, every
    // prerendered /docs page plus its raw `.md` twin and the llms.txt indexes
    // were emitted as individual excludes, pinning the file at exactly 100 rules
    // (one content page away from a rejected deploy). `/docs/*` covers all docs
    // HTML pages, their `.md` twins, and /docs/llms{,-full}.txt in one rule.
    cloudflare: {
      pages: {
        routes: {
          exclude: ['/docs/*', '/docs.md', '/llms.txt']
        }
      }
    },
    prerender: {
      failOnError: false,
      crawlLinks: true,
      routes: docsRoutes
    }
  }
})
