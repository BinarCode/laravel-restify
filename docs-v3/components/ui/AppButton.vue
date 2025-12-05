<template>
  <NuxtLink
    v-if="isNuxtLink"
    :to="href"
    :class="buttonClasses"
  >
    <div v-if="variant === 'primary'" class="absolute inset-0 bg-gradient-to-r from-blue-600 to-cyan-700 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    <component v-if="icon" :is="icon" class="relative z-10 w-5 h-5 group-hover:rotate-12 transition-transform duration-300" />
    <span class="relative z-10"><slot /></span>
    <ArrowTopRightOnSquareIcon v-if="showExternalIcon && !isNuxtLink" class="relative z-10 w-4 h-4 ml-1" />
  </NuxtLink>
  <a
    v-else
    :href="href"
    :target="external ? '_blank' : undefined"
    :rel="external ? 'noopener noreferrer' : undefined"
    :class="buttonClasses"
  >
    <div v-if="variant === 'primary'" class="absolute inset-0 bg-gradient-to-r from-blue-600 to-cyan-700 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    <component v-if="icon" :is="icon" class="relative z-10 w-5 h-5 group-hover:rotate-12 transition-transform duration-300" />
    <span class="relative z-10"><slot /></span>
  </a>
</template>

<script setup lang="ts">
import type { Component } from 'vue'
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline'

type ButtonVariant = 'primary' | 'secondary'
type ButtonSize = 'sm' | 'md' | 'lg'

interface Props {
  variant?: ButtonVariant
  size?: ButtonSize
  href: string
  icon?: Component
  isNuxtLink?: boolean
  external?: boolean
  showExternalIcon?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'lg',
  isNuxtLink: false,
  external: true,
  showExternalIcon: false
})

const SIZE_CLASSES: Record<ButtonSize, string> = {
  sm: 'px-4 py-2 text-sm min-h-[40px]',
  md: 'px-5 py-3 text-base min-h-[48px]',
  lg: 'px-6 sm:px-8 py-4 text-base sm:text-lg min-h-[56px]'
}

const BASE_CLASSES = 'cursor-pointer group relative inline-flex items-center justify-center gap-3 font-semibold rounded-full overflow-hidden transition-all duration-300 hover:scale-105'

const VARIANT_STYLES: Record<ButtonVariant, string> = {
  primary: 'text-white bg-gradient-to-r from-blue-500 to-cyan-600 hover:shadow-2xl hover:shadow-blue-500/25',
  secondary: 'text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-white/5 border border-gray-300 dark:border-white/10 backdrop-blur-sm hover:bg-gray-200 dark:hover:bg-white/10 hover:border-gray-400 dark:hover:border-white/20'
}

const buttonClasses = computed(() => `${BASE_CLASSES} ${SIZE_CLASSES[props.size]} ${VARIANT_STYLES[props.variant]}`)
</script>
