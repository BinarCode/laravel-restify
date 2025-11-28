<template>
  <Teleport to="body">
    <div class="fixed bottom-4 right-4 z-50 space-y-2">
      <TransitionGroup
        name="toast"
        tag="div"
        class="space-y-2"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'flex items-center p-4 rounded-lg shadow-lg border max-w-sm',
            {
              'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200': toast.type === 'success',
              'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200': toast.type === 'error',
              'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200': toast.type === 'info'
            }
          ]"
        >
          <div class="flex-shrink-0 mr-3">
            <!-- Success icon -->
            <CheckCircleIcon
              v-if="toast.type === 'success'"
              class="w-5 h-5"
            />
            
            <!-- Error icon -->
            <XCircleIcon
              v-else-if="toast.type === 'error'"
              class="w-5 h-5"
            />
            
            <!-- Info icon -->
            <InformationCircleIcon
              v-else
              class="w-5 h-5"
            />
          </div>
          
          <div class="flex-1 text-sm font-medium">
            {{ toast.message }}
          </div>
          
          <button
            @click="removeToast(toast.id)"
            class="flex-shrink-0 ml-3 text-current hover:opacity-70 transition-opacity"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { CheckCircleIcon, XCircleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'
import { useAppToast } from '~/composables/useAppToast'

const { toasts, removeToast } = useAppToast()
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.toast-move {
  transition: transform 0.3s ease;
}
</style>