<template>
  <NuxtLayout name="website">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <!-- Header -->
      <div class="text-center mb-16">
        <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-4">
          Laravel Restify Playground
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
          Test and experiment with Laravel Restify features in our interactive playground.
          See how your API endpoints work without setting up a local environment.
        </p>

        <!-- GitHub Repository Link -->
        <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 max-w-2xl mx-auto">
          <div class="flex items-center justify-center space-x-2 text-blue-700 dark:text-blue-300">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium">Demo Repository:</span>
            <a
              href="https://github.com/BinarCode/restify-demo"
              target="_blank"
              rel="noopener noreferrer"
              class="font-mono text-sm hover:underline"
            >
              BinarCode/restify-demo
            </a>
          </div>
          <p class="text-sm text-blue-600 dark:text-blue-400 text-center mt-2">
            This playground uses the demo repository above. Feel free to explore the code or submit PRs!
          </p>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div class="mb-8">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-2">
          <div class="flex space-x-2">
            <button
              @click="switchToHttp"
              :class="activeTab === 'http' ? 'bg-red-500 text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
              class="flex-1 px-6 py-4 rounded-lg font-medium transition-all duration-200"
            >
              <div class="flex items-center justify-center space-x-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3" />
                </svg>
                <span class="text-lg">HTTP API</span>
              </div>
              <p class="text-sm opacity-80 mt-1">Traditional REST API endpoints</p>
            </button>
            <button
              @click="switchToMcp"
              :class="activeTab === 'mcp' ? 'bg-red-500 text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
              class="flex-1 px-6 py-4 rounded-lg font-medium transition-all duration-200"
            >
              <div class="flex items-center justify-center space-x-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <span class="text-lg">MCP Server</span>
              </div>
              <p class="text-sm opacity-80 mt-1">AI Agents & Model Context Protocol</p>
            </button>
          </div>
        </div>
      </div>

      <!-- Quick Examples -->
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Quick Examples</h2>
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-6">
          <!-- HTTP API Examples -->
          <div v-if="activeTab === 'http'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <button
              @click="loadExample('api/restify/organizations', 'GET')"
              class="p-4 text-left bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors border border-blue-200 dark:border-blue-800"
            >
              <div class="font-mono text-sm text-blue-600 dark:text-blue-400 font-medium">GET</div>
              <div class="text-sm text-gray-700 dark:text-gray-300 mt-1">Organizations</div>
            </button>

            <button
              @click="loadExample('api/restify/organizations?search=Roma', 'GET')"
              class="p-4 text-left bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg transition-colors border border-purple-200 dark:border-purple-800"
            >
              <div class="font-mono text-sm text-purple-600 dark:text-purple-400 font-medium">GET</div>
              <div class="text-sm text-gray-700 dark:text-gray-300 mt-1">Search Roma</div>
            </button>

            <button
              @click="loadExample('api/restify/organizations?related=contacts', 'GET')"
              class="p-4 text-left bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors border border-green-200 dark:border-green-800"
            >
              <div class="font-mono text-sm text-green-600 dark:text-green-400 font-medium">GET</div>
              <div class="text-sm text-gray-700 dark:text-gray-300 mt-1">With Contacts</div>
            </button>

            <button
              @click="loadExample('api/restify/contacts', 'GET')"
              class="p-4 text-left bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30 rounded-lg transition-colors border border-orange-200 dark:border-orange-800"
            >
              <div class="font-mono text-sm text-orange-600 dark:text-orange-400 font-medium">GET</div>
              <div class="text-sm text-gray-700 dark:text-gray-300 mt-1">Contacts</div>
            </button>
          </div>

          <!-- MCP Examples -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <button
              @click="loadMcpExample('mcp/restify', 'POST', JSON.stringify({jsonrpc: '2.0', method: 'initialize', params: {capabilities: {tools: []}}, id: 1}, null, 2))"
              class="p-4 text-left bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-lg transition-colors border border-indigo-200 dark:border-indigo-800"
            >
              <div class="font-mono text-sm text-indigo-600 dark:text-indigo-400 font-medium">POST</div>
              <div class="text-sm text-gray-700 dark:text-gray-300 mt-1">Initialize</div>
            </button>

            <button
              @click="loadMcpExample('mcp/restify', 'POST', JSON.stringify({method: 'tools/list', params: {}, jsonrpc: '2.0', id: 2}, null, 2))"
              class="p-4 text-left bg-cyan-50 dark:bg-cyan-900/20 hover:bg-cyan-100 dark:hover:bg-cyan-900/30 rounded-lg transition-colors border border-cyan-200 dark:border-cyan-800"
            >
              <div class="font-mono text-sm text-cyan-600 dark:text-cyan-400 font-medium">POST</div>
              <div class="text-sm text-gray-700 dark:text-gray-300 mt-1">List Tools</div>
            </button>

            <button
              @click="loadMcpExample('mcp/restify', 'POST', JSON.stringify({method: 'tools/call', params: {name: 'restify-list', arguments: {resource: 'organizations', search: 'Roma'}}, jsonrpc: '2.0', id: 3}, null, 2))"
              class="p-4 text-left bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 rounded-lg transition-colors border border-emerald-200 dark:border-emerald-800"
            >
              <div class="font-mono text-sm text-emerald-600 dark:text-emerald-400 font-medium">POST</div>
              <div class="text-sm text-gray-700 dark:text-gray-300 mt-1">Organization List (Roma)</div>
            </button>

            <button
              @click="loadMcpExample('mcp/restify', 'POST', JSON.stringify({method: 'tools/call', params: {name: 'organizations-index-tool', arguments: {search: 'BinarCode'}}, jsonrpc: '2.0', id: 2}, null, 2))"
              class="p-4 text-left bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 rounded-lg transition-colors border border-amber-200 dark:border-amber-800"
            >
              <div class="font-mono text-sm text-amber-600 dark:text-amber-400 font-medium">POST</div>
              <div class="text-sm text-gray-700 dark:text-gray-300 mt-1">Search BinarCode</div>
            </button>
          </div>
        </div>
      </div>

      <!-- API Playground -->
      <div class="mb-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Request Panel -->
          <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Request</h2>

            <!-- Method & URL -->
            <div class="mb-6">
              <div class="flex space-x-3">
                <select v-if="activeTab === 'http'" v-model="method" class="px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-mono text-sm font-medium">
                  <option value="GET">GET</option>
                  <option value="POST">POST</option>
                  <option value="PATCH">PATCH</option>
                  <option value="DELETE">DELETE</option>
                </select>
                <div v-else class="px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-mono text-sm font-medium">
                  POST
                </div>
                <input
                  v-if="activeTab === 'http'"
                  v-model="endpoint"
                  type="text"
                  class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm border-0 outline-none rounded-lg"
                  placeholder="api/restify/organizations"
                >
                <input
                  v-else
                  v-model="mcpEndpoint"
                  type="text"
                  class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm border-0 outline-none rounded-lg"
                  placeholder="mcp/restify"
                >
              </div>
            </div>

            <!-- Request Body -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Request Body (JSON)</label>
              <textarea
                v-model="requestBody"
                rows="12"
                class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-mono text-sm resize-none"
                :placeholder="placeholderText"
              ></textarea>
            </div>

            <!-- Send Button -->
            <button
              @click="sendRequest"
              :disabled="isLoading"
              class="w-full bg-red-500 hover:bg-red-600 disabled:bg-red-300 disabled:cursor-not-allowed text-white font-medium py-4 px-6 rounded-lg transition-colors text-lg"
            >
              {{ isLoading ? 'Sending...' : 'Send Request' }}
            </button>
          </div>

          <!-- Response Panel -->
          <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Response</h2>
            <div class="bg-gray-900 rounded-lg p-6 h-[800px] overflow-y-auto">
              <pre class="text-green-400 text-sm whitespace-pre-wrap" v-if="response"><code>{{ response }}</code></pre>
              <div class="text-gray-400 text-sm" v-else>
                <p class="mb-4">Click "Send Request" to see the API response here.</p>
                <p class="text-xs">Example response:</p>
                <pre class="text-gray-500 text-xs mt-2"><code v-if="activeTab === 'http'">{
  "status": 200,
  "data": {
    "data": [...],
    "meta": {...}
  }
}</code><code v-else>{
  "status": 200,
  "data": {
    "jsonrpc": "2.0",
    "result": {
      "tools": [...],
      "resources": [...]
    }
  }
}</code></pre>
              </div>
            </div>
          </div>
        </div>
      </div>


      <!-- Sample Data Section -->
      <div class="mb-16">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
          Sample Data
        </h2>

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg overflow-hidden">
          <div class="bg-gray-50 dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Available Resources</h3>
            <p class="text-gray-600 dark:text-gray-400 mt-1">The playground includes sample data for the following resources</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
            <!-- Organizations -->
            <div class="text-center">
              <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Organizations</h4>
              <p class="text-gray-600 dark:text-gray-400 text-sm mt-2">25 organizations with contacts and locations across cities like Roma</p>
            </div>

            <!-- Contacts -->
            <div class="text-center">
              <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Contacts</h4>
              <p class="text-gray-600 dark:text-gray-400 text-sm mt-2">50 contacts with organization relationships and profiles</p>
            </div>

            <!-- Users -->
            <div class="text-center">
              <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Users</h4>
              <p class="text-gray-600 dark:text-gray-400 text-sm mt-2">15 users with authentication and access management</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom CTA -->
      <div class="text-center">
        <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
            Ready to Build Your Own API?
          </h2>
          <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-2xl mx-auto">
            The playground gives you a taste of what's possible. Follow our quick start guide
            to build your own REST API with Laravel Restify in minutes.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <NuxtLink
              to="/docs/quickstart"
              class="inline-flex items-center px-6 py-3 bg-red-500 hover:bg-red-600 text-white font-medium rounded-lg transition-colors"
            >
              Get Started Now
            </NuxtLink>
            <NuxtLink
              to="/templates"
              class="inline-flex items-center px-6 py-3 bg-transparent border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-400 dark:hover:border-gray-500 font-medium rounded-lg transition-colors"
            >
              Browse Templates
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

