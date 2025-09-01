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
      </div>

      <!-- Interactive Demo Section -->
      <div class="mb-16">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl overflow-hidden">
          <!-- Demo Header -->
          <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
            <h2 class="text-xl font-bold text-white">Interactive API Demo</h2>
            <p class="text-red-100 mt-1">Try real API calls with sample data</p>
          </div>

          <!-- Demo Content -->
          <div class="grid grid-cols-1 lg:grid-cols-2 h-96">
            <!-- Request Panel -->
            <div class="p-6 border-r border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Make a Request</h3>
              <div class="space-y-4">
                <!-- Method & URL -->
                <div class="flex space-x-2">
                  <select class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-mono text-sm">
                    <option>GET</option>
                    <option>POST</option>
                    <option>PATCH</option>
                    <option>DELETE</option>
                  </select>
                  <input 
                    type="text" 
                    value="/api/restify/posts"
                    class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-mono text-sm"
                  >
                </div>

                <!-- Request Body -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Request Body (JSON)</label>
                  <textarea 
                    rows="6"
                    class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-mono text-sm"
                    placeholder='{\n  "title": "My New Post",\n  "content": "This is sample content"\n}'
                  ></textarea>
                </div>

                <!-- Send Button -->
                <button class="w-full bg-red-500 hover:bg-red-600 text-white font-medium py-3 px-4 rounded-lg transition-colors">
                  Send Request
                </button>
              </div>
            </div>

            <!-- Response Panel -->
            <div class="p-6 bg-gray-50 dark:bg-gray-800">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Response</h3>
              <div class="bg-gray-900 rounded-lg p-4 h-64 overflow-y-auto">
                <pre class="text-green-400 text-sm"><code>{
  "data": [
    {
      "type": "posts",
      "id": "1",
      "attributes": {
        "title": "Getting Started with Laravel Restify",
        "content": "Laravel Restify makes it easy...",
        "created_at": "2024-01-15T10:30:00Z"
      }
    }
  ],
  "meta": {
    "per_page": 15,
    "total": 25
  }
}</code></pre>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Code Examples Section -->
      <div class="mb-16">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
          Try These Examples
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Basic CRUD Example -->
          <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
              <h3 class="text-lg font-bold text-white">Basic CRUD Operations</h3>
            </div>
            <div class="p-6">
              <div class="space-y-4 text-sm">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">GET /api/restify/posts</code>
                  <button class="text-blue-600 hover:text-blue-700 font-medium">Try it</button>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">POST /api/restify/posts</code>
                  <button class="text-blue-600 hover:text-blue-700 font-medium">Try it</button>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">PATCH /api/restify/posts/1</code>
                  <button class="text-blue-600 hover:text-blue-700 font-medium">Try it</button>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">DELETE /api/restify/posts/1</code>
                  <button class="text-blue-600 hover:text-blue-700 font-medium">Try it</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Search & Filter Example -->
          <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
              <h3 class="text-lg font-bold text-white">Search & Filtering</h3>
            </div>
            <div class="p-6">
              <div class="space-y-4 text-sm">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">GET /posts?search=laravel</code>
                  <button class="text-purple-600 hover:text-purple-700 font-medium">Try it</button>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">GET /posts?filter[status]=published</code>
                  <button class="text-purple-600 hover:text-purple-700 font-medium">Try it</button>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">GET /posts?sort=-created_at</code>
                  <button class="text-purple-600 hover:text-purple-700 font-medium">Try it</button>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">GET /posts?include=author,tags</code>
                  <button class="text-purple-600 hover:text-purple-700 font-medium">Try it</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Relationships Example -->
          <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
              <h3 class="text-lg font-bold text-white">Relationships</h3>
            </div>
            <div class="p-6">
              <div class="space-y-4 text-sm">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">GET /posts/1/comments</code>
                  <button class="text-green-600 hover:text-green-700 font-medium">Try it</button>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">POST /posts/1/comments</code>
                  <button class="text-green-600 hover:text-green-700 font-medium">Try it</button>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">GET /users/1/posts</code>
                  <button class="text-green-600 hover:text-green-700 font-medium">Try it</button>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">POST /posts/1/relationships/tags</code>
                  <button class="text-green-600 hover:text-green-700 font-medium">Try it</button>
                </div>
              </div>
            </div>
          </div>

          <!-- MCP Server Example -->
          <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
              <h3 class="text-lg font-bold text-white">MCP Server Integration</h3>
            </div>
            <div class="p-6">
              <div class="space-y-4 text-sm">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">GET /mcp/tools</code>
                  <button class="text-indigo-600 hover:text-indigo-700 font-medium">Try it</button>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">POST /mcp/tools/users-index</code>
                  <button class="text-indigo-600 hover:text-indigo-700 font-medium">Try it</button>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">GET /mcp/resources</code>
                  <button class="text-indigo-600 hover:text-indigo-700 font-medium">Try it</button>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <code class="text-gray-900 dark:text-white">POST /mcp/call</code>
                  <button class="text-indigo-600 hover:text-indigo-700 font-medium">Try it</button>
                </div>
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
            <!-- Posts -->
            <div class="text-center">
              <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Posts</h4>
              <p class="text-gray-600 dark:text-gray-400 text-sm mt-2">25 blog posts with authors, tags, and comments</p>
            </div>

            <!-- Users -->
            <div class="text-center">
              <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Users</h4>
              <p class="text-gray-600 dark:text-gray-400 text-sm mt-2">15 users with profiles and relationships</p>
            </div>

            <!-- Products -->
            <div class="text-center">
              <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Products</h4>
              <p class="text-gray-600 dark:text-gray-400 text-sm mt-2">30 products with categories and variants</p>
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
useHead({
  title: 'Playground - Laravel Restify',
  meta: [
    {
      name: 'description',
      content: 'Test and experiment with Laravel Restify features in our interactive playground. Try real API calls without setting up a local environment.'
    }
  ]
})
</script>