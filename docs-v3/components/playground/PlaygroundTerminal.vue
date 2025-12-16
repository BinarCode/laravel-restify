<template>
  <div class="bg-white dark:bg-gray-900 backdrop-blur-lg border border-gray-200 dark:border-gray-700/50 rounded-2xl overflow-hidden shadow-xl dark:shadow-2xl flex flex-col max-h-full">
    <!-- Terminal Header -->
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-800/80">
      <div class="flex items-center space-x-3">
        <div class="flex space-x-2">
          <div class="w-3 h-3 rounded-full bg-red-500"></div>
          <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
          <div class="w-3 h-3 rounded-full bg-green-500"></div>
        </div>
        <span class="text-gray-500 dark:text-gray-400 text-sm font-mono">{{ title }}</span>
      </div>
      <div class="flex items-center gap-2">
        <span v-if="statusText" class="text-xs font-mono px-2 py-1 rounded" :class="statusClass">
          {{ statusText }}
        </span>
        <button 
          v-if="copyable && content"
          @click="handleCopy" 
          class="p-1.5 bg-gray-100 dark:bg-gray-800/50 hover:bg-gray-200 dark:hover:bg-gray-700/50 border border-gray-300 dark:border-gray-600/50 rounded-lg transition-colors duration-200 cursor-pointer"
          :class="{ 'bg-green-100 dark:bg-green-500/20 border-green-300 dark:border-green-500/50': copied }"
          title="Copy content"
        >
          <ClipboardIcon v-if="!copied" class="h-4 w-4 text-gray-500 dark:text-gray-400" />
          <CheckIcon v-else class="h-4 w-4 text-green-600 dark:text-green-400" />
        </button>
      </div>
    </div>

    <!-- Terminal Content -->
    <div 
      ref="terminalContent"
      class="flex-1 p-4 bg-gray-50 dark:bg-gray-900/95 font-mono text-sm overflow-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-700 scrollbar-track-gray-100 dark:scrollbar-track-gray-900"
      :class="{ 'min-h-[300px]': !autoHeight, 'max-h-[500px]': maxHeight }"
    >
      <slot>
        <div v-if="loading" class="flex items-center space-x-2 text-gray-500 dark:text-gray-400">
          <div class="flex space-x-1">
            <div class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
            <div class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
            <div class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
          </div>
          <span>Processing request...</span>
        </div>
        <pre v-else-if="content" class="whitespace-pre-wrap break-words" :class="contentClass"><code>{{ formattedContent }}</code></pre>
        <div v-else class="text-gray-400 dark:text-gray-500 italic">{{ placeholder }}</div>
      </slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ClipboardIcon, CheckIcon } from '@heroicons/vue/24/outline'

interface Props {
  title?: string
  content?: string
  loading?: boolean
  isConnected?: boolean
  statusText?: string
  statusType?: 'success' | 'error' | 'info' | 'warning'
  copyable?: boolean
  placeholder?: string
  autoHeight?: boolean
  maxHeight?: boolean
  contentType?: 'json' | 'text' | 'error'
}

const props = withDefaults(defineProps<Props>(), {
  title: 'terminal',
  loading: false,
  isConnected: true,
  copyable: true,
  placeholder: 'Output will appear here...',
  autoHeight: false,
  maxHeight: true,
  contentType: 'json'
})

const terminalContent = ref<HTMLElement | null>(null)
const copied = ref(false)

const formattedContent = computed(() => {
  if (!props.content) return ''
  if (props.contentType === 'json') {
    try {
      return JSON.stringify(JSON.parse(props.content), null, 2)
    } catch {
      return props.content
    }
  }
  return props.content
})

const contentClass = computed(() => {
  switch (props.contentType) {
    case 'error':
      return 'text-red-600 dark:text-red-400'
    case 'json':
      return 'text-green-600 dark:text-green-400'
    default:
      return 'text-gray-700 dark:text-gray-300'
  }
})

const statusClass = computed(() => {
  switch (props.statusType) {
    case 'success':
      return 'bg-green-100 dark:bg-green-500/20 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-500/30'
    case 'error':
      return 'bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/30'
    case 'warning':
      return 'bg-yellow-100 dark:bg-yellow-500/20 text-yellow-600 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-500/30'
    default:
      return 'bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30'
  }
})

async function handleCopy() {
  if (!props.content) return
  
  try {
    await navigator.clipboard.writeText(props.content)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy:', err)
  }
}

// Keep scroll at top when content changes
watch(() => props.content, () => {
  nextTick(() => {
    if (terminalContent.value) {
      terminalContent.value.scrollTop = 0
    }
  })
})
</script>
