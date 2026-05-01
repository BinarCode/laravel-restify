<template>
  <section class="min-h-screen relative overflow-hidden flex items-center">
    <!-- Background Effects -->
    <div class="absolute inset-0">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(120,119,198,0.15),transparent_50%)]"></div>
      <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.02)_1px,transparent_1px)] bg-[size:100px_100px]"></div>
    </div>

    <!-- Main Hero Content -->
    <div class="relative z-10 flex flex-col justify-center min-h-screen max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
      <!-- Floating Badge -->
      <div class="text-center">
        <div class="inline-flex items-center px-4 py-2 sm:px-6 sm:py-3 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/20 backdrop-blur-sm rounded-full font-medium text-blue-600 dark:text-blue-300 mb-4">
          <div class="hidden sm:flex items-center mr-3">
            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse mr-2"></div>
            <CodeBracketIcon class="w-4 h-4 mr-1 transition-transform duration-300 hover:rotate-12" />
          </div>
          <span class="text-xs sm:text-sm">Open Source • Laravel Package • MIT Licensed</span>
        </div>
      </div>

      <!-- Hero Title -->
      <div class="text-center mb-6 md:mb-8">
        <h1 class="text-4xl md:text-6xl lg:text-7xl font-black tracking-tight mb-6 md:mb-8">
          <span class="text-4xl sm:text-5xl md:text-5xl lg:text-5xl text-gray-800 dark:text-white leading-tight">
            Build APIs for
          </span>
          <br />
          <span class="text-5xl sm:text-6xl md:text-6xl lg:text-8xl font-extrabold bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-500 bg-clip-text text-transparent">
            Humans & AI
          </span>
          <br />
          <span class="relative">
            <span class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-light italic px-1 bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">
              Simultaneously 
            </span>
            <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-32 md:w-64 h-1 bg-gradient-to-r from-transparent via-blue-400 to-transparent animate-pulse"></div>
          </span>
        </h1>
        
        <!-- Hero Subtitle -->
        <div class="max-w-4xl mx-auto">
          <p class="text-base sm:text-lg md:text-xl lg:text-2xl text-gray-700 dark:text-gray-300 mb-4 leading-relaxed font-light px-2 md:px-0">
            Transform your <span class="text-orange-500 dark:text-orange-400 font-semibold">Laravel Eloquent models</span> into 
            <span class="text-green-600 dark:text-green-400 font-semibold">JSON:API endpoints</span> and 
            <span class="text-blue-600 dark:text-blue-400 font-semibold">MCP servers</span> automatically.
          </p>
          <p class="text-sm sm:text-base md:text-lg text-gray-600 dark:text-gray-400 italic px-2 md:px-0">
            Zero boilerplate. Pure results. Future-ready architecture.
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center mt-6 md:mt-8 px-2 md:px-0">
          <AppButton
            variant="primary"
            href="/docs"
            :icon="BoltIcon"
            :is-nuxt-link="true"
            :external="false"
          >
            Start Building
          </AppButton>

          <AppButton
            variant="secondary"
            href="https://github.com/BinarCode/laravel-restify"
            :icon="CodeBracketIcon"
          >
            Explore Code
          </AppButton>
        </div>
      </div>

      <!-- Interactive Code Demo -->
      <div class="mt-10 md:mt-16">
        <div class="max-w-6xl mx-auto">
          <div class="bg-gray-900 backdrop-blur-lg border border-gray-700/50 rounded-2xl overflow-hidden shadow-2xl">
            <!-- Terminal Header -->
            <div class="flex items-center justify-between p-4 md:p-6 border-b border-gray-700/50 bg-gray-800/80">
              <div class="flex items-center space-x-3">
                <div class="flex space-x-2">
                  <div class="w-3 h-3 rounded-full animate-pulse bg-red-500"></div>
                  <div class="w-3 h-3 rounded-full animate-pulse bg-yellow-500" style="animation-delay: 200ms"></div>
                  <div class="w-3 h-3 rounded-full animate-pulse bg-green-500" style="animation-delay: 400ms"></div>
                </div>
                <span class="text-gray-400 text-sm font-mono hidden sm:block">terminal</span>
              </div>
              <div class="flex items-center gap-2 md:gap-4 text-gray-400 text-xs md:text-sm font-mono">
                <span class="hidden md:inline-block px-4 py-2 bg-gray-800 border border-gray-600 rounded-lg text-green-400 font-bold text-base">composer require binaryk/laravel-restify</span>
                <span class="md:hidden px-2 py-1 bg-gray-800 border border-gray-600 rounded text-green-400 font-bold text-xs">install</span>
                <button 
                  @click="handleCopyCommand" 
                  class="p-2 bg-gray-800/50 hover:bg-gray-700/50 border border-gray-600/50 rounded-lg transition-colors duration-200 cursor-pointer"
                  :class="{ 'bg-green-500/20 border-green-500/50': copied }"
                  title="Copy command"
                >
                  <ClipboardIcon v-if="!copied" class="h-4 w-4 text-gray-400 group-hover:text-gray-300" />
                  <CheckIcon v-else class="h-4 w-4 text-green-400" />
                </button>
              </div>
            </div>

            <!-- Code Content -->
            <div class="p-4 md:p-8 bg-gray-800">
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
                <!-- Input Section -->
                <CodeSection
                  type="input"
                  title="Input"
                  :codeBlocks="inputCodeBlocks"
                />

                <!-- Output Section -->
                <CodeSection
                  type="output"
                  title="Output"
                  :outputBlocks="outputBlocks"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Section -->
      <div class="mt-10 md:mt-16">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-8 max-w-4xl mx-auto">
          <HeroStat
            v-for="stat in stats"
            :key="stat.label"
            :icon="stat.icon"
            :label="stat.label"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { 
  BoltIcon, 
  CodeBracketIcon, 
  ClipboardIcon, 
  CheckIcon 
} from '@heroicons/vue/24/outline'

