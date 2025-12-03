<template>
  <div class="group relative">
    <img 
      :src="avatarUrl" 
      :alt="username" 
      class="w-16 h-16 rounded-full border-2 border-gray-200 dark:border-gray-700 group-hover:border-blue-500 transition-all duration-300 group-hover:scale-110"
    />
    <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 bg-gray-900 dark:bg-gray-800 text-white text-xs px-3 py-1 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap border border-gray-700 shadow-lg">
      {{ username }} ({{ contributions }})
      <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900 dark:border-t-gray-800"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  username: string
  githubId: string
  contributions: number
}

const props = defineProps<Props>()

const avatarUrl = computed(() => `https://avatars.githubusercontent.com/u/${props.githubId}?v=4`)
</script>
