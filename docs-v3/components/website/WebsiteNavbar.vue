<template>
  <nav class="sticky top-0 z-50 bg-white/90 dark:bg-gray-900/90 backdrop-blur border-b border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <!-- Logo and brand -->
        <div class="flex items-center">
          <!-- Mobile menu button -->
          <button
            @click="toggleMobileMenu"
            class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white focus-ring"
            aria-label="Toggle menu"
          >
            <Bars3Icon class="h-6 w-6" />
          </button>
          
          <!-- Logo -->
          <NuxtLink to="/" class="flex items-center ml-2 lg:ml-0">
            <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-red-600 rounded-lg flex items-center justify-center">
              <span class="text-white font-bold text-lg">R</span>
            </div>
            <span class="ml-3 text-xl font-semibold text-gray-900 dark:text-white">
              Laravel Restify
            </span>
          </NuxtLink>
        </div>

        <!-- Desktop Navigation -->
        <div class="hidden lg:flex lg:items-center lg:space-x-8">
          <NuxtLink
            to="/docs"
            class="text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors cursor-pointer"
            :class="{ 'text-red-600 dark:text-red-400': $route.path.startsWith('/docs') }"
          >
            Documentation
          </NuxtLink>
          
          <NuxtLink
            to="/templates"
            class="text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors cursor-pointer"
            :class="{ 'text-red-600 dark:text-red-400': $route.path.startsWith('/templates') }"
          >
            Templates
          </NuxtLink>
          
          <NuxtLink
            to="/case-studies"
            class="text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors cursor-pointer"
            :class="{ 'text-red-600 dark:text-red-400': $route.path.startsWith('/case-studies') }"
          >
            Case Studies
          </NuxtLink>
          
          <NuxtLink
            to="/community"
            class="text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors cursor-pointer"
            :class="{ 'text-red-600 dark:text-red-400': $route.path.startsWith('/community') }"
          >
            Community
          </NuxtLink>
          
          <NuxtLink
            to="/playground"
            class="text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors cursor-pointer"
            :class="{ 'text-red-600 dark:text-red-400': $route.path.startsWith('/playground') }"
          >
            Playground
          </NuxtLink>
        </div>

        <!-- Right side actions -->
        <div class="flex items-center space-x-4">
          <!-- Search (desktop only, for docs pages) -->
          <div v-if="$route.path.startsWith('/docs')" class="hidden lg:block">
            <UContentSearchButton :label="'Search...'" :collapsed="false" />
          </div>
          
          <!-- GitHub link -->
          <a
            href="https://github.com/binarcode/laravel-restify"
            target="_blank"
            rel="noopener noreferrer"
            class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white focus-ring rounded-md p-2"
            aria-label="GitHub Repository"
          >
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
            </svg>
          </a>
          
          <!-- Dark mode toggle -->
          <ThemeToggle />
        </div>
      </div>
    </div>
    
    <!-- Mobile Navigation Menu -->
    <div
      v-if="isMobileMenuOpen"
      class="lg:hidden border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900"
    >
      <div class="px-2 pt-2 pb-3 space-y-1">
        <NuxtLink
          to="/docs"
          @click="toggleMobileMenu"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors cursor-pointer"
          :class="{ 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20': $route.path.startsWith('/docs') }"
        >
          Documentation
        </NuxtLink>
        
        <NuxtLink
          to="/templates"
          @click="toggleMobileMenu"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors cursor-pointer"
          :class="{ 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20': $route.path.startsWith('/templates') }"
        >
          Templates
        </NuxtLink>
        
        <NuxtLink
          to="/case-studies"
          @click="toggleMobileMenu"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors cursor-pointer"
          :class="{ 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20': $route.path.startsWith('/case-studies') }"
        >
          Case Studies
        </NuxtLink>
        
        <NuxtLink
          to="/community"
          @click="toggleMobileMenu"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors cursor-pointer"
          :class="{ 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20': $route.path.startsWith('/community') }"
        >
          Community
        </NuxtLink>
        
        <NuxtLink
          to="/playground"
          @click="toggleMobileMenu"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors cursor-pointer"
          :class="{ 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20': $route.path.startsWith('/playground') }"
        >
          Playground
        </NuxtLink>
      </div>
      
      <!-- Mobile search for docs -->
      <div v-if="$route.path.startsWith('/docs')" class="px-4 pb-4 border-t border-gray-200 dark:border-gray-700">
        <div class="pt-4">
          <UContentSearchButton :label="'Search...'" :collapsed="false" />
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
// Mobile menu state
const isMobileMenuOpen = ref(false)

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

// Close mobile menu when route changes
const route = useRoute()
watch(() => route.path, () => {
  isMobileMenuOpen.value = false
})

// Provide mobile menu controls for child components that might need it
provide('isMobileMenuOpen', isMobileMenuOpen)
provide('toggleMobileMenu', toggleMobileMenu)
</script>