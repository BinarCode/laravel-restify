<template>
  <div 
    class="bg-gray-900 backdrop-blur-lg border border-gray-700/50 rounded-2xl overflow-hidden shadow-2xl flex flex-col h-full"
    :class="[
      tilted ? 'transform rotate-2 hover:rotate-0 transition-transform duration-500' : ''
    ]"
  >
    <!-- Terminal Header -->
    <div class="flex items-center justify-between p-4 md:p-6 border-b border-gray-700/50 bg-gray-800/80">
      <div class="flex items-center space-x-3">
        <div class="flex space-x-2">
          <div class="w-3 h-3 rounded-full animate-pulse bg-red-500"></div>
          <div class="w-3 h-3 rounded-full animate-pulse bg-yellow-500" style="animation-delay: 200ms"></div>
          <div class="w-3 h-3 rounded-full animate-pulse bg-green-500" style="animation-delay: 400ms"></div>
        </div>
        <span class="text-gray-400 text-sm font-mono hidden sm:block">{{ title }}</span>
      </div>
      <div v-if="command" class="flex items-center gap-2 md:gap-4 text-gray-400 text-xs md:text-sm font-mono">
        <span class="hidden md:inline-block px-4 py-2 bg-gray-800 border border-gray-600 rounded-lg text-green-400 font-bold text-sm">{{ command }}</span>
        <button 
          v-if="copyable"
          @click="handleCopy" 
          class="p-2 bg-gray-800/50 hover:bg-gray-700/50 border border-gray-600/50 rounded-lg transition-colors duration-200 cursor-pointer"
          :class="{ 'bg-green-500/20 border-green-500/50': copied }"
          title="Copy command"
        >
          <ClipboardIcon v-if="!copied" class="h-4 w-4 text-gray-400 group-hover:text-gray-300" />
          <CheckIcon v-else class="h-4 w-4 text-green-400" />
        </button>
      </div>
    </div>

    <!-- Terminal Content -->
    <div class="p-4 md:p-6 bg-gray-800 flex-1">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ClipboardIcon, CheckIcon } from '@heroicons/vue/24/outline'

interface Props {
  title?: string
  command?: string
  copyable?: boolean
  tilted?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  title: 'terminal',
  copyable: false,
  tilted: false
})

const copied = ref(false)

async function handleCopy() {
  if (!props.command) return
  
  try {
    await navigator.clipboard.writeText(props.command)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy:', err)
  }
}
</script>
