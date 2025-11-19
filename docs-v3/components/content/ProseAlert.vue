<template>
  <div 
    :class="[
      'border-l-4 p-4 rounded-r-lg mb-6',
      alertClasses
    ]"
  >
    <div class="flex">
      <div class="flex-shrink-0">
        <!-- Info Icon -->
        <svg v-if="type === 'info'" class="h-5 w-5" :class="iconClasses" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        
        <!-- Warning Icon -->
        <svg v-if="type === 'warning'" class="h-5 w-5" :class="iconClasses" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        
        <!-- Success Icon -->
        <svg v-if="type === 'success'" class="h-5 w-5" :class="iconClasses" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        
        <!-- Error Icon -->
        <svg v-if="type === 'error'" class="h-5 w-5" :class="iconClasses" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div class="ml-3">
        <div class="prose prose-sm max-w-none" :class="textClasses">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  type?: 'info' | 'warning' | 'success' | 'error'
}

const props = withDefaults(defineProps<Props>(), {
  type: 'info'
})

const alertClasses = computed(() => {
  const classes = {
    info: 'bg-blue-50 dark:bg-blue-900/20 border-blue-400 dark:border-blue-600',
    warning: 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-400 dark:border-yellow-600',
    success: 'bg-green-50 dark:bg-green-900/20 border-green-400 dark:border-green-600',
    error: 'bg-red-50 dark:bg-red-900/20 border-red-400 dark:border-red-600'
  }
  return classes[props.type]
})

const iconClasses = computed(() => {
  const classes = {
    info: 'text-blue-400 dark:text-blue-300',
    warning: 'text-yellow-400 dark:text-yellow-300',
    success: 'text-green-400 dark:text-green-300',
    error: 'text-red-400 dark:text-red-300'
  }
  return classes[props.type]
})

const textClasses = computed(() => {
  const classes = {
    info: 'text-blue-800 dark:text-blue-200',
    warning: 'text-yellow-800 dark:text-yellow-200',
    success: 'text-green-800 dark:text-green-200',
    error: 'text-red-800 dark:text-red-200'
  }
  return classes[props.type]
})

</script>