<template>
  <component
    :is="href ? (isExternal ? 'a' : 'NuxtLink') : 'button'"
    :to="!isExternal && href ? href : undefined"
    :href="isExternal ? href : undefined"
    :target="isExternal ? '_blank' : undefined"
    :rel="isExternal ? 'noopener noreferrer' : undefined"
    :class="buttonClasses"
  >
    <slot />
    <ArrowTopRightOnSquareIcon v-if="isExternal && showExternalIcon" class="w-4 h-4 ml-2" />
  </component>
</template>

<script setup lang="ts">
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline'
import { computed } from 'vue'

interface Props {
  href?: string
  variant?: 'primary' | 'secondary' | 'outline' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
  isExternal?: boolean
  showExternalIcon?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  isExternal: false,
  showExternalIcon: true
})

const buttonClasses = computed(function computeButtonClasses(): string {
  const base = 'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200'
  
  const sizes: Record<string, string> = {
    sm: 'px-4 py-2 text-sm',
    md: 'px-6 py-3',
    lg: 'px-8 py-4 text-lg'
  }
  
  const variants: Record<string, string> = {
    primary: 'bg-red-600 hover:bg-red-700 text-white shadow-sm hover:shadow-md',
    secondary: 'bg-gray-900 hover:bg-gray-800 dark:bg-white dark:hover:bg-gray-100 text-white dark:text-gray-900',
    outline: 'border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-400 dark:hover:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800',
    ghost: 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800'
  }
  
  return `${base} ${sizes[props.size]} ${variants[props.variant]}`
})
</script>
