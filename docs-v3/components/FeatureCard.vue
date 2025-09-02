<template>
  <div
    class="group relative p-8 bg-white dark:bg-gray-800 backdrop-blur-sm rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600 transition-all duration-200 hover:shadow-xl hover:-translate-y-2 focus-within:ring-2 focus-within:ring-primary-500"
    tabindex="0"
    role="region"
    :aria-label="title"
  >
    <div class="relative">
      <div :class="`w-12 h-12 bg-gradient-to-br ${color} rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform`">
        <component :is="icon" :size="24" class="text-white" />
      </div>
      <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">{{ title }}</h3>
      <p class="text-gray-600 dark:text-gray-400">
        <slot />
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps({
  icon: { type: [Object, Function], required: true },
  title: { type: String, required: true },
  color: { type: String, default: 'from-primary-500 to-blue-500' }
})
</script>
