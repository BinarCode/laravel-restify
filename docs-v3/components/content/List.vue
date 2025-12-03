<template>
  <ProseList :items="processedItems" />
</template>

<script setup lang="ts">
interface Props {
  items: string[] | string
}

const props = defineProps<Props>()

const processedItems = computed(() => {
  if (Array.isArray(props.items)) return props.items
  
  if (typeof props.items !== 'string') return []

  try {
    return JSON.parse(props.items)
  } catch {
    return [props.items]
  }
})
</script>