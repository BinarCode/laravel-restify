<template>
  <NuxtLayout name="docs">
    <div v-if="post">
      <header class="mb-8">
        <div class="flex flex-col-reverse sm:flex-row sm:items-start sm:justify-between gap-4">
          <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            {{ post.title }}
          </h1>
          <DocsCopyPageDropdown class="shrink-0 sm:mt-2" />
        </div>
      </header>

      <ContentRenderer :value="post" />
    </div>
    
    <div v-else>
      <div class="text-center py-16">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
          Documentation Page Not Found
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">
          The documentation page you're looking for doesn't exist.
        </p>
        <NuxtLink
          to="/docs"
          class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          Back to Documentation
        </NuxtLink>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
const route = useRoute()

const pathSegments = Array.isArray(route.params.slug) ? route.params.slug : [route.params.slug || '']
const contentPath = `/docs/${pathSegments.join('/')}`

const { data: post } = await useAsyncData(`docs-${contentPath}`, function fetchDocsContent() {
  return queryCollection('content').path(contentPath).first()
})

if (!post.value) {
  throw createError({ statusCode: 404, statusMessage: 'Documentation page not found', fatal: true })
}

useSeoMeta({
  title: post.value.title || 'Documentation',
  description: post.value.description || 'Laravel Restify Documentation - Build amazing REST APIs with Laravel'
})
</script>