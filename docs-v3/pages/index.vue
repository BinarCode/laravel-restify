<template>
  <NuxtLayout>
    <!-- Main container with proper spacing -->
    <div class="min-h-screen bg-white dark:bg-gray-900">
      <!-- Open Source Laravel Package Badge -->
      <div class="text-center pt-12 pb-6">
        <div class="inline-flex items-center px-4 py-2 bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 rounded-full text-sm font-medium">
          <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
          Open Source Laravel Package
        </div>
      </div>

      <!-- Main Hero Section -->
      <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <!-- Main Title -->
          <h1 class="text-6xl sm:text-7xl lg:text-8xl font-bold text-gray-900 dark:text-white mb-6">
            Build APIs with 
            <span class="text-red-500">Zero</span>
            <br class="block">
            <span class="text-red-500">Configuration</span>
          </h1>

          <!-- Subtitle -->
          <p class="text-xl sm:text-2xl text-gray-600 dark:text-gray-300 mb-12 max-w-4xl mx-auto leading-relaxed">
            Transform your Laravel Eloquent models into JSON:API compliant endpoints<br>
            and MCP servers automatically. No boilerplate, just results.
          </p>

          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16">
            <NuxtLink
              to="/quickstart"
              class="inline-flex items-center px-8 py-4 text-lg font-medium rounded-lg text-white bg-red-500 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 shadow-lg"
            >
              <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Get Started
            </NuxtLink>

            <a
              href="https://github.com/BinarCode/laravel-restify"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center px-8 py-4 text-lg font-medium rounded-lg text-gray-900 dark:text-white bg-transparent border-2 border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 transition-all duration-200"
            >
              <svg class="mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
              </svg>
              View on GitHub
            </a>
          </div>
        </div>

        <!-- Code Example Section -->
        <section class="pb-20">
          <div class="bg-gray-900 dark:bg-gray-950 rounded-xl p-8 shadow-2xl max-w-5xl mx-auto">
            <!-- Terminal-like header -->
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-700">
              <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
              </div>
              <div class="text-gray-400 text-sm">composer require binaryk/laravel-restify</div>
            </div>
            
            <!-- Code content -->
            <div class="space-y-6">
              <!-- Step 1: Install -->
              <div class="text-gray-400 text-sm mb-2">// Create a repository</div>
              <pre class="text-green-400 text-sm overflow-x-auto"><code>class PostRepository extends Repository
{
    public static string $model = Post::class;
}</code></pre>
              
              <!-- Step 2: Register -->
              <div class="text-gray-400 text-sm mt-6 mb-2">// Register the repository</div>
              <pre class="text-green-400 text-sm overflow-x-auto"><code>Route::restify();</code></pre>
              
              <!-- Step 3: Ready -->
              <div class="text-gray-400 text-sm mt-6 mb-2">// Your API is ready!</div>
              <div class="space-y-1 text-sm">
                <div class="text-gray-400">// GET /api/restify/posts</div>
                <div class="text-gray-400">// POST /api/restify/posts</div>
                <div class="text-gray-400">// PATCH /api/restify/posts/{id}</div>
                <div class="text-gray-400">// DELETE /api/restify/posts/{id}</div>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
useHead({
  title: 'Laravel Restify - Build APIs with Zero Configuration',
  meta: [
    {
      name: 'description',
      content: 'Transform your Laravel Eloquent models into JSON:API compliant endpoints and MCP servers automatically. No boilerplate, just results.'
    }
  ]
})
</script>

<style scoped>
/* Custom styles if needed */
</style>