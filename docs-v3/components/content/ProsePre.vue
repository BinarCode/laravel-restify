<template>
  <div ref="containerRef" class="relative group">
    <pre ref="preRef" :class="$attrs.class"><slot /></pre>
    <button
      @click="copyCode"
      class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-all duration-200 p-2 bg-white/90 dark:bg-gray-800/90 hover:bg-white dark:hover:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 active:scale-95"
      :class="{ 
        'opacity-100': copied,
        'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700': copied
      }"
      :title="copied ? 'Copied to clipboard!' : 'Copy code to clipboard'"
    >
      <svg
        v-if="!copied"
        class="w-4 h-4"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
      </svg>
      <svg
        v-else
        class="w-4 h-4 text-green-600 dark:text-green-400"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
const copied = ref(false)
const containerRef = ref<HTMLElement>()
const preRef = ref<HTMLElement>()

const copyCode = async () => {
  console.log('🔍 Copy button clicked')
  
  try {
    // Use template refs instead of getCurrentInstance
    const preElement = preRef.value
    
    if (!preElement) {
      console.error('❌ Pre element ref not found')
      throw new Error('Pre element not found')
    }

    console.log('✅ Pre element found:', preElement)

    // Get code text
    const codeElement = preElement.querySelector('code')
    let codeText = ''
    
    if (codeElement) {
      codeText = codeElement.textContent || ''
      console.log('📝 Got text from code element')
    } else {
      codeText = preElement.textContent || ''
      console.log('📝 Got text from pre element')
    }

    codeText = codeText.trim()
    
    console.log('📏 Code text length:', codeText.length)
    console.log('📄 First 100 chars:', codeText.substring(0, 100))
    
    if (!codeText) {
      console.error('❌ No code content found')
      throw new Error('No code content found')
    }

    // Simple, reliable copy method for macOS
    const textarea = document.createElement('textarea')
    textarea.value = codeText
    textarea.style.position = 'fixed'
    textarea.style.left = '0'
    textarea.style.top = '0'
    textarea.style.width = '1px'
    textarea.style.height = '1px'
    textarea.style.padding = '0'
    textarea.style.border = 'none'
    textarea.style.outline = 'none'
    textarea.style.background = 'transparent'
    
    document.body.appendChild(textarea)
    textarea.focus()
    textarea.select()
    
    const success = document.execCommand('copy')
    document.body.removeChild(textarea)
    
    console.log('📋 Copy result:', success)
    
    if (success) {
      console.log('✅ Successfully copied!')
      copied.value = true
      const { success: showSuccess } = useToast()
      showSuccess('Code copied to clipboard!')
      
      setTimeout(() => {
        copied.value = false
      }, 2000)
    } else {
      throw new Error('Copy command failed')
    }
    
  } catch (err) {
    console.error('❌ Copy error:', err)
    
    const { error } = useToast()
    error('Failed to copy code')
    
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 1500)
  }
}
</script>

<style scoped>
/* Custom styles for the copy button */
button {
  backdrop-filter: blur(4px);
}
</style>