<template>
  <NuxtLayout name="website">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <!-- Header Section -->
      <div class="text-center mb-12">
        <!-- Badge -->
        <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/20 backdrop-blur-sm rounded-full font-medium text-blue-400 mb-6">
          <CommandLineIcon class="w-4 h-4 mr-2" />
          <span class="text-sm">Interactive API Playground</span>
        </div>

        <!-- Title -->
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight mb-6">
          <span class="text-gray-900 dark:text-white">Test </span>
          <span class="bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">Laravel Restify</span>
          <br />
          <span class="text-gray-900 dark:text-white">in Real-Time</span>
        </h1>

        <!-- Subtitle -->
        <p class="text-lg sm:text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-8">
          Experience the power of Laravel Restify with our live demo API. 
          Send real requests, see real responses.
        </p>

        <!-- Connection Status -->
        <ConnectionStatus :is-connected="isConnected" />
      </div>
      
      <!-- Mode Switcher -->
      <div class="flex justify-center mb-8">
        <AppToggle
          v-model="activeMode"
          :options="modeOptions"
        />
      </div>

      <!-- Terminal Workspace -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 overflow-hidden">
        
        <!-- Examples Sidebar -->
        <div class="lg:col-span-1 space-y-4">
          <div class="bg-white/50 dark:bg-gray-900/30 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-2xl p-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4 flex items-center">
              <BookmarkIcon class="w-4 h-4 mr-2" />
              Quick Examples
            </h3>
            
            <!-- HTTP Examples -->
            <div v-if="activeMode === 'http'" class="space-y-2 overflow-hidden">
              <ExampleCard
                v-for="example in httpExamples"
                :key="example.title"
                :method="example.method"
                :title="example.title"
                :description="example.description"
                :endpoint="example.endpoint"
                :color="example.color"
                :is-active="currentExample?.title === example.title"
                @click="loadExample(example)"
              />
            </div>
            
            <!-- MCP Examples -->
            <div v-else class="space-y-2">
              <ExampleCard
                v-for="example in mcpExamples"
                :key="example.title"
                :method="example.method"
                :title="example.title"
                :description="example.description"
                :endpoint="example.endpoint"
                :color="example.color"
                :is-active="currentExample?.title === example.title"
                @click="loadExample(example)"
              />
            </div>
          </div>

          <!-- Demo Repository Card -->
          <div class="bg-white/50 dark:bg-gray-900/30 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-2xl p-4">
            <div class="flex items-center space-x-3 mb-3">
              <div class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                </svg>
              </div>
              <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Demo Repository</h4>
                <p class="text-xs text-gray-500">BinarCode/restify-demo</p>
              </div>
            </div>
            <a
              href="https://github.com/BinarCode/restify-demo"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center text-xs text-blue-400 hover:text-blue-300 font-medium"
            >
              View Source Code
              <ArrowTopRightOnSquareIcon class="w-3 h-3 ml-1" />
            </a>
          </div>
        </div>

        <!-- Main Terminal Area -->
        <div class="lg:col-span-3 space-y-6">
          <!-- Request Terminal -->
          <RequestTerminal
            v-model="request"
            :title="activeMode === 'http' ? 'HTTP Request' : 'MCP Request'"
            :loading="isLoading"
            :methods="activeMode === 'http' ? ['GET', 'POST', 'PATCH', 'DELETE'] : ['POST']"
            :endpoint-placeholder="activeMode === 'http' ? 'api/restify/organizations' : 'mcp/restify'"
            :body-placeholder="activeMode === 'http' ? httpBodyPlaceholder : mcpBodyPlaceholder"
            @send="sendRequest"
          />

          <!-- Response Terminal -->
          <PlaygroundTerminal
            :title="responseTitle"
            :content="response"
            :loading="isLoading"
            :is-connected="isConnected"
            :status-text="responseStatus"
            :status-type="responseStatusType"
            :content-type="responseContentType"
            :max-height="true"
            placeholder="Send a request to see the response here..."
          />

          <!-- Response Stats -->
          <div v-if="lastRequestStats" class="flex items-center justify-center space-x-6 text-sm">
            <div class="flex items-center space-x-2 text-gray-400">
              <ClockIcon class="w-4 h-4" />
              <span>{{ lastRequestStats.duration }}ms</span>
            </div>
            <div class="flex items-center space-x-2 text-gray-400">
              <DocumentTextIcon class="w-4 h-4" />
              <span>{{ lastRequestStats.size }}</span>
            </div>
            <div class="flex items-center space-x-2" :class="lastRequestStats.statusClass">
              <CheckCircleIcon v-if="lastRequestStats.success" class="w-4 h-4" />
              <XCircleIcon v-else class="w-4 h-4" />
              <span>{{ lastRequestStats.statusCode }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Available Resources Section -->
      <div class="mt-16">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-12">
          Available Resources
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <ResourceCard
            v-for="resource in resources"
            :key="resource.name"
            :name="resource.name"
            :description="resource.description"
            :count="resource.count"
            :icon="resource.icon"
            :color="resource.color"
          />
        </div>
      </div>

      <!-- CTA Section -->
      <div class="mt-16">
        <div class="bg-white/50 dark:bg-gray-900/30 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-3xl p-8 md:p-12 text-center">
          <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4">
            Ready to Build Your Own API?
          </h2>
          <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-2xl mx-auto">
            This playground is just a taste of what's possible. Get started with Laravel Restify 
            and build production-ready APIs in minutes.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <AppButton
              variant="primary"
              href="/docs"
              :icon="RocketLaunchIcon"
              :is-nuxt-link="true"
              :external="false"
            >
              Start Building
            </AppButton>
            <AppButton
              variant="secondary"
              href="/templates"
              :icon="Square3Stack3DIcon"
              :is-nuxt-link="true"
              :external="false"
            >
              Browse Templates
            </AppButton>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import { 
  CommandLineIcon,
  GlobeAltIcon,
  CpuChipIcon,
  BookmarkIcon,
  ArrowTopRightOnSquareIcon,
  ClockIcon,
  DocumentTextIcon,
  CheckCircleIcon,
  XCircleIcon,
  RocketLaunchIcon,
  Square3Stack3DIcon
} from '@heroicons/vue/24/outline'

import ConnectionStatus from '~/components/playground/ConnectionStatus.vue'
import RequestTerminal from '~/components/playground/RequestTerminal.vue'
import PlaygroundTerminal from '~/components/playground/PlaygroundTerminal.vue'
import ExampleCard from '~/components/playground/ExampleCard.vue'
import ResourceCard from '~/components/playground/ResourceCard.vue'
import AppButton from '~/components/ui/AppButton.vue'
import AppToggle from '~/components/ui/AppToggle.vue'

useHead({
  title: 'Playground - Laravel Restify',
  meta: [
    {
      name: 'description',
      content: 'Test and experiment with Laravel Restify features in our interactive playground. Send real API requests and see responses in real-time.'
    }
  ]
})

// State
const activeMode = ref<'http' | 'mcp'>('http')
const isConnected = ref(true)
const isLoading = ref(false)
const response = ref('')
const responseStatus = ref('')
const responseStatusType = ref<'success' | 'error' | 'info'>('info')
const responseContentType = ref<'json' | 'text' | 'error'>('json')
const currentExample = ref<Example | null>(null)

// Mode toggle options
const modeOptions = [
  { value: 'http', label: 'REST API', icon: GlobeAltIcon },
  { value: 'mcp', label: 'MCP Server', icon: CpuChipIcon }
]

interface RequestData {
  method: string
  endpoint: string
  body: string
}

const request = ref<RequestData>({
  method: 'GET',
  endpoint: 'api/restify/organizations',
  body: ''
})

interface RequestStats {
  duration: number
  size: string
  statusCode: number
  success: boolean
  statusClass: string
}

const lastRequestStats = ref<RequestStats | null>(null)

// Placeholders
const httpBodyPlaceholder = `{
  "name": "New Organization",
  "city": "Roma"
}`

const mcpBodyPlaceholder = `{
  "method": "tools/list",
  "params": {},
  "jsonrpc": "2.0",
  "id": 1
}`

// Examples
interface Example {
  method: string
  title: string
  description: string
  endpoint: string
  body?: string
  color: string
}

const httpExamples: Example[] = [
  {
    method: 'GET',
    title: 'List Organizations',
    description: 'Fetch all organizations with pagination',
    endpoint: 'api/restify/organizations',
    color: 'green'
  },
  {
    method: 'GET',
    title: 'Search Organizations',
    description: 'Search for organizations in Roma',
    endpoint: 'api/restify/organizations?search=Roma',
    color: 'blue'
  },
  {
    method: 'GET',
    title: 'With Relations',
    description: 'Include related contacts',
    endpoint: 'api/restify/organizations?related=contacts',
    color: 'purple'
  },
  {
    method: 'GET',
    title: 'List Contacts',
    description: 'Fetch all contacts',
    endpoint: 'api/restify/contacts',
    color: 'cyan'
  },
  {
    method: 'GET',
    title: 'Filter & Sort',
    description: 'Advanced filtering example',
    endpoint: 'api/restify/organizations?sort=-id&perPage=5',
    color: 'orange'
  }
]

const mcpExamples: Example[] = [
  {
    method: 'POST',
    title: 'Initialize',
    description: 'Initialize MCP connection',
    endpoint: 'mcp/restify',
    body: JSON.stringify({
      jsonrpc: '2.0',
      method: 'initialize',
      params: { capabilities: { tools: [] } },
      id: 1
    }, null, 2),
    color: 'indigo'
  },
  {
    method: 'POST',
    title: 'List Tools',
    description: 'Get available MCP tools',
    endpoint: 'mcp/restify',
    body: JSON.stringify({
      method: 'tools/list',
      params: {},
      jsonrpc: '2.0',
      id: 2
    }, null, 2),
    color: 'cyan'
  },
  {
    method: 'POST',
    title: 'Search Organizations',
    description: 'Use restify-list tool',
    endpoint: 'mcp/restify',
    body: JSON.stringify({
      method: 'tools/call',
      params: {
        name: 'restify-list',
        arguments: { resource: 'organizations', search: 'Roma' }
      },
      jsonrpc: '2.0',
      id: 3
    }, null, 2),
    color: 'emerald'
  },
  {
    method: 'POST',
    title: 'Call Index Tool',
    description: 'Organizations index tool',
    endpoint: 'mcp/restify',
    body: JSON.stringify({
      method: 'tools/call',
      params: {
        name: 'organizations-index-tool',
        arguments: { search: 'BinarCode' }
      },
      jsonrpc: '2.0',
      id: 4
    }, null, 2),
    color: 'amber'
  }
]

// Resources
const resources = [
  {
    name: 'Organizations',
    description: '25 organizations with contacts and locations',
    count: '25',
    icon: 'building',
    color: 'blue'
  },
  {
    name: 'Contacts',
    description: '50 contacts with organization relationships',
    count: '50',
    icon: 'users',
    color: 'green'
  },
  {
    name: 'Users',
    description: '15 users with authentication data',
    count: '15',
    icon: 'user',
    color: 'purple'
  }
]

// Computed
const responseTitle = computed(() => {
  if (isLoading.value) return 'Processing...'
  if (!response.value) return 'Response'
  return `Response`
})

// Methods
function loadExample(example: Example) {
  currentExample.value = example
  request.value = {
    method: example.method,
    endpoint: example.endpoint,
    body: example.body || ''
  }
  response.value = ''
  lastRequestStats.value = null
}

async function sendRequest() {
  isLoading.value = true
  response.value = ''
  responseStatus.value = ''
  lastRequestStats.value = null
  
  const startTime = performance.now()

  try {
    const url = `https://api.laravel-restify.com/${request.value.endpoint}`
    const options: RequestInit = {
      method: request.value.method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    }

    if (['POST', 'PATCH', 'PUT'].includes(request.value.method) && request.value.body.trim()) {
      try {
        JSON.parse(request.value.body)
        options.body = request.value.body
      } catch (e: any) {
        response.value = JSON.stringify({
          error: 'Invalid JSON in request body',
          details: e?.message
        }, null, 2)
        responseStatus.value = 'Invalid JSON'
        responseStatusType.value = 'error'
        responseContentType.value = 'error'
        isLoading.value = false
        return
      }
    }

    const res = await fetch(url, options)
    const endTime = performance.now()
    const responseText = await res.text()
    
    let data
    if (responseText.trim()) {
      try {
        data = JSON.parse(responseText)
      } catch {
        data = responseText
      }
    }

    response.value = data ? (typeof data === 'string' ? data : JSON.stringify(data, null, 2)) : 'No content'
    responseStatus.value = `${res.status} ${res.statusText}`
    responseStatusType.value = res.ok ? 'success' : 'error'
    responseContentType.value = res.ok ? 'json' : 'error'
    
    // Calculate stats
    const duration = Math.round(endTime - startTime)
    const size = formatBytes(new Blob([responseText]).size)
    
    lastRequestStats.value = {
      duration,
      size,
      statusCode: res.status,
      success: res.ok,
      statusClass: res.ok ? 'text-green-400' : 'text-red-400'
    }

  } catch (error: any) {
    response.value = JSON.stringify({
      error: 'Request failed',
      details: error?.message
    }, null, 2)
    responseStatus.value = 'Error'
    responseStatusType.value = 'error'
    responseContentType.value = 'error'
    isConnected.value = false
  } finally {
    isLoading.value = false
  }
}

function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

// Watch mode changes
watch(activeMode, (mode) => {
  currentExample.value = null
  response.value = ''
  lastRequestStats.value = null
  
  if (mode === 'http') {
    request.value = {
      method: 'GET',
      endpoint: 'api/restify/organizations',
      body: ''
    }
  } else {
    request.value = {
      method: 'POST',
      endpoint: 'mcp/restify',
      body: mcpBodyPlaceholder
    }
  }
})
</script>
