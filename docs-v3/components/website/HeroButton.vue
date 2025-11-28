<template>
  <component 
    :is="isNuxtLink ? 'NuxtLink' : 'a'"
    :to="isNuxtLink ? href : undefined"
    :href="!isNuxtLink ? href : undefined"
    :target="!isNuxtLink ? '_blank' : undefined"
    :rel="!isNuxtLink ? 'noopener noreferrer' : undefined"
    :class="buttonClasses"
  >
    <div v-if="type === 'primary'" class="absolute inset-0 bg-gradient-to-r from-blue-600 to-cyan-700 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    <component :is="icon" class="relative z-10 w-5 h-5 group-hover:rotate-12 transition-transform duration-300" />
    <span class="relative z-10"><slot /></span>
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Component } from 'vue'

interface Props {
  type: 'primary' | 'secondary'
  href: string
  icon: Component
  isNuxtLink?: boolean
}

const props = defineProps<Props>()

const buttonClasses = computed(() => {
  const baseClasses = 'group relative inline-flex items-center justify-center gap-3 px-6 sm:px-8 py-4 text-base sm:text-lg font-semibold rounded-full overflow-hidden transition-all duration-300 hover:scale-105 min-h-[56px]'
  
  return {
    primary: `${baseClasses} text-white bg-gradient-to-r from-blue-500 to-cyan-600 hover:shadow-2xl hover:shadow-blue-500/25`,
    secondary: `${baseClasses} text-gray-300 bg-white/5 border border-white/10 backdrop-blur-sm hover:bg-white/10 hover:border-white/20`
  }[props.type || 'primary']
})
</script>