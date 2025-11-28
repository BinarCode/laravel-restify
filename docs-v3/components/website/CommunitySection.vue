<template>
  <section class="py-24 bg-gradient-to-b from-slate-950 to-gray-900 relative overflow-hidden">
    <!-- Background Effects -->
    <div class="absolute inset-0">
      <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-r from-blue-500/5 to-cyan-500/5 rounded-full blur-3xl"></div>
      <div class="absolute top-20 right-20 w-64 h-64 bg-gradient-to-r from-blue-500/5 to-cyan-500/5 rounded-full blur-2xl"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Section Header -->
      <div class="text-center mb-16 md:mb-20 px-4 md:px-0">
        <div class="inline-flex items-center px-4 md:px-6 py-3 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/20 backdrop-blur-sm rounded-full text-sm font-medium text-blue-300 mb-6 md:mb-8">
          <UsersIcon class="w-4 h-4 mr-2" />
          Join the Revolution
        </div>
        
        <h2 class="text-3xl md:text-4xl lg:text-6xl font-black tracking-tight mb-6 md:mb-8">
          <span class="bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent">
            Built by Developers,
          </span>
          <br />
          <span class="bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-400 bg-clip-text text-transparent">
            for Developers
          </span>
        </h2>
        
        <p class="text-lg md:text-xl text-gray-300 max-w-3xl mx-auto leading-relaxed">
          Laravel Restify is open source and community-driven. Join thousands of developers who are building the future of API development.
        </p>
      </div>

      <!-- Community Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8 px-4 md:px-0">
        <!-- Open Source -->
        <div class="group relative bg-gradient-to-br from-gray-900/50 to-gray-800/50 backdrop-blur-lg border border-gray-700/50 rounded-3xl p-6 md:p-8 text-center hover:scale-105 transition-all duration-500 hover:border-blue-500/30 flex flex-col h-full">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          
          <div class="relative z-10 flex flex-col h-full">
            <div class="w-20 h-20 bg-blue-500 rounded-3xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
              <ShareIcon class="h-10 w-10 text-white" />
            </div>
            
            <h3 class="text-2xl font-bold text-white mb-4 group-hover:text-blue-400 transition-colors">
              Open Source
            </h3>
            
            <p class="text-gray-400 mb-8 leading-relaxed flex-1">
              MIT licensed, completely free, and transparently developed on GitHub with full source code access.
            </p>
            
            <a
              href="https://github.com/BinarCode/laravel-restify"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 border border-blue-500/30 rounded-xl text-sm font-medium text-blue-300 hover:bg-blue-500/30 hover:border-blue-500/50 transition-all duration-300 group/btn"
            >
              <svg class="mr-2 h-4 w-4 group-hover/btn:rotate-12 transition-transform duration-300" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
              </svg>
              Star on GitHub
            </a>
          </div>
        </div>

        <!-- Documentation -->
        <div class="group relative bg-gradient-to-br from-gray-900/50 to-gray-800/50 backdrop-blur-lg border border-gray-700/50 rounded-3xl p-8 text-center hover:scale-105 transition-all duration-500 hover:border-blue-500/30 flex flex-col h-full">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-purple-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          
          <div class="relative z-10 flex flex-col h-full">
            <div class="w-20 h-20 bg-blue-500 rounded-3xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
              <BookOpenIcon class="h-10 w-10 text-white" />
            </div>
            
            <h3 class="text-2xl font-bold text-white mb-4 group-hover:text-blue-400 transition-colors">
              Documentation
            </h3>
            
            <p class="text-gray-400 mb-8 leading-relaxed flex-1">
              Comprehensive guides, tutorials, and API reference with real-world examples and best practices.
            </p>
            
            <NuxtLink
              to="/docs"
              class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 border border-blue-500/30 rounded-xl text-sm font-medium text-blue-300 hover:bg-blue-500/30 hover:border-blue-500/50 transition-all duration-300 group/btn"
            >
              <CheckCircleIcon class="mr-2 h-4 w-4 group-hover/btn:rotate-12 transition-transform duration-300" />
              Read Docs
            </NuxtLink>
          </div>
        </div>

        <!-- Community Discussion -->
        <div class="group relative bg-gradient-to-br from-gray-900/50 to-gray-800/50 backdrop-blur-lg border border-gray-700/50 rounded-3xl p-8 text-center hover:scale-105 transition-all duration-500 hover:border-blue-500/30 flex flex-col h-full">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          
          <div class="relative z-10 flex flex-col h-full">
            <div class="w-20 h-20 bg-blue-500 rounded-3xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
              <ChatBubbleLeftRightIcon class="h-10 w-10 text-white" />
            </div>
            
            <h3 class="text-2xl font-bold text-white mb-4 group-hover:text-blue-400 transition-colors">
              Discussions
            </h3>
            
            <p class="text-gray-400 mb-8 leading-relaxed flex-1">
              Get help, share ideas, and connect with other developers in our active GitHub Discussions community.
            </p>
            
            <a
              href="https://github.com/BinarCode/laravel-restify/discussions"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 border border-blue-500/30 rounded-xl text-sm font-medium text-blue-300 hover:bg-blue-500/30 hover:border-blue-500/50 transition-all duration-300 group/btn"
            >
              <ChatBubbleLeftRightIcon class="mr-2 h-4 w-4 group-hover/btn:rotate-12 transition-transform duration-300" />
              Join Discussion
            </a>
          </div>
        </div>

        <!-- BinarCode -->
        <div class="group relative bg-gradient-to-br from-gray-900/50 to-gray-800/50 backdrop-blur-lg border border-gray-700/50 rounded-3xl p-8 text-center hover:scale-105 transition-all duration-500 hover:border-blue-500/30 flex flex-col h-full">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          
          <div class="relative z-10 flex flex-col h-full">
            <div class="w-20 h-20 bg-blue-500 rounded-3xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
              <span class="text-3xl font-black text-white">B</span>
            </div>
            
            <h3 class="text-2xl font-bold text-white mb-4 group-hover:text-blue-400 transition-colors">
              BinarCode
            </h3>
            
            <p class="text-gray-400 mb-8 leading-relaxed flex-1">
              Proudly maintained by BinarCode, a team passionate about Laravel and modern web development.
            </p>
            
            <a
              href="https://binarcode.com"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 border border-blue-500/30 rounded-xl text-sm font-medium text-blue-300 hover:bg-blue-500/30 hover:border-blue-500/50 transition-all duration-300 group/btn"
            >
              <ArrowTopRightOnSquareIcon class="mr-2 h-4 w-4 group-hover/btn:rotate-12 transition-transform duration-300" />
              Visit Website
            </a>
          </div>
        </div>
      </div>

      <!-- Stats Section -->
      <div class="mt-24 pt-16 border-t border-gray-700/30">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
          <div class="group">
            <div class="text-4xl md:text-5xl font-black bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent mb-2 group-hover:scale-110 transition-transform duration-300">
              1K+
            </div>
            <div class="text-gray-400 text-sm">GitHub Stars</div>
          </div>
          <div class="group">
            <div class="text-4xl md:text-5xl font-black bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent mb-2 group-hover:scale-110 transition-transform duration-300">
              50K+
            </div>
            <div class="text-gray-400 text-sm">Downloads</div>
          </div>
          <div class="group">
            <div class="text-4xl md:text-5xl font-black bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent mb-2 group-hover:scale-110 transition-transform duration-300">
              100+
            </div>
            <div class="text-gray-400 text-sm">Contributors</div>
          </div>
          <div class="group">
            <div class="text-4xl md:text-5xl font-black bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent mb-2 group-hover:scale-110 transition-transform duration-300">
              5+
            </div>
            <div class="text-gray-400 text-sm">Years Active</div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import {
  UsersIcon,
  ShareIcon,
  BookOpenIcon,
  ChatBubbleLeftRightIcon,
  CheckCircleIcon,
  ArrowTopRightOnSquareIcon
} from '@heroicons/vue/24/outline'
</script>