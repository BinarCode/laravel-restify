<template>
  <section class="hero-section">
    <!-- Background Effects -->
    <div class="hero-background">
      <div class="hero-gradient-radial"></div>
      <div class="hero-floating-orbs">
        <div class="hero-orb hero-orb-1 animate-pulse"></div>
        <div class="hero-orb hero-orb-2 animate-pulse" style="animation-delay: 2s"></div>
        <div class="hero-orb hero-orb-3 animate-pulse" style="animation-delay: 4s"></div>
      </div>
      <div class="hero-grid-pattern"></div>
    </div>

    <!-- Main Hero Content -->
    <div class="hero-content">
      <!-- Floating Badge -->
      <div class="text-center mb-8">
        <div class="hero-badge">
          <div class="flex items-center mr-3">
            <div class="hero-status-dot"></div>
            <CodeBracketIcon class="hero-icon-sm hover:rotate-12" />
          </div>
          Open Source • Laravel Package • MIT Licensed
        </div>
      </div>

      <!-- Hero Title -->
      <div class="text-center mb-8">
        <h1 class="hero-title">
          <span class="hero-title-primary text-3xl md:text-5xl lg:text-5xl">
            Build APIs for
          </span>
          <br />
          <span class="hero-title-highlight text-4xl md:text-6xl lg:text-8xl font-extrabold">
            Humans & AI
          </span>
          <br />
          <span class="relative">
            <span class="hero-title-accent text-2xl md:text-4xl lg:text-5xl font-light italic px-1">
              Simultaneously 
            </span>
            <div class="hero-title-underline"></div>
          </span>
        </h1>
        
        <!-- Hero Subtitle -->
        <div class="hero-subtitle-container">
          <p class="hero-subtitle">
            Transform your <span class="text-orange-400 font-semibold">Laravel Eloquent models</span> into 
            <span class="text-green-400 font-semibold">JSON:API endpoints</span> and 
            <span class="text-blue-400 font-semibold">MCP servers</span> automatically.
          </p>
          <p class="hero-tagline">
            Zero boilerplate. Pure results. Future-ready architecture.
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="hero-actions">
          <HeroButton
            type="primary"
            href="https://restify.binarcode.com/docs"
            :icon="BoltIcon"
          >
            Start Building
          </HeroButton>

          <HeroButton
            type="secondary"
            href="https://github.com/binaryk/laravel-restify"
            :icon="CodeBracketIcon"
          >
            Explore Code
          </HeroButton>
        </div>
      </div>

      <!-- Interactive Code Demo -->
      <div class="hero-demo">
        <div class="hero-demo-container">
          <div class="hero-terminal">
            <!-- Terminal Header -->
            <div class="hero-terminal-header">
              <div class="flex items-center space-x-3">
                <div class="flex space-x-2">
                  <div class="terminal-dot terminal-dot-red"></div>
                  <div class="terminal-dot terminal-dot-yellow"></div>
                  <div class="terminal-dot terminal-dot-green"></div>
                </div>
                <span class="text-gray-400 text-sm font-mono hidden sm:block">terminal</span>
              </div>
              <div class="hero-terminal-command">
                <span class="hero-install-command">composer require binaryk/laravel-restify</span>
                <span class="hero-install-command-mobile">install</span>
                <button 
                  @click="copyToClipboard" 
                  class="p-2 bg-gray-800/50 hover:bg-gray-700/50 border border-gray-600/50 rounded-lg transition-colors duration-200 group/btn"
                  :class="{ 'bg-green-500/20 border-green-500/50': copied }"
                  title="Copy command"
                >
                  <ClipboardIcon v-if="!copied" class="h-4 w-4 text-gray-400 group-hover/btn:text-gray-300" />
                  <CheckIcon v-else class="h-4 w-4 text-green-400" />
                </button>
              </div>
            </div>

            <!-- Code Content -->
            <div class="hero-terminal-content">
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
                <!-- Input Section -->
                <CodeSection
                  type="input"
                  title="Input"
                  :codeBlocks="inputCodeBlocks"
                />

                <!-- Output Section -->
                <CodeSection
                  type="output"
                  title="Output"
                  :outputBlocks="outputBlocks"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Section -->
      <div class="hero-stats">
        <div class="hero-stats-grid">
          <HeroStat
            v-for="stat in stats"
            :key="stat.label"
            :icon="stat.icon"
            :label="stat.label"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { 
  BoltIcon, 
  CodeBracketIcon, 
  ClipboardIcon, 
  CheckIcon 
} from '@heroicons/vue/24/outline'
import HeroButton from './HeroButton.vue'
import HeroStat from './HeroStat.vue'
import CodeSection from './CodeSection.vue'

const copied = ref(false)

const stats = [
  { icon: '⚡', label: 'Zero Config' },
  { icon: '🚀', label: 'JSON:API' },
  { icon: '🤖', label: 'AI Ready' },
  { icon: '💎', label: 'Laravel Native' }
]

const inputCodeBlocks = [
  {
    comment: '// 1. Create your repository',
    code: `<span class="text-purple-400">class</span> <span class="text-yellow-400">PostRepository</span> <span class="text-purple-400">extends</span> <span class="text-blue-400">Repository</span>
{
    <span class="text-purple-400">public static</span> <span class="text-blue-400">string</span> <span class="text-white">$model</span> = <span class="text-orange-400">Post</span>::<span class="text-purple-400">class</span>;
}`
  },
  {
    comment: '// 2. Enable MCP for AI agents',
    code: `<span class="text-blue-400">Mcp</span>::<span class="text-yellow-400">web</span>(<span class="text-green-400">'restify'</span>, <span class="text-orange-400">RestifyServer</span>::<span class="text-purple-400">class</span>);`
  }
]

