<template>
  <div class="app-layout">
    <div class="app-background">
      <div class="app-gradient-radial"></div>
      <div class="app-grid-pattern"></div>
    </div>

    <div class="relative z-10">
      <WebsiteNavbar />
      
      <main>
        <slot />
      </main>
      
      <WebsiteFooter />
      
      <ToastContainer />
    </div>
  </div>
</template>

<script setup lang="ts">
</script>