useHead({
  title: 'Playground - Laravel Restify',
  meta: [
    {
      name: 'description',
      content: 'Test and experiment with Laravel Restify features in our interactive playground. Try real API calls without setting up a local environment.'
    }
  ]
})

const activeTab = ref('http')
const method = ref('GET')
const endpoint = ref('api/restify/organizations')
const mcpEndpoint = ref('mcp/restify')
const requestBody = ref('')
const response = ref('')
const isLoading = ref(false)

const placeholderText = computed(() => {
  return activeTab.value === 'http'
    ? `{
  "name": "New Organization",
  "city": "Roma",
  "description": "Sample organization"
}`
    : `{
  "method": "tools/list",
  "params": {

  },
  "jsonrpc": "2.0",
  "id": 2
}`
})

const loadExample = (exampleEndpoint: string, exampleMethod: string) => {
  endpoint.value = exampleEndpoint
  method.value = exampleMethod
  requestBody.value = ''
  response.value = ''
}

const loadMcpExample = (exampleEndpoint: string, exampleMethod: string, exampleBody: string) => {
  mcpEndpoint.value = exampleEndpoint
  method.value = exampleMethod
  requestBody.value = exampleBody
  response.value = ''
}

const switchToHttp = () => {
  activeTab.value = 'http'
  if (method.value !== 'GET' && method.value !== 'POST' && method.value !== 'PATCH' && method.value !== 'DELETE') {
    method.value = 'GET'
  }
}

