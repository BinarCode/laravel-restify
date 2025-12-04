<template>
  <section class="py-24 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Section Header -->
      <div class="text-center mb-16 md:mb-20 px-4 md:px-0">
        <div class="inline-flex items-center px-4 md:px-6 py-3 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/20 backdrop-blur-sm rounded-full text-sm font-medium text-blue-600 dark:text-blue-300 mb-6 md:mb-8">
          <BoltIcon class="w-4 h-4 mr-2" />
          One Codebase, Infinite Possibilities
        </div>
        
        <h2 class="text-3xl md:text-4xl lg:text-6xl font-black tracking-tight mb-6 md:mb-8">
          <span class="bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-400 bg-clip-text text-transparent">
            Write Once,
          </span>
          <br />
          <span class="bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-400 bg-clip-text text-transparent">
            Deploy Everywhere
          </span>
        </h2>
        
        <p class="text-lg md:text-xl text-gray-600 dark:text-gray-300 max-w-4xl mx-auto leading-relaxed">
          Transform your Laravel models into production-ready APIs and AI-compatible servers 
          with <span class="text-blue-500 dark:text-blue-400 font-semibold">zero configuration</span>. 
          The future of API development is here.
        </p>
      </div>

      <!-- Feature Showcase -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8 px-4 md:px-0">
        <!-- JSON:API Compliant -->
        <TerminalWindow title="json-api.json">
          <div class="mb-4">
            <div class="flex items-center mb-3">
              <CircleStackIcon class="h-5 w-5 text-blue-400 mr-2" />
              <span class="text-gray-200 font-medium text-sm">JSON:API Compliant</span>
            </div>
            <p class="text-gray-400 text-xs mb-4 leading-relaxed">
              Automatically generates endpoints that follow JSON:API specifications with proper relationships, pagination, filtering, and sparse fieldsets.
            </p>
          </div>
          <pre class="text-xs text-gray-300 overflow-x-auto whitespace-pre-wrap break-words font-mono"><code>{
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"id"</span>: <span class="text-green-400">"1"</span>,
    <span class="text-blue-400">"type"</span>: <span class="text-green-400">"posts"</span>,
    <span class="text-blue-400">"attributes"</span>: {
      <span class="text-blue-400">"title"</span>: <span class="text-green-400">"Laravel Restify"</span>,
      <span class="text-blue-400">"published_at"</span>: <span class="text-green-400">"2024-01-15"</span>
    },
    <span class="text-blue-400">"relationships"</span>: {
      <span class="text-blue-400">"author"</span>: {
        <span class="text-blue-400">"data"</span>: {<span class="text-blue-400">"id"</span>: <span class="text-green-400">"1"</span>, <span class="text-blue-400">"type"</span>: <span class="text-green-400">"users"</span>}
      }
    }
  }
}</code></pre>
        </TerminalWindow>

        <!-- Policy-Based Authorization -->
        <TerminalWindow title="PostPolicy.php">
          <div class="mb-4">
            <div class="flex items-center mb-3">
              <ShieldCheckIcon class="h-5 w-5 text-green-400 mr-2" />
              <span class="text-gray-200 font-medium text-sm">Policy-Based Auth</span>
            </div>
            <p class="text-gray-400 text-xs mb-4 leading-relaxed">
              Built-in integration with Laravel's authorization system. Use policies for fine-grained access control on every endpoint.
            </p>
          </div>
          <pre class="text-xs text-gray-300 overflow-x-auto whitespace-pre-wrap break-words font-mono"><code><span class="text-purple-400">class</span> <span class="text-yellow-400">PostPolicy</span>
{
    <span class="text-purple-400">public function</span> <span class="text-yellow-400">index</span>(<span class="text-blue-400">User</span> <span class="text-white">$user</span>)
    {
        <span class="text-purple-400">return</span> <span class="text-white">$user</span>-><span class="text-yellow-400">can</span>(<span class="text-green-400">'view posts'</span>);
    }
    
    <span class="text-purple-400">public function</span> <span class="text-yellow-400">store</span>(<span class="text-blue-400">User</span> <span class="text-white">$user</span>)
    {
        <span class="text-purple-400">return</span> <span class="text-white">$user</span>-><span class="text-yellow-400">hasRole</span>(<span class="text-green-400">'admin'</span>);
    }
}</code></pre>
        </TerminalWindow>

        <!-- Advanced Filtering -->
        <TerminalWindow title="api-request.http">
          <div class="mb-4">
            <div class="flex items-center mb-3">
              <MagnifyingGlassIcon class="h-5 w-5 text-purple-400 mr-2" />
              <span class="text-gray-200 font-medium text-sm">Smart Filtering</span>
            </div>
            <p class="text-gray-400 text-xs mb-4 leading-relaxed">
              Complex queries, sorting, filtering, and search across relationships without any extra configuration.
            </p>
          </div>
          <pre class="text-xs text-gray-300 overflow-x-auto whitespace-pre-wrap break-words font-mono"><code><span class="text-green-400">GET</span> /api/restify/posts?
  <span class="text-blue-400">filter[title]</span>=Laravel&
  <span class="text-blue-400">filter[status]</span>=published&
  <span class="text-blue-400">sort</span>=-created_at&
  <span class="text-blue-400">include</span>=author,comments&
  <span class="text-blue-400">page[size]</span>=10</code></pre>
        </TerminalWindow>

        <!-- MCP Server Support -->
        <TerminalWindow title="routes/web.php">
          <div class="mb-4">
            <div class="flex items-center mb-3">
              <CpuChipIcon class="h-5 w-5 text-cyan-400 mr-2" />
              <span class="text-gray-200 font-medium text-sm">AI Agent Ready</span>
            </div>
            <p class="text-gray-400 text-xs mb-4 leading-relaxed">
              Generate Model Context Protocol servers that allow AI agents to interact with your APIs using structured, type-safe tools.
            </p>
          </div>
          <pre class="text-xs text-gray-300 overflow-x-auto whitespace-pre-wrap break-words font-mono"><code><span class="text-gray-500">// Enable MCP for AI agents</span>
