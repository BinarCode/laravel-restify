<template>
    <section class="min-h-screen bg-gradient-to-b from-slate-950 via-gray-900 to-slate-950 relative overflow-hidden flex items-center">
    <!-- Background Effects (Fixed - removed moving overlays) -->
    <div class="absolute inset-0">
      <!-- Static gradient background -->
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(120,119,198,0.15),transparent_50%)]"></div>
      
      <!-- Floating orbs with enhanced animations -->
      <div class="absolute top-20 left-10 w-72 h-72 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 rounded-full mix-blend-multiply filter blur-xl animate-blob"></div>
      <div class="absolute top-40 right-10 w-96 h-96 bg-gradient-to-r from-cyan-500/20 to-blue-500/20 rounded-full mix-blend-multiply filter blur-xl animate-blob animation-delay-2000"></div>
      <div class="absolute -bottom-32 left-20 w-80 h-80 bg-gradient-to-r from-slate-500/20 to-blue-500/20 rounded-full mix-blend-multiply filter blur-xl animate-blob animation-delay-4000"></div>
      
      <!-- Grid pattern overlay -->
      <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.02)_1px,transparent_1px)] bg-[size:100px_100px]"></div>
    </div>

    <!-- Main Hero Content -->
    <div class="relative z-10 flex flex-col justify-center min-h-screen max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <!-- Floating Badge -->
      <div class="text-center mb-8">
                <div class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/20 backdrop-blur-sm rounded-full text-sm font-medium text-blue-300 mb-8">
          <div class="flex items-center mr-3">
            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse mr-2"></div>
            <svg class="w-4 h-4 mr-1 group-hover:rotate-12 transition-transform duration-300" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </div>
          Open Source • Laravel Package • MIT Licensed
          <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
          </svg>
        </div>
      </div>

      <!-- Revolutionary Title -->
      <div class="text-center mb-8">
        <h1 class="text-2xl md:text-4xl lg:text-4xl font-black tracking-tight mb-6">
          <span class="bg-gradient-to-r from-white via-gray-100 to-gray-300 bg-clip-text text-transparent leading-tight text-5xl md:text-7xl lg:text-8xl">
            Build APIs for
          </span>
          <br />
          <span class="relative">
                        <span class="bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-500 bg-clip-text text-transparent text-5xl md:text-7xl lg:text-8xl">
              Humans & AI
            </span>
          </span>
          <br />
          <span class="relative">
            <span class="bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">
              Simultaneously
            </span>
            <!-- Underline decoration -->
            <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-64 h-1 bg-gradient-to-r from-transparent via-blue-400 to-transparent animate-pulse"></div>
          </span>
        </h1>

        <!-- Enhanced Subtitle with typing effect -->
        <div class="max-w-4xl mx-auto">
          <p class="text-xl md:text-2xl text-gray-300 mb-4 leading-relaxed font-light">
            Transform your <span class="text-orange-400 font-semibold">Laravel Eloquent models</span> into 
            <span class="text-green-400 font-semibold">JSON:API endpoints</span> and 
            <span class="text-blue-400 font-semibold">MCP servers</span> automatically.
          </p>
          <p class="text-lg text-gray-400 italic">
            Zero boilerplate. Pure results. Future-ready architecture.
          </p>
        </div>

        <!-- Enhanced Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-6 justify-center mt-8">
          <!-- Primary CTA -->
          <a
            href="https://restify.binarcode.com/docs"
            target="_blank"
            rel="noopener noreferrer"
            class="group relative inline-flex items-center justify-center gap-3 px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-blue-500 to-cyan-600 rounded-full overflow-hidden transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-blue-500/25"
          >
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-cyan-700 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            <svg class="relative z-10 w-5 h-5 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span class="relative z-10">Start Building</span>
          </a>

          <!-- Secondary CTA -->
          <a
            href="https://github.com/binaryk/laravel-restify"
            target="_blank"
            rel="noopener noreferrer"
            class="group relative inline-flex items-center justify-center gap-3 px-8 py-4 text-lg font-semibold text-gray-300 bg-white/5 border border-white/10 backdrop-blur-sm rounded-full overflow-hidden transition-all duration-300 hover:bg-white/10 hover:border-white/20 hover:scale-105"
          >
            <svg class="w-5 h-5 group-hover:rotate-12 transition-transform duration-300" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
            </svg>
            <span>Explore Code</span>
          </a>
        </div>
      </div>

      <!-- Interactive Code Demo -->
      <div class="mt-16">
        <div class="max-w-6xl mx-auto">
          <!-- Terminal Window -->
          <div class="bg-gray-900/50 backdrop-blur-lg border border-gray-700/50 rounded-2xl overflow-hidden shadow-2xl">
            <!-- Terminal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-700/50">
              <div class="flex items-center space-x-3">
                <div class="flex space-x-2">
                  <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                  <div class="w-3 h-3 bg-yellow-500 rounded-full animate-pulse animation-delay-200"></div>
                  <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse animation-delay-400"></div>
                </div>
                <span class="text-gray-400 text-sm font-mono">terminal</span>
              </div>
              <div class="flex items-center gap-4 text-gray-400 text-sm font-mono">
                <span class="px-4 py-2 bg-gray-800 border border-gray-600 rounded-lg text-green-400 font-bold text-base">composer require binaryk/laravel-restify</span>
                <button @click="copyToClipboard" class="p-2 hover:bg-gray-700 rounded transition-colors group" title="Copy command">
                  <svg v-if="!copied" class="w-4 h-4 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                <svg v-else class="h-4 w-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Code Content with Enhanced Styling -->
            <div class="p-8">
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Input Code -->
                <div class="space-y-6">
                  <div class="flex items-center gap-2 mb-4">
                    <div class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></div>
                    <span class="text-blue-400 text-sm font-semibold uppercase tracking-wider">Input</span>
                  </div>
                  
                  <div class="space-y-4">
                    <div>
                      <div class="text-gray-500 text-xs mb-2 font-mono">// 1. Create your repository</div>
                      <pre class="text-emerald-400 font-mono text-sm leading-relaxed"><code><span class="text-purple-400">class</span> <span class="text-yellow-400">PostRepository</span> <span class="text-purple-400">extends</span> <span class="text-blue-400">Repository</span>
{
    <span class="text-purple-400">public static</span> <span class="text-blue-400">string</span> <span class="text-white">$model</span> = <span class="text-orange-400">Post</span>::<span class="text-purple-400">class</span>;
}</code></pre>
                    </div>
                    
                    <div>
                      <div class="text-gray-500 text-xs mb-2 font-mono">// 2. Enable MCP for AI agents</div>
                      <pre class="text-emerald-400 font-mono text-sm leading-relaxed"><code><span class="text-blue-400">Mcp</span>::<span class="text-yellow-400">web</span>(<span class="text-green-400">'restify'</span>, <span class="text-orange-400">RestifyServer</span>::<span class="text-purple-400">class</span>);</code></pre>
                    </div>
                  </div>
                </div>

                <!-- Output -->
                <div class="space-y-6">
                  <div class="flex items-center gap-2 mb-4">
                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                    <span class="text-green-400 text-sm font-semibold uppercase tracking-wider">Output</span>
                  </div>
                  
                  <div class="space-y-4">
                    <!-- REST API -->
                    <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700/30">
                      <div class="flex items-center gap-2 mb-2">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span class="text-red-400 text-xs font-bold">REST API</span>
                      </div>
                      <div class="space-y-1 text-xs font-mono text-gray-300">
                        <div><span class="text-green-400">GET</span> /api/restify/posts</div>
                        <div><span class="text-blue-400">POST</span> /api/restify/posts</div>
                        <div><span class="text-yellow-400">PATCH</span> /api/restify/posts/{id}</div>
                        <div><span class="text-red-400">DELETE</span> /api/restify/posts/{id}</div>
                      </div>
                    </div>

                    <!-- MCP Tools -->
                    <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700/30">
                      <div class="flex items-center gap-2 mb-2">
                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                        <span class="text-blue-400 text-xs font-bold">MCP TOOLS</span>
                      </div>
                      <div class="space-y-1 text-xs font-mono text-gray-300">
                        <div>🤖 posts-index-tool</div>
                        <div>🤖 posts-create-tool</div>
                        <div>🤖 posts-update-tool</div>
                        <div>🤖 posts-delete-tool</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Section -->
      <div class="mt-16">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto">
          <div class="text-center">
            <div class="text-3xl font-bold text-white mb-2">⚡</div>
            <div class="text-gray-400 text-sm">Zero Config</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-white mb-2">🚀</div>
            <div class="text-gray-400 text-sm">JSON:API</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-white mb-2">🤖</div>
            <div class="text-gray-400 text-sm">AI Ready</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-white mb-2">💎</div>
            <div class="text-gray-400 text-sm">Laravel Native</div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const copied = ref(false)

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
/* Custom animations */
@keyframes blob {
  0% {
    transform: translate(0px, 0px) scale(1);
  }
  33% {
    transform: translate(30px, -50px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
  100% {
    transform: translate(0px, 0px) scale(1);
  }
}

.animate-blob {
  animation: blob 7s infinite;
}

.animation-delay-200 {
  animation-delay: 200ms;
}

.animation-delay-400 {
  animation-delay: 400ms;
}

.animation-delay-2000 {
  animation-delay: 2000ms;
}

.animation-delay-4000 {
  animation-delay: 4000ms;
}

/* Responsive text sizing */
@media (min-width: 768px) {
  .md\:text-6xl {
    font-size: 3.75rem;
    line-height: 1;
  }
  .md\:text-7xl {
    font-size: 4.5rem;
    line-height: 1;
  }
}

@media (min-width: 1024px) {
  .lg\:text-7xl {
    font-size: 4.5rem;
    line-height: 1;
  }
  .lg\:text-8xl {
    font-size: 6rem;
    line-height: 1;
  }
}
</style>