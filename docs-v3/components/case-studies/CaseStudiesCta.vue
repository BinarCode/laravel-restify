<template>
  <div class="relative bg-gray-900/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-8 md:p-12 overflow-hidden">
    <!-- Background gradient effects -->
    <div class="absolute inset-0 bg-gradient-to-br from-red-500/5 via-transparent to-blue-500/5" />
    <div class="absolute top-0 left-1/4 w-64 h-64 bg-red-500/10 rounded-full blur-3xl" />
    <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl" />
    
    <div class="relative text-center">
      <h2 class="text-2xl md:text-3xl font-bold text-white mb-4">
        Ready to Build Your Next Success Story?
      </h2>
      <p class="text-lg text-gray-400 mb-8 max-w-2xl mx-auto">
        Join the companies already using Laravel Restify to build powerful APIs and applications. 
        Start your project today and become our next case study.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <NuxtLink
          to="/docs"
          class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-red-500 to-red-600 text-white hover:from-red-600 hover:to-red-700 font-semibold rounded-xl transition-all duration-300 text-lg shadow-lg shadow-red-500/20 hover:shadow-red-500/30"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
          Get Started with Restify
        </NuxtLink>
        <a
          href="https://www.binarcode.com/recent-work"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center justify-center px-8 py-4 bg-gray-800/50 border border-gray-700/50 text-gray-300 hover:bg-gray-700/50 hover:text-white font-semibold rounded-xl transition-all duration-300 text-lg backdrop-blur-sm"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3-3 3 3 3-3 3 3V5a2 2 0 00-2-2z" />
          </svg>
          Recent Projects
        </a>
      </div>
    </div>
  </div>
</template>
