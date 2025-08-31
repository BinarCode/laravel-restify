<template>
  <ProseAlert :type="type">
    <slot />
  </ProseAlert>
</template>

<script setup lang="ts">
interface Props {
  type?: 'info' | 'warning' | 'success' | 'error'
}

defineProps<Props>()
</script>