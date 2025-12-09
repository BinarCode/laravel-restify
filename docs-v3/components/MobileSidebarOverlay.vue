<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 lg:hidden"
      @click="$emit('close')"
      @touchmove.prevent
    >
      <div class="absolute inset-0 bg-gray-600 opacity-75"></div>
      
      <nav 
        class="relative max-w-xs w-full bg-white dark:bg-gray-900 h-full shadow-xl flex flex-col"
        @click.stop
        @touchmove.stop
      >
        <div class="sticky top-0 z-10 flex items-center justify-between px-4 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0 bg-white dark:bg-gray-900">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h2>
          <button
            @click.stop="$emit('close')"
            class="p-2 rounded-md text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>
        
        <div class="flex-1 overflow-y-auto px-4 py-6" style="-webkit-overflow-scrolling: touch;">
          <slot />
        </div>
      </nav>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { XMarkIcon } from '@heroicons/vue/24/outline'

interface Props {
  isOpen: boolean
  title?: string
}

withDefaults(defineProps<Props>(), {
  title: 'Navigation'
})

defineEmits<{
  close: []
}>()
</script>
