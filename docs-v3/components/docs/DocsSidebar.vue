<template>
  <aside class="hidden lg:block lg:w-64 lg:flex-shrink-0">
    <nav class="fixed top-16 left-0 w-64 h-[calc(100vh-4rem)] overflow-y-auto px-4 py-6 bg-gray-50 dark:bg-gray-800/50 border-r border-gray-200 dark:border-gray-700">
      <div class="space-y-6">
        <NavigationSection 
          v-for="section in navigationSections" 
          :key="section.title"
          :title="section.title"
          :items="section.items"
          :collapsed="isSectionCollapsed(section.title)"
          active-class="nav-link-active text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20"
          @toggle="toggleSection(section.title)"
        />
      </div>
    </nav>
  </aside>
  
  <MobileSidebarOverlay 
    :is-open="isMobileMenuOpen" 
    title="Documentation"
    @close="toggleMobileMenu"
  >
    <div class="space-y-6">
      <NavigationSection 
        v-for="section in navigationSections" 
        :key="section.title"
        :title="section.title"
        :items="section.items"
        :collapsed="isSectionCollapsed(section.title)"
        active-class="nav-link-active text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20"
        @toggle="toggleSection(section.title)"
        @item-click="toggleMobileMenu"
      />
    </div>
  </MobileSidebarOverlay>
</template>

<script setup lang="ts">
const INITIALLY_COLLAPSED = ['Performance', 'Extensions', 'Testing']

// Fetch docs navigation from Nuxt Content
const { data: navigation } = await useAsyncData('docs-navigation', () => 
  queryCollectionNavigation('content')
)

// Transform navigation tree to sidebar format
const navigationSections = computed(() => {
  if (!navigation.value) return []
  
  // Find the docs section in the navigation
  const docsNav = navigation.value.find(item => item.path === '/docs')
  if (!docsNav?.children) return []
  
  // Transform each section (folder) into sidebar format
  return docsNav.children
    .filter(section => section.children && section.children.length > 0)
    .map(section => ({
      title: section.title,
      items: section.children.map(item => ({
        title: item.title,
        path: item.path
      }))
    }))
})

const isMobileMenuOpen = inject('isMobileMenuOpen', ref(false))
const toggleMobileMenu = inject('toggleMobileMenu', () => {})

const { watchScrollLock } = useBodyScrollLock()
watchScrollLock(isMobileMenuOpen)

const { toggleSection, isSectionCollapsed } = useCollapsibleSections(INITIALLY_COLLAPSED)
</script>
