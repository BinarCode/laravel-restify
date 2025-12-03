<template>
  <aside class="hidden lg:flex lg:w-64 lg:flex-col">
    <div class="flex flex-col h-screen sticky top-0 bg-gray-50 dark:bg-gray-800/50 border-r border-gray-200 dark:border-gray-700">
      <nav class="flex-1 px-4 py-6 overflow-y-auto">
        <div class="space-y-6">
          <NavigationSection 
            v-for="section in navigationSections" 
            :key="section.title"
            :title="section.title"
            :items="section.items"
            :collapsed="isSectionCollapsed(section.title)"
            @toggle="toggleSection(section.title)"
          />
        </div>
      </nav>
    </div>
  </aside>
  
  <MobileSidebarOverlay 
    :is-open="isMobileMenuOpen" 
    @close="toggleMobileMenu"
  >
    <div class="space-y-6">
      <NavigationSection 
        v-for="section in navigationSections" 
        :key="section.title"
        :title="section.title"
        :items="section.items"
        :collapsed="isSectionCollapsed(section.title)"
        @toggle="toggleSection(section.title)"
        @item-click="toggleMobileMenu"
      />
    </div>
  </MobileSidebarOverlay>
</template>

<script setup lang="ts">
const { navigationSections } = useNavigation()

const isMobileMenuOpen = inject('isMobileMenuOpen', ref(false))
const toggleMobileMenu = inject('toggleMobileMenu', () => {})

const { watchScrollLock } = useBodyScrollLock()
watchScrollLock(isMobileMenuOpen)

const { toggleSection, isSectionCollapsed, collapseAll } = useCollapsibleSections()

onMounted(() => {
  if (!navigationSections) return
  const allSectionTitles = navigationSections.map(section => section.title)
  collapseAll(allSectionTitles)
})
</script>