<template>
  <div class="group relative bg-gray-900/30 backdrop-blur-sm border border-gray-700/50 rounded-2xl p-6 md:p-8 hover:border-gray-600/50 transition-all duration-300">
    <div class="relative z-10 text-center">
      <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500/20 to-cyan-500/20 border border-blue-500/30 flex items-center justify-center mx-auto mb-6 group-hover:border-blue-400/50 transition-colors duration-300">
        <slot />
      </div>
      
      <h3 class="text-xl font-bold text-white mb-3">
        {{ title }}
      </h3>
      
      <p class="text-gray-400 mb-6 text-sm">
        {{ description }}
      </p>
      
      <div class="relative">
        <div class="bg-black/50 backdrop-blur rounded-xl p-4 text-left font-mono text-sm text-gray-300 border border-gray-700/30 break-all">
          <span class="text-gray-500">$</span> {{ command }}
        </div>
        
        <button 
          @click="$emit('copy')"
          class="absolute top-3 right-3 p-2 bg-gray-800/50 hover:bg-gray-700/50 border border-gray-600/50 rounded-lg transition-colors duration-200 group/btn"
          :class="{ 'bg-green-500/20 border-green-500/50': copied }"
        >
          <ClipboardIcon v-if="!copied" class="h-4 w-4 text-gray-400 group-hover/btn:text-gray-300" />
          <CheckIcon v-else class="h-4 w-4 text-green-400" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ClipboardIcon, CheckIcon } from '@heroicons/vue/24/outline'

interface Props {
  title: string
  description: string
  command: string
  copied: boolean
}

defineProps<Props>()

defineEmits<{
  copy: []
}>()
</script>
