import { ref, readonly } from 'vue'

type ToastType = 'success' | 'error' | 'info'

interface ToastMessage {
  id: string
  message: string
  type: ToastType
  duration?: number
}

const DEFAULT_DURATION = 3000
const toasts = ref<ToastMessage[]>([])

function generateId(): string {
  return Math.random().toString(36).substr(2, 9)
}

export function useAppToast() {
  function showToast(message: string, type: ToastType = 'info', duration = DEFAULT_DURATION): void {
    const id = generateId()
    const toast: ToastMessage = { id, message, type, duration }

    toasts.value.push(toast)

    setTimeout(function removeToastAfterDelay() {
      removeToast(id)
    }, duration)
  }

  function removeToast(id: string): void {
    const index = toasts.value.findIndex(function findToast(toast) {
      return toast.id === id
    })
    if (index === -1) return

    toasts.value.splice(index, 1)
  }

  function success(message: string, duration?: number): void {
    showToast(message, 'success', duration)
  }

  function error(message: string, duration?: number): void {
    showToast(message, 'error', duration)
  }

  function info(message: string, duration?: number): void {
    showToast(message, 'info', duration)
  }

  return {
    toasts: readonly(toasts),
    showToast,
    removeToast,
    success,
    error,
    info
  }
}