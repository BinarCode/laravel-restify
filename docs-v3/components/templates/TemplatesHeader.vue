<template>
  <div class="text-center mb-16">
    <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">
      Choose Your Package
    </h1>
    <p class="text-xl text-gray-400 max-w-2xl mx-auto mb-8">
      From free starter kits to premium enterprise solutions. Get production-ready Laravel Restify templates.
    </p>
    
    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
      <a
        href="https://restifytemplates.com/"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200"
      >
        Browse All Templates
        <ArrowTopRightOnSquareIcon class="w-4 h-4 ml-2" />
      </a>
      <a
        href="https://www.binarcode.com/estimate-project"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center px-6 py-3 bg-gray-800/50 border border-gray-700 text-gray-300 hover:border-gray-600 hover:bg-gray-800 font-medium rounded-lg transition-all duration-200"
      >
        Get Your Custom Template
        <ArrowTopRightOnSquareIcon class="w-4 h-4 ml-2" />
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline'
</script>
