<template>
  <aside class="hidden lg:block lg:w-64 lg:flex-shrink-0">
    <nav class="fixed top-16 left-0 w-64 h-[calc(100vh-4rem)] overflow-y-auto px-4 py-6 bg-gray-50 dark:bg-gray-800/50 border-r border-gray-200 dark:border-gray-700">
      <div class="space-y-6">
        <NavigationSection 
          v-for="section in docsNavigationSections" 
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
        v-for="section in docsNavigationSections" 
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

const docsNavigationSections = [
  {
    title: "Getting Started",
    items: [
      { title: "Quick Start", path: "/docs/quickstart" },
    ]
  },
  {
    title: "Authentication",
    items: [
      { title: "Authentication", path: "/docs/auth/authentication" },
      { title: "Authorization", path: "/docs/auth/authorization" },
      { title: "Profile Management", path: "/docs/auth/profile" }
    ]
  },
  {
    title: "API Resources",
    items: [
      { title: "Basic Repositories", path: "/docs/api/repositories-basic" },
      { title: "Repositories", path: "/docs/api/repositories" },
      { title: "Advanced Repositories", path: "/docs/api/repositories-advanced" },
      { title: "Repository Generation", path: "/docs/api/repository-generation" },
      { title: "Fields", path: "/docs/api/fields" },
      { title: "Relations", path: "/docs/api/relations" },
      { title: "REST Methods", path: "/docs/api/rest-methods" },
      { title: "Validation Methods", path: "/docs/api/validation-methods" },
      { title: "Actions", path: "/docs/api/actions" },
      { title: "Getters", path: "/docs/api/getters" },
      { title: "Serializer", path: "/docs/api/serializer" }
    ]
  },
  {
    title: "Search & Filtering",
    items: [
      { title: "Basic Filters", path: "/docs/search/basic-filters" },
      { title: "Advanced Filters", path: "/docs/search/advanced-filters" },
      { title: "Sorting", path: "/docs/search/sorting" }
    ]
  },
  {
    title: "GraphQL",
    items: [
      { title: "GraphQL Overview", path: "/docs/graphql/graphql" },
      { title: "Schema Generation", path: "/docs/graphql/graphql-generation" }
    ]
  },
  {
    title: "MCP Integration",
    items: [
      { title: "MCP Server", path: "/docs/mcp/mcp" },
      { title: "MCP Repositories", path: "/docs/mcp/repositories" },
      { title: "MCP Fields", path: "/docs/mcp/fields" },
      { title: "MCP Getters", path: "/docs/mcp/getters" },
      { title: "JSON Schema Converter", path: "/docs/mcp/json-schema-converter" },
      { title: "MCP Actions", path: "/docs/mcp/actions" }
    ]
  },
  {
    title: "Performance",
    items: [
      { title: "Performance Overview", path: "/docs/performance/performance" },
      { title: "Optimization Solutions", path: "/docs/performance/solutions" }
    ]
  },
  {
    title: "Extensions",
    items: [
      { title: "Boost Package", path: "/docs/boost/boost" }
    ]
  },
  {
    title: "Testing",
    items: [
      { title: "Testing Guide", path: "/docs/testing/testing" }
    ]
  }
]

const isMobileMenuOpen = inject('isMobileMenuOpen', ref(false))
const toggleMobileMenu = inject('toggleMobileMenu', () => {})

const { watchScrollLock } = useBodyScrollLock()
watchScrollLock(isMobileMenuOpen)

const { toggleSection, isSectionCollapsed } = useCollapsibleSections(INITIALLY_COLLAPSED)
</script>
