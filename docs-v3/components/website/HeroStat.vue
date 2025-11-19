<template>
  <div class="text-center">
    <div class="text-3xl font-bold text-white mb-2">{{ icon }}</div>
    <div class="text-gray-400 text-sm">{{ label }}</div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  icon: string
  label: string
}

defineProps<Props>()
</script>