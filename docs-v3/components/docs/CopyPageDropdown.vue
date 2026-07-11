<template>
  <div
    class="inline-flex items-stretch rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden"
  >
    <button
      type="button"
      class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
      :aria-label="copied ? 'Copied' : 'Copy page as Markdown'"
      @click="copyPage"
    >
      <UIcon
        :name="copied ? 'i-lucide-check' : 'i-lucide-copy'"
        class="w-4 h-4 shrink-0"
        :class="copied ? 'text-green-600 dark:text-green-400' : ''"
      />
      <span>{{ copied ? 'Copied' : 'Copy page' }}</span>
    </button>

    <UDropdownMenu
      :items="items"
      :content="{ align: 'end', side: 'bottom', sideOffset: 6 }"
      :ui="{ content: 'w-72' }"
    >
      <button
        type="button"
        class="inline-flex items-center px-2 border-l border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
        aria-label="More copy options"
      >
        <UIcon name="i-lucide-chevron-down" class="w-4 h-4" />
      </button>

      <template #item-label="{ item }">
        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ item.label }}</span>
      </template>
    </UDropdownMenu>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const route = useRoute()
const config = useRuntimeConfig()
const { copied, copyToClipboard } = useClipboardCopy()

const siteUrl = config.public.siteUrl as string

const mdPath = computed(() => `${route.path.replace(/\/$/, '')}.md`)
const absoluteMdUrl = computed(() => `${siteUrl}${mdPath.value}`)

const llmPrompt = computed(
  () => `Read from ${absoluteMdUrl.value} so I can ask questions about it.`
)

const chatGptUrl = computed(
  () => `https://chatgpt.com/?hints=search&q=${encodeURIComponent(llmPrompt.value)}`
)

const claudeUrl = computed(
  () => `https://claude.ai/new?q=${encodeURIComponent(llmPrompt.value)}`
)

async function fetchMarkdown(): Promise<string> {
  try {
    const response = await fetch(mdPath.value)

    if (response.ok) {
      const text = await response.text()

      if (text && !text.trimStart().startsWith('<')) {
        return text
      }
    }
  } catch {
    // Fall through to URL fallback (e.g. .md twin missing in dev)
  }

  return absoluteMdUrl.value
}

async function copyPage(): Promise<void> {
  const markdown = await fetchMarkdown()
  await copyToClipboard(markdown)
}

const items = computed(() => [
  [
    {
      label: 'Copy page',
      description: 'Copy page as Markdown for LLMs',
      icon: 'i-lucide-file-text',
      onSelect: copyPage
    },
    {
      label: 'View as Markdown',
      description: 'Open the raw Markdown in a new tab',
      icon: 'i-lucide-file-code',
      to: mdPath.value,
      target: '_blank'
    }
  ],
  [
    {
      label: 'Open in ChatGPT',
      description: 'Ask ChatGPT about this page',
      icon: 'i-lucide-bot',
      to: chatGptUrl.value,
      target: '_blank'
    },
    {
      label: 'Open in Claude',
      description: 'Ask Claude about this page',
      icon: 'i-lucide-sparkles',
      to: claudeUrl.value,
      target: '_blank'
    }
  ]
])
</script>
