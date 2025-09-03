<template>
  <NuxtLayout name="website">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <!-- Header -->
      <div class="text-center mb-16">
        <h1 class="text-5xl font-bold text-gray-900 dark:text-white mb-6">
          Case Studies
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 max-w-4xl mx-auto">
          Real-world applications built with Laravel Restify and the BinarCode ecosystem.
          See how leading companies leverage our tools to build scalable, robust
          solutions.
        </p>
      </div>

      <!-- Case Studies Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-20">
        <!-- GiveLegacy -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-8 border-t-4 border-rose-400 overflow-hidden group">
          <div class="flex items-start justify-between mb-6">
            <div class="flex items-center">
              <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center mr-4">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
              </div>
              <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">GiveLegacy</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Healthcare Legacy Platform</p>
              </div>
            </div>
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-medium px-2.5 py-0.5 rounded">Healthcare</span>
          </div>
          
          <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm leading-relaxed">
            A comprehensive healthcare platform that helps families preserve and share medical histories, creating digital legacies for future generations.
          </p>
          
          <div class="flex items-center text-red-500 text-sm font-medium mb-4">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Trusted by families worldwide
          </div>
          
          <div class="flex flex-wrap gap-2 mb-4">
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-xs border">Laravel</span>
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-xs border">Vue.js</span>
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-xs border">Healthcare APIs</span>
          </div>
          
          <a
            href="https://www.givelegacy.com/"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center w-full justify-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-transparent border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-red-500 hover:text-white hover:border-red-500 transition-colors group-hover:bg-red-500 group-hover:text-white group-hover:border-red-500"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            View Project
          </a>
        </div>

        <!-- Growee.ai -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-8 border-t-4 border-blue-400 overflow-hidden group">
          <div class="flex items-start justify-between mb-6">
            <div class="flex items-center">
              <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center mr-4">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Growee.ai</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">AI-Powered HR Management</p>
              </div>
            </div>
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-medium px-2.5 py-0.5 rounded">Human Resources</span>
          </div>
          
          <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm leading-relaxed">
            Advanced HR tool leveraging artificial intelligence to streamline recruitment, employee management, and organizational growth for modern businesses.
          </p>
          
          <div class="flex items-center text-red-500 text-sm font-medium mb-4">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            100+ companies using the platform
          </div>
          
          <div class="flex flex-wrap gap-2 mb-4">
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-xs border">Laravel Restify</span>
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-xs border">AI Integration</span>
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-xs border">React</span>
          </div>
          
          <a
            href="https://www.growee.ai/"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center w-full justify-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-transparent border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-red-500 hover:text-white hover:border-red-500 transition-colors group-hover:bg-red-500 group-hover:text-white group-hover:border-red-500"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            View Project
          </a>
        </div>

        <!-- Construction Partner -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-8 border-t-4 border-amber-400 overflow-hidden group">
          <div class="flex items-start justify-between mb-6">
            <div class="flex items-center">
              <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center mr-4">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3-3 3 3 3-3 3 3V5a2 2 0 00-2-2z" />
                </svg>
              </div>
              <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Construction Partner</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Enterprise Accounting Platform</p>
              </div>
            </div>
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-medium px-2.5 py-0.5 rounded">Construction & Finance</span>
          </div>
          
          <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm leading-relaxed">
            Comprehensive accounting and project management solution designed specifically for large construction companies, handling complex financial workflows and compliance.
          </p>
          
          <div class="flex items-center text-red-500 text-sm font-medium mb-4">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Large enterprise clients
          </div>
          
          <div class="flex flex-wrap gap-2 mb-4">
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-xs border">Laravel</span>
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-xs border">Vue.js</span>
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-xs border">Financial Systems</span>
          </div>
          
          <a
            href="https://www.binarcode.com/recent-work"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center w-full justify-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-transparent border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-red-500 hover:text-white hover:border-red-500 transition-colors group-hover:bg-red-500 group-hover:text-white group-hover:border-red-500"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            View Project
          </a>
        </div>

        <!-- JobMatix -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-8 border-t-4 border-green-400 overflow-hidden group">
          <div class="flex items-start justify-between mb-6">
            <div class="flex items-center">
              <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center mr-4">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0H8m8 0v2a2 2 0 01-2 2H10a2 2 0 01-2-2V6" />
                </svg>
              </div>
              <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">JobMatix</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Job Advertising Platform</p>
              </div>
            </div>
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-medium px-2.5 py-0.5 rounded">Recruitment Technology</span>
          </div>
          
          <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm leading-relaxed">
            Advanced job advertising and filtering platform that connects employers with candidates through intelligent matching algorithms and comprehensive job management tools.
          </p>
          
          <div class="flex items-center text-red-500 text-sm font-medium mb-4">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            100,000+ jobs filtered daily
          </div>
          
          <div class="flex flex-wrap gap-2 mb-4">
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-xs border">Laravel Restify</span>
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-xs border">Advanced Filtering</span>
            <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-xs border">API Integration</span>
          </div>
          
          <a
            href="https://www.jobmatix.com/nl/"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center w-full justify-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-transparent border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-red-500 hover:text-white hover:border-red-500 transition-colors group-hover:bg-red-500 group-hover:text-white group-hover:border-red-500"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            View Project
          </a>
        </div>
      </div>

      <!-- Call to Action -->
      <div class="bg-gradient-to-r from-red-500 to-blue-600 text-white rounded-2xl p-8 md:p-12 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">
          Ready to Build Your Next Success Story?
        </h2>
        <p class="text-lg opacity-90 mb-6 max-w-2xl mx-auto">
          Join the companies already using Laravel Restify to build powerful APIs and applications. 
          Start your project today and become our next case study.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <NuxtLink
            to="/docs"
            class="inline-flex items-center px-8 py-4 bg-white/20 border border-white/20 text-white hover:bg-white hover:text-gray-900 font-semibold rounded-lg transition-colors text-lg backdrop-blur-sm"
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
            class="inline-flex items-center px-8 py-4 bg-white/10 border border-white/20 text-white hover:bg-white/20 font-semibold rounded-lg transition-colors text-lg backdrop-blur-sm"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3-3 3 3 3-3 3 3V5a2 2 0 00-2-2z" />
            </svg>
            Recent Projects
          </a>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
useHead({
  title: 'Case Studies - Laravel Restify',
  meta: [
    {
      name: 'description',
      content: 'Real-world applications built with Laravel Restify and the BinarCode ecosystem. See how leading companies leverage our tools to build scalable, robust solutions.'
    }
  ]
})
</script>