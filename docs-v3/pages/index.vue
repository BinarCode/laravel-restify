<template>
  <NuxtLayout name="website">
    <!-- Hero section -->
    <section class="relative overflow-hidden bg-white dark:bg-gray-900">
      <!-- Background gradient -->
      <div class="absolute inset-0 bg-gradient-to-br from-red-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-900 dark:to-blue-900/20"></div>

      <!-- Animated background elements -->
      <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-red-200/30 dark:bg-red-800/20 rounded-full mix-blend-multiply filter blur-xl animate-pulse"></div>
        <div class="absolute -bottom-32 -left-40 w-80 h-80 bg-blue-200/30 dark:bg-blue-800/20 rounded-full mix-blend-multiply filter blur-xl animate-pulse" style="animation-delay: 2s"></div>
      </div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <!-- Open Source Badge -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center px-4 py-2 bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 rounded-full text-sm font-medium">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
            Open Source Laravel Package
          </div>
        </div>

        <div class="text-center">
          <!-- Main Title -->
          <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold tracking-tight text-gray-900 dark:text-white mb-6">
            Build APIs for <span class="text-red-500">Humans & AI Agents</span> via MCP
          </h1>

          <!-- Subtitle -->
          <p class="text-xl md:text-2xl text-gray-600 dark:text-gray-300 mb-8 leading-relaxed">
            Transform your Laravel Eloquent models into JSON:API compliant endpoints 
            and MCP servers automatically. No boilerplate, just results.
          </p>

          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
            <a
              href="https://restify.binarcode.com/docs"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 h-11 px-8 bg-red-500 text-white hover:bg-red-600 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"
            >
              <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Get Started
              <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
              </svg>
            </a>

            <a
              href="https://github.com/binaryk/laravel-restify"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 h-11 px-8 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"
            >
              <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
              </svg>
              View on GitHub
            </a>
          </div>
        </div>

        <!-- Code Example Section -->
        <div class="mt-20">
          <div class="bg-gray-900 dark:bg-gray-950 rounded-xl p-8 shadow-2xl max-w-5xl mx-auto">
            <!-- Terminal-like header -->
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-700">
              <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
              </div>
              <div class="text-gray-400 text-sm">composer require binaryk/laravel-restify</div>
            </div>

            <!-- Code content -->
            <div class="space-y-6">
              <!-- Step 1: Create Repository -->
              <div class="text-gray-400 text-sm mb-2">// Create a repository</div>
              <pre class="text-green-400 text-sm overflow-x-auto whitespace-pre-wrap"><code>class PostRepository extends Repository
{
    public static string $model = Post::class;
}</code></pre>

              <!-- Step 2: MCP Server -->
              <div class="text-gray-400 text-sm mt-6 mb-2">// Enable MCP server for AI agents</div>
              <pre class="text-green-400 text-sm overflow-x-auto whitespace-pre-wrap"><code>Mcp::web('restify', RestifyServer::class);</code></pre>

              <!-- Step 3: Ready -->
              <div class="text-gray-400 text-sm mt-6 mb-2">// That's it! Your API is ready</div>
              <div class="space-y-1 text-sm">
                <div class="text-gray-400">// GET /api/restify/posts</div>
                <div class="text-gray-400">// POST /api/restify/posts</div>
                <div class="text-gray-400">// + MCP tools for AI agents</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- One Codebase, Two Outputs -->
    <section class="py-20 bg-white dark:bg-gray-900">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Badge -->
        <div class="flex justify-center mb-8">
          <div class="inline-flex items-center px-4 py-2 rounded-full border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
            <svg class="w-4 h-4 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span class="text-sm font-medium text-red-600 dark:text-red-400">One Codebase, Two Outputs</span>
          </div>
        </div>

        <!-- Main Heading -->
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-6xl lg:text-7xl font-bold tracking-tight text-gray-900 dark:text-white mb-6 leading-tight">
            Write your models once, get both
            <span class="text-red-600 dark:text-red-400">REST APIs</span> for humans and
            <span class="text-blue-600 dark:text-blue-400">MCP servers</span> for AI agents automatically.
          </h2>
        </div>

        <!-- Code Demo -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          <!-- Laravel Models -->
          <div>
            <div class="flex items-center mb-4">
              <div class="w-8 h-8 bg-gray-900 dark:bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-white dark:text-gray-900" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Laravel Models</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Your existing Eloquent models</p>
              </div>
            </div>
            <div class="bg-gray-900 dark:bg-gray-950 rounded-lg p-6">
              <pre class="text-sm text-green-400 overflow-x-auto whitespace-pre-wrap"><code>#[Model(Post::class)]
