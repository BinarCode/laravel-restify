<template>
  <div class="relative bg-white/80 dark:bg-gray-900/50 backdrop-blur-sm rounded-2xl border border-gray-200 dark:border-gray-700/50 p-8 md:p-12 overflow-hidden">
    <!-- Background gradient effects -->
    <div class="absolute inset-0 bg-gradient-to-br from-red-500/5 via-transparent to-blue-500/5" />
    <div class="absolute top-0 left-1/4 w-64 h-64 bg-red-500/10 rounded-full blur-3xl" />
    <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl" />
    
    <div class="relative text-center">
      <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4">
        Ready to Build Your Next Success Story?
      </h2>
      <p class="text-lg text-gray-600 dark:text-gray-400 mb-8 max-w-2xl mx-auto">
        Join the companies already using Laravel Restify to build powerful APIs and applications. 
        Start your project today and become our next case study.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <HeroButton
          type="primary"
          href="/docs"
          :icon="BookOpenIcon"
          :is-nuxt-link="true"
        >
          Get Started with Restify
        </HeroButton>
        <HeroButton
          type="secondary"
          href="https://www.binarcode.com/recent-work"
          :icon="BriefcaseIcon"
        >
          Recent Projects
        </HeroButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { BookOpenIcon, BriefcaseIcon } from '@heroicons/vue/24/outline'
import HeroButton from '~/components/website/HeroButton.vue'
</script>
