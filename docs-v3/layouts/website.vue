<template>
  <div class="min-h-screen bg-white dark:bg-gray-900 transition-colors">
    <!-- Website Header/Navbar -->
    <WebsiteNavbar />
    
    <!-- Main content -->
    <main>
      <slot />
    </main>
    
    <!-- Website Footer -->
    <WebsiteFooter />
    
    <!-- Toast Notifications -->
    <ToastContainer />
  </div>
</template>

<script setup lang="ts">
// Website layout for marketing pages, templates, community, etc.
// No sidebar navigation needed here
</script>