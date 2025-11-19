<template>
  <footer class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- Brand and description - spans 2 columns on desktop -->
        <div class="col-span-1 md:col-span-2">
          <div class="flex items-center space-x-2 mb-4">
            <div class="w-8 h-8 bg-red-600 dark:bg-red-500 rounded-lg flex items-center justify-center">
              <span class="text-white font-bold text-lg">R</span>
            </div>
            <span class="font-semibold text-xl text-gray-900 dark:text-white">Laravel Restify</span>
          </div>
          <p class="text-gray-600 dark:text-gray-400 mb-4 max-w-md">
            Build JSON:API compliant REST APIs and MCP servers from your Laravel Eloquent models 
            with zero configuration. Open source and community-driven.
          </p>
          <div class="flex space-x-4">
            <a
              href="https://github.com/binarcode/laravel-restify"
              target="_blank"
              rel="noopener noreferrer"
              class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
              aria-label="GitHub"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"></path>
                <path d="M9 18c-4.51 2-5-2-7-2"></path>
              </svg>
            </a>
          </div>
        </div>

        <!-- Resources -->
        <div>
          <h3 class="font-semibold mb-4 text-gray-900 dark:text-white">Resources</h3>
          <ul class="space-y-2">
            <li>
              <NuxtLink 
                to="/docs" 
                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors flex items-center"
              >
                Documentation
                <ArrowTopRightOnSquareIcon class="w-3 h-3 ml-1" />
              </NuxtLink>
            </li>
            <li>
              <NuxtLink 
                to="/docs/quickstart" 
                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors flex items-center"
              >
                Getting Started
                <ArrowTopRightOnSquareIcon class="w-3 h-3 ml-1" />
              </NuxtLink>
            </li>
            <li>
              <a 
                href="https://github.com/binarcode/laravel-restify/blob/main/CHANGELOG.md" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors flex items-center"
              >
                Changelog
                <ArrowTopRightOnSquareIcon class="w-3 h-3 ml-1" />
              </a>
            </li>
            <li>
              <a 
                href="https://github.com/binarcode/laravel-restify/releases" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors flex items-center"
              >
                Releases
                <ArrowTopRightOnSquareIcon class="w-3 h-3 ml-1" />
              </a>
            </li>
          </ul>
        </div>

        <!-- Community -->
        <div>
          <h3 class="font-semibold mb-4 text-gray-900 dark:text-white">Community</h3>
          <ul class="space-y-2">
            <li>
              <a 
                href="https://github.com/binarcode/laravel-restify" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors flex items-center"
              >
                GitHub
                <ArrowTopRightOnSquareIcon class="w-3 h-3 ml-1" />
              </a>
            </li>
            <li>
              <a 
                href="https://github.com/binarcode/laravel-restify/discussions" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors flex items-center"
              >
                Discussions
                <ArrowTopRightOnSquareIcon class="w-3 h-3 ml-1" />
              </a>
            </li>
            <li>
              <a 
                href="https://github.com/binarcode/laravel-restify/issues" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors flex items-center"
              >
                Issues
                <ArrowTopRightOnSquareIcon class="w-3 h-3 ml-1" />
              </a>
            </li>
            <li>
              <a 
                href="https://binarcode.com" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors flex items-center"
              >
                BinarCode
                <ArrowTopRightOnSquareIcon class="w-3 h-3 ml-1" />
              </a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Bottom section -->
      <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
        <div class="flex flex-col md:flex-row items-center justify-between">
          <p class="text-gray-600 dark:text-gray-400 text-sm">
            © {{ currentYear }} Laravel Restify. Open source under MIT License.
          </p>
          <div class="mt-4 md:mt-0">
            <p class="text-gray-600 dark:text-gray-400 text-sm">
              Made with <span class="text-red-500">❤</span> by the <a href="https://binarcode.com" target="_blank" rel="noopener noreferrer" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors font-medium">BinarCode</a> team
            </p>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline'

// Website footer component
const currentYear = computed(() => new Date().getFullYear())
</script>