class PostRepository extends Repository
{
    use HasMcpTools;

    public function fields(): array
    {
        return [
            field('title')->required(),
            field('content'),
            field('published_at'),
        ];
    }
}</code></pre>
            </div>
          </div>

          <!-- Outputs -->
          <div class="space-y-6">
            <!-- REST API -->
            <div>
              <div class="flex items-center mb-3">
                <div class="w-6 h-6 bg-red-500 rounded flex items-center justify-center mr-2">
                  <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
                  </svg>
                </div>
                <div>
                  <h4 class="font-semibold text-gray-900 dark:text-white">REST API</h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400">For humans & applications</p>
                </div>
              </div>
              <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
                <pre class="text-xs text-gray-800 dark:text-gray-200"><code>GET  /api/restify/posts
POST /api/restify/posts
PATCH /api/restify/posts/{id}
DELETE /api/restify/posts/{id}</code></pre>
              </div>
            </div>

            <!-- MCP Server -->
            <div>
              <div class="flex items-center mb-3">
                <div class="w-6 h-6 bg-blue-500 rounded flex items-center justify-center mr-2">
                  <span class="text-xs">🤖</span>
                </div>
                <div>
                  <h4 class="font-semibold text-gray-900 dark:text-white">MCP Server</h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400">For AI agents</p>
                </div>
              </div>
              <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
                <pre class="text-xs text-gray-800 dark:text-gray-200"><code>tools/call:
  posts-index-tool
  posts-create-tool
  posts-update-tool
  posts-delete-tool</code></pre>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Key Features -->
    <section class="py-20 bg-gray-50 dark:bg-gray-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-6xl lg:text-7xl font-bold tracking-tight text-gray-900 dark:text-white mb-4">
            Key Features
          </h2>
          <p class="text-xl text-gray-600 dark:text-gray-400">
            Everything you need to build modern APIs
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <!-- JSON:API Compliant -->
          <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-xl flex items-center justify-center mb-6">
              <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">JSON:API Compliant</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Automatically generate endpoints that follow JSON:API specifications with proper relationships, pagination, and filtering.</p>

            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
              <pre class="text-xs text-gray-700 dark:text-gray-300 overflow-x-auto whitespace-pre-wrap"><code>{
  "data": {
    "id": "1",
    "type": "posts",
    "attributes": {
      "title": "Laravel Restify"
    }
  },
  "relationships": {
    "author": {
      "data": {"id": "1", "type": "users"}
    }
  }
}</code></pre>
            </div>
          </div>

          <!-- Policy-Based Authorization -->
          <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-xl flex items-center justify-center mb-6">
              <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Policy-Based Authorization</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Leverage Laravel's built-in authorization system with policies for fine-grained access control on every endpoint.</p>

            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
              <pre class="text-xs text-gray-700 dark:text-gray-300 overflow-x-auto whitespace-pre-wrap"><code>class PostPolicy
{
    public function index(User $user): bool
    {
        return $user->can('view posts');
    }

    public function show(User $user, Post
$post): bool
    {
        return $user->id === $post->user_id;
    }
}</code></pre>
            </div>
          </div>

          <!-- Advanced Filtering -->
          <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-xl flex items-center justify-center mb-6">
              <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Advanced Filtering</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Built-in support for complex queries, sorting, filtering, and search across relationships without extra configuration.</p>

            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
              <pre class="text-xs text-gray-700 dark:text-gray-300 overflow-x-auto whitespace-pre-wrap"><code>GET /api/restify/posts?filter[title]=Laravel
&filter[status]=published
&sort=-created_at
&include=author,comments</code></pre>
            </div>
          </div>

          <!-- MCP Server Support -->
          <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-xl flex items-center justify-center mb-6">
              <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">MCP Server Support</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Generate Model Context Protocol servers for AI agents to interact with your APIs using structured data exchange.</p>

            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
              <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><code>use Binaryk\LaravelRestify\MCP\RestifyServer;
