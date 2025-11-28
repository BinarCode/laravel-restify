<template>
  <header class="sticky top-0 z-40 bg-white/90 dark:bg-gray-900/90 backdrop-blur border-b border-gray-200 dark:border-gray-700 w-full">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
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
          <NuxtLink to="/" class="flex items-center ml-2 md:ml-0">
            <img src="/icon.png" alt="Laravel Restify" class="h-8 w-8" />
            <span class="ml-3 text-xl font-semibold text-gray-900 dark:text-white">
              Laravel Restify
            </span>
          </NuxtLink>
        </div>

        <!-- Navigation and actions -->
        <div class="flex items-center space-x-4">
          <!-- Search (desktop) -->
          <div class="hidden lg:block">
            <UContentSearchButton 
              :collapsed="false"
              placeholder="Search documentation..."
            />
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
          
          <!-- Twitter link -->
          <a
            href="https://twitter.com/LupacescuEuard"
            target="_blank"
            rel="noopener noreferrer"
            class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white focus-ring rounded-md p-2"
            aria-label="Twitter Profile"
          >
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
            </svg>
          </a>
          
          <!-- Dark mode toggle -->
          <ThemeToggle />
        </div>
      </div>
    </div>
    
    <!-- Mobile search -->
    <div class="lg:hidden px-4 pb-4 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 w-full">
      <div class="pt-4 w-full">
        <UContentSearchButton 
          :collapsed="false" 
          placeholder="Search documentation..."
        />
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { Bars3Icon } from '@heroicons/vue/24/outline'

// Inject mobile menu state from layout
const toggleMobileMenu = inject('toggleMobileMenu', () => {})
</script>