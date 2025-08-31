<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 overflow-y-auto"
      @keydown="handleModalKeydown"
    >
      <!-- Backdrop -->
      <div 
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"
        @click="closeModalWithReset"
      ></div>
      
      <!-- Modal -->
      <div class="flex min-h-full items-start justify-center p-2 sm:p-4 pt-8 sm:pt-16 md:pt-24">
        <div
          data-modal-content
          tabindex="-1"
          class="relative w-full max-w-full sm:max-w-2xl transform rounded-xl bg-white/90 dark:bg-gray-800/90 backdrop-blur-2xl shadow-2xl border-2 border-primary-400/40 dark:border-primary-500/50 ring-4 ring-primary-500/30 dark:ring-primary-400/40 transition-all"
          style="box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 2px rgba(59, 130, 246, 0.3), 0 0 30px rgba(59, 130, 246, 0.2), 0 0 60px rgba(59, 130, 246, 0.1);"
          @click.stop
        >
          <!-- Search input -->
          <div class="flex items-center px-3 sm:px-4 py-4 border-b border-gray-200 dark:border-gray-700">
            <svg
              class="h-5 w-5 text-gray-400 dark:text-gray-500 mr-3"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            
            <input
              ref="searchInput"
              v-model="searchQuery"
              type="text"
              placeholder="Search documentation..."
              class="flex-1 bg-transparent text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 border-0 focus:ring-0 focus:outline-none text-lg"
              @input="performSearch"
              @keydown.down.prevent="navigateResults('down')"
              @keydown.up.prevent="navigateResults('up')"
              @keydown.enter.prevent="selectResult"
            />
            
            <div class="hidden sm:flex items-center space-x-2 text-xs text-gray-400 dark:text-gray-500">
              <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">↑↓</kbd>
              <span>navigate</span>
              <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">↵</kbd>
              <span>select</span>
              <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">esc</kbd>
              <span>close</span>
            </div>
          </div>
          
          <!-- Results -->
          <div class="max-h-96 overflow-y-auto">
            <!-- No query state -->
            <div v-if="!searchQuery" class="px-3 sm:px-6 py-14 text-center">
              <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <p class="mt-4 text-lg text-gray-900 dark:text-white font-medium">Search documentation</p>
              <p class="mt-2 text-gray-500 dark:text-gray-400">Find pages, sections, and content quickly.</p>
            </div>
            
            <!-- Loading state -->
            <div v-else-if="isSearching" class="px-3 sm:px-6 py-14 text-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
              <p class="mt-4 text-gray-500 dark:text-gray-400">Searching...</p>
            </div>
            
            <!-- No results -->
            <div v-else-if="searchQuery && searchResults.length === 0" class="px-3 sm:px-6 py-14 text-center">
              <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.205 0-4.219-.896-5.671-2.343M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <p class="mt-4 text-lg text-gray-900 dark:text-white font-medium">No results found</p>
              <p class="mt-2 text-gray-500 dark:text-gray-400">Try adjusting your search terms.</p>
            </div>
            
            <!-- Results -->
            <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
              <li
                v-for="(result, index) in searchResults"
                :key="result.path"
                :class="[
                  'px-3 sm:px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors',
                  selectedIndex === index ? 'bg-primary-50 dark:bg-primary-900/20' : ''
                ]"
                @click="goToResult(result)"
                @mouseenter="selectedIndex = index"
              >
                <div class="flex items-start">
                  <div class="flex-shrink-0 mt-1">
                    <div class="w-2 h-2 bg-primary-500 rounded-full"></div>
                  </div>
                  <div class="ml-3 flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white" v-html="highlightSearchTerm(result.title, searchQuery)">
                    </p>
                    <p v-if="result.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-clamp-2" v-html="highlightSearchTerm(result.description, searchQuery)">
                    </p>
                    <div class="mt-2 flex items-center text-xs text-gray-400 dark:text-gray-500">
                      <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded capitalize">
                        {{ result.category || 'Documentation' }}
                      </span>
                      <span class="ml-2">{{ result.path }}</span>
                    </div>
                  </div>
                  <div class="flex-shrink-0 ml-2">
                    <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </div>
                </div>
              </li>
            </ul>
          </div>
          
          <!-- Footer -->
          <div class="flex items-center justify-between px-3 sm:px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ searchResults.length > 0 ? `${searchResults.length} result${searchResults.length === 1 ? '' : 's'}` : '' }}
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500">
              Search powered by Nuxt Content
            </p>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import type { SearchResult } from '~/composables/useSearch'

// Import search functionality
const { searchContent, highlightSearchTerm } = useSearch()
const { isOpen, closeModal } = useSearchModal()
const searchQuery = ref('')
const searchResults = ref<SearchResult[]>([])
const selectedIndex = ref(0)
const isSearching = ref(false)
const searchInput = ref<HTMLInputElement>()

// Keyboard shortcut handler
const handleKeydown = (event: KeyboardEvent) => {
  if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
    event.preventDefault()
    openModal()
    return
  }
  
  // Handle ESC key when modal is open
  if (event.key === 'Escape') {
    if (isOpen.value) {
      event.preventDefault()
      event.stopPropagation()
      closeModalWithReset()
    }
  }
}

// Separate handler for modal-specific keys when modal is open
const handleModalKeydown = (event: KeyboardEvent) => {
  if (!isOpen.value) return
  
  if (event.key === 'Escape') {
    event.preventDefault()
    event.stopPropagation()
    closeModalWithReset()
  }
}

// Modal controls
const { openModal: openModalFromComposable } = useSearchModal()

const openModal = () => {
  console.log('📂 SearchModal openModal called')
  openModalFromComposable()
  console.log('🎯 Modal should be open now, isOpen:', isOpen.value)
  nextTick(() => {
    searchInput.value?.focus()
  })
}

const closeModalWithReset = () => {
  closeModal()
  searchQuery.value = ''
  searchResults.value = []
  selectedIndex.value = 0
}

// Navigation
const navigateResults = (direction: 'up' | 'down') => {
  if (searchResults.value.length === 0) return
  
  if (direction === 'down') {
    selectedIndex.value = (selectedIndex.value + 1) % searchResults.value.length
  } else {
    selectedIndex.value = selectedIndex.value === 0 ? searchResults.value.length - 1 : selectedIndex.value - 1
  }
}

const selectResult = () => {
  if (searchResults.value[selectedIndex.value]) {
    goToResult(searchResults.value[selectedIndex.value])
  }
}

const goToResult = (result: SearchResult) => {
  navigateTo(result.path)
  closeModalWithReset()
}

// Debounce function
const debounce = (fn: Function, delay: number) => {
  let timeoutId: ReturnType<typeof setTimeout>
  return (...args: any[]) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => fn(...args), delay)
  }
}

// Search functionality
const performSearch = debounce(async () => {
  if (!searchQuery.value.trim()) {
    searchResults.value = []
    return
  }

  isSearching.value = true
  selectedIndex.value = 0

  try {
    searchResults.value = await searchContent(searchQuery.value)
  } catch (error) {
    console.error('Search error:', error)
    searchResults.value = []
  } finally {
    isSearching.value = false
  }
}, 300)

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})

// Watch for modal open state to add/remove specific event listeners
watch(isOpen, (newIsOpen) => {
  if (newIsOpen) {
    // Focus the modal for keyboard navigation
    nextTick(() => {
      const modalElement = document.querySelector('[data-modal-content]')
      if (modalElement) {
        (modalElement as HTMLElement).focus()
      }
    })
  }
})

// No need to expose methods since we're using global state
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>