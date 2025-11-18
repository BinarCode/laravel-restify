<template>
  <NuxtLayout>
    <div v-if="post">
      <article>
        <header class="mb-8">
          <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            {{ post.title }}
          </h1>
          <p v-if="post.description" class="text-xl text-gray-600 dark:text-gray-400">
            {{ post.description }}
          </p>
        </header>
        
        <div class="prose-docs">
          <ContentRenderer :value="post" />
        </div>
      </article>
    </div>
    
    <div v-else>
      <div class="text-center py-16">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
          Page Not Found
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">
          The page you're looking for doesn't exist.
        </p>
        <NuxtLink
          to="/"
          class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          Go back home
        </NuxtLink>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
const route = useRoute()

// Build the path from route params
const pathSegments = Array.isArray(route.params.slug) ? route.params.slug : [route.params.slug || '']
const contentPath = `/${pathSegments.join('/')}`

// Query the content using Nuxt Content v3 API
const { data: post } = await useAsyncData(`content-${contentPath}`, () => {
  return queryCollection('content').path(contentPath).first()
})

// Set page meta for SEO
if (post.value) {
  useHead({
    title: post.value.title || 'Laravel Restify',
    meta: [
      { name: 'description', content: post.value.description || 'Laravel Restify' }
    ]
  })
}
</script>

