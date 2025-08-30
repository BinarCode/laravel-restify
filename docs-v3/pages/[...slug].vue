<template>
  <NuxtLayout>
    <div>
    <ContentDoc>
      <template #not-found>
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
      </template>
      
      <template #default="{ doc }">
        <article>
          <!-- Page header -->
          <header class="mb-8">
            <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
              <NuxtLink to="/" class="hover:text-gray-700 dark:hover:text-gray-300">
                Documentation
              </NuxtLink>
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
              <span>{{ doc.title }}</span>
            </div>
            
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              {{ doc.title }}
            </h1>
            
            <p v-if="doc.description" class="text-xl text-gray-600 dark:text-gray-400">
              {{ doc.description }}
            </p>
          </header>
          
          <!-- Page content -->
          <div class="prose-docs">
            <ContentRenderer :value="doc" />
          </div>
          
          <!-- Page navigation -->
          <div v-if="surround && (surround[0] || surround[1])" class="mt-16">
            <div class="flex justify-between items-center py-8 border-t border-gray-200 dark:border-gray-700">
              <div>
                <NuxtLink
                  v-if="surround[0]"
                  :to="surround[0]._path"
                  class="group inline-flex items-center text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                >
                  <svg class="mr-2 h-4 w-4 group-hover:text-gray-600 dark:group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                  </svg>
                  <div class="text-left">
                    <div class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">Previous</div>
                    <div class="text-gray-900 dark:text-white">{{ surround[0].title }}</div>
                  </div>
                </NuxtLink>
              </div>
              
              <div>
                <NuxtLink
                  v-if="surround[1]"
                  :to="surround[1]._path"
                  class="group inline-flex items-center text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                >
                  <div class="text-right">
                    <div class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">Next</div>
                    <div class="text-gray-900 dark:text-white">{{ surround[1].title }}</div>
                  </div>
                  <svg class="ml-2 h-4 w-4 group-hover:text-gray-600 dark:group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                  </svg>
                </NuxtLink>
              </div>
            </div>
          </div>
        </article>
      </template>
    </ContentDoc>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
const route = useRoute()

const { data: surround } = await useAsyncData(`surround-${route.path}`, () => 
  queryContent()
    .where({ _extension: 'md' })
    .only(['_path', 'title'])
    .findSurround(route.path)
)

// Watch for route changes to update surround data
watch(() => route.path, async (newPath) => {
  const { data: newSurround } = await useAsyncData(`surround-${newPath}`, () => 
    queryContent()
      .where({ _extension: 'md' })
      .only(['_path', 'title'])
      .findSurround(newPath)
  )
  surround.value = newSurround.value
})
</script>