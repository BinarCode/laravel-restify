<template>
  <div class="w-full max-w-3xl mx-auto px-4 py-6">
    <!-- Main transformation container -->
    <div class="relative flex items-center justify-between">
      <!-- Left side - Humans -->
      <div class="flex flex-col items-center space-y-2">
        <div class="relative">
          <!-- Smaller Users icon -->
          <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900 dark:to-blue-800 rounded-xl flex items-center justify-center shadow-md">
            <Users :size="24" class="text-blue-600 dark:text-blue-400" />
          </div>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Humans</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 text-center">REST APIs</p>
      </div>

      <!-- Center - Progress Bar Only -->
      <div class="flex-1 flex items-center px-6">
        <!-- Progress bar container -->
        <div class="relative w-full">
          <!-- Background bar -->
          <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden relative">
            <!-- Left to Right progress bar (Human → AI) -->
            <div 
              class="absolute inset-0 h-full bg-gradient-to-r from-green-400 to-green-600 rounded-full transition-all ease-out"
              :style="{ 
                width: leftToRightWidth,
                transitionDuration: transitionDuration,
                opacity: showLeftToRight ? 1 : 0
              }"
            ></div>
            <!-- Right to Left progress bar (AI → Human) -->
            <div 
              class="absolute inset-0 h-full bg-gradient-to-l from-green-400 to-green-600 rounded-full transition-all ease-out ml-auto"
              :style="{ 
                width: rightToLeftWidth,
                transitionDuration: transitionDuration,
                opacity: showRightToLeft ? 1 : 0
              }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Right side - AI Agents -->
      <div class="flex flex-col items-center space-y-2">
        <div class="relative">
          <!-- Smaller Bot icon -->
          <div class="w-12 h-12 bg-gradient-to-br from-emerald-100 to-emerald-200 dark:from-emerald-900 dark:to-emerald-800 rounded-xl flex items-center justify-center shadow-md">
            <Bot :size="24" class="text-emerald-600 dark:text-emerald-400" />
          </div>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">AI Agents</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 text-center">MCP Server</p>
      </div>
    </div>

    <!-- Connecting lines with animation -->
    <svg class="absolute inset-0 w-full h-full pointer-events-none" style="z-index: -1;">
      <defs>
        <linearGradient id="connectionGradient" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:0.4" />
          <stop offset="50%" style="stop-color:#10b981;stop-opacity:0.5" />
          <stop offset="100%" style="stop-color:#10b981;stop-opacity:0.4" />
        </linearGradient>
      </defs>
      <path 
        :d="connectionPath"
        stroke="url(#connectionGradient)"
        stroke-width="1"
        fill="none"
        stroke-dasharray="8,4"
        :style="{ strokeDashoffset: dashOffset }"
        class="transition-all duration-1000"
      />
    </svg>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Users, Bot } from 'lucide-vue-next'

const leftToRightWidth = ref('0%')
const rightToLeftWidth = ref('0%')
const showLeftToRight = ref(true)
const showRightToLeft = ref(false)
const isComplete = ref(false)
const dashOffset = ref(100)
const transitionDuration = ref('3000ms')

const connectionPath = computed(() => {
  return 'M80,40 Q240,20 400,40'
})

let animationTimeout: NodeJS.Timeout | null = null

const startAnimation = () => {
  // Reset everything
  leftToRightWidth.value = '0%'
  rightToLeftWidth.value = '0%'
  showLeftToRight.value = true
  showRightToLeft.value = false
  isComplete.value = false
  dashOffset.value = 100
  transitionDuration.value = '3000ms'

  // Phase 1: Human → AI (Left to Right)
  setTimeout(() => {
    leftToRightWidth.value = '100%'
    dashOffset.value = 0
    isComplete.value = true
  }, 100)

  // Phase 2: Switch to AI → Human after 2 seconds
  setTimeout(() => {
    showLeftToRight.value = false
    showRightToLeft.value = true
    leftToRightWidth.value = '0%'
    rightToLeftWidth.value = '0%'
    isComplete.value = false
    dashOffset.value = 100
  }, 5100) // 100ms + 3000ms + 2000ms wait

  // Fill right to left (AI → Human)
  setTimeout(() => {
    rightToLeftWidth.value = '100%'
    dashOffset.value = 0
    isComplete.value = true
  }, 5200)

  // Restart cycle after 2 seconds
  setTimeout(() => {
    animationTimeout = setTimeout(startAnimation, 1000)
  }, 8200)
}

onMounted(() => {
  startAnimation()
})

onUnmounted(() => {
  if (animationTimeout) {
    clearTimeout(animationTimeout)
  }
})
</script>

<style scoped>
.transition-all {
  transition-property: all;
}

.duration-3000 {
  transition-duration: 3s;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

.animate-float {
  animation: float 3s ease-in-out infinite;
}
</style>
