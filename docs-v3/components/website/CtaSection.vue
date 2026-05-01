<template>
  <section class="py-24 relative overflow-hidden">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <div class="mb-12 md:mb-16">
        <div class="inline-flex items-center px-4 md:px-6 py-3 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/20 backdrop-blur-sm rounded-full text-sm font-medium text-blue-600 dark:text-blue-300 mb-6 md:mb-8">
          <BoltIcon class="w-4 h-4 mr-2" />
          Start Building Today
        </div>
        
        <h2 class="text-3xl md:text-4xl lg:text-6xl font-black tracking-tight mb-6 md:mb-8">
          <span class="bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-400 bg-clip-text text-transparent">
            Ready to build
          </span>
          <br />
          <span class="bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-400 bg-clip-text text-transparent">
            amazing APIs?
          </span>
        </h2>
        
        <p class="text-lg md:text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto mb-8 md:mb-12 leading-relaxed">
          Install Laravel Restify today and transform your API development experience with elegant, powerful, and intuitive tools.
        </p>
      </div>

      <div class="grid grid-cols-1 gap-6 md:gap-8 mb-8 md:mb-12 max-w-3xl mx-auto">
        <!-- Composer Install Terminal -->
        <TerminalWindow 
          title="composer" 
          :command="COMPOSER_COMMAND"
          :copyable="true"
        >
          <div class="text-left">
            <div class="flex items-center mb-3">
              <CloudArrowDownIcon class="h-5 w-5 text-blue-400 mr-2" />
              <span class="text-gray-300 dark:text-gray-300 font-medium text-sm">Install via Composer</span>
            </div>
            <p class="text-gray-500 text-xs mb-4">
              Get started instantly with Composer package manager
            </p>
            <div class="bg-black/30 rounded-lg p-3 font-mono text-sm">
              <span class="text-gray-500">$</span> <span class="text-green-400">{{ COMPOSER_COMMAND }}</span>
            </div>
          </div>
        </TerminalWindow>

        <!-- Git Clone Terminal -->
        <TerminalWindow 
          title="git" 
          :command="GIT_COMMAND"
          :copyable="true"
        >
          <div class="text-left">
            <div class="flex items-center mb-3">
              <CodeBracketIcon class="h-5 w-5 text-blue-400 mr-2" />
              <span class="text-gray-300 dark:text-gray-300 font-medium text-sm">Clone from GitHub</span>
            </div>
            <p class="text-gray-500 text-xs mb-4">
              Explore the source code and contribute
            </p>
            <div class="bg-black/30 rounded-lg p-3 font-mono text-sm">
              <span class="text-gray-500">$</span> <span class="text-green-400">{{ GIT_COMMAND }}</span>
            </div>
          </div>
        </TerminalWindow>
      </div>

      <div class="flex flex-col sm:flex-row gap-4 justify-center px-4 md:px-0">
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
  </section>
</template>

<script setup lang="ts">
import { 
  BoltIcon, 
  CloudArrowDownIcon, 
  CodeBracketIcon
} from '@heroicons/vue/24/outline'
import AppButton from '~/components/ui/AppButton.vue'
import TerminalWindow from './TerminalWindow.vue'

const COMPOSER_COMMAND = 'composer require binaryk/laravel-restify'
const GIT_COMMAND = 'git clone https://github.com/BinarCode/laravel-restify.git'
const GIT_URL = 'github.com/.../restify'
</script>