<span class="text-blue-400">Mcp</span>::<span class="text-yellow-400">web</span>(<span class="text-green-400">'restify'</span>, <span class="text-orange-400">RestifyServer</span>::<span class="text-purple-400">class</span>)
    -><span class="text-yellow-400">middleware</span>([<span class="text-green-400">'auth:sanctum'</span>])
    -><span class="text-yellow-400">name</span>(<span class="text-green-400">'mcp.restify'</span>);</code></pre>
        </TerminalWindow>

        <!-- Zero Configuration -->
        <TerminalWindow title="UserRepository.php">
          <div class="mb-4">
            <div class="flex items-center mb-3">
              <BoltIcon class="h-5 w-5 text-yellow-400 mr-2" />
              <span class="text-gray-200 font-medium text-sm">Zero Configuration</span>
            </div>
            <p class="text-gray-400 text-xs mb-4 leading-relaxed">
              Start building in seconds. Just create a repository class pointing to your model and you're ready.
            </p>
          </div>
          <pre class="text-xs text-gray-300 overflow-x-auto whitespace-pre-wrap break-words font-mono"><code><span class="text-purple-400">class</span> <span class="text-yellow-400">UserRepository</span> <span class="text-purple-400">extends</span> <span class="text-blue-400">Repository</span>
{
    <span class="text-purple-400">public static</span> <span class="text-blue-400">string</span> <span class="text-white">$model</span> = <span class="text-orange-400">User</span>::<span class="text-purple-400">class</span>;
    
    <span class="text-gray-500">// That's it! Full CRUD API ready 🚀</span>
}</code></pre>
        </TerminalWindow>

        <!-- Laravel Ecosystem -->
        <TerminalWindow title="routes/api.php">
          <div class="mb-4">
            <div class="flex items-center mb-3">
              <HeartIcon class="h-5 w-5 text-red-400 mr-2" />
              <span class="text-gray-200 font-medium text-sm">Laravel Native</span>
            </div>
            <p class="text-gray-400 text-xs mb-4 leading-relaxed">
              Built specifically for Laravel, leveraging Eloquent ORM, Service Container, and all Laravel conventions.
            </p>
          </div>
          <pre class="text-xs text-gray-300 overflow-x-auto whitespace-pre-wrap break-words font-mono"><code><span class="text-gray-500">// Works with Laravel middleware</span>
<span class="text-blue-400">Route</span>::<span class="text-yellow-400">middleware</span>(<span class="text-green-400">'auth:sanctum'</span>)
    -><span class="text-yellow-400">group</span>(<span class="text-purple-400">function</span> () {
        <span class="text-blue-400">Route</span>::<span class="text-yellow-400">restify</span>();
    });</code></pre>
        </TerminalWindow>
      </div>
    </div>
  </section>
</template>

<script setup>
import {
  BoltIcon,
  CircleStackIcon,
  ShieldCheckIcon,
  MagnifyingGlassIcon,
  CpuChipIcon,
  HeartIcon
} from '@heroicons/vue/24/outline'
import TerminalWindow from './TerminalWindow.vue'
</script>