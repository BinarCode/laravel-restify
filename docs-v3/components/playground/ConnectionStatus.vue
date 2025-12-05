<template>
  <div class="inline-flex items-center space-x-2 px-3 py-1.5 bg-white/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-full shadow-sm">
    <div class="flex items-center space-x-1">
      <div 
        class="w-2 h-2 rounded-full"
        :class="isConnected ? 'bg-green-500 animate-pulse' : 'bg-red-500'"
      ></div>
      <span class="text-xs font-mono" :class="isConnected ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
        {{ isConnected ? 'Connected' : 'Disconnected' }}
      </span>
    </div>
    <span class="text-gray-400 dark:text-gray-600">•</span>
    <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ baseUrl }}</span>
  </div>
</template>

<script setup lang="ts">
interface Props {
  isConnected?: boolean
  baseUrl?: string
}

withDefaults(defineProps<Props>(), {
  isConnected: true,
  baseUrl: 'api.laravel-restify.com'
})
</script>
