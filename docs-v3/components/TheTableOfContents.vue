<template>
  <aside class="hidden xl:block xl:w-64">
    <div class="sticky top-20 px-4 py-6">
      <div v-if="toc && toc.links && toc.links.length > 0" class="space-y-4">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
          On this page
        </h4>
        
        <nav class="space-y-2">
          <ul class="space-y-1">
            <li v-for="link in toc.links" :key="link.id">
              <a
                :href="`#${link.id}`"
                class="block text-sm text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                :class="{
                  'pl-0': link.depth === 2,
                  'pl-4': link.depth === 3,
                  'pl-8': link.depth === 4,
                  'text-primary-600 dark:text-primary-400': activeId === link.id
                }"
                @click="scrollToHeading(link.id)"
              >
                {{ link.text }}
              </a>
              
              <!-- Nested headings -->
              <ul v-if="link.children" class="mt-1 space-y-1">
                <li v-for="child in link.children" :key="child.id">
                  <a
                    :href="`#${child.id}`"
                    class="block text-sm text-gray-500 dark:text-gray-500 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                    :class="{
                      'pl-4': child.depth === 3,
                      'pl-8': child.depth === 4,
                      'text-primary-600 dark:text-primary-400': activeId === child.id
                    }"
                    @click="scrollToHeading(child.id)"
                  >
                    {{ child.text }}
                  </a>
                </li>
              </ul>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
const { toc } = useContent()
const activeId = ref<string>('')

// Track active heading
const observer = ref<IntersectionObserver | null>(null)

onMounted(() => {
  // Observe headings
  const headings = document.querySelectorAll('h2, h3, h4')
  
  observer.value = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          activeId.value = entry.target.id
        }
      })
    },
    {
      rootMargin: '0px 0px -80% 0px'
    }
  )
  
  headings.forEach((heading) => {
    if (observer.value) {
      observer.value.observe(heading)
    }
  })
})

onUnmounted(() => {
  if (observer.value) {
    observer.value.disconnect()
  }
})

const scrollToHeading = (id: string) => {
  const element = document.getElementById(id)
  if (element) {
    element.scrollIntoView({
      behavior: 'smooth',
      block: 'start'
    })
  }
}
</script>