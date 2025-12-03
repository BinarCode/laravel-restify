<template>
  <div class="relative bg-gray-900/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-8 overflow-hidden">
    <!-- Background gradient effects -->
    <div class="absolute inset-0 bg-gradient-to-br from-red-500/5 via-transparent to-blue-500/5" />
    <div class="absolute top-0 left-1/4 w-64 h-64 bg-red-500/10 rounded-full blur-3xl" />
    <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl" />
    
    <div class="relative max-w-4xl mx-auto text-center">
      <h2 class="text-3xl font-bold text-white mb-4">
        Explore BinarCode Projects
      </h2>
      <p class="text-lg text-gray-400 mb-12">
        Laravel Restify is part of a comprehensive suite of open-source tools and premium solutions built by the
        BinarCode team to accelerate development workflows.
      </p>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="bg-gray-800/50 border border-gray-700/50 p-6 rounded-xl">
          <div class="w-14 h-14 bg-gradient-to-br from-red-500/20 to-red-500/10 border border-red-500/30 rounded-xl flex items-center justify-center mb-4 mx-auto">
            <svg class="h-7 w-7 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-white mb-2">Open Source Tools</h3>
          <p class="text-gray-400 text-sm">
            Laravel packages and developer utilities available for free
          </p>
        </div>
        
        <div class="bg-gray-800/50 border border-gray-700/50 p-6 rounded-xl">
          <div class="w-14 h-14 bg-gradient-to-br from-blue-500/20 to-blue-500/10 border border-blue-500/30 rounded-xl flex items-center justify-center mb-4 mx-auto">
            <svg class="h-7 w-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-white mb-2">Premium Solutions</h3>
          <p class="text-gray-400 text-sm">
            Enterprise-grade applications and custom development services
          </p>
        </div>
        
        <div class="bg-gray-800/50 border border-gray-700/50 p-6 rounded-xl">
          <div class="w-14 h-14 bg-gradient-to-br from-amber-500/20 to-amber-500/10 border border-amber-500/30 rounded-xl flex items-center justify-center mb-4 mx-auto">
            <svg class="h-7 w-7 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-white mb-2">Community Driven</h3>
          <p class="text-gray-400 text-sm">
            Built with feedback from developers worldwide
          </p>
        </div>
      </div>
      
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a
          href="https://www.binarcode.com/projects"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-red-500 to-red-600 text-white hover:from-red-600 hover:to-red-700 font-semibold rounded-xl transition-all duration-300 text-lg shadow-lg shadow-red-500/20 hover:shadow-red-500/30"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
          View All BinarCode Projects
        </a>
        <a
          href="https://github.com/BinarCode"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center justify-center px-8 py-4 bg-gray-800/50 border border-gray-700/50 text-gray-300 hover:bg-gray-700/50 hover:text-white font-semibold rounded-xl transition-all duration-300 text-lg backdrop-blur-sm"
        >
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
          </svg>
          Open Source Projects
        </a>
      </div>
    </div>
  </div>
</template>
