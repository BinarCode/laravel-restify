<template>
  <section class="py-20">
    <h2 class="text-4xl font-bold text-center text-gray-900 dark:text-white mb-20">
      How It Works
    </h2>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 items-center justify-center">
      <!-- Laravel Models -->
      <div class="text-center">
        <div class="w-24 h-24 bg-gradient-to-br from-red-500 to-orange-500 rounded-2xl flex items-center justify-center mb-8 mx-auto shadow-xl">
          <Database :size="48" class="text-white" />
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Laravel Models</h3>
        <p class="text-lg text-gray-600 dark:text-gray-400">Your existing Eloquent models</p>
      </div>
      <!-- Restify Processing -->
      <div class="text-center">
        <div class="w-24 h-24 bg-gradient-to-br from-primary-500 to-blue-600 rounded-2xl flex items-center justify-center mb-8 mx-auto shadow-xl">
          <Sparkles :size="40" class="text-white" />
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Restify Repositories</h3>
        <p class="text-lg text-gray-600 dark:text-gray-400">Control API generation</p>
      </div>
      <!-- Dual Output -->
      <div class="space-y-8">
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xl flex items-center">
          <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center mr-4">
            <Users :size="24" class="text-white" />
          </div>
          <div class="text-left">
            <h4 class="text-xl font-bold text-gray-900 dark:text-white">REST API</h4>
            <p class="text-base text-gray-600 dark:text-gray-400">For humans & apps</p>
          </div>
        </div>
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xl flex items-center">
          <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mr-4">
            <Bot :size="24" class="text-white" />
          </div>
          <div class="text-left">
            <h4 class="text-xl font-bold text-gray-900 dark:text-white">MCP Server</h4>
            <p class="text-base text-gray-600 dark:text-gray-400">For AI agents</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { Database, Sparkles, Users, Bot } from 'lucide-vue-next'
</script>
