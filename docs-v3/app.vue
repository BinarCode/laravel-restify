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
// Global app configuration
useHead({
  htmlAttrs: {
    lang: 'en'
  }
})

// Fetch navigation and search data using the correct Nuxt Content composables
const { data: navigation } = await useAsyncData('navigation', () => queryCollectionNavigation('content'))
const { data: files } = await useAsyncData('search', () => queryCollectionSearchSections('content'))

const searchTerm = ref('')
</script>