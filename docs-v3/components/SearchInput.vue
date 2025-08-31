<template>
  <div class="relative max-w-md">
    <button
      @click="openSearchModal"
      class="flex items-center w-full px-3 py-2 text-left border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors group"
    >
      <svg class="h-5 w-5 text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      
      <span class="flex-1 text-sm text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300">
        Search docs...
      </span>
      
      <div class="hidden sm:flex items-center space-x-1">
        <kbd class="px-2 py-1 text-xs text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 rounded font-mono">
          {{ isMac ? '⌘' : 'Ctrl' }}
        </kbd>
        <kbd class="px-2 py-1 text-xs text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 rounded font-mono">
          K
        </kbd>
      </div>
    </button>
  </div>
</template>

<script setup lang="ts">
const { openModal } = useSearchModal()

const isMac = computed(() => {
  if (process.client) {
    return navigator.platform.toUpperCase().indexOf('MAC') >= 0
  }
  return false
})

const openSearchModal = () => {
  console.log('🔍 Search input button clicked')
  openModal()
  console.log('📞 openModal() called from SearchInput')
}
</script>