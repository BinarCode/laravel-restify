<template>
  <div class="min-h-screen bg-white dark:bg-gray-900 transition-colors">
    <!-- Website Header/Navbar -->
    <WebsiteNavbar />
    
    <!-- Main container -->
    <div class="flex">
      <!-- Documentation Sidebar -->
      <DocsSidebar />
      
      <!-- Main content -->
      <main class="flex-1 min-w-0">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <article class="prose prose-gray dark:prose-invert prose-lg max-w-none">
            <slot />
          </article>
        </div>
      </main>
      
          <!-- Table of Contents (right sidebar) -->
    <DocsTableOfContents />
  </div>
  
  <!-- Toast Notifications -->
    <ToastContainer />
  </div>
</template>

<script setup lang="ts">
// Documentation layout with sidebar navigation
// Mobile menu state management
const isMobileMenuOpen = ref(false)

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

// Provide mobile menu state to all child components
provide('isMobileMenuOpen', isMobileMenuOpen)
provide('toggleMobileMenu', toggleMobileMenu)
</script>