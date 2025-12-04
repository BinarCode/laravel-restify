<template>
  <div class="glass-panel text-center p-8 max-w-2xl mx-auto">
    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
      <slot name="title" />
    </h3>
    <p class="text-gray-600 dark:text-gray-400 mb-6">
      <slot name="description" />
    </p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup lang="ts">
</script>
