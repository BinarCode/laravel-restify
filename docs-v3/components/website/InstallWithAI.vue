<template>
  <section class="py-16 md:py-24 bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-800">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-500/10 to-blue-500/10 border border-purple-500/20 backdrop-blur-sm rounded-full font-medium text-purple-600 dark:text-purple-300 mb-4">
          <span class="text-sm">🤖 AI-Powered Installation</span>
        </div>
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
          Install with AI
        </h2>
        <p class="text-lg text-gray-600 dark:text-gray-400">
          Copy this prompt and paste it into Claude, ChatGPT, or any AI assistant
        </p>
      </div>

      <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-xl">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 md:p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <div class="flex items-center space-x-3">
            <div class="flex space-x-2">
              <div class="w-3 h-3 rounded-full bg-red-500"></div>
              <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
              <div class="w-3 h-3 rounded-full bg-green-500"></div>
            </div>
            <span class="text-gray-600 dark:text-gray-400 text-sm font-medium">Install with AI</span>
          </div>
          <button
            @click="handleCopyPrompt"
            class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white rounded-lg transition-all duration-200 font-medium text-sm shadow-lg hover:shadow-xl"
            :class="{ 'from-green-600 to-green-600 hover:from-green-700 hover:to-green-700': copied }"
          >
            <ClipboardIcon v-if="!copied" class="h-4 w-4" />
            <CheckIcon v-else class="h-4 w-4" />
            <span>{{ copied ? 'Copied!' : 'Copy prompt' }}</span>
          </button>
        </div>

        <!-- Content -->
        <div class="p-6 md:p-8 bg-gray-50 dark:bg-gray-900">
          <div class="prose prose-sm md:prose dark:prose-invert max-w-none">
            <pre class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 overflow-x-auto"><code class="text-gray-800 dark:text-gray-200 text-sm leading-relaxed">{{ installPrompt }}</code></pre>
          </div>
        </div>
      </div>

      <div class="mt-8 text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          This prompt will guide an AI assistant to help you install and set up Laravel Restify in your project.
        </p>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ClipboardIcon, CheckIcon } from '@heroicons/vue/24/outline'

const { copied, copyToClipboard } = useClipboardCopy()

const installPrompt = `## Install Laravel Restify in my Laravel project

Please help me install and set up Laravel Restify, a package that transforms Laravel Eloquent models into JSON:API endpoints and MCP servers for AI agents.

### Step 1: Install the package

Run this command in your terminal:

\`\`\`shell
composer require binaryk/laravel-restify
\`\`\`

### Step 2: Run the setup command

After installation, scaffold the API structure:

\`\`\`shell
php artisan restify:setup
\`\`\`

This command will:
- Publish the \`config/restify.php\` configuration file
- Create \`providers/RestifyServiceProvider\` and register it
- Create the \`app/Restify\` directory for repositories
- Generate an abstract \`app/Restify/Repository.php\` base class
- Scaffold \`app/Restify/UserRepository\` for immediate use
- Create \`routes/ai.php\` with commented MCP server configuration

### Step 3: Run migrations

Complete the setup by running migrations:

\`\`\`shell
php artisan migrate
\`\`\`

### Step 4: Test the API

Your API is now ready! Test it with:

\`\`\`bash
GET /api/restify/users?perPage=10&page=1
\`\`\`

### Step 5 (Optional): Enable MCP Server for AI Agents

To make your API accessible to AI agents like Claude Desktop:

1. Add the MCP server to your \`routes/ai.php\` file:

\`\`\`php
use Binaryk\\LaravelRestify\\MCP\\RestifyServer;
use Laravel\\Mcp\\Facades\\Mcp;

Mcp::web('restify', RestifyServer::class)
    ->middleware(['auth:sanctum'])
    ->name('mcp.restify');
\`\`\`

2. Enable MCP tools in your repositories by adding the \`HasMcpTools\` trait:

\`\`\`php
use Binaryk\\LaravelRestify\\MCP\\Concerns\\HasMcpTools;

#[Model(User::class)]
class UserRepository extends Repository
{
    use HasMcpTools; // Enables MCP tools for AI agents

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name')->required(),
            field('email')->required(),
        ];
    }
}
\`\`\`

### Next Steps

After installation, please help me:
- Understand what was created during setup
- Learn how to create my first repository
- Test the API endpoints
- Optionally configure authentication with Sanctum

For more information, visit the Laravel Restify documentation at https://restify.binarcode.com`

function handleCopyPrompt(): void {
  copyToClipboard(installPrompt)
}
</script>
