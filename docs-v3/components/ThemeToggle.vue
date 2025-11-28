<template>
  <button
    @click="toggleDarkMode"
    class="dark-mode-toggle p-2 rounded-md text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white focus-ring"
    :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
  >
    <!-- Sun icon (light mode) -->
    <SunIcon
      v-if="$colorMode.value === 'dark'"
      class="h-5 w-5"
    />
    
    <!-- Moon icon (dark mode) -->
    <MoonIcon
      v-else
      class="h-5 w-5"
    />
  </button>
</template>

<script setup lang="ts">
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline'

const colorMode = useColorMode()

const isDark = computed(() => colorMode.value === 'dark')

const toggleDarkMode = () => {
  colorMode.preference = colorMode.value === 'dark' ? 'light' : 'dark'
}
</script>