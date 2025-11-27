<template>
  <div class="min-h-screen bg-white dark:bg-gray-900 transition-colors">
    <!-- Header -->
    <TheHeader />
    
    <!-- Main container -->
    <div class="flex">
      <!-- Sidebar -->
      <TheSidebar />
      
      <!-- Main content -->
      <main class="flex-1 min-w-0">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <slot />
        </div>
      </main>
      
          <!-- Table of Contents (right sidebar) -->
    <TheTableOfContents />
  </div>
  
  <!-- Toast Notifications -->
    <ToastContainer />
  </div>
</template>

<script setup lang="ts">
// Mobile menu state management
const isMobileMenuOpen = ref(false)

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

// Provide mobile menu state to all child components
provide('isMobileMenuOpen', isMobileMenuOpen)
provide('toggleMobileMenu', toggleMobileMenu)
</script>