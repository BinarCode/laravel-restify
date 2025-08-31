<template>
  <div>
    <NuxtPage />
  </div>
</template>

<script setup lang="ts">
// Global app configuration
useHead({
  htmlAttrs: {
    lang: 'en'
  }
})

// Enable dark mode
const colorMode = useColorMode()
</script>