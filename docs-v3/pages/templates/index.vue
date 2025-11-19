<template>
  <NuxtLayout name="website">
    <div class="min-h-screen bg-background">
      <main class="container mx-auto px-4 py-16">
        <!-- Header -->
        <div class="text-center mb-16">
          <h1 class="text-4xl md:text-6xl font-bold mb-6 text-gray-900 dark:text-white">
            Choose Your Package
          </h1>
          <p class="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-8">
            From free starter kits to premium enterprise solutions. Get production-ready Laravel Restify templates.
          </p>
          
          <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <a
              href="https://restifytemplates.com/"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md"
            >
              Browse All Templates
              <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M15 3h6v6"></path>
                <path d="M10 14 21 3"></path>
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
              </svg>
            </a>
            <a
              href="https://www.binarcode.com/estimate-project"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center px-6 py-3 bg-transparent border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-400 dark:hover:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md"
            >
              Get Your Custom Template
              <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M15 3h6v6"></path>
                <path d="M10 14 21 3"></path>
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
              </svg>
            </a>
          </div>
        </div>

        <!-- Template Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
          <!-- Starter Kit Template -->
          <div class="relative bg-white dark:bg-gray-800 rounded-lg border-2 border-green-500 shadow-lg hover:shadow-xl transition-all duration-300 flex flex-col h-full">
            <!-- Badge -->
            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 z-10">
              <span class="px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full">
                FREE
              </span>
            </div>
            
            <!-- Header -->
            <div class="text-center pb-4 pt-6 px-6">
              <div class="flex justify-center mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path>
                  <path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path>
                  <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"></path>
                  <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path>
                </svg>
              </div>
              <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Laravel Restify Starter Kit</h3>
              <div class="text-3xl font-bold text-red-600 mb-2">
                FREE
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Production-ready API starter with auth, teams & permissions - save weeks of development
              </p>
            </div>
            
            <!-- Features -->
            <div class="px-6 space-y-3 mb-6 flex-grow">
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Complete Auth System</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Advanced Teams with Member Management</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Email Invitations with Approval Flow</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Full User CRUD with Profile Management</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Role-Based Permissions (Spatie)</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Production-Ready API Architecture</span>
              </div>
            </div>
            
            <!-- Buttons -->
            <div class="px-6 pb-6">
              <a
                href="https://restifytemplates.com/download/free/bOcviUe7kYdm"
                target="_blank"
                rel="noopener noreferrer"
                class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md mb-2"
              >
                Start Building Free
              </a>
              <a
                href="https://restifytemplates.com/documentation/starter-kit"
                target="_blank"
                rel="noopener noreferrer"
                class="w-full inline-flex justify-center items-center px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium text-sm transition-colors"
              >
                📚 Documentation
              </a>
            </div>
          </div>

          <!-- Essential Template -->
          <div class="relative bg-white dark:bg-gray-800 rounded-lg border-2 border-blue-500 shadow-lg hover:shadow-xl transition-all duration-300 flex flex-col h-full">            
            <!-- Header -->
            <div class="text-center pb-4 pt-6 px-6">
              <div class="flex justify-center mb-4">
                <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                </svg>
              </div>
              <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Essential</h3>
              <div class="text-3xl font-bold text-red-600 mb-2">
                €19.99
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Great for solo devs or MVPs
              </p>
            </div>
            
            <!-- Features -->
            <div class="px-6 space-y-3 mb-6 flex-grow">
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Everything from Free + Advanced Features</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">CI/CD setup (GitHub Actions)</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">File uploads with S3/local storage</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Custom middlewares per repository</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Public repositories</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Versioned API endpoints (v1, v2)</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Advanced filtering & search</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Custom email templates</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Activity logs & audit trails</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Export data to CSV/Excel</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">6 months of security updates</span>
              </div>
            </div>
            
            <!-- Buttons -->
            <div class="px-6 pb-6">
              <a
                href="https://restifytemplates.com/checkout/essential"
                target="_blank"
                rel="noopener noreferrer"
                class="w-full inline-flex justify-center items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md mb-2"
              >
                Unlock Essential Features
              </a>
              <a
                href="https://restifytemplates.com/documentation/essential"
                target="_blank"
                rel="noopener noreferrer"
                class="w-full inline-flex justify-center items-center px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium text-sm transition-colors"
              >
                📚 Documentation
              </a>
            </div>
          </div>

          <!-- Pro Template -->
          <div class="relative bg-white dark:bg-gray-800 rounded-lg border-2 border-purple-500 shadow-lg hover:shadow-xl transition-all duration-300 flex flex-col h-full">            
            <!-- Header -->
            <div class="text-center pb-4 pt-6 px-6">
              <div class="flex justify-center mb-4">
                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
                  <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
                  <path d="M4 22h16"></path>
                  <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path>
                  <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path>
                  <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path>
                </svg>
              </div>
              <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Pro</h3>
              <div class="text-3xl font-bold text-red-600 mb-2">
                €49.00
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                For real projects with payments & scaling
              </p>
            </div>
            
            <!-- Features -->
            <div class="px-6 space-y-3 mb-6 flex-grow">
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Everything in Essential</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">OpenAI Integration</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">AI Solutions</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Stripe integration (Laravel Cashier)</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Multi-tenancy support</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Laravel Scout search (Algolia/Meilisearch)</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Webhook event broadcasting</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Basic Nova setup</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Environment-ready configs</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">1 year of security updates</span>
              </div>
            </div>
            
            <!-- Buttons -->
            <div class="px-6 pb-6">
              <a
                href="https://restifytemplates.com/checkout/pro"
                target="_blank"
                rel="noopener noreferrer"
                class="w-full inline-flex justify-center items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md mb-2"
              >
                Unlock Pro Features
              </a>
              <a
                href="https://restifytemplates.com/documentation/pro"
                target="_blank"
                rel="noopener noreferrer"
                class="w-full inline-flex justify-center items-center px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium text-sm transition-colors"
              >
                📚 Documentation
              </a>
            </div>
          </div>

          <!-- Premium Template -->
          <div class="relative bg-white dark:bg-gray-800 rounded-lg border-2 border-orange-500 shadow-lg hover:shadow-xl transition-all duration-300 flex flex-col h-full">
            <!-- Badge -->
            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 z-10">
              <span class="px-3 py-1 bg-orange-500 text-white text-xs font-bold rounded-full">
                COMING SOON
              </span>
            </div>
            
            <!-- Header -->
            <div class="text-center pb-4 pt-6 px-6">
              <div class="flex justify-center mb-4">
                <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path>
                  <path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path>
                  <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"></path>
                  <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path>
                </svg>
              </div>
              <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Premium</h3>
              <div class="text-3xl font-bold text-red-600 mb-2">
                €99.99
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                For teams who want to move fast
              </p>
            </div>
            
            <!-- Features -->
            <div class="px-6 space-y-3 mb-6 flex-grow">
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Everything in Pro</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Anthropic & Gemini AI integration</span>
              </div>
              <div class="flex items-start space-x-2 bg-orange-50 dark:bg-orange-900/20 p-3 rounded-lg border border-orange-200 dark:border-orange-800">
                <svg class="w-4 h-4 text-orange-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm font-semibold text-orange-900 dark:text-orange-100">🔥 MCP Server with your data</span>
              </div>
              <div class="flex items-start space-x-2 bg-orange-50 dark:bg-orange-900/20 p-3 rounded-lg border border-orange-200 dark:border-orange-800">
                <svg class="w-4 h-4 text-orange-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm font-semibold text-orange-900 dark:text-orange-100">🔥 MCP Client for a smart AI Chat</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Laravel Nova full admin prebuilt</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Laravel Telescope pre-configured</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Laravel Horizon for queue monitoring</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Fully customized Auth (register, login, forgot and 2FA</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Priority support (email or Discord)</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Access to private repo with bonus tools</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Early access to new kits/templates</span>
              </div>
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Lifetime security updates</span>
              </div>
            </div>
            
            <!-- Buttons -->
            <div class="px-6 pb-6">
              <div class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-100 text-gray-400 font-medium rounded-lg mb-2 cursor-not-allowed">
                Get Early Access Today 🚀
              </div>
              <a
                href="https://restifytemplates.com/documentation/premium"
                target="_blank"
                rel="noopener noreferrer"
                class="w-full inline-flex justify-center items-center px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium text-sm transition-colors"
              >
                📚 Documentation
              </a>
            </div>
          </div>
      </div>

        <!-- Bottom CTA -->
        <div class="text-center">
          <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-8 max-w-2xl mx-auto">
            <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Need Something Custom?</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
              Our team can build a custom Laravel Restify template tailored specifically to your project needs.
            </p>
            <a
              href="https://www.binarcode.com/estimate-project"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md"
            >
              Get Your Custom Template
              <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M15 3h6v6"></path>
                <path d="M10 14 21 3"></path>
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
              </svg>
            </a>
          </div>
        </div>
      </main>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
useHead({
  title: 'Templates - Laravel Restify',
  meta: [
    {
      name: 'description',
      content: 'Jump-start your API development with ready-to-use Laravel Restify templates. CRUD, authentication, MCP integration, and more.'
    }
  ]
})
</script>
