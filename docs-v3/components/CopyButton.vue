<template>
  <button
    @click="handleCopy"
    class="copy-btn"
    :class="{ copied: iscopied }"
    :title="iscopied ? 'Copied!' : 'Copy to clipboard'"
  >
    <svg v-if="!iscopied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
    </svg>
    <svg v-else class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg>
  </button>
</template>

<script setup lang="ts">
interface Props {
  text: string
}

const props = defineProps<Props>()
const iscopied = ref(false)

const handleCopy = async () => {
  console.log('🚀 CopyButton: Starting copy process')
  console.log('📝 Text to copy:', props.text.substring(0, 50) + '...')
  
  try {
    // Method 1: Try navigator.clipboard.writeText() 
    if (navigator.clipboard) {
      try {
        await navigator.clipboard.writeText(props.text)
        console.log('✅ Clipboard API worked')
        showSuccess()
        return
      } catch (e) {
        console.log('⚠️ Clipboard API failed, trying fallback')
      }
    }
    
    // Method 2: Legacy method using document.execCommand
    const textArea = document.createElement('textarea')
    textArea.value = props.text
    textArea.style.position = 'absolute'
    textArea.style.left = '-999999px'
    textArea.style.top = '-999999px'
    document.body.appendChild(textArea)
    textArea.focus()
    textArea.select()
    
    const successful = document.execCommand('copy')
    document.body.removeChild(textArea)
    
    if (successful) {
      console.log('✅ execCommand worked')
      showSuccess()
    } else {
      throw new Error('execCommand failed')
    }
    
  } catch (error) {
    console.error('❌ All copy methods failed:', error)
    const { error: showError } = useAppToast()
    showError('Copy failed - please manually select and copy the text')
  }
}

const showSuccess = () => {
  iscopied.value = true
  const { success } = useAppToast()
  success('Copied to clipboard!')
  
  setTimeout(() => {
    iscopied.value = false
  }, 2000)
}
</script>

<style scoped>
@reference "tailwindcss";

.copy-btn {
  @apply absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-all duration-200 p-2 bg-white/90 dark:bg-gray-800/90 hover:bg-white dark:hover:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 active:scale-95;
}

.copy-btn.copied {
  @apply opacity-100 bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700;
}
</style>