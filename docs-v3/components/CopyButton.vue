<template>
  <button
    @click="handleCopy"
    class="copy-btn"
    :class="{ copied: copied }"
    :title="copied ? 'Copied!' : 'Copy to clipboard'"
  >
    <CheckIcon v-if="copied" class="w-4 h-4 text-green-600" />
    <ClipboardIcon v-else class="w-4 h-4" />
  </button>
</template>

<script setup lang="ts">
import { ClipboardIcon, CheckIcon } from '@heroicons/vue/24/outline'

interface Props {
  text: string
}

const props = defineProps<Props>()
const { copied, copyToClipboard } = useClipboardCopy()

function handleCopy(): void {
  copyToClipboard(props.text)
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