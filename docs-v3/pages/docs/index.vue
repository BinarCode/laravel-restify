<template>
  <NuxtLayout name="docs">
    <div class="not-prose">
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
          Laravel Restify Documentation
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400">
          Learn how to build powerful REST APIs with zero configuration using Laravel Restify.
        </p>
      </div>

      <!-- Quick Start Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12 not-prose">
        <!-- Get Started -->
        <NuxtLink
          to="/docs/quickstart"
          class="group block p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-red-300 dark:hover:border-red-600 transition-colors hover:shadow-lg"
        >
          <div class="flex items-center mb-4">
            <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 rounded-lg flex items-center justify-center mr-4 group-hover:scale-105 transition-transform">
              <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Start</h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400">
            Get up and running with Laravel Restify in minutes. Install, configure, and build your first API.
          </p>
        </NuxtLink>

        <!-- API Reference -->
        <NuxtLink
          to="/docs/api"
          class="group block p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 transition-colors hover:shadow-lg"
        >
          <div class="flex items-center mb-4">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-4 group-hover:scale-105 transition-transform">
              <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">API Reference</h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400">
            Complete reference for repositories, fields, relationships, and advanced features.
          </p>
        </NuxtLink>

        <!-- MCP Integration -->
        <NuxtLink
          to="/docs/mcp"
          class="group block p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600 transition-colors hover:shadow-lg"
        >
          <div class="flex items-center mb-4">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mr-4 group-hover:scale-105 transition-transform">
              <span class="text-lg">🤖</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">MCP Integration</h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400">
            Enable AI agents to interact with your APIs using the Model Context Protocol.
          </p>
        </NuxtLink>

        <!-- Authentication -->
        <NuxtLink
          to="/docs/auth"
          class="group block p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-green-300 dark:hover:border-green-600 transition-colors hover:shadow-lg"
        >
          <div class="flex items-center mb-4">
            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center mr-4 group-hover:scale-105 transition-transform">
              <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Authentication & Authorization</h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400">
            Secure your APIs with Laravel Sanctum authentication and policy-based authorization.
          </p>
        </NuxtLink>
      </div>

      <!-- Popular Guides -->
      <div class="mb-12">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Popular Guides</h2>
        <div class="space-y-4 not-prose">
          <NuxtLink
            to="/docs/api/repositories"
            class="flex items-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900 dark:text-white">Creating Repositories</h3>
              <p class="text-gray-600 dark:text-gray-400 text-sm">Learn how to create and configure repositories for your models.</p>
            </div>
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </NuxtLink>

          <NuxtLink
            to="/docs/api/fields"
            class="flex items-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900 dark:text-white">Working with Fields</h3>
              <p class="text-gray-600 dark:text-gray-400 text-sm">Configure field types, validation, and transformations.</p>
            </div>
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </NuxtLink>

          <NuxtLink
            to="/docs/search/basic-filters"
            class="flex items-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900 dark:text-white">Search & Filtering</h3>
              <p class="text-gray-600 dark:text-gray-400 text-sm">Add powerful search and filtering capabilities to your APIs.</p>
            </div>
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </NuxtLink>
        </div>
      </div>

      <!-- What's New -->
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">What's New</h2>
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 not-prose">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                MCP Server Support Now Available
              </h3>
              <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                <p>
                  Laravel Restify now supports automatic MCP server generation! Enable AI agents to interact with your APIs seamlessly.
                  <NuxtLink to="/docs/mcp" class="font-medium underline hover:text-blue-600 dark:hover:text-blue-100">
                    Learn more →
                  </NuxtLink>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
useHead({
  title: 'Documentation - Laravel Restify',
  meta: [
    {
      name: 'description',
      content: 'Learn how to build powerful REST APIs with zero configuration using Laravel Restify. Complete guides and API reference.'
    }
  ]
})
</script>