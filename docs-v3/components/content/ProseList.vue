<template>
  <ul class="space-y-2 mb-6">
    <li 
      v-for="(item, index) in processedItems" 
      :key="index"
      class="flex items-start"
    >
      <div class="flex-shrink-0 mt-1 mr-3">
        <div class="w-2 h-2 bg-primary-500 rounded-full"></div>
      </div>
      <div class="text-gray-700 dark:text-gray-300" v-html="item"></div>
    </li>
  </ul>
</template>

<script setup lang="ts">
interface Props {
  items: string[] | string
}

const props = defineProps<Props>()

const processedItems = computed(() => {
  // Handle case where items might be a string instead of array
  if (typeof props.items === 'string') {
    try {
      return JSON.parse(props.items)
    } catch {
      return [props.items]
    }
  }
  // Handle case where it's already an array
  return Array.isArray(props.items) ? props.items : []
})
</script>