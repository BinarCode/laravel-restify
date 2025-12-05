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
      <div class="mb-16">
        <CommunityStats />
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
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- GitHub -->
          <CommunityChannelCard
            title="GitHub Repository"
            subtitle="Contribute code & report issues"
            description="Star the project, open issues, submit PRs, and help improve Laravel Restify for everyone."
            href="https://github.com/BinarCode/laravel-restify"
            linkText="View Repository"
            accent="gray"
          >
            <template #icon>
              <svg class="h-6 w-6 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
              </svg>
            </template>
          </CommunityChannelCard>

          <!-- Discussions -->
          <CommunityChannelCard
            title="GitHub Discussions"
            subtitle="Ask questions & share ideas"
            description="Get help from the community, share your use cases, and discuss new features."
            href="https://github.com/BinarCode/laravel-restify/discussions"
            linkText="Join Discussions"
            accent="blue"
          >
            <template #icon>
              <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
            </template>
          </CommunityChannelCard>

          <!-- Twitter -->
          <CommunityChannelCard
            title="Twitter Updates"
            subtitle="News & announcements"
            description="Follow for updates, tips, and announcements about Laravel Restify development."
            href="https://twitter.com/LupacescuEuard"
            linkText="Follow on Twitter"
            accent="blue"
          >
            <template #icon>
              <svg class="h-6 w-6 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
              </svg>
            </template>
          </CommunityChannelCard>

          <!-- Vimeo Videos -->
          <CommunityChannelCard
            title="Video Tutorials"
            subtitle="Vimeo tutorials (not up to date)"
            description="Watch video tutorials and learn Laravel Restify through examples (content may be outdated)."
            href="https://www.binarcode.com/learn/restify"
            linkText="Watch Videos"
            accent="purple"
          >
            <template #icon>
              <svg class="h-6 w-6 text-purple-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.977 6.416c-.105 2.338-1.739 5.543-4.894 9.609-3.268 4.247-6.026 6.37-8.29 6.37-1.409 0-2.578-1.294-3.553-3.881L5.322 11.4C4.603 8.816 3.834 7.522 3.01 7.522c-.179 0-.806.378-1.881 1.132L0 7.197c1.185-1.044 2.351-2.084 3.501-3.128C5.08 2.701 6.266 1.984 7.055 1.91c1.867-.18 3.016 1.1 3.447 3.838.465 2.953.789 4.789.971 5.507.539 2.45 1.131 3.674 1.776 3.674.502 0 1.256-.796 2.265-2.385 1.004-1.589 1.54-2.797 1.612-3.628.144-1.371-.395-2.061-1.614-2.061-.574 0-1.167.121-1.777.391 1.186-3.868 3.434-5.757 6.762-5.637 2.473.06 3.628 1.664 3.493 4.797l-.013.01z"/>
              </svg>
            </template>
          </CommunityChannelCard>
        </div>
      </div>

      <!-- Explore BinarCode Projects -->
      <div class="mb-16">
        <BinarCodeProjects />
      </div>

      <!-- Demo Repository -->
      <div class="relative bg-white/80 dark:bg-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-200 dark:border-gray-700/50 p-8 text-center overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-red-500/5 to-blue-500/5" />
        <div class="relative">
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
            class="inline-flex items-center text-primary-500 dark:text-primary-400 hover:text-primary-600 dark:hover:text-primary-300 font-medium transition-colors"
          >
            View Demo Repository
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
          </a>
        </div>
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