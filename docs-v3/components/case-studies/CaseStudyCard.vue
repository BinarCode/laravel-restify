<template>
  <div 
    class="relative bg-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-8 overflow-hidden group hover:border-gray-600/50 transition-all duration-300"
    :class="[`hover:shadow-lg hover:shadow-${accent}-500/10`]"
  >
    <!-- Accent top border -->
    <div 
      class="absolute top-0 left-0 right-0 h-1 opacity-60 group-hover:opacity-100 transition-opacity"
      :class="accentGradientClass"
    />
    
    <!-- Header -->
    <div class="flex items-start justify-between mb-6">
      <div class="flex items-center">
        <div 
          class="w-12 h-12 rounded-xl flex items-center justify-center mr-4"
          :class="iconBgClass"
        >
          <slot name="icon">
            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3-3 3 3 3-3 3 3V5a2 2 0 00-2-2z" />
            </svg>
          </slot>
        </div>
        <div>
          <h3 class="text-xl font-bold text-white">{{ title }}</h3>
          <p class="text-sm text-gray-400 font-medium">{{ subtitle }}</p>
        </div>
      </div>
      <span 
        class="text-xs font-medium px-2.5 py-1 rounded-lg border"
        :class="categoryBadgeClass"
      >
        {{ category }}
      </span>
    </div>
    
    <!-- Description -->
    <p class="text-gray-400 mb-6 text-sm leading-relaxed">
      {{ description }}
    </p>
    
    <!-- Stats -->
    <div class="flex items-center text-sm font-medium mb-5" :class="statsTextClass">
      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
      </svg>
      {{ stats }}
    </div>
    
    <!-- Tags -->
    <div class="flex flex-wrap gap-2 mb-6">
      <span 
        v-for="tag in tags" 
        :key="tag"
        class="px-3 py-1 rounded-full text-xs border"
        :class="tagClass"
      >
        {{ tag }}
      </span>
    </div>
    
    <!-- Action Button -->
    <a
      :href="href"
      target="_blank"
      rel="noopener noreferrer"
      class="inline-flex items-center w-full justify-center px-4 py-2.5 text-sm font-medium rounded-xl border transition-all duration-300"
      :class="buttonClass"
    >
      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
      </svg>
      View Project
    </a>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string
  subtitle: string
  category: string
  description: string
  stats: string
  tags: string[]
  href: string
  accent?: 'rose' | 'blue' | 'amber' | 'green' | 'red'
}

const props = withDefaults(defineProps<Props>(), {
  accent: 'red'
})

const accentGradientClass = computed(() => {
  const gradients: Record<string, string> = {
    rose: 'bg-gradient-to-r from-rose-500 to-pink-500',
    blue: 'bg-gradient-to-r from-blue-500 to-cyan-500',
    amber: 'bg-gradient-to-r from-amber-500 to-orange-500',
    green: 'bg-gradient-to-r from-green-500 to-emerald-500',
    red: 'bg-gradient-to-r from-red-500 to-rose-500'
  }
  return gradients[props.accent]
})

const iconBgClass = computed(() => {
  const bgs: Record<string, string> = {
    rose: 'bg-gradient-to-br from-rose-500/20 to-rose-500/10 border border-rose-500/30',
    blue: 'bg-gradient-to-br from-blue-500/20 to-blue-500/10 border border-blue-500/30',
    amber: 'bg-gradient-to-br from-amber-500/20 to-amber-500/10 border border-amber-500/30',
    green: 'bg-gradient-to-br from-green-500/20 to-green-500/10 border border-green-500/30',
    red: 'bg-gradient-to-br from-red-500/20 to-red-500/10 border border-red-500/30'
  }
  return bgs[props.accent]
})

const categoryBadgeClass = computed(() => {
  const badges: Record<string, string> = {
    rose: 'bg-rose-500/10 text-rose-400 border-rose-500/30',
    blue: 'bg-blue-500/10 text-blue-400 border-blue-500/30',
    amber: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
    green: 'bg-green-500/10 text-green-400 border-green-500/30',
    red: 'bg-red-500/10 text-red-400 border-red-500/30'
  }
  return badges[props.accent]
})

const statsTextClass = computed(() => {
  const colors: Record<string, string> = {
    rose: 'text-rose-400',
    blue: 'text-blue-400',
    amber: 'text-amber-400',
    green: 'text-green-400',
    red: 'text-red-400'
  }
  return colors[props.accent]
})

const tagClass = computed(() => {
  return 'bg-gray-800/50 text-gray-300 border-gray-700/50'
})

const buttonClass = computed(() => {
  const buttons: Record<string, string> = {
    rose: 'text-gray-300 border-gray-700/50 hover:bg-rose-500 hover:text-white hover:border-rose-500 group-hover:bg-rose-500 group-hover:text-white group-hover:border-rose-500',
    blue: 'text-gray-300 border-gray-700/50 hover:bg-blue-500 hover:text-white hover:border-blue-500 group-hover:bg-blue-500 group-hover:text-white group-hover:border-blue-500',
    amber: 'text-gray-300 border-gray-700/50 hover:bg-amber-500 hover:text-white hover:border-amber-500 group-hover:bg-amber-500 group-hover:text-white group-hover:border-amber-500',
    green: 'text-gray-300 border-gray-700/50 hover:bg-green-500 hover:text-white hover:border-green-500 group-hover:bg-green-500 group-hover:text-white group-hover:border-green-500',
    red: 'text-gray-300 border-gray-700/50 hover:bg-red-500 hover:text-white hover:border-red-500 group-hover:bg-red-500 group-hover:text-white group-hover:border-red-500'
  }
  return buttons[props.accent]
})
</script>
