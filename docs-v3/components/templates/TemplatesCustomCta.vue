<template>
  <div class="text-center">
    <div class="glass-panel max-w-2xl mx-auto">
      <h3 class="text-2xl font-bold mb-4 text-white">Need Something Custom?</h3>
      <p class="text-gray-400 mb-6">
        Our team can build a custom Laravel Restify template tailored specifically to your project needs.
      </p>
      <AppButton
        variant="primary"
        size="md"
        href="https://www.binarcode.com/estimate-project"
        :icon="WrenchScrewdriverIcon"
        :show-external-icon="true"
      >
        Get Your Custom Template
      </AppButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { WrenchScrewdriverIcon } from '@heroicons/vue/24/outline'
import AppButton from '~/components/ui/AppButton.vue'
</script>
