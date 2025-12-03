<template>
  <a
    :href="href"
    target="_blank"
    rel="noopener noreferrer"
    class="group relative bg-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 overflow-hidden hover:border-gray-600/50 transition-all duration-300 block"
  >
    <!-- Hover gradient -->
    <div 
      class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
      :class="gradientClass"
    />
    
    <div class="relative">
      <div class="flex items-center mb-4">
        <div 
          class="w-12 h-12 rounded-xl flex items-center justify-center mr-4 border"
          :class="iconBgClass"
        >
          <slot name="icon" />
        </div>
        <div>
          <h3 class="text-lg font-semibold text-white">{{ title }}</h3>
          <p class="text-gray-400 text-sm">{{ subtitle }}</p>
        </div>
      </div>
      
      <p class="text-gray-400 mb-4">
        {{ description }}
      </p>
      
      <div class="font-medium flex items-center" :class="linkColorClass">
        {{ linkText }}
        <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
        </svg>
      </div>
    </div>
  </a>
</template>

<script setup lang="ts">
interface Props {
  title: string
  subtitle: string
  description: string
  href: string
  linkText: string
  accent?: 'gray' | 'blue' | 'red' | 'green' | 'purple' | 'amber'
}

const props = withDefaults(defineProps<Props>(), {
  accent: 'red'
})

const gradientClass = computed(() => {
  const gradients: Record<string, string> = {
    gray: 'bg-gradient-to-br from-gray-500/5 to-transparent',
    blue: 'bg-gradient-to-br from-blue-500/5 to-transparent',
    red: 'bg-gradient-to-br from-red-500/5 to-transparent',
    green: 'bg-gradient-to-br from-green-500/5 to-transparent',
    purple: 'bg-gradient-to-br from-purple-500/5 to-transparent',
    amber: 'bg-gradient-to-br from-amber-500/5 to-transparent'
  }
  return gradients[props.accent]
})

const iconBgClass = computed(() => {
  const bgs: Record<string, string> = {
    gray: 'bg-gradient-to-br from-gray-500/20 to-gray-500/10 border-gray-500/30',
    blue: 'bg-gradient-to-br from-blue-500/20 to-blue-500/10 border-blue-500/30',
    red: 'bg-gradient-to-br from-red-500/20 to-red-500/10 border-red-500/30',
    green: 'bg-gradient-to-br from-green-500/20 to-green-500/10 border-green-500/30',
    purple: 'bg-gradient-to-br from-purple-500/20 to-purple-500/10 border-purple-500/30',
    amber: 'bg-gradient-to-br from-amber-500/20 to-amber-500/10 border-amber-500/30'
  }
  return bgs[props.accent]
})

const linkColorClass = computed(() => {
  const colors: Record<string, string> = {
    gray: 'text-gray-400 group-hover:text-gray-300',
    blue: 'text-blue-400 group-hover:text-blue-300',
    red: 'text-red-400 group-hover:text-red-300',
    green: 'text-green-400 group-hover:text-green-300',
    purple: 'text-purple-400 group-hover:text-purple-300',
    amber: 'text-amber-400 group-hover:text-amber-300'
  }
  return colors[props.accent]
})
</script>
