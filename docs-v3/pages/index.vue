<template>
  <NuxtLayout>
    <ContentDoc>
      <!-- Use content from index.md -->
      <template #default="{ doc }">
        <!-- Page header with hero styling -->
        <header class="text-center py-16 mb-8">
          <div class="mb-8">
            <div class="text-6xl mb-6">✨</div>
          </div>
          
          <h1 class="text-6xl font-bold text-gray-900 dark:text-white mb-6">
            Laravel Restify
          </h1>
          
          <p class="text-2xl text-gray-600 dark:text-gray-400 mb-8 max-w-2xl mx-auto">
            {{ doc.description }}
          </p>
          
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <NuxtLink
              to="/quickstart"
              class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
            >
              Get Started
              <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </NuxtLink>
            
            <a
              href="https://github.com/binarcode/laravel-restify"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
            >
              <svg class="mr-2 h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
              </svg>
              View on GitHub
            </a>
          </div>
        </header>
        
        <!-- Content from index.md -->
        <div class="prose-docs">
          <ContentRenderer :value="doc" />
        </div>
      </template>
      
      <!-- Fallback if no content found -->
      <template #not-found>
        <div class="text-center py-16">
          <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Welcome to Laravel Restify
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mb-8">
            Build amazing REST APIs with Laravel.
          </p>
          <NuxtLink
            to="/quickstart"
            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
          >
            Get Started
          </NuxtLink>
        </div>
      </template>
    </ContentDoc>
  </NuxtLayout>
</template>

<script setup lang="ts">
useHead({
  title: 'Laravel Restify - Build amazing REST APIs with Laravel',
  meta: [
    {
      name: 'description',
      content: 'Laravel Restify provides a simple, powerful way to create JSON:API compliant REST APIs with Laravel. Fast, efficient, and flexible.'
    }
  ]
})
</script>