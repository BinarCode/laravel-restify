<template>
  <nav
    v-if="surround && (surround[0] || surround[1])"
    class="mt-12 pt-6 border-t border-gray-200 dark:border-gray-700"
  >
    <div class="flex items-center justify-between gap-4">
      <NuxtLink
        v-if="surround[0]"
        :to="surround[0].path"
        class="group flex-1 flex items-center gap-3 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-all"
      >
        <ChevronLeftIcon
          class="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors"
        />
        <div class="min-w-0">
          <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Previous</div>
          <div
            class="text-sm font-medium text-gray-900 dark:text-white truncate transition-colors"
          >
            {{ surround[0].title }}
          </div>
        </div>
      </NuxtLink>

      <div v-else class="flex-1"></div>

      <NuxtLink
        v-if="surround[1]"
        :to="surround[1].path"
        class="group flex-1 flex items-center justify-end gap-3 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-all text-right"
      >
        <div class="min-w-0">
          <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Next</div>
          <div
            class="text-sm font-medium text-gray-900 dark:text-white truncate transition-colors"
          >
            {{ surround[1].title }}
          </div>
        </div>
        <ChevronRightIcon
          class="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors"
        />
      </NuxtLink>

      <div v-else class="flex-1"></div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const route = useRoute()

const { data: surround } = await useAsyncData(`surround-${route.path}`, () => {
  return queryCollectionItemSurroundings('content', route.path)
})
</script>