const outputBlocks = [
  {
    title: 'REST API',
    color: 'red',
    items: [
      { method: 'GET', endpoint: '/api/restify/posts', color: 'green' },
      { method: 'POST', endpoint: '/api/restify/posts', color: 'blue' },
      { method: 'PATCH', endpoint: '/api/restify/posts/{id}', color: 'yellow' },
      { method: 'DELETE', endpoint: '/api/restify/posts/{id}', color: 'red' }
    ]
  },
  {
    title: 'MCP TOOLS',
    color: 'blue',
    items: [
      { text: '🤖 posts-index-tool' },
      { text: '🤖 posts-create-tool' },
      { text: '🤖 posts-update-tool' },
      { text: '🤖 posts-delete-tool' }
    ]
  }
]

const copyToClipboard = async () => {
  try {
    await navigator.clipboard.writeText('composer require binaryk/laravel-restify')
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy: ', err)
  }
}
</script>

<style scoped>
@reference "tailwindcss";

/* Reusable component classes with Tailwind @apply */
.hero-section {
  @apply min-h-screen bg-gradient-to-b from-slate-950 via-gray-900 to-slate-950 relative overflow-hidden flex items-center;
}

.hero-background {
  @apply absolute inset-0;
}

.hero-gradient-radial {
  @apply absolute inset-0;
  background: radial-gradient(circle at 50% 50%, rgba(120,119,198,0.15), transparent 50%);
}

.hero-floating-orbs {
  @apply absolute inset-0;
}

.hero-orb {
  @apply absolute rounded-full mix-blend-multiply filter blur-xl;
}

.hero-orb-1 {
  @apply top-20 left-10 w-72 h-72 bg-gradient-to-r from-blue-500/20 to-cyan-500/20;
}

.hero-orb-2 {
  @apply top-40 right-10 w-96 h-96 bg-gradient-to-r from-cyan-500/20 to-blue-500/20;
}

.hero-orb-3 {
  @apply -bottom-32 left-20 w-80 h-80 bg-gradient-to-r from-slate-500/20 to-blue-500/20;
}

.hero-grid-pattern {
  @apply absolute inset-0;
  background-image: 
    linear-gradient(rgba(255,255,255,.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.02) 1px, transparent 1px);
  background-size: 100px 100px;
}

.hero-content {
  @apply relative z-10 flex flex-col justify-center min-h-screen max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16;
}

.hero-badge {
  @apply inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/20 backdrop-blur-sm rounded-full text-sm font-medium text-blue-300 mb-8;
}

.hero-status-dot {
  @apply w-2 h-2 bg-green-400 rounded-full animate-pulse mr-2;
}

.hero-icon-sm {
  @apply w-4 h-4 mr-1 transition-transform duration-300;
}

.hero-title {
  @apply text-4xl md:text-6xl lg:text-7xl font-black tracking-tight mb-8;
}

.hero-title-primary {
  @apply bg-gradient-to-r from-white via-gray-100 to-gray-300 bg-clip-text text-transparent leading-tight;
}

.hero-title-highlight {
  @apply bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-500 bg-clip-text text-transparent;
}

.hero-title-accent {
  @apply bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent;
}

.hero-title-underline {
  @apply absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-32 md:w-64 h-1 bg-gradient-to-r from-transparent via-blue-400 to-transparent animate-pulse;
}

.hero-subtitle-container {
  @apply max-w-4xl mx-auto;
}

.hero-subtitle {
  @apply text-lg md:text-xl lg:text-2xl text-gray-300 mb-4 leading-relaxed font-light px-4 md:px-0;
}

.hero-tagline {
  @apply text-base md:text-lg text-gray-400 italic px-4 md:px-0;
}

.hero-actions {
  @apply flex flex-col sm:flex-row gap-4 justify-center mt-8 px-4 md:px-0;
}

.hero-demo {
  @apply mt-16;
}

.hero-demo-container {
  @apply max-w-6xl mx-auto;
}

.hero-terminal {
  @apply bg-gray-900/50 backdrop-blur-lg border border-gray-700/50 rounded-2xl overflow-hidden shadow-2xl;
}

.hero-terminal-header {
  @apply flex items-center justify-between p-4 md:p-6 border-b border-gray-700/50;
}

.terminal-dot {
  @apply w-3 h-3 rounded-full animate-pulse;
}

.terminal-dot-red {
  @apply bg-red-500;
}

.terminal-dot-yellow {
  @apply bg-yellow-500;
  animation-delay: 200ms;
}

.terminal-dot-green {
  @apply bg-green-500;
  animation-delay: 400ms;
}

.hero-terminal-command {
  @apply flex items-center gap-2 md:gap-4 text-gray-400 text-xs md:text-sm font-mono;
}

.hero-install-command {
  @apply hidden md:inline-block px-4 py-2 bg-gray-800 border border-gray-600 rounded-lg text-green-400 font-bold text-base;
}

.hero-install-command-mobile {
  @apply md:hidden px-2 py-1 bg-gray-800 border border-gray-600 rounded text-green-400 font-bold text-xs;
}

.hero-terminal-content {
  @apply p-4 md:p-8;
}

.hero-stats {
  @apply mt-16;
}

.hero-stats-grid {
  @apply grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto;
}
</style>