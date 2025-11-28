<template>
  <NuxtLayout name="website">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <!-- Header -->
      <div class="text-center mb-16">
        <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-4">
          Join the Laravel Restify Community
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
          Connect with fellow developers, get support, share your projects, and help make Laravel Restify better.
        </p>
      </div>

      <!-- Community Stats -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-8 mb-16">
        <div class="text-center">
          <div class="text-3xl font-bold text-green-500 mb-2">368K+</div>
          <div class="text-gray-600 dark:text-gray-400">Downloads</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-red-500 mb-2">626</div>
          <div class="text-gray-600 dark:text-gray-400">GitHub Stars</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-purple-500 mb-2">57</div>
          <div class="text-gray-600 dark:text-gray-400">Forks</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-blue-500 mb-2">20+</div>
          <div class="text-gray-600 dark:text-gray-400">Contributors</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-orange-500 mb-2">100+</div>
          <div class="text-gray-600 dark:text-gray-400">Projects Built</div>
        </div>
      </div>

      <!-- Contributors Section -->
      <div class="mb-16">
        <ContributorsSection />
      </div>

      <!-- Community Channels -->
      <div class="mb-16">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-12">
          Get Involved
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- GitHub -->
          <a
            href="https://github.com/BinarCode/laravel-restify"
            target="_blank"
            rel="noopener noreferrer"
            class="group bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg hover:shadow-xl transition-all hover:-translate-y-1"
          >
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-gray-900 dark:bg-white rounded-lg flex items-center justify-center mr-4">
                <svg class="h-6 w-6 text-white dark:text-gray-900" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">GitHub Repository</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Contribute code & report issues</p>
              </div>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
              Star the project, open issues, submit PRs, and help improve Laravel Restify for everyone.
            </p>
            <div class="mt-4 text-red-500 group-hover:text-red-600 font-medium">
              View Repository →
            </div>
          </a>

          <!-- Discussions -->
          <a
            href="https://github.com/BinarCode/laravel-restify/discussions"
            target="_blank"
            rel="noopener noreferrer"
            class="group bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg hover:shadow-xl transition-all hover:-translate-y-1"
          >
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mr-4">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">GitHub Discussions</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Ask questions & share ideas</p>
              </div>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
              Get help from the community, share your use cases, and discuss new features.
            </p>
            <div class="mt-4 text-blue-500 group-hover:text-blue-600 font-medium">
              Join Discussions →
            </div>
          </a>


          <!-- Twitter -->
          <a
            href="https://twitter.com/LupacescuEuard"
            target="_blank"
            rel="noopener noreferrer"
            class="group bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg hover:shadow-xl transition-all hover:-translate-y-1"
          >
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-blue-400 rounded-lg flex items-center justify-center mr-4">
                <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Twitter Updates</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">News & announcements</p>
              </div>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
              Follow for updates, tips, and announcements about Laravel Restify development.
            </p>
            <div class="mt-4 text-blue-400 group-hover:text-blue-500 font-medium">
              Follow on Twitter →
            </div>
          </a>

          <!-- Vimeo Videos -->
          <a
            href="https://www.binarcode.com/learn/restify"
            target="_blank"
            rel="noopener noreferrer"
            class="group bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg hover:shadow-xl transition-all hover:-translate-y-1"
          >
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mr-4">
                <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M23.977 6.416c-.105 2.338-1.739 5.543-4.894 9.609-3.268 4.247-6.026 6.37-8.29 6.37-1.409 0-2.578-1.294-3.553-3.881L5.322 11.4C4.603 8.816 3.834 7.522 3.01 7.522c-.179 0-.806.378-1.881 1.132L0 7.197c1.185-1.044 2.351-2.084 3.501-3.128C5.08 2.701 6.266 1.984 7.055 1.91c1.867-.18 3.016 1.1 3.447 3.838.465 2.953.789 4.789.971 5.507.539 2.45 1.131 3.674 1.776 3.674.502 0 1.256-.796 2.265-2.385 1.004-1.589 1.54-2.797 1.612-3.628.144-1.371-.395-2.061-1.614-2.061-.574 0-1.167.121-1.777.391 1.186-3.868 3.434-5.757 6.762-5.637 2.473.06 3.628 1.664 3.493 4.797l-.013.01z"/>
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Video Tutorials</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Vimeo tutorials (not up to date)</p>
              </div>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
              Watch video tutorials and learn Laravel Restify through examples (content may be outdated).
            </p>
            <div class="mt-4 text-blue-500 group-hover:text-blue-600 font-medium">
              Watch Videos →
            </div>
          </a>
        </div>
      </div>

      <!-- Explore BinarCode Projects -->
      <div class="bg-gradient-to-r from-red-50 to-blue-50 dark:from-gray-800 dark:to-gray-800 rounded-2xl p-8 mb-16">
        <div class="max-w-4xl mx-auto text-center">
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
            Explore BinarCode Projects
          </h2>
          <p class="text-lg text-gray-600 dark:text-gray-400 mb-12">
            Laravel Restify is part of a comprehensive suite of open-source tools and premium solutions built by the
            BinarCode team to accelerate development workflows.
          </p>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg">
              <div class="w-16 h-16 bg-red-500 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
              </div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Open Source Tools</h3>
              <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                Laravel packages and developer utilities available for free
              </p>
            </div>
            
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg">
              <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Premium Solutions</h3>
              <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                Enterprise-grade applications and custom development services
              </p>
            </div>
            
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg">
              <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                <svg class="h-8 w-8 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
              </div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Community Driven</h3>
              <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                Built with feedback from developers worldwide
              </p>
            </div>
          </div>
          
          <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
            <a
              href="https://www.binarcode.com/projects"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center px-8 py-4 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition-colors text-lg"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
              </svg>
              View All BinarCode Projects
            </a>
            <a
              href="https://github.com/BinarCode"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center px-8 py-4 bg-gray-900 hover:bg-gray-800 dark:bg-white dark:hover:bg-gray-100 text-white dark:text-gray-900 font-semibold rounded-lg transition-colors text-lg"
            >
              <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
              </svg>
              Open Source Projects
            </a>
          </div>
        </div>
      </div>

      <!-- Demo Repository -->
      <div class="text-center">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
          Demo Repository
        </h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-2xl mx-auto">
          Explore a complete example of Laravel Restify in action with our demo repository. 
          See real-world implementation and get inspired for your own projects.
        </p>
        <a
          href="https://github.com/BinarCode/restify-demo"
          target="_blank"
          rel="noopener noreferrer"
          class="text-red-500 hover:text-red-600 font-medium"
        >
          View Demo Repository →
        </a>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import ContributorsSection from '~/components/website/ContributorsSection.vue'

useHead({
  title: 'Community - Laravel Restify',
  meta: [
    {
      name: 'description',
      content: 'Join the Laravel Restify community. Get support, contribute to the project, and connect with fellow developers.'
    }
  ]
})
</script>

