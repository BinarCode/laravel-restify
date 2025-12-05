<template>
  <NuxtLink v-if="linkable" to="/" class="flex items-center group">
    <span class="text-2xl font-bold tracking-tight">
      <span class="bg-gradient-to-r from-gray-700 via-gray-500 to-gray-700 dark:from-white dark:via-gray-200 dark:to-white bg-clip-text text-transparent drop-shadow-sm">
        Laravel Restify
      </span>
      <span class="inline-block w-2 h-2 ml-0.5 mb-1 rounded-full bg-gradient-to-br from-red-500 to-red-600 shadow-lg shadow-red-500/50 group-hover:shadow-red-500/70 group-hover:scale-110 transition-all duration-300"></span>
    </span>
  </NuxtLink>
  <div v-else class="flex items-center">
    <span class="text-2xl font-bold tracking-tight">
      <span class="bg-gradient-to-r from-gray-700 via-gray-500 to-gray-700 dark:from-white dark:via-gray-200 dark:to-white bg-clip-text text-transparent drop-shadow-sm">
        Laravel Restify
      </span>
      <span class="inline-block w-2 h-2 ml-0.5 mb-1 rounded-full bg-gradient-to-br from-red-500 to-red-600 shadow-lg shadow-red-500/50"></span>
    </span>
  </div>
</template>

<script setup lang="ts">
interface Props {
  linkable?: boolean
}

withDefaults(defineProps<Props>(), {
  linkable: false
})
</script>
