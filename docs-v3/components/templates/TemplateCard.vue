<template>
  <div 
    class="relative bg-gray-900/30 backdrop-blur-sm rounded-2xl p-6 transition-all duration-300 flex flex-col h-full border"
    :class="template.borderColor"
  >
    <!-- Badge -->
    <div v-if="template.badge" class="absolute -top-3 left-1/2 transform -translate-x-1/2 z-10">
      <span :class="badgeClasses">
        {{ template.badge }}
      </span>
    </div>
    
    <!-- Header -->
    <div class="text-center pb-4 pt-2">
      <div class="flex justify-center mb-4">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-gray-700/50 to-gray-800/50 border border-gray-600/50 flex items-center justify-center">
          <component :is="iconComponent" class="w-6 h-6" :class="template.iconColor" />
        </div>
      </div>
      <h3 class="text-xl font-bold text-white mb-2">{{ template.name }}</h3>
      <div class="text-3xl font-bold text-red-400 mb-2">
        {{ template.price }}
      </div>
      <p class="text-sm text-gray-400">
        {{ template.description }}
      </p>
    </div>
    
    <!-- Features -->
    <div class="space-y-3 mb-6 flex-grow">
      <div 
        v-for="(feature, index) in template.features" 
        :key="index"
        class="flex items-start space-x-2"
        :class="feature.highlighted ? 'bg-orange-500/10 p-2 rounded-lg border border-orange-500/20 -mx-2' : ''"
      >
        <CheckIcon class="w-4 h-4 mt-0.5 flex-shrink-0" :class="feature.highlighted ? 'text-orange-400' : 'text-green-400'" />
        <span class="text-sm" :class="feature.highlighted ? 'font-semibold text-orange-200' : 'text-gray-300'">
          {{ feature.text }}
        </span>
      </div>
    </div>
    
    <!-- Buttons -->
    <div class="space-y-2">
      <a
        v-if="!template.primaryAction.disabled"
        :href="template.primaryAction.href"
        target="_blank"
        rel="noopener noreferrer"
        class="w-full inline-flex justify-center items-center px-4 py-2.5 font-medium rounded-lg transition-all duration-200"
        :class="primaryButtonClasses"
      >
        {{ template.primaryAction.label }}
      </a>
      <div
        v-else
        class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-gray-800/50 text-gray-500 font-medium rounded-lg cursor-not-allowed"
      >
        {{ template.primaryAction.label }}
      </div>
      <a
        :href="template.docsHref"
        target="_blank"
        rel="noopener noreferrer"
        class="w-full inline-flex justify-center items-center px-4 py-2 text-gray-400 hover:text-white font-medium text-sm transition-colors"
      >
        📚 Documentation
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { CheckIcon, RocketLaunchIcon, StarIcon, TrophyIcon } from '@heroicons/vue/24/outline'

interface TemplateFeature {
  text: string
  highlighted?: boolean
}

interface Template {
  name: string
  price: string
  description: string
  badge?: string
  badgeColor?: 'green' | 'blue' | 'purple' | 'orange'
  borderColor: string
  icon: string
  iconColor: string
  features: TemplateFeature[]
  primaryAction: {
    label: string
    href: string
    disabled?: boolean
  }
  docsHref: string
}

interface Props {
  template: Template
}

const props = defineProps<Props>()

const iconComponent = computed(function computeIconComponent() {
  const icons: Record<string, any> = {
    rocket: RocketLaunchIcon,
    star: StarIcon,
    trophy: TrophyIcon
  }
  return icons[props.template.icon] || RocketLaunchIcon
})

const badgeClasses = computed(function computeBadgeClasses(): string {
  const base = 'px-3 py-1 text-white text-xs font-bold rounded-full'
  const colors: Record<string, string> = {
    green: 'bg-green-500',
    blue: 'bg-blue-500',
    purple: 'bg-purple-500',
    orange: 'bg-orange-500'
  }
  return `${base} ${colors[props.template.badgeColor || 'blue']}`
})

const primaryButtonClasses = computed(function computePrimaryButtonClasses(): string {
  if (props.template.badge === 'FREE') {
    return 'bg-green-600 hover:bg-green-700 text-white'
  }
  return 'bg-gray-700/50 border border-gray-600 text-gray-200 hover:bg-gray-700 hover:border-gray-500'
})
</script>
