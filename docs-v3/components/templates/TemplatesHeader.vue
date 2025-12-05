<template>
  <div class="text-center mb-16">
    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-6">
      Choose Your Package
    </h1>
    <p class="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-8">
      From free starter kits to premium enterprise solutions. Get production-ready Laravel Restify templates.
    </p>
    
    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
      <AppButton
        variant="primary"
        size="md"
        href="https://restifytemplates.com/"
        :icon="RocketLaunchIcon"
        :show-external-icon="true"
      >
        Browse All Templates
      </AppButton>
      <AppButton
        variant="secondary"
        size="md"
        href="https://www.binarcode.com/estimate-project"
        :icon="WrenchScrewdriverIcon"
        :show-external-icon="true"
      >
        Get Custom Template
      </AppButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { RocketLaunchIcon, WrenchScrewdriverIcon } from '@heroicons/vue/24/outline'
import AppButton from '~/components/ui/AppButton.vue'
</script>
