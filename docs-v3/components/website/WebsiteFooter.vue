<template>
  <footer class="bg-white/90 dark:bg-gray-900/90 backdrop-blur border-t border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- Brand and description - spans 2 columns on desktop -->
        <div class="col-span-1 md:col-span-2">
          <div class="mb-4">
            <AppLogo />
          </div>
          <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md">
            Build JSON:API compliant REST APIs and MCP servers from your Laravel Eloquent models 
            with zero configuration. Open source and community-driven.
          </p>
          <div class="flex space-x-4">
            <a
              href="https://github.com/binarcode/laravel-restify"
              target="_blank"
              rel="noopener noreferrer"
              class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-800/50 border border-gray-300 dark:border-gray-700/50 flex items-center justify-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:border-gray-400 dark:hover:border-gray-600 transition-colors"
              aria-label="GitHub"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
              </svg>
            </a>
          </div>
        </div>

        <!-- Resources -->
        <div>
          <h3 class="font-semibold mb-4 text-gray-900 dark:text-white">Resources</h3>
          <ul class="space-y-3">
            <li v-for="link in resourceLinks" :key="link.label">
              <NuxtLink 
                v-if="!link.isExternal"
                :to="link.href"
                class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center group"
              >
                {{ link.label }}
              </NuxtLink>
              <a 
                v-else
                :href="link.href"
                target="_blank"
                rel="noopener noreferrer"
                class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center group"
              >
                {{ link.label }}
                <ArrowTopRightOnSquareIcon class="w-3 h-3 ml-1 opacity-50 group-hover:opacity-100" />
              </a>
            </li>
          </ul>
        </div>

        <!-- Community -->
        <div>
          <h3 class="font-semibold mb-4 text-gray-900 dark:text-white">Community</h3>
          <ul class="space-y-3">
            <li v-for="link in communityLinks" :key="link.label">
              <a 
                :href="link.href" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center group"
              >
                {{ link.label }}
                <ArrowTopRightOnSquareIcon class="w-3 h-3 ml-1 opacity-50 group-hover:opacity-100" />
              </a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Bottom section -->
      <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
        <div class="flex flex-col md:flex-row items-center justify-between">
          <p class="text-gray-600 dark:text-gray-500 text-sm">
            © {{ currentYear }} Laravel Restify. Open source under MIT License.
          </p>
          <div class="mt-4 md:mt-0">
            <p class="text-gray-600 dark:text-gray-500 text-sm">
              Made with <span class="text-red-500">❤</span> by the 
              <a href="https://binarcode.com" target="_blank" rel="noopener noreferrer" class="text-red-400 hover:text-red-300 transition-colors font-medium">
                BinarCode
              </a> team
            </p>
          </div>
        </div>

        <BuiltByCredit class="mt-4" />
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline'
import AppLogo from '../ui/AppLogo.vue'
import BuiltByCredit from '../shared/BuiltByCredit.vue'

const currentYear = new Date().getFullYear()

interface FooterLink {
  label: string
  href: string
  isExternal?: boolean
}

const resourceLinks: FooterLink[] = [
  { label: 'Documentation', href: '/docs', isExternal: false },
  { label: 'Getting Started', href: '/docs/getting-started/quickstart', isExternal: false },
  { label: 'Changelog', href: 'https://github.com/binarcode/laravel-restify/blob/10.x/CHANGELOG.md', isExternal: true },
  { label: 'Releases', href: 'https://github.com/binarcode/laravel-restify/releases', isExternal: true }
]

const communityLinks: FooterLink[] = [
  { label: 'GitHub', href: 'https://github.com/binarcode/laravel-restify', isExternal: true },
  { label: 'Discussions', href: 'https://github.com/binarcode/laravel-restify/discussions', isExternal: true },
  { label: 'Issues', href: 'https://github.com/binarcode/laravel-restify/issues', isExternal: true },
  { label: 'BinarCode', href: 'https://binarcode.com', isExternal: true }
]
</script>