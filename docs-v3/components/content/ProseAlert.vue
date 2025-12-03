<template>
  <div :class="['border-l-4 p-4 rounded-r-lg mb-6', styleConfig.alert]">
    <div class="flex">
      <div class="flex-shrink-0">
        <component :is="iconComponent" class="h-5 w-5" :class="styleConfig.icon" />
      </div>
      <div class="ml-3">
        <div class="prose prose-sm max-w-none" :class="styleConfig.text">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { InformationCircleIcon, ExclamationTriangleIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline'

type AlertType = 'info' | 'warning' | 'success' | 'error'

interface Props {
  type?: AlertType
}

const props = withDefaults(defineProps<Props>(), {
  type: 'info'
})

const ALERT_STYLES: Record<AlertType, { alert: string; icon: string; text: string }> = {
  info: {
    alert: 'bg-blue-50 dark:bg-blue-900/20 border-blue-400 dark:border-blue-600',
    icon: 'text-blue-400 dark:text-blue-300',
    text: 'text-blue-800 dark:text-blue-200'
  },
  warning: {
    alert: 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-400 dark:border-yellow-600',
    icon: 'text-yellow-400 dark:text-yellow-300',
    text: 'text-yellow-800 dark:text-yellow-200'
  },
  success: {
    alert: 'bg-green-50 dark:bg-green-900/20 border-green-400 dark:border-green-600',
    icon: 'text-green-400 dark:text-green-300',
    text: 'text-green-800 dark:text-green-200'
  },
  error: {
    alert: 'bg-red-50 dark:bg-red-900/20 border-red-400 dark:border-red-600',
    icon: 'text-red-400 dark:text-red-300',
    text: 'text-red-800 dark:text-red-200'
  }
}

const ALERT_ICONS: Record<AlertType, typeof InformationCircleIcon> = {
  info: InformationCircleIcon,
  warning: ExclamationTriangleIcon,
  success: CheckCircleIcon,
  error: XCircleIcon
}

const styleConfig = computed(() => ALERT_STYLES[props.type])
const iconComponent = computed(() => ALERT_ICONS[props.type])</script>