import AppButton from '~/components/ui/AppButton.vue'
import HeroStat from './HeroStat.vue'
import CodeSection from './CodeSection.vue'

const COMPOSER_COMMAND = 'composer require binaryk/laravel-restify'

const { copied, copyToClipboard } = useClipboardCopy()

function handleCopyCommand(): void {
  copyToClipboard(COMPOSER_COMMAND)
}

const stats = [
  { icon: '⚡', label: 'Zero Config' },
  { icon: '🚀', label: 'JSON:API' },
  { icon: '🤖', label: 'AI Ready' },
  { icon: '💎', label: 'Laravel Native' }
]

const inputCodeBlocks = [
  {
    comment: '// 1. Create your repository',
    code: `<span class="text-purple-400">class</span> <span class="text-yellow-400">PostRepository</span> <span class="text-purple-400">extends</span> <span class="text-blue-400">Repository</span>
{
    <span class="text-purple-400">public static</span> <span class="text-blue-400">string</span> <span class="text-white">$model</span> = <span class="text-orange-400">Post</span>::<span class="text-purple-400">class</span>;
}`
  },
  {
    comment: '// 2. Enable MCP for AI agents',
    code: `<span class="text-blue-400">Mcp</span>::<span class="text-yellow-400">web</span>(<span class="text-green-400">'restify'</span>, <span class="text-orange-400">RestifyServer</span>::<span class="text-purple-400">class</span>);`
  }
]

const outputBlocks = [
  {
    title: 'REST API',
    color: 'red',
    items: [
      { method: 'GET', endpoint: '/api/restify/posts', color: 'green' },
      { method: 'POST', endpoint: '/api/restify/posts', color: 'blue' },
      { method: 'PATCH', endpoint: '/api/restify/posts/{id}', color: 'yellow' },
      { method: 'DELETE', endpoint: '/api/restify/posts/{id}', color: 'red' }
    ]
  },
  {
    title: 'MCP TOOLS',
    color: 'blue',
    items: [
      { text: '🤖 posts-index-tool' },
      { text: '🤖 posts-create-tool' },
      { text: '🤖 posts-update-tool' },
      { text: '🤖 posts-delete-tool' }
    ]
  }
]
</script>