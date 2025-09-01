<template>
  <aside class="hidden xl:flex xl:w-64 xl:flex-col">
    <div class="sticky top-16 py-6 px-4 max-h-screen overflow-y-auto">
      <div v-if="toc?.links?.length" class="space-y-2">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
          On This Page
        </h3>
        <nav class="space-y-1">
          <a
            v-for="link in toc.links"
            :key="link.id"
            :href="`#${link.id}`"
            class="block py-1 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
            :class="{
              'pl-0': link.depth === 2,
              'pl-3': link.depth === 3,
              'pl-6': link.depth === 4,
              'pl-9': link.depth >= 5,
              'text-red-600 dark:text-red-400': activeId === link.id
            }"
          >
            {{ link.text }}
          </a>
        </nav>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
// Get table of contents from current document
const { toc } = useContent()

// Track active heading
const activeId = ref<string>('')

// Intersection observer for tracking active headings
onMounted(() => {
  if (process.client) {
    const observer = new IntersectionObserver(
      (entries) => {
        // Find the most visible heading
        let mostVisible = { entry: null as any, ratio: 0 }
        
        entries.forEach((entry) => {
          if (entry.isIntersecting && entry.intersectionRatio > mostVisible.ratio) {
            mostVisible = { entry, ratio: entry.intersectionRatio }
          }
        })
        
        if (mostVisible.entry) {
          activeId.value = mostVisible.entry.target.id
        }
      },
      {
        rootMargin: '0% 0% -80% 0%',
        threshold: [0, 0.25, 0.5, 0.75, 1]
      }
    )
    
    // Observe all headings
    const headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6')
    headings.forEach((heading) => {
      if (heading.id) {
        observer.observe(heading)
      }
    })
    
    // Cleanup observer
    onUnmounted(() => {
      observer.disconnect()
    })
  }
})
</script>