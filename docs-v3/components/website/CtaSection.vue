<template>
  <section class="py-24 bg-gradient-to-b from-gray-900 to-slate-950 relative overflow-hidden">
    <!-- Background Effects -->
    <div class="absolute inset-0">
      <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 left-20 w-64 h-64 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-full blur-2xl"></div>
    </div>

    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <!-- Section Header -->
      <div class="mb-12 md:mb-16">
        <div class="inline-flex items-center px-4 md:px-6 py-3 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/20 backdrop-blur-sm rounded-full text-sm font-medium text-blue-300 mb-6 md:mb-8">
          <BoltIcon class="w-4 h-4 mr-2" />
          Start Building Today
        </div>
        
        <h2 class="text-3xl md:text-4xl lg:text-6xl font-black tracking-tight mb-6 md:mb-8">
          <span class="bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent">
            Ready to build
          </span>
          <br />
          <span class="bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-400 bg-clip-text text-transparent">
            amazing APIs?
          </span>
        </h2>
        
        <p class="text-lg md:text-xl text-gray-300 max-w-3xl mx-auto mb-8 md:mb-12 leading-relaxed">
          Install Laravel Restify today and transform your API development experience with elegant, powerful, and intuitive tools.
        </p>
      </div>

      <!-- Installation Options -->
      <div class="space-y-6 md:space-y-8 mb-8 md:mb-12 max-w-2xl mx-auto">
        <!-- Composer Installation -->
        <div class="group relative bg-gradient-to-br from-gray-900/80 to-gray-800/80 backdrop-blur-lg border border-gray-700/50 rounded-3xl p-6 md:p-8 hover:scale-105 transition-all duration-500">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          
          <div class="relative z-10 text-center">
            <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
              <CloudArrowDownIcon class="h-8 w-8 text-white" />
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
                <ClipboardIcon v-if="!composerCopied" class="h-4 w-4 text-gray-400 group-hover/btn:text-gray-300" />
                <CheckIcon v-else class="h-4 w-4 text-green-400" />
              </button>
            </div>
          </div>
        </div>

        <!-- GitHub Clone -->
        <div class="group relative bg-gradient-to-br from-gray-900/80 to-gray-800/80 backdrop-blur-lg border border-gray-700/50 rounded-3xl p-6 md:p-8 hover:scale-105 transition-all duration-500">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          
          <div class="relative z-10 text-center">
            <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
              <CodeBracketIcon class="h-8 w-8 text-white" />
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
                <ClipboardIcon v-if="!gitCopied" class="h-4 w-4 text-gray-400 group-hover/btn:text-gray-300" />
                <CheckIcon v-else class="h-4 w-4 text-green-400" />
              </button>
            </div>
          </div>
        </div>
      </div>
      </div>

      <!-- Quick Links -->
      <div class="flex flex-col sm:flex-row gap-4 justify-center px-4 md:px-0">
        <HeroButton
          type="primary"
          href="https://restify.binarcode.com/docs"
          :icon="BoltIcon"
        >
          Start Building
        </HeroButton>

        <HeroButton
          href="https://github.com/binaryk/laravel-restify"
          type="secondary"
          :icon="CodeBracketIcon"
        >
          Explore Code
        </HeroButton>
      </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { 
  BoltIcon, 
  CloudArrowDownIcon, 
  CodeBracketIcon, 
  ClipboardIcon, 
  CheckIcon,
  BookOpenIcon
} from '@heroicons/vue/24/outline'
import HeroButton from './HeroButton.vue'

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