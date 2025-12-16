<template>
  <div class="group relative bg-white/50 dark:bg-gray-900/30 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-2xl p-6 hover:border-gray-300 dark:hover:border-gray-600/50 transition-all duration-300 overflow-hidden">
    <div class="relative">
      <!-- Icon -->
      <div 
        class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4 transition-transform duration-300 group-hover:scale-110"
        :class="iconContainerClass"
      >
        <!-- Building Icon -->
        <svg v-if="icon === 'building'" class="h-7 w-7" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        <!-- Users Icon -->
        <svg v-else-if="icon === 'users'" class="h-7 w-7" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <!-- User Icon -->
        <svg v-else class="h-7 w-7" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
      </div>

      <!-- Content -->
      <div class="flex items-start justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ name }}</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">{{ description }}</p>
        </div>
        
        <!-- Count Badge -->
        <div 
          class="flex-shrink-0 ml-4 px-3 py-1 rounded-full font-mono text-sm font-bold"
          :class="badgeClass"
        >
          {{ count }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  name: string
  description: string
  count: string
  icon: 'building' | 'users' | 'user'
  color?: string
}

const props = withDefaults(defineProps<Props>(), {
  color: 'blue'
})

const iconContainerClass = computed(() => {
  switch (props.color) {
    case 'blue':
      return 'bg-blue-100 dark:bg-blue-500/20 border border-blue-200 dark:border-blue-500/30'
    case 'green':
      return 'bg-green-100 dark:bg-green-500/20 border border-green-200 dark:border-green-500/30'
    case 'purple':
      return 'bg-purple-100 dark:bg-purple-500/20 border border-purple-200 dark:border-purple-500/30'
    default:
      return 'bg-blue-100 dark:bg-blue-500/20 border border-blue-200 dark:border-blue-500/30'
  }
})

const iconColorClass = computed(() => {
  switch (props.color) {
    case 'blue':
      return 'text-blue-600 dark:text-blue-400'
    case 'green':
      return 'text-green-600 dark:text-green-400'
    case 'purple':
      return 'text-purple-600 dark:text-purple-400'
    default:
      return 'text-blue-600 dark:text-blue-400'
  }
})

const badgeClass = computed(() => {
  switch (props.color) {
    case 'blue':
      return 'bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30'
    case 'green':
      return 'bg-green-100 dark:bg-green-500/20 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-500/30'
    case 'purple':
      return 'bg-purple-100 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-500/30'
    default:
      return 'bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30'
  }
})
</script>
