<template>
  <UApp>
    <NuxtPage />
    <ClientOnly>
      <LazyUContentSearch
        v-model:search-term="searchTerm"
        shortcut="meta_k"
        :files="files"
        :navigation="navigation"
        :fuse="searchFuse"
        placeholder="Search the docs, headings and content…"
      >
        <template #footer>
          <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 w-full text-xs text-gray-500 dark:text-gray-400">
            <span class="flex items-center gap-1.5">
              <UIcon name="i-lucide-sparkles" class="size-3.5 text-blue-500 dark:text-blue-400 shrink-0" />
              <span>
                <span class="font-medium text-gray-700 dark:text-gray-300">AI tip:</span>
                append
                <code class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-800 font-mono text-[0.7rem] text-gray-700 dark:text-gray-300">.md</code>
                to any docs URL for raw markdown
              </span>
            </span>
            <NuxtLink
              to="/docs/llms.txt"
              external
              target="_blank"
              class="flex items-center gap-1 font-medium text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300"
            >
              <UIcon name="i-lucide-file-text" class="size-3.5 shrink-0" />
              /docs/llms.txt
            </NuxtLink>
          </div>
        </template>
      </LazyUContentSearch>
    </ClientOnly>
  </UApp>
</template>

<script setup lang="ts">
const route = useRoute()
const siteUrl = useRuntimeConfig().public.siteUrl as string

// Self-referencing canonical for every page (fixes "duplicate pages without canonical")
const canonicalUrl = computed(() => `${siteUrl}${route.path === '/' ? '' : route.path}`)

// Global app configuration
useHead({
  htmlAttrs: {
    lang: 'en'
  },
  // Append a brand suffix to short page titles, but leave already-branded titles untouched
  titleTemplate: (title?: string) => {
    if (!title || title.includes('Laravel Restify')) {
      return title || 'Laravel Restify — PHP REST API Framework'
    }
    return `${title} | Laravel Restify Documentation`
  },
  link: [
    { rel: 'canonical', href: canonicalUrl }
  ]
})

// Fetch navigation and search data using the correct Nuxt Content composables
const { data: navigation } = await useAsyncData('navigation', () => queryCollectionNavigation('content'))
const { data: files } = await useAsyncData('search', () => queryCollectionSearchSections('content'))

const searchTerm = ref('')

// Fuse.js tuning for AI/agent-era docs search: fuzzy, section-aware, generous recall.
// Keys stay at the CommandPalette default (['label', 'suffix'] = heading + body content) —
// defu concatenates arrays, so redefining them here would duplicate the defaults.
const searchFuse = {
  resultLimit: 20,
  fuseOptions: {
    ignoreLocation: true,
    threshold: 0.3,
    minMatchCharLength: 2,
    includeScore: true
  }
}
</script>