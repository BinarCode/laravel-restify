<template>
  <NuxtLayout name="docs">
    <div class="not-prose">
      <div class="mb-10">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
          Laravel Restify Documentation
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400">
          Learn how to build powerful REST APIs with zero configuration using Laravel Restify.
        </p>
      </div>

      <!-- Quick Start Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-12 not-prose">
        <!-- Get Started -->
        <NuxtLink
          to="/docs/quickstart"
          class="group block p-6 glass-card glass-card-accent-red"
        >
          <div class="flex items-center mb-3">
            <ArrowRightCircleIcon class="h-6 w-6 text-gray-900 dark:text-white mr-4" />
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white transition-colors">Quick Start</h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
            Get up and running with Laravel Restify in minutes. Install, configure, and build your first API.
          </p>
        </NuxtLink>

        <!-- API Reference -->
        <NuxtLink
          to="/docs/api/repositories"
          class="group block p-6 glass-card glass-card-accent-blue"
        >
          <div class="flex items-center mb-3">
            <DocumentTextIcon class="h-6 w-6 text-gray-900 dark:text-white mr-4" />
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white transition-colors">API Reference</h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
            Complete reference for repositories, fields, relationships, and advanced features.
          </p>
        </NuxtLink>

        <!-- MCP Integration -->
        <NuxtLink
          to="/docs/mcp/mcp"
          class="group block p-6 glass-card glass-card-accent-purple"
        >
          <div class="flex items-center mb-3">
            <CpuChipIcon class="h-6 w-6 text-gray-900 dark:text-white mr-4" />
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white transition-colors">MCP Integration</h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
            Enable AI agents to interact with your APIs using the Model Context Protocol.
          </p>
        </NuxtLink>

        <!-- Authentication -->
        <NuxtLink
          to="/docs/auth/authentication"
          class="group block p-6 glass-card glass-card-accent-green"
        >
          <div class="flex items-center mb-3">
            <LockClosedIcon class="h-6 w-6 text-gray-900 dark:text-white mr-4" />
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white transition-colors">Authentication & Authorization</h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
            Secure your APIs with Laravel Sanctum authentication and policy-based authorization.
          </p>
        </NuxtLink>
      </div>

      <!-- Popular Guides -->
      <div class="mb-12">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Popular Guides</h2>
        <div class="space-y-3 not-prose">
          <NuxtLink
            to="/docs/api/repositories"
            class="group flex items-center p-4 glass-card glass-card-accent-blue"
          >
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900 dark:text-white transition-colors">Creating Repositories</h3>
              <p class="text-gray-500 dark:text-gray-500 text-sm mt-1">Learn how to create and configure repositories for your models.</p>
            </div>
            <svg class="arrow-indicator" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </NuxtLink>

          <NuxtLink
            to="/docs/api/fields"
            class="group flex items-center p-4 glass-card glass-card-accent-blue"
          >
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900 dark:text-white transition-colors">Working with Fields</h3>
              <p class="text-gray-500 dark:text-gray-500 text-sm mt-1">Configure field types, validation, and transformations.</p>
            </div>
            <svg class="arrow-indicator" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </NuxtLink>

          <NuxtLink
            to="/docs/search/basic-filters"
            class="group flex items-center p-4 glass-card glass-card-accent-blue"
          >
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900 dark:text-white transition-colors">Search & Filtering</h3>
              <p class="text-gray-500 dark:text-gray-500 text-sm mt-1">Add powerful search and filtering capabilities to your APIs.</p>
            </div>
            <svg class="arrow-indicator" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </NuxtLink>
        </div>
      </div>

      <!-- What's New -->
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">What's New</h2>
        <div class="glass-panel-blue not-prose">
          <div class="flex">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-500/20 dark:bg-blue-500/30 rounded-lg flex items-center justify-center">
                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <h3 class="text-base font-semibold text-blue-900 dark:text-blue-200">
                MCP Server Support Now Available
              </h3>
              <p class="mt-2 text-sm text-blue-800 dark:text-blue-300/80 leading-relaxed">
                Laravel Restify now supports automatic MCP server generation! Enable AI agents to interact with your APIs seamlessly.
                <NuxtLink to="/docs/mcp/mcp" class="inline-flex items-center font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 ml-1">
                  Learn more
                  <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </NuxtLink>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import {
  ArrowRightCircleIcon,
  DocumentTextIcon,
  CpuChipIcon,
  LockClosedIcon
} from '@heroicons/vue/24/outline'

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