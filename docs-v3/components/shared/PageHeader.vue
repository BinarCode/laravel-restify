<template>
  <div class="text-center mb-16">
    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-6">
      <slot name="title" />
    </h1>
    <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
      <slot name="description" />
    </p>
    <div v-if="$slots.actions" class="mt-8">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup lang="ts">
</script>
