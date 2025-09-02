<template>
  <section class="relative text-center py-20 mb-8">
    <h1 class="text-5xl md:text-7xl font-bold text-gray-900 dark:text-white mb-6">
      Laravel Restify
    </h1>
    <p class="text-2xl text-gray-600 dark:text-gray-400 mb-8 max-w-3xl mx-auto leading-relaxed">
      <slot name="subtitle" />
    </p>
    <div class="mb-12">
      <HumanToAiTransformation />
    </div>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <NuxtLink
        to="/quickstart"
        aria-label="Get Started"
        class="inline-flex items-center px-8 py-4 text-lg font-medium rounded-xl text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200 shadow-lg hover:shadow-xl"
      >
        Get Started
        <ArrowRight :size="20" class="ml-2" />
      </NuxtLink>
      <a
        href="https://github.com/binarcode/laravel-restify"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="View on GitHub"
        class="inline-flex items-center px-8 py-4 text-lg font-medium rounded-xl text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200 shadow-lg hover:shadow-xl"
      >
        <Github :size="20" class="mr-2" />
        View on GitHub
      </a>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ArrowRight, Github } from 'lucide-vue-next'
import HumanToAiTransformation from './HumanToAiTransformation.vue'
</script>
