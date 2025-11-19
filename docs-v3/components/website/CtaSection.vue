<template>
  <section class="py-24 bg-gradient-to-b from-gray-900 to-slate-950 relative overflow-hidden">
    <!-- Background Effects -->
    <div class="absolute inset-0">
      <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 left-20 w-64 h-64 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-full blur-2xl"></div>
    </div>

    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <!-- Section Header -->
      <div class="mb-16">
        <div class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/20 backdrop-blur-sm rounded-full text-sm font-medium text-blue-300 mb-8">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          Start Building Today
        </div>
        
        <h2 class="text-4xl md:text-6xl font-black tracking-tight mb-8">
          <span class="bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent">
            Ready to build
          </span>
          <br />
          <span class="bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-400 bg-clip-text text-transparent">
            amazing APIs?
          </span>
        </h2>
        
        <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-12 leading-relaxed">
          Install Laravel Restify today and transform your API development experience with elegant, powerful, and intuitive tools.
        </p>
      </div>

      <!-- Installation Options -->
      <div class="space-y-8 mb-12 max-w-2xl mx-auto">
        <!-- Composer Installation -->
        <div class="group relative bg-gradient-to-br from-gray-900/80 to-gray-800/80 backdrop-blur-lg border border-gray-700/50 rounded-3xl p-8 hover:scale-105 transition-all duration-500">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          
          <div class="relative z-10 text-center">
            <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
              <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
              </svg>
            </div>
            
            <h3 class="text-2xl font-bold text-white mb-4">
              Install via Composer
            </h3>
            
            <p class="text-gray-400 mb-6">
              Get started instantly with Composer package manager
            </p>
            
            <div class="relative">
              <div class="bg-black/50 backdrop-blur rounded-xl p-4 text-left font-mono text-sm text-gray-300 border border-gray-700/30">
                <span class="text-gray-500">$</span> composer require binaryk/laravel-restify
              </div>
              
              <button 
                @click="copyComposerCommand"
                class="absolute top-3 right-3 p-2 bg-gray-800/50 hover:bg-gray-700/50 border border-gray-600/50 rounded-lg transition-colors duration-200 group/btn"
                :class="{ 'bg-green-500/20 border-green-500/50': composerCopied }"
              >
                <svg v-if="!composerCopied" class="h-4 w-4 text-gray-400 group-hover/btn:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <svg v-else class="h-4 w-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- GitHub Clone -->
        <div class="group relative bg-gradient-to-br from-gray-900/80 to-gray-800/80 backdrop-blur-lg border border-gray-700/50 rounded-3xl p-8 hover:scale-105 transition-all duration-500">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          
          <div class="relative z-10 text-center">
            <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
              <svg class="h-8 w-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
              </svg>
            </div>
            
            <h3 class="text-2xl font-bold text-white mb-4">
              Clone from GitHub
            </h3>
            
            <p class="text-gray-400 mb-6">
              Explore the source code and contribute to development
            </p>
            
            <div class="relative">
              <div class="bg-black/50 backdrop-blur rounded-xl p-4 text-left font-mono text-sm text-gray-300 border border-gray-700/30 break-all">
                <span class="text-gray-500">$</span> git clone https://github.com/BinarCode/laravel-restify.git
              </div>
              
              <button 
                @click="copyGitCommand"
                class="absolute top-3 right-3 p-2 bg-gray-800/50 hover:bg-gray-700/50 border border-gray-600/50 rounded-lg transition-colors duration-200 group/btn"
                :class="{ 'bg-green-500/20 border-green-500/50': gitCopied }"
              >
                <svg v-if="!gitCopied" class="h-4 w-4 text-gray-400 group-hover/btn:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <svg v-else class="h-4 w-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="flex flex-col sm:flex-row gap-6 justify-center items-center">
        <NuxtLink
          to="/docs"
          class="group inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white font-semibold rounded-2xl transition-all duration-300 shadow-xl shadow-blue-500/25 hover:shadow-blue-500/40 hover:scale-105"
        >
          <svg class="mr-3 h-5 w-5 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
          Read Documentation
        </NuxtLink>

        <a
          href="https://github.com/BinarCode/laravel-restify"
          target="_blank"
          rel="noopener noreferrer"
          class="group inline-flex items-center px-8 py-4 bg-gradient-to-r from-gray-800/80 to-gray-700/80 backdrop-blur border border-gray-600/50 hover:border-gray-500/70 text-white font-semibold rounded-2xl transition-all duration-300 hover:scale-105"
        >
          <svg class="mr-3 h-5 w-5 group-hover:rotate-12 transition-transform duration-300" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
          </svg>
          View on GitHub
        </a>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'

const composerCopied = ref(false)
const gitCopied = ref(false)

const copyComposerCommand = async () => {
  try {
    await navigator.clipboard.writeText('composer require binaryk/laravel-restify')
    composerCopied.value = true
    setTimeout(() => {
      composerCopied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy composer command: ', err)
  }
}

const copyGitCommand = async () => {
  try {
    await navigator.clipboard.writeText('git clone https://github.com/BinarCode/laravel-restify.git')
    gitCopied.value = true
    setTimeout(() => {
      gitCopied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy git command: ', err)
  }
}
</script>