<template>
  <div class="inline-flex bg-gray-100 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-xl p-1">
    <button
      v-for="option in options"
      :key="option.value"
      @click="$emit('update:modelValue', option.value)"
      class="relative px-5 py-2.5 rounded-lg font-medium transition-all duration-200 cursor-pointer"
      :class="modelValue === option.value 
        ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-sm' 
        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
    >
      <div class="flex items-center space-x-2">
        <component v-if="option.icon" :is="option.icon" class="w-5 h-5" />
        <span>{{ option.label }}</span>
      </div>
    </button>
  </div>
</template>

<script setup lang="ts">
import type { Component } from 'vue'

interface ToggleOption {
  value: string
  label: string
  icon?: Component
}

interface Props {
  modelValue: string
  options: ToggleOption[]
}

defineProps<Props>()
defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>
