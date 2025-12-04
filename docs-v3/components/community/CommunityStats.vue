<template>
  <div class="grid grid-cols-2 md:grid-cols-5 gap-6 md:gap-8">
    <div 
      v-for="stat in stats" 
      :key="stat.label"
      class="relative bg-white/80 dark:bg-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-200 dark:border-gray-700/50 p-6 text-center overflow-hidden group hover:border-gray-300 dark:hover:border-gray-600/50 transition-all duration-300"
    >
      <!-- Subtle gradient overlay -->
      <div 
        class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
        :class="stat.gradientClass"
      />
      
      <div class="relative">
        <div class="text-3xl font-bold mb-2" :class="stat.colorClass">{{ stat.value }}</div>
        <div class="text-gray-600 dark:text-gray-400 text-sm">{{ stat.label }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const stats = [
  { 
    value: '368K+', 
    label: 'Downloads', 
    colorClass: 'text-green-400',
    gradientClass: 'bg-gradient-to-br from-green-500/10 to-transparent'
  },
  { 
    value: '626', 
    label: 'GitHub Stars', 
    colorClass: 'text-red-400',
    gradientClass: 'bg-gradient-to-br from-red-500/10 to-transparent'
  },
  { 
    value: '57', 
    label: 'Forks', 
    colorClass: 'text-purple-400',
    gradientClass: 'bg-gradient-to-br from-purple-500/10 to-transparent'
  },
  { 
    value: '20+', 
    label: 'Contributors', 
    colorClass: 'text-blue-400',
    gradientClass: 'bg-gradient-to-br from-blue-500/10 to-transparent'
  },
  { 
    value: '100+', 
    label: 'Projects Built', 
    colorClass: 'text-amber-400',
    gradientClass: 'bg-gradient-to-br from-amber-500/10 to-transparent'
  }
]
</script>
