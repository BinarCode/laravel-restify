<template>
  <NuxtLink
    v-if="isNuxtLink"
    :to="href"
    :class="buttonClasses"
  >
    <div v-if="isPrimary" class="absolute inset-0 bg-gradient-to-r from-blue-600 to-cyan-700 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    <component :is="icon" class="relative z-10 w-5 h-5 group-hover:rotate-12 transition-transform duration-300" />
    <span class="relative z-10"><slot /></span>
  </NuxtLink>
  <a
    v-else
    :href="href"
    target="_blank"
    rel="noopener noreferrer"
    :class="buttonClasses"
  >
    <div v-if="isPrimary" class="absolute inset-0 bg-gradient-to-r from-blue-600 to-cyan-700 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    <component :is="icon" class="relative z-10 w-5 h-5 group-hover:rotate-12 transition-transform duration-300" />
    <span class="relative z-10"><slot /></span>
  </a>
</template>

<script setup lang="ts">
import type { Component } from 'vue'

type ButtonType = 'primary' | 'secondary'

interface Props {
  type: ButtonType
  href: string
  icon: Component
  isNuxtLink?: boolean
}

const props = defineProps<Props>()

const isPrimary = computed(() => props.type === 'primary')

const BASE_CLASSES = 'cursor-pointer group relative inline-flex items-center justify-center gap-3 px-6 sm:px-8 py-4 text-base sm:text-lg font-semibold rounded-full overflow-hidden transition-all duration-300 hover:scale-105 min-h-[56px]'

const BUTTON_STYLES: Record<ButtonType, string> = {
  primary: `${BASE_CLASSES} text-white bg-gradient-to-r from-blue-500 to-cyan-600 hover:shadow-2xl hover:shadow-blue-500/25`,
  secondary: `${BASE_CLASSES} text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-white/5 border border-gray-300 dark:border-white/10 backdrop-blur-sm hover:bg-gray-200 dark:hover:bg-white/10 hover:border-gray-400 dark:hover:border-white/20`
}

const buttonClasses = computed(() => BUTTON_STYLES[props.type])
</script>