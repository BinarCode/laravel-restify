<template>
  <nav class="sticky top-0 z-50 bg-white/90 dark:bg-gray-900/90 backdrop-blur border-b border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <div class="flex items-center">
          <button
            @click="toggleMobileMenu"
            class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
            aria-label="Toggle menu"
          >
            <Bars3Icon class="h-6 w-6" />
          </button>
          
          <NuxtLink to="/" class="flex items-center ml-2 lg:ml-0 group">
            <span class="text-2xl font-bold tracking-tight">
              <span class="bg-gradient-to-r from-gray-700 via-gray-500 to-gray-700 dark:from-white dark:via-gray-200 dark:to-white bg-clip-text text-transparent drop-shadow-sm">
                Laravel Restify
              </span>
              <span class="inline-block w-2 h-2 ml-0.5 mb-1 rounded-full bg-gradient-to-br from-red-500 to-red-600 shadow-lg shadow-red-500/50 group-hover:shadow-red-500/70 group-hover:scale-110 transition-all duration-300"></span>
            </span>
          </NuxtLink>
        </div>

        <div class="hidden lg:flex lg:items-center lg:space-x-8">
          <NuxtLink
            v-for="link in navLinks"
            :key="link.to"
            :to="link.to"
            class="text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors cursor-pointer"
            :class="{ 'text-red-600 dark:text-red-400': isActiveRoute(link.to) }"
          >
            {{ link.label }}
          </NuxtLink>
        </div>

        <div class="flex items-center space-x-4">
          <div v-if="isDocsPage" class="hidden lg:block">
            <UContentSearchButton label="Search..." :collapsed="false" />
          </div>
          
          <a
            href="https://github.com/binarcode/laravel-restify"
            target="_blank"
            rel="noopener noreferrer"
            class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md p-2"
            aria-label="GitHub Repository"
          >
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
            </svg>
          </a>
          
          <ThemeToggle />
        </div>
      </div>
    </div>
    
    <div
      v-if="isMobileMenuOpen"
      class="lg:hidden border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900"
    >
      <div class="px-2 pt-2 pb-3 space-y-1">
        <NuxtLink
          v-for="link in navLinks"
          :key="link.to"
          :to="link.to"
          @click="closeMobileMenu"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors cursor-pointer"
          :class="{ 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20': isActiveRoute(link.to) }"
        >
          {{ link.label }}
        </NuxtLink>
      </div>
      
      <div v-if="isDocsPage" class="px-4 pb-4 border-t border-gray-200 dark:border-gray-700">
        <div class="pt-4">
          <UContentSearchButton label="Search..." :collapsed="false" />
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { Bars3Icon } from '@heroicons/vue/24/outline'

interface NavLink {
  to: string
  label: string
}

const navLinks: NavLink[] = [
  { to: '/docs', label: 'Documentation' },
  { to: '/templates', label: 'Templates' },
  { to: '/case-studies', label: 'Case Studies' },
  { to: '/community', label: 'Community' },
  { to: '/playground', label: 'Playground' }
]

const route = useRoute()
const isMobileMenuOpen = ref(false)

const isDocsPage = computed(() => route.path.startsWith('/docs'))

function isActiveRoute(path: string): boolean {
  return route.path.startsWith(path)
}

function toggleMobileMenu(): void {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

function closeMobileMenu(): void {
  isMobileMenuOpen.value = false
}

watch(() => route.path, closeMobileMenu)

provide('isMobileMenuOpen', isMobileMenuOpen)
provide('toggleMobileMenu', toggleMobileMenu)
</script>
