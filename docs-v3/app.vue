<template>
  <UApp>
    <NuxtPage />
    <ClientOnly>
      <LazyUContentSearch
        v-model:search-term="searchTerm"
        shortcut="meta_k"
        :files="files"
        :navigation="navigation"
        :fuse="{ resultLimit: 42 }"
        placeholder="Search documentation..."
      />
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
</script>