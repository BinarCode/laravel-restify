<template>
  <ProseList :items="processedItems" />
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