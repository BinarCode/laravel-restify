<template>
  <button
    @click="$emit('click')"
    class="group relative w-full p-3 text-left rounded-xl transition-all duration-300 border overflow-hidden"
    :class="[
      isActive 
        ? 'bg-blue-500/20 border-blue-500/50 shadow-lg shadow-blue-500/10' 
        : 'bg-gray-100/50 dark:bg-gray-800/30 border-gray-200 dark:border-gray-700/50 hover:bg-blue-500/10 hover:border-blue-500/30'
    ]"
  >
    <div class="relative min-w-0">
      <!-- Method Badge & Arrow -->
      <div class="flex items-center justify-between mb-1.5">
        <span 
          class="font-mono text-xs font-bold px-2 py-0.5 rounded flex-shrink-0"
          :class="methodClass"
        >
          {{ method }}
        </span>
        <ChevronRightIcon 
          class="w-4 h-4 flex-shrink-0 transition-transform duration-200"
          :class="[
            isActive ? 'text-blue-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-400',
            isActive ? 'translate-x-0' : 'group-hover:translate-x-1'
          ]"
        />
      </div>
      
      <!-- Title -->
      <h4 
        class="font-semibold text-sm mb-0.5 transition-colors truncate"
        :class="isActive ? 'text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white'"
      >
        {{ title }}
      </h4>
      
      <!-- Description -->
      <p class="text-xs text-gray-500 dark:text-gray-500 line-clamp-1">{{ description }}</p>
      
      <!-- Endpoint Preview -->
      <div class="mt-1.5 font-mono text-xs text-gray-400 dark:text-gray-600 truncate">
        {{ endpoint }}
      </div>
    </div>
  </button>
</template>

<script setup lang="ts">
import { ChevronRightIcon } from '@heroicons/vue/24/outline'

interface Props {
  method: string
  title: string
  description: string
  endpoint: string
  color?: string
  isActive?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  color: 'blue',
  isActive: false
})

defineEmits<{
  click: []
}>()

const methodClass = computed(() => {
  switch (props.method) {
    case 'GET':
      return 'bg-green-500/20 text-green-400'
    case 'POST':
      return 'bg-blue-500/20 text-blue-400'
    case 'PATCH':
    case 'PUT':
      return 'bg-yellow-500/20 text-yellow-400'
    case 'DELETE':
      return 'bg-red-500/20 text-red-400'
    default:
      return 'bg-gray-500/20 text-gray-400'
  }
})
</script>
