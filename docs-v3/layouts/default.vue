<template>
  <div class="app-layout">
    <div class="app-background">
      <div class="app-gradient-radial"></div>
      <div class="app-grid-pattern"></div>
    </div>

    <div class="relative z-10">
      <TheHeader />
      
      <div class="flex">
        <TheSidebar />
        
        <main class="flex-1 min-w-0">
          <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <slot />
          </div>
        </main>
        
        <TheTableOfContents />
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