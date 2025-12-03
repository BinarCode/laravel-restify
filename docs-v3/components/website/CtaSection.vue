<template>
  <section class="py-24 bg-slate-950 relative overflow-hidden">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <div class="mb-12 md:mb-16">
        <div class="inline-flex items-center px-4 md:px-6 py-3 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/20 backdrop-blur-sm rounded-full text-sm font-medium text-blue-300 mb-6 md:mb-8">
          <BoltIcon class="w-4 h-4 mr-2" />
          Start Building Today
        </div>
        
        <h2 class="text-3xl md:text-4xl lg:text-6xl font-black tracking-tight mb-6 md:mb-8">
          <span class="bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent">
            Ready to build
          </span>
          <br />
          <span class="bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-400 bg-clip-text text-transparent">
            amazing APIs?
          </span>
        </h2>
        
        <p class="text-lg md:text-xl text-gray-300 max-w-3xl mx-auto mb-8 md:mb-12 leading-relaxed">
          Install Laravel Restify today and transform your API development experience with elegant, powerful, and intuitive tools.
        </p>
      </div>

      <div class="space-y-6 md:space-y-8 mb-8 md:mb-12 max-w-2xl mx-auto">
        <InstallationCard
          title="Install via Composer"
          description="Get started instantly with Composer package manager"
          :command="COMPOSER_COMMAND"
          :copied="composerCopied"
          @copy="handleCopyComposer"
        >
          <CloudArrowDownIcon class="h-6 w-6 text-blue-400" />
        </InstallationCard>

        <InstallationCard
          title="Clone from GitHub"
          description="Explore the source code and contribute to development"
          :command="GIT_COMMAND"
          :copied="gitCopied"
          @copy="handleCopyGit"
        >
          <CodeBracketIcon class="h-6 w-6 text-blue-400" />
        </InstallationCard>
      </div>

      <div class="flex flex-col sm:flex-row gap-4 justify-center px-4 md:px-0">
        <HeroButton
          type="primary"
          href="https://restify.binarcode.com/docs"
          :icon="BoltIcon"
        >
          Start Building
        </HeroButton>

        <HeroButton
          href="https://github.com/binaryk/laravel-restify"
          type="secondary"
          :icon="CodeBracketIcon"
        >
          Explore Code
        </HeroButton>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { 
  BoltIcon, 
  CloudArrowDownIcon, 
  CodeBracketIcon
} from '@heroicons/vue/24/outline'
import HeroButton from './HeroButton.vue'
import InstallationCard from './InstallationCard.vue'

const COMPOSER_COMMAND = 'composer require binaryk/laravel-restify'
const GIT_COMMAND = 'git clone https://github.com/BinarCode/laravel-restify.git'

const composerCopied = ref(false)
const gitCopied = ref(false)

const { copyToClipboard } = useClipboardCopy()

async function handleCopyComposer(): Promise<void> {
  const success = await copyToClipboard(COMPOSER_COMMAND)
  if (!success) return
  
  composerCopied.value = true
  setTimeout(() => {
    composerCopied.value = false
  }, 2000)
}

async function handleCopyGit(): Promise<void> {
  const success = await copyToClipboard(GIT_COMMAND)
  if (!success) return
  
  gitCopied.value = true
  setTimeout(() => {
    gitCopied.value = false
  }, 2000)
}
</script>
