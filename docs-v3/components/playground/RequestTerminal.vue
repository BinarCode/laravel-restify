<template>
  <div class="bg-white dark:bg-gray-900 backdrop-blur-lg border border-gray-200 dark:border-gray-700/50 rounded-2xl overflow-hidden shadow-xl dark:shadow-2xl">
    <!-- Terminal Header -->
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-800/80">
      <div class="flex items-center space-x-3">
        <div class="flex space-x-2">
          <div class="w-3 h-3 rounded-full bg-red-500 animate-pulse"></div>
          <div class="w-3 h-3 rounded-full bg-yellow-500 animate-pulse" style="animation-delay: 200ms"></div>
          <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse" style="animation-delay: 400ms"></div>
        </div>
        <span class="text-gray-500 dark:text-gray-400 text-sm font-mono">{{ title }}</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-xs font-mono px-2 py-1 rounded bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30">
          {{ modelValue.method }}
        </span>
      </div>
    </div>

    <!-- Request Input Area -->
    <div class="p-4 bg-gray-50 dark:bg-gray-900/95 space-y-4">
      <!-- Method & URL Bar -->
      <div class="flex flex-col sm:flex-row gap-2 bg-white dark:bg-gray-800/50 rounded-xl p-2 border border-gray-200 dark:border-gray-700/50">
        <!-- Method selector and Send button row on mobile -->
        <div class="flex items-center gap-2 sm:contents">
          <select 
            :value="modelValue.method"
            @change="updateMethod($event)"
            class="pl-3 pr-8 py-2 bg-gray-100 dark:bg-gray-700/50 border-0 text-gray-900 dark:text-white rounded-lg font-mono text-sm font-bold cursor-pointer focus:ring-2 focus:ring-blue-500/50 outline-none appearance-none bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20stroke%3D%22%236b7280%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-width%3D%221.5%22%20d%3D%22m6%208%204%204%204-4%22%2F%3E%3C%2Fsvg%3E')] bg-[length:1.25rem_1.25rem] bg-[right_0.5rem_center] bg-no-repeat"
            :class="methodClass"
          >
            <option v-for="m in methods" :key="m" :value="m">{{ m }}</option>
          </select>
          
          <!-- Send button - visible on mobile in this row -->
          <button
            @click="$emit('send')"
            :disabled="loading"
            class="sm:hidden ml-auto px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 disabled:from-gray-400 disabled:to-gray-400 text-white font-semibold rounded-lg transition-all duration-200 flex items-center space-x-2 disabled:cursor-not-allowed"
          >
            <PlayIcon v-if="!loading" class="w-4 h-4" />
            <ArrowPathIcon v-else class="w-4 h-4 animate-spin" />
          </button>
        </div>
        
        <!-- URL input - full width on mobile -->
        <div class="flex-1 flex items-center bg-gray-100 dark:bg-gray-800/80 rounded-lg border border-gray-200 dark:border-gray-600/50 overflow-hidden">
          <span class="hidden sm:block px-3 py-2 text-gray-400 dark:text-gray-500 font-mono text-sm border-r border-gray-200 dark:border-gray-600/50 bg-gray-50 dark:bg-gray-800/50 whitespace-nowrap">
            {{ baseUrl }}
          </span>
          <span class="sm:hidden px-3 py-2 text-gray-400 dark:text-gray-500 font-mono text-sm">/</span>
          <input
            :value="modelValue.endpoint"
            @input="updateEndpoint($event)"
            type="text"
            class="flex-1 pl-0 sm:pl-3 pr-3 py-2 bg-transparent text-green-600 dark:text-green-400 font-mono text-sm outline-none placeholder-gray-400 dark:placeholder-gray-500 min-w-0"
            :placeholder="endpointPlaceholder"
          >
        </div>
        
        <!-- Send button - hidden on mobile, visible on desktop -->
        <button
          @click="$emit('send')"
          :disabled="loading"
          class="hidden sm:flex px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 disabled:from-gray-400 disabled:to-gray-400 dark:disabled:from-gray-600 dark:disabled:to-gray-600 text-white font-semibold rounded-lg transition-all duration-200 items-center space-x-2 disabled:cursor-not-allowed"
        >
          <PlayIcon v-if="!loading" class="w-4 h-4" />
          <ArrowPathIcon v-else class="w-4 h-4 animate-spin" />
          <span>{{ loading ? 'Sending...' : 'Send' }}</span>
        </button>
      </div>

      <!-- Request Body (for POST/PATCH) -->
      <div v-if="showBody" class="space-y-2">
        <div class="flex items-center justify-between">
          <label class="text-xs font-mono text-gray-500 dark:text-gray-400 uppercase tracking-wider">Request Body</label>
          <button 
            @click="formatJson"
            class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 font-mono"
          >
            Format JSON
          </button>
        </div>
        <div class="relative">
          <textarea
            :value="modelValue.body"
            @input="updateBody($event)"
            rows="8"
            class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 text-green-600 dark:text-green-400 rounded-xl font-mono text-sm resize-none focus:border-blue-500/50 focus:ring-1 focus:ring-blue-500/50 outline-none placeholder-gray-400 dark:placeholder-gray-500"
            :placeholder="bodyPlaceholder"
          ></textarea>
          <div class="absolute bottom-3 right-3 text-xs text-gray-400 dark:text-gray-500 font-mono">
            JSON
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { PlayIcon, ArrowPathIcon } from '@heroicons/vue/24/solid'

interface RequestData {
  method: string
  endpoint: string
  body: string
}

interface Props {
  title?: string
  modelValue: RequestData
  loading?: boolean
  baseUrl?: string
  methods?: string[]
  endpointPlaceholder?: string
  bodyPlaceholder?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: 'request',
  loading: false,
  baseUrl: 'https://api.laravel-restify.com/',
  methods: () => ['GET', 'POST', 'PATCH', 'DELETE'],
  endpointPlaceholder: 'api/restify/organizations',
  bodyPlaceholder: '{\n  "name": "value"\n}'
})

const emit = defineEmits<{
  'update:modelValue': [value: RequestData]
  'send': []
}>()

const showBody = computed(() => ['POST', 'PATCH', 'PUT'].includes(props.modelValue.method))

const methodClass = computed(() => {
  switch (props.modelValue.method) {
    case 'GET':
      return 'text-green-400'
    case 'POST':
      return 'text-blue-400'
    case 'PATCH':
    case 'PUT':
      return 'text-yellow-400'
    case 'DELETE':
      return 'text-red-400'
    default:
      return 'text-gray-400'
  }
})

function updateMethod(event: Event) {
  const target = event.target as HTMLSelectElement
  emit('update:modelValue', { ...props.modelValue, method: target.value })
}

function updateEndpoint(event: Event) {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', { ...props.modelValue, endpoint: target.value })
}

function updateBody(event: Event) {
  const target = event.target as HTMLTextAreaElement
  emit('update:modelValue', { ...props.modelValue, body: target.value })
}

function formatJson() {
  if (!props.modelValue.body) return
  try {
    const parsed = JSON.parse(props.modelValue.body)
    emit('update:modelValue', { ...props.modelValue, body: JSON.stringify(parsed, null, 2) })
  } catch {
    // Invalid JSON, do nothing
  }
}
</script>
