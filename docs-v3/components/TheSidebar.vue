<template>
  <aside class="hidden lg:flex lg:w-64 lg:flex-col">
    <div class="flex flex-col h-screen sticky top-0 bg-gray-50 dark:bg-gray-800/50 border-r border-gray-200 dark:border-gray-700">
      <nav class="flex-1 px-4 py-6 overflow-y-auto">
        <div class="space-y-6">
          <div 
            v-for="section in navigationSections" 
            :key="section.title"
            class="space-y-2"
          >
            <button
              @click="toggleSection(section.title)"
              class="flex items-center justify-between w-full text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
            >
              {{ section.title }}
              <svg 
                class="h-4 w-4 transition-transform duration-200"
                :class="{ 'rotate-180': isSectionCollapsed(section.title) }"
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <ul 
              v-show="!isSectionCollapsed(section.title)"
              class="space-y-1 transition-all duration-200"
            >
              <li v-for="item in section.items" :key="item.path">
                <NuxtLink
                  :to="item.path"
                  class="group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                  :class="isActive(item.path) 
                    ? 'nav-link-active text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20' 
                    : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50'"
                >
                  {{ item.title }}
                </NuxtLink>
              </li>
            </ul>
          </div>
        </div>
      </nav>
    </div>
  </aside>
  
  <!-- Mobile sidebar overlay -->
  <Teleport to="body">
    <div
      v-if="isMobileMenuOpen"
      class="fixed inset-0 z-50 lg:hidden"
      @click="toggleMobileMenu"
      @touchmove.prevent
    >
      <div class="absolute inset-0 bg-gray-600 opacity-75"></div>
      
      <nav 
        class="relative max-w-xs w-full bg-white dark:bg-gray-900 h-full shadow-xl flex flex-col"
        @click.stop
        @touchmove.stop
      >
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Navigation</h2>
          <button
            @click.stop="toggleMobileMenu"
            class="p-2 rounded-md text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white focus-ring"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <div class="flex-1 overflow-y-auto px-4 py-6" style="-webkit-overflow-scrolling: touch;">
          <div class="space-y-6">
            <div 
              v-for="section in navigationSections" 
              :key="section.title"
              class="space-y-2"
            >
              <button
                @click="toggleSection(section.title)"
                class="flex items-center justify-between w-full text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
              >
                {{ section.title }}
                <svg 
                  class="h-4 w-4 transition-transform duration-200"
                  :class="{ 'rotate-180': isSectionCollapsed(section.title) }"
                  fill="none" 
                  viewBox="0 0 24 24" 
                  stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <ul 
                v-show="!isSectionCollapsed(section.title)"
                class="space-y-1 transition-all duration-200"
              >
                <li v-for="item in section.items" :key="item.path">
                  <NuxtLink
                    :to="item.path"
                    @click="toggleMobileMenu"
                    class="group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                    :class="isActive(item.path) 
                      ? 'nav-link-active text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20' 
                      : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50'"
                  >
                    {{ item.title }}
                  </NuxtLink>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </nav>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
const route = useRoute()
const { navigationSections } = useNavigation()

// Inject mobile menu state
const isMobileMenuOpen = inject('isMobileMenuOpen', ref(false))
const toggleMobileMenu = inject('toggleMobileMenu', () => {})

// Handle body scroll locking for mobile menu
watch(isMobileMenuOpen, (isOpen) => {
  if (process.client) {
    if (isOpen) {
      // Lock body scroll when mobile menu is open
      document.body.style.overflow = 'hidden'
      document.body.style.position = 'fixed'
      document.body.style.width = '100%'
    } else {
      // Restore body scroll when mobile menu is closed
      document.body.style.overflow = ''
      document.body.style.position = ''
      document.body.style.width = ''
    }
  }
})

// Collapsible sections state - start with all sections collapsed
const collapsedSections = ref<Set<string>>(new Set())

// Initialize all sections as collapsed when navigation sections are available
onMounted(() => {
  if (navigationSections.value) {
    const allSectionTitles = navigationSections.value.map(section => section.title)
    collapsedSections.value = new Set(allSectionTitles)
  }
})

// Toggle section visibility
const toggleSection = (sectionTitle: string) => {
  if (collapsedSections.value.has(sectionTitle)) {
    collapsedSections.value.delete(sectionTitle)
  } else {
    collapsedSections.value.add(sectionTitle)
  }
}

// Check if section is collapsed
const isSectionCollapsed = (sectionTitle: string) => {
  return collapsedSections.value.has(sectionTitle)
}

// Check if current route is active
const isActive = (path: string) => {
  return route.path === path || route.path.startsWith(path + '/')
}
</script>