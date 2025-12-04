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
          :class="['flex items-center p-4 rounded-lg shadow-lg border max-w-sm', toastStyles[toast.type]]"
        >
          <div class="flex-shrink-0 mr-3">
            <component :is="toastIcons[toast.type]" class="w-5 h-5" />
          </div>
          
          <div class="flex-1 text-sm font-medium">
            {{ toast.message }}
          </div>
          
          <button
            @click="removeToast(toast.id)"
            class="flex-shrink-0 ml-3 text-current hover:opacity-70 transition-opacity"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { CheckCircleIcon, XCircleIcon, InformationCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline'

type ToastType = 'success' | 'error' | 'info'

const { toasts, removeToast } = useAppToast()

const toastStyles: Record<ToastType, string> = {
  success: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200',
  error: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200',
  info: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200'
}

const toastIcons: Record<ToastType, typeof CheckCircleIcon> = {
  success: CheckCircleIcon,
  error: XCircleIcon,
  info: InformationCircleIcon
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.toast-move {
  transition: transform 0.3s ease;
}
</style>