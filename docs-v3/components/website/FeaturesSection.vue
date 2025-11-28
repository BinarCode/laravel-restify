<template>
  <section class="py-24 bg-gradient-to-b from-slate-900 via-gray-900 to-slate-950 relative overflow-hidden">
    <!-- Background Effects -->
    <div class="absolute inset-0">
      <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-96 h-96 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-gradient-to-r from-red-500/10 to-pink-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Section Header -->
      <div class="text-center mb-16 md:mb-20 px-4 md:px-0">
        <div class="inline-flex items-center px-4 md:px-6 py-3 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/20 backdrop-blur-sm rounded-full text-sm font-medium text-blue-300 mb-6 md:mb-8">
          <BoltIcon class="w-4 h-4 mr-2" />
          One Codebase, Infinite Possibilities
        </div>
        
        <h2 class="text-3xl md:text-4xl lg:text-6xl font-black tracking-tight mb-6 md:mb-8">
          <span class="bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent">
            Write Once,
          </span>
          <br />
          <span class="bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-400 bg-clip-text text-transparent">
            Deploy Everywhere
          </span>
        </h2>
        
        <p class="text-lg md:text-xl text-gray-300 max-w-4xl mx-auto leading-relaxed">
          Transform your Laravel models into production-ready APIs and AI-compatible servers 
          with <span class="text-blue-400 font-semibold">zero configuration</span>. 
          The future of API development is here.
        </p>
      </div>

      <!-- Feature Showcase -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8 px-4 md:px-0">
        <!-- JSON:API Compliant -->
        <div class="group relative bg-gradient-to-br from-gray-900/50 to-gray-800/50 backdrop-blur-lg border border-gray-700/50 rounded-3xl p-6 md:p-8 hover:scale-105 transition-all duration-500 hover:border-blue-500/30">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          
          <div class="relative z-10">
            <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
              <CircleStackIcon class="h-8 w-8 text-white" />
            </div>
            
            <p class="text-gray-400 mb-6 leading-relaxed">
              Automatically generates endpoints that follow JSON:API specifications with proper relationships, pagination, filtering, and sparse fieldsets.
            </p>

            <div class="bg-gray-800/50 rounded-xl p-3 md:p-4 border border-gray-700/30">
              <pre class="text-xs text-gray-300 overflow-x-auto whitespace-pre-wrap break-words"><code>{
  "data": {
    "id": "1",
    "type": "posts",
    "attributes": {
      "title": "Laravel Restify",
      "published_at": "2024-01-15"
    },
    "relationships": {
      "author": {
        "data": {"id": "1", "type": "users"}
      }
    }
  }
}</code></pre>
            </div>
          </div>
        </div>

        <!-- Policy-Based Authorization -->
        <div class="group relative bg-gradient-to-br from-gray-900/50 to-gray-800/50 backdrop-blur-lg border border-gray-700/50 rounded-3xl p-6 md:p-8 hover:scale-105 transition-all duration-500 hover:border-blue-500/30">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          
          <div class="relative z-10">
            <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
              <ShieldCheckIcon class="h-8 w-8 text-white" />
            </div>
            
            <p class="text-gray-400 mb-6 leading-relaxed">
              Built-in integration with Laravel's authorization system. Use policies for fine-grained access control on every endpoint and MCP tool.
            </p>

            <div class="bg-gray-800/50 rounded-xl p-3 md:p-4 border border-gray-700/30">
              <pre class="text-xs text-gray-300 overflow-x-auto whitespace-pre-wrap break-words"><code><span class="text-purple-400">class</span> <span class="text-yellow-400">PostPolicy</span>
{
    <span class="text-purple-400">public function</span> <span class="text-yellow-400">index</span>(<span class="text-blue-400">User</span> <span class="text-white">$user</span>)
    {
        <span class="text-purple-400">return</span> <span class="text-white">$user</span>-><span class="text-yellow-400">can</span>(<span class="text-green-400">'view posts'</span>);
    }
}</code></pre>
            </div>
          </div>
        </div>

        <!-- Advanced Filtering -->
        <div class="group relative bg-gradient-to-br from-gray-900/50 to-gray-800/50 backdrop-blur-lg border border-gray-700/50 rounded-3xl p-6 md:p-8 hover:scale-105 transition-all duration-500 hover:border-blue-500/30">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          
          <div class="relative z-10">
            <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
              <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            
            <h3 class="text-xl md:text-2xl font-bold text-white mb-4 group-hover:text-blue-400 transition-colors">
              Smart Filtering
            </h3>
            
            <p class="text-gray-400 mb-6 leading-relaxed">
              Complex queries, sorting, filtering, and search across relationships without any extra configuration. Full-text search included.
            </p>

            <div class="bg-gray-800/50 rounded-xl p-3 md:p-4 border border-gray-700/30">
              <pre class="text-xs text-gray-300 overflow-x-auto whitespace-pre-wrap break-words"><code><span class="text-green-400">GET</span> /api/restify/posts?
  filter[title]=Laravel&
  filter[status]=published&
  sort=-created_at&
  include=author,comments&
  page[size]=10</code></pre>
            </div>
          </div>
        </div>

        <!-- MCP Server Support -->
        <div class="group relative bg-gradient-to-br from-gray-900/50 to-gray-800/50 backdrop-blur-lg border border-gray-700/50 rounded-3xl p-6 md:p-8 hover:scale-105 transition-all duration-500 hover:border-blue-500/30">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          
          <div class="relative z-10">
            <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
              <span class="text-2xl">🤖</span>
            </div>
            
            <h3 class="text-xl md:text-2xl font-bold text-white mb-4 group-hover:text-blue-400 transition-colors">
              AI Agent Ready
            </h3>
            
            <p class="text-gray-400 mb-6 leading-relaxed">
              Generate Model Context Protocol servers that allow AI agents to interact with your APIs using structured, type-safe tools.
            </p>

            <div class="bg-gray-800/50 rounded-xl p-3 md:p-4 border border-gray-700/30">
              <pre class="text-xs text-gray-300 overflow-x-auto whitespace-pre-wrap break-words"><code><span class="text-blue-400">Mcp</span>::<span class="text-yellow-400">web</span>(<span class="text-green-400">'restify'</span>, <span class="text-orange-400">RestifyServer</span>::<span class="text-purple-400">class</span>)
  -><span class="text-yellow-400">middleware</span>([<span class="text-green-400">'auth:sanctum'</span>])
  -><span class="text-yellow-400">name</span>(<span class="text-green-400">'mcp.restify'</span>);</code></pre>
            </div>
          </div>
        </div>

        <!-- Zero Configuration -->
        <div class="group relative bg-gradient-to-br from-gray-900/50 to-gray-800/50 backdrop-blur-lg border border-gray-700/50 rounded-3xl p-6 md:p-8 hover:scale-105 transition-all duration-500 hover:border-blue-500/30">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          
          <div class="relative z-10">
            <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
              <BoltIcon class="h-8 w-8 text-white" />
            </div>
            
            <h3 class="text-xl md:text-2xl font-bold text-white mb-4 group-hover:text-blue-400 transition-colors">
              Zero Configuration
            </h3>
            
            <p class="text-gray-400 mb-6 leading-relaxed">
              Start building in seconds. Just create a repository class pointing to your model and you're ready with a production API.
            </p>

            <div class="bg-gray-800/50 rounded-xl p-3 md:p-4 border border-gray-700/30">
              <pre class="text-xs text-gray-300 overflow-x-auto whitespace-pre-wrap break-words"><code><span class="text-purple-400">class</span> <span class="text-yellow-400">UserRepository</span> <span class="text-purple-400">extends</span> <span class="text-blue-400">Repository</span>
{
    <span class="text-purple-400">public static</span> <span class="text-blue-400">string</span> <span class="text-white">$model</span> = <span class="text-orange-400">User</span>::<span class="text-purple-400">class</span>;
    
    <span class="text-gray-500">// That's it! 🚀</span>
}</code></pre>
            </div>
          </div>
        </div>

        <!-- Laravel Ecosystem -->
        <div class="group relative bg-gradient-to-br from-gray-900/50 to-gray-800/50 backdrop-blur-lg border border-gray-700/50 rounded-3xl p-6 md:p-8 hover:scale-105 transition-all duration-500 hover:border-blue-500/30">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          
          <div class="relative z-10">
            <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
              <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
            </div>
            
            <h3 class="text-xl md:text-2xl font-bold text-white mb-4 group-hover:text-blue-400 transition-colors">
              Laravel Native
            </h3>
            
            <p class="text-gray-400 mb-6 leading-relaxed">
              Built specifically for Laravel, leveraging Eloquent ORM, Service Container, and all Laravel conventions you already know and love.
            </p>

            <div class="bg-gray-800/50 rounded-xl p-3 md:p-4 border border-gray-700/30">
              <pre class="text-xs text-gray-300 overflow-x-auto whitespace-pre-wrap break-words"><code><span class="text-blue-400">Route</span>::<span class="text-yellow-400">middleware</span>(<span class="text-green-400">'auth:sanctum'</span>)
  -><span class="text-yellow-400">group</span>(<span class="text-purple-400">function</span> () {
      <span class="text-blue-400">Route</span>::<span class="text-yellow-400">restify</span>();
  });</code></pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import {
  BoltIcon,
  CircleStackIcon,
  ShieldCheckIcon,
  CpuChipIcon,
  CommandLineIcon,
  SwatchIcon
} from '@heroicons/vue/24/outline'
</script>