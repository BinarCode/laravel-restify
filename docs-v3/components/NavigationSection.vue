<template>
  <div class="space-y-2">
    <button
      @click="$emit('toggle')"
      class="flex items-center justify-between w-full text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
    >
      {{ title }}
      <ChevronDownIcon 
        class="h-4 w-4 transition-transform duration-200"
        :class="{ 'rotate-180': collapsed }"
      />
    </button>
    <ul 
      v-show="!collapsed"
      class="space-y-1 transition-all duration-200"
    >
      <li v-for="item in items" :key="item.path">
        <NuxtLink
          :to="item.path"
          class="group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
          :class="isActive(item.path) 
            ? activeClass 
            : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50'"
          @click="$emit('itemClick', item)"
        >
          {{ item.title }}
        </NuxtLink>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

interface NavigationItem {
  title: string
  path: string
}

interface Props {
  title: string
  items: NavigationItem[]
  collapsed: boolean
  activeClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  activeClass: 'nav-link-active text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20'
})

defineEmits<{
  toggle: []
  itemClick: [item: NavigationItem]
}>()

const route = useRoute()

function isActive(path: string): boolean {
  return route.path === path || route.path.startsWith(path + '/')
}
</script>