use Laravel\Mcp\Facades\Mcp;

// Web-based MCP server with authentication
Mcp::web('restify', RestifyServer::class)
    ->middleware(['auth:sanctum'])
    ->name('mcp.restify');</code></pre>
            </div>
          </div>

          <!-- Zero Configuration -->
          <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-xl flex items-center justify-center mb-6">
              <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Zero Configuration</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Get started in minutes. Just create a repository class and you're ready to go with a fully functional API.</p>

            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
              <pre class="text-xs text-gray-700 dark:text-gray-300 overflow-x-auto whitespace-pre-wrap"><code>class UserRepository extends Repository
{
    public static string $model = User::class;

    // That's it! Your API is ready
}</code></pre>
            </div>
          </div>

          <!-- Laravel Ecosystem -->
          <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-xl flex items-center justify-center mb-6">
              <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Laravel Ecosystem</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Built specifically for Laravel, leveraging Eloquent ORM, Service Container, and all Laravel conventions you know and love.</p>

            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
              <pre class="text-xs text-gray-700 dark:text-gray-300 overflow-x-auto whitespace-pre-wrap"><code>// Works with your existing Laravel app
Route::middleware('auth:sanctum')
    ->group(function () {
        Route::restify();
    });
});</code></pre>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Join the Community -->
    <section class="py-20 bg-white dark:bg-gray-900">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-6xl lg:text-7xl font-bold tracking-tight text-gray-900 dark:text-white mb-6">
            Join the Community
          </h2>
          <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
            Laravel Restify is open source and community-driven. Get help, contribute, and help shape the future of API development in Laravel.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          <!-- Open Source -->
          <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8 text-center flex flex-col">
            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
              <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Open Source</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-8 flex-1">MIT licensed and fully open source</p>
            <a
              href="https://github.com/BinarCode/laravel-restify"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors w-full"
            >
              <svg class="mr-2 h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
              </svg>
              Star on GitHub
            </a>
          </div>

          <!-- Documentation -->
          <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8 text-center flex flex-col">
            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
              <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Documentation</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-8 flex-1">Comprehensive guides and API reference</p>
            <NuxtLink
              to="/docs"
              class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors w-full"
            >
              Read Docs
            </NuxtLink>
          </div>

          <!-- Discussions -->
          <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8 text-center flex flex-col">
            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
              <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2v-6a2 2 0 012-2h8z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Discussions</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-8 flex-1">Get help and share your experience</p>
            <a
              href="https://github.com/BinarCode/laravel-restify/discussions"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors w-full"
            >
              Join Discussion
            </a>
          </div>

          <!-- BinarCode -->
          <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8 text-center flex flex-col">
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
              <span class="text-2xl font-bold text-red-600 dark:text-red-400">B</span>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">BinarCode</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-8 flex-1">Maintained by the BinarCode team</p>
            <a
              href="https://binarcode.com"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors w-full"
            >
              Visit Website
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Ready to Build CTA -->
    <section class="py-20 bg-gray-900 dark:bg-gray-950">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
          Ready to build amazing APIs?
        </h2>
        <p class="text-xl text-gray-300 mb-12">
          Get started with Laravel Restify today and transform how you build APIs.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <a
            href="https://restify.binarcode.com/docs"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 h-11 px-8 bg-white text-gray-900 hover:bg-gray-100 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"
          >
            Get Started Now
          </a>

          <a
            href="https://github.com/binaryk/laravel-restify"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 h-11 px-8 border border-white bg-transparent text-white hover:bg-white hover:text-gray-900 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"
          >
            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.30.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
            </svg>
            View on GitHub
          </a>
        </div>
      </div>
    </section>
  </NuxtLayout>
</template>

<script setup lang="ts">
useHead({
  title: 'Laravel Restify - Build APIs with Zero Configuration',
  meta: [
    {
      name: 'description',
      content: 'Transform your Laravel Eloquent models into JSON:API compliant endpoints and MCP servers automatically. No boilerplate, just results.'
    }
  ]
})
</script>

<style scoped>
@media (min-width: 768px) {
  .md\:text-6xl {
    font-size: 3.75rem;
    line-height: 1;
  }
}
</style>
