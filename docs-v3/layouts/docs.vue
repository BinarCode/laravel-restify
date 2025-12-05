<template>
  <div class="app-layout">
    <div class="app-background">
      <div class="app-gradient-radial"></div>
      <div class="app-grid-pattern"></div>
    </div>

    <div class="relative z-10">
      <WebsiteNavbar />
      
      <div class="flex">
        <DocsSidebar />
        
        <main class="flex-1 min-w-0">
          <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <article class="prose prose-gray dark:prose-invert prose-lg max-w-none">
              <slot />
            </article>
            
            <DocsPagination />
          </div>
        </main>
        
        <DocsTableOfContents />
      </div>
    
      <ToastContainer />
    </div>
  </div>
</template>

<script setup lang="ts">
const isMobileMenuOpen = ref(false)

function toggleMobileMenu(): void {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

provide('isMobileMenuOpen', isMobileMenuOpen)
provide('toggleMobileMenu', toggleMobileMenu)
</script>