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
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8">
        <!-- Starter Kit Template -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
          <div class="h-48 bg-gradient-to-br from-green-500 to-emerald-600 relative">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            <div class="absolute bottom-4 left-4 right-4">
              <h3 class="text-xl font-bold text-white mb-2">Laravel Restify Starter Kit</h3>
              <p class="text-green-100 text-sm">Production-ready API starter with auth</p>
            </div>
            <div class="absolute top-4 right-4">
              <span class="px-2 py-1 bg-green-500 text-white text-xs font-bold rounded-full">
                FREE
              </span>
            </div>
          </div>
          <div class="p-6">
            <div class="flex items-center mb-4">
              <span class="px-2 py-1 bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200 text-xs font-medium rounded-full">
                Beginner
              </span>
              <span class="ml-2 text-gray-500 dark:text-gray-400 text-sm">
                10 min setup
              </span>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
              Complete auth system, advanced teams with member management, role-based permissions, and production-ready API architecture.
            </p>
            <div class="flex items-center justify-between">
              <a
                href="https://restifytemplates.com/download/free/bOcviUe7kYdm"
                target="_blank"
                rel="noopener noreferrer"
                class="text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 font-medium"
              >
                Get Started Free →
              </a>
              <div class="flex space-x-2">
                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Essential Template -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
          <div class="h-48 bg-gradient-to-br from-blue-500 to-blue-600 relative">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            <div class="absolute bottom-4 left-4 right-4">
              <h3 class="text-xl font-bold text-white mb-2">Essential Package</h3>
              <p class="text-blue-100 text-sm">Great for solo devs or MVPs</p>
            </div>
            <div class="absolute top-4 right-4">
              <span class="px-2 py-1 bg-blue-600 text-white text-xs font-bold rounded-full">
                €19.99
              </span>
            </div>
          </div>
          <div class="p-6">
            <div class="flex items-center mb-4">
              <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200 text-xs font-medium rounded-full">
                Intermediate
              </span>
              <span class="ml-2 text-gray-500 dark:text-gray-400 text-sm">
                20 min setup
              </span>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
              Everything from Free + Advanced features, CI/CD setup, file uploads with S3, custom middlewares, and versioned API endpoints.
            </p>
            <div class="flex items-center justify-between">
              <a
                href="https://restifytemplates.com/checkout/essential"
                target="_blank"
                rel="noopener noreferrer"
                class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium"
              >
                Unlock Essential →
              </a>
              <div class="flex space-x-2">
                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Pro Template -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
          <div class="h-48 bg-gradient-to-br from-purple-500 to-violet-600 relative">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            <div class="absolute bottom-4 left-4 right-4">
              <h3 class="text-xl font-bold text-white mb-2">Pro Package</h3>
              <p class="text-purple-100 text-sm">For real projects with payments & scaling</p>
            </div>
            <div class="absolute top-4 right-4">
              <span class="px-2 py-1 bg-purple-600 text-white text-xs font-bold rounded-full">
                €49.00
              </span>
            </div>
          </div>
          <div class="p-6">
            <div class="flex items-center mb-4">
              <span class="px-2 py-1 bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200 text-xs font-medium rounded-full">
                Advanced
              </span>
              <span class="ml-2 text-gray-500 dark:text-gray-400 text-sm">
                35 min setup
              </span>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
              Everything in Essential + OpenAI Integration, AI Solutions, Stripe integration, multi-tenancy support, Laravel Scout search, and webhook event broadcasting.
            </p>
            <div class="flex items-center justify-between">
              <a
                href="https://restifytemplates.com/checkout/pro"
                target="_blank"
                rel="noopener noreferrer"
                class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-medium"
              >
                Unlock Pro →
              </a>
              <div class="flex space-x-2">
                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Premium Template -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
          <div class="h-48 bg-gradient-to-br from-orange-500 to-red-600 relative">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            <div class="absolute bottom-4 left-4 right-4">
              <h3 class="text-xl font-bold text-white mb-2">Premium Package</h3>
              <p class="text-orange-100 text-sm">For teams who want to move fast</p>
            </div>
            <div class="absolute top-4 right-4">
              <span class="px-2 py-1 bg-orange-600 text-white text-xs font-bold rounded-full">
                €99.99
              </span>
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
              Everything in Pro + Anthropic & Gemini AI integration, MCP Server with your data, MCP Client for smart AI Chat, Laravel Nova admin, and priority support.
            </p>
            <div class="flex items-center justify-between">
                <span class="text-gray-400 font-medium">Coming Soon</span>
              <div class="flex space-x-2">
                <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
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
              href="https://www.binarcode.com/estimate-project"
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
