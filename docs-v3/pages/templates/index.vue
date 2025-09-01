<template>
  <NuxtLayout name="website">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <!-- Header -->
      <div class="text-center mb-16">
        <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-4">
          Laravel Restify Templates
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
          Jump-start your API development with ready-to-use templates and examples. 
          Copy, customize, and ship faster.
        </p>
      </div>

      <!-- Template Categories -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Basic CRUD Template -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
          <div class="h-48 bg-gradient-to-br from-blue-500 to-blue-600 relative">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            <div class="absolute bottom-4 left-4 right-4">
              <h3 class="text-xl font-bold text-white mb-2">Basic CRUD API</h3>
              <p class="text-blue-100 text-sm">Complete CRUD operations with validation</p>
            </div>
          </div>
          <div class="p-6">
            <div class="flex items-center mb-4">
              <span class="px-2 py-1 bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200 text-xs font-medium rounded-full">
                Beginner
              </span>
              <span class="ml-2 text-gray-500 dark:text-gray-400 text-sm">
                5 min setup
              </span>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
              Get started with basic Create, Read, Update, Delete operations including field validation and relationships.
            </p>
            <div class="flex items-center justify-between">
              <NuxtLink 
                to="/templates/basic-crud" 
                class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium"
              >
                View Template →
              </NuxtLink>
              <div class="flex space-x-2">
                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Authentication Template -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
          <div class="h-48 bg-gradient-to-br from-green-500 to-emerald-600 relative">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            <div class="absolute bottom-4 left-4 right-4">
              <h3 class="text-xl font-bold text-white mb-2">Authentication & Auth</h3>
              <p class="text-green-100 text-sm">Sanctum auth with role-based access</p>
            </div>
          </div>
          <div class="p-6">
            <div class="flex items-center mb-4">
              <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200 text-xs font-medium rounded-full">
                Intermediate
              </span>
              <span class="ml-2 text-gray-500 dark:text-gray-400 text-sm">
                15 min setup
              </span>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
              Complete authentication system with Laravel Sanctum, user registration, login, and policy-based authorization.
            </p>
            <div class="flex items-center justify-between">
              <NuxtLink 
                to="/templates/authentication" 
                class="text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 font-medium"
              >
                View Template →
              </NuxtLink>
              <div class="flex space-x-2">
                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- MCP Integration Template -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
          <div class="h-48 bg-gradient-to-br from-purple-500 to-violet-600 relative">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            <div class="absolute bottom-4 left-4 right-4">
              <h3 class="text-xl font-bold text-white mb-2">MCP Integration</h3>
              <p class="text-purple-100 text-sm">AI-ready API with MCP server</p>
            </div>
          </div>
          <div class="p-6">
            <div class="flex items-center mb-4">
              <span class="px-2 py-1 bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200 text-xs font-medium rounded-full">
                Advanced
              </span>
              <span class="ml-2 text-gray-500 dark:text-gray-400 text-sm">
                25 min setup
              </span>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
              Enable AI agents to interact with your API using the Model Context Protocol with automatic tool generation.
            </p>
            <div class="flex items-center justify-between">
              <NuxtLink 
                to="/templates/mcp-integration" 
                class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-medium"
              >
                View Template →
              </NuxtLink>
              <div class="flex space-x-2">
                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- E-commerce Template -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
          <div class="h-48 bg-gradient-to-br from-orange-500 to-red-600 relative">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            <div class="absolute bottom-4 left-4 right-4">
              <h3 class="text-xl font-bold text-white mb-2">E-commerce API</h3>
              <p class="text-orange-100 text-sm">Products, orders & payment handling</p>
            </div>
          </div>
          <div class="p-6">
            <div class="flex items-center mb-4">
              <span class="px-2 py-1 bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200 text-xs font-medium rounded-full">
                Advanced
              </span>
              <span class="ml-2 text-gray-500 dark:text-gray-400 text-sm">
                45 min setup
              </span>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
              Complete e-commerce backend with products, categories, orders, payments, and inventory management.
            </p>
            <div class="flex items-center justify-between">
              <span class="text-gray-400 font-medium">Coming Soon</span>
              <div class="flex space-x-2">
                <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Blog API Template -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
          <div class="h-48 bg-gradient-to-br from-indigo-500 to-purple-600 relative">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            <div class="absolute bottom-4 left-4 right-4">
              <h3 class="text-xl font-bold text-white mb-2">Blog & CMS API</h3>
              <p class="text-indigo-100 text-sm">Posts, comments & content management</p>
            </div>
          </div>
          <div class="p-6">
            <div class="flex items-center mb-4">
              <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200 text-xs font-medium rounded-full">
                Intermediate
              </span>
              <span class="ml-2 text-gray-500 dark:text-gray-400 text-sm">
                30 min setup
              </span>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
              Content management system with posts, categories, tags, comments, and user roles for content creation.
            </p>
            <div class="flex items-center justify-between">
              <span class="text-gray-400 font-medium">Coming Soon</span>
              <div class="flex space-x-2">
                <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                <span class="w-2 h-2 bg-pink-500 rounded-full"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Multi-tenant Template -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
          <div class="h-48 bg-gradient-to-br from-teal-500 to-cyan-600 relative">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            <div class="absolute bottom-4 left-4 right-4">
              <h3 class="text-xl font-bold text-white mb-2">Multi-tenant SaaS</h3>
              <p class="text-teal-100 text-sm">Tenant isolation & subscription billing</p>
            </div>
          </div>
          <div class="p-6">
            <div class="flex items-center mb-4">
              <span class="px-2 py-1 bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200 text-xs font-medium rounded-full">
                Advanced
              </span>
              <span class="ml-2 text-gray-500 dark:text-gray-400 text-sm">
                60 min setup
              </span>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
              Multi-tenant architecture with tenant isolation, subscription management, and scalable data segregation.
            </p>
            <div class="flex items-center justify-between">
              <span class="text-gray-400 font-medium">Coming Soon</span>
              <div class="flex space-x-2">
                <span class="w-2 h-2 bg-teal-500 rounded-full"></span>
                <span class="w-2 h-2 bg-cyan-500 rounded-full"></span>
                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom CTA -->
      <div class="mt-16 text-center">
        <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
            Need a Custom Template?
          </h2>
          <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-2xl mx-auto">
            Have a specific use case or need help creating a custom template? 
            Join our community or reach out for support.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <NuxtLink
              to="/community"
              class="inline-flex items-center px-6 py-3 bg-red-500 hover:bg-red-600 text-white font-medium rounded-lg transition-colors"
            >
              Join Community
            </NuxtLink>
            <a
              href="https://github.com/BinarCode/laravel-restify/discussions"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center px-6 py-3 bg-transparent border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-400 dark:hover:border-gray-500 font-medium rounded-lg transition-colors"
            >
              Request Template
            </a>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
useHead({
  title: 'Templates - Laravel Restify',
  meta: [
    {
      name: 'description',
      content: 'Jump-start your API development with ready-to-use Laravel Restify templates. CRUD, authentication, MCP integration, and more.'
    }
  ]
})
</script>