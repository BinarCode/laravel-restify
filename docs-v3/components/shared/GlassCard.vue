<template>
  <div :class="cardClasses" class="group">
    <div class="relative z-10 flex flex-col h-full">
      <!-- Icon -->
      <div v-if="$slots.icon" :class="iconContainerClasses">
        <slot name="icon" />
      </div>
      
      <!-- Badge -->
      <div v-if="badge" class="absolute -top-3 left-1/2 transform -translate-x-1/2 z-10">
        <span :class="badgeClasses">
          {{ badge }}
        </span>
      </div>
      
      <!-- Content -->
      <div class="flex-1">
        <h3 v-if="title" class="text-xl font-bold text-gray-900 dark:text-white mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
          {{ title }}
        </h3>
        <slot />
      </div>
      
      <!-- Footer -->
      <div v-if="$slots.footer" class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  title?: string
  badge?: string
  badgeColor?: 'green' | 'blue' | 'purple' | 'orange' | 'red'
  accentColor?: 'blue' | 'green' | 'purple' | 'red' | 'yellow' | 'cyan'
  interactive?: boolean
  centered?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  badgeColor: 'blue',
  accentColor: 'blue',
  interactive: false,
  centered: false
})

const cardClasses = computed(function computeCardClasses(): string {
  const base = 'relative bg-gray-900/30 backdrop-blur-sm border border-gray-700/50 rounded-2xl p-6 transition-all duration-300 flex flex-col h-full'
  const hover = props.interactive ? 'hover:border-gray-600/50 cursor-pointer' : ''
  const center = props.centered ? 'text-center' : ''
  return `${base} ${hover} ${center}`
})

const iconContainerClasses = computed(function computeIconContainerClasses(): string {
  const base = 'w-12 h-12 rounded-xl flex items-center justify-center mb-6 transition-colors duration-300'
  const colors: Record<string, string> = {
    blue: 'bg-gradient-to-br from-blue-500/20 to-cyan-500/20 border border-blue-500/30 group-hover:border-blue-400/50',
    green: 'bg-gradient-to-br from-green-500/20 to-emerald-500/20 border border-green-500/30 group-hover:border-green-400/50',
    purple: 'bg-gradient-to-br from-purple-500/20 to-violet-500/20 border border-purple-500/30 group-hover:border-purple-400/50',
    red: 'bg-gradient-to-br from-red-500/20 to-pink-500/20 border border-red-500/30 group-hover:border-red-400/50',
    yellow: 'bg-gradient-to-br from-yellow-500/20 to-orange-500/20 border border-yellow-500/30 group-hover:border-yellow-400/50',
    cyan: 'bg-gradient-to-br from-cyan-500/20 to-blue-500/20 border border-cyan-500/30 group-hover:border-cyan-400/50'
  }
  const center = props.centered ? 'mx-auto' : ''
  return `${base} ${colors[props.accentColor]} ${center}`
})

const badgeClasses = computed(function computeBadgeClasses(): string {
  const base = 'px-3 py-1 text-white text-xs font-bold rounded-full'
  const colors: Record<string, string> = {
    green: 'bg-green-500',
    blue: 'bg-blue-500',
    purple: 'bg-purple-500',
    orange: 'bg-orange-500',
    red: 'bg-red-500'
  }
  return `${base} ${colors[props.badgeColor]}`
})
</script>
