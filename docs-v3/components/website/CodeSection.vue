<template>
  <div class="space-y-4 md:space-y-6">
    <!-- Section Header -->
    <div class="flex items-center gap-2 mb-4">
      <div :class="`w-2 h-2 ${type === 'input' ? 'bg-blue-400' : 'bg-green-400'} rounded-full animate-pulse`"></div>
      <span :class="`${type === 'input' ? 'text-blue-400' : 'text-green-400'} text-sm font-semibold uppercase tracking-wider`">
        {{ title }}
      </span>
    </div>
    
    <!-- Input Code Blocks -->
    <div v-if="type === 'input'" class="space-y-4">
      <div v-for="(block, index) in codeBlocks" :key="index">
        <div class="text-gray-500 text-xs mb-2 font-mono">{{ block.comment }}</div>
        <pre class="text-emerald-400 font-mono text-xs md:text-sm leading-relaxed overflow-x-auto"><code v-html="block.code"></code></pre>
      </div>
    </div>

    <!-- Output Blocks -->
    <div v-if="type === 'output'" class="space-y-4">
      <div 
        v-for="(block, index) in outputBlocks" 
        :key="index"
        class="bg-gray-800/50 rounded-lg p-3 md:p-4 border border-gray-700/30"
      >
        <div class="flex items-center gap-2 mb-2">
          <div :class="`w-3 h-3 bg-${block.color}-500 rounded-full`"></div>
          <span :class="`text-${block.color}-400 text-xs font-bold`">{{ block.title }}</span>
        </div>
        <div class="space-y-1 text-xs font-mono text-gray-300 overflow-x-auto">
          <div v-for="(item, itemIndex) in block.items" :key="itemIndex">
            <span v-if="item.method" :class="`text-${item.color}-400`">{{ item.method }}</span>
            <span v-if="item.endpoint">{{ item.endpoint }}</span>
            <span v-if="item.text">{{ item.text }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface CodeBlock {
  comment: string
  code: string
}

interface OutputItem {
  method?: string
  endpoint?: string
  color?: string
  text?: string
}

interface OutputBlock {
  title: string
  color: string
  items: OutputItem[]
}

interface Props {
  type: 'input' | 'output'
  title: string
  codeBlocks?: CodeBlock[]
  outputBlocks?: OutputBlock[]
}

defineProps<Props>()
</script>