<template>
  <section class="py-20 md:py-24 relative overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-10 md:mb-12">
        <div class="inline-flex items-center px-4 md:px-6 py-3 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/20 backdrop-blur-sm rounded-full text-sm font-medium text-blue-600 dark:text-blue-300 mb-6">
          <PlayCircleIcon class="w-4 h-4 mr-2" />
          Package Overview
        </div>

        <h2 class="text-3xl md:text-4xl lg:text-5xl font-black tracking-tight mb-4">
          <span class="bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-400 bg-clip-text text-transparent">
            See Laravel Restify
          </span>
          <br />
          <span class="bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-500 bg-clip-text text-transparent">
            In Action
          </span>
        </h2>

        <p class="text-lg md:text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto leading-relaxed">
          A quick walkthrough of the package, what it does, and how it helps you expose Laravel models as APIs without the usual boilerplate.
        </p>
      </div>

      <div class="relative rounded-3xl overflow-hidden border border-gray-200/80 dark:border-gray-700/60 bg-white/70 dark:bg-gray-900/70 backdrop-blur-sm shadow-2xl shadow-blue-500/10">
        <div class="aspect-video">
          <iframe
            class="h-full w-full"
            src="https://www.youtube.com/embed/hvDptykQTgg"
            title="Laravel Restify package overview"
            loading="lazy"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
            referrerpolicy="strict-origin-when-cross-origin"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { PlayCircleIcon } from '@heroicons/vue/24/outline'
</script>