const switchToMcp = () => {
  activeTab.value = 'mcp'
  method.value = 'POST'
}

const sendRequest = async () => {
  isLoading.value = true
  response.value = 'Loading...'

  try {
    const currentEndpoint = activeTab.value === 'http' ? endpoint.value : mcpEndpoint.value
    const url = `https://api.laravel-restify.com/${currentEndpoint}`
    const options: RequestInit = {
      method: method.value,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    }

    if (method.value !== 'GET' && requestBody.value.trim()) {
      try {
        JSON.parse(requestBody.value)
        options.body = requestBody.value
      } catch (e: any) {
        response.value = JSON.stringify({
          error: 'Invalid JSON in request body',
          details: e?.message || 'Unknown JSON parsing error'
        }, null, 2)
        isLoading.value = false
        return
      }
    }

    const res = await fetch(url, options)
    const responseText = await res.text()
    let data

    if (responseText.trim()) {
      try {
        data = JSON.parse(responseText)
      } catch (e: any) {
        data = responseText
      }
    } else {
      data = null
    }

    response.value = data ? (typeof data === 'string' ? data : JSON.stringify(data, null, 2)) : 'No content'
  } catch (error: any) {
    response.value = JSON.stringify({
      error: 'Request failed',
      details: error?.message || 'Unknown request error'
    }, null, 2)
  } finally {
    isLoading.value = false
  }
}
</script>
