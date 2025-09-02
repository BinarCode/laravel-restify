<template>
  <NuxtLayout>
    <ContentDoc>
      <!-- Use content from index.md -->
      <template #default="{ doc }">
        <!-- Clean Hero Section -->
        <Hero>
          <template #subtitle>{{ doc.description }}</template>
        </Hero>

        <!-- Clean Feature Grid -->
        <FeatureGrid />

        <!-- Clean How It Works Section -->
        <HowItWorks />

        <!-- Code Preview Section -->
        <section class="py-16 relative">
          <div class="mx-auto px-4">
            <h2 class="text-4xl font-bold text-center text-gray-900 dark:text-white mb-4">
              One Codebase, Two Outputs
            </h2>
            <p class="text-xl text-center text-gray-600 dark:text-gray-400 mb-16 max-w-4xl mx-auto">
              Write your models once, get both REST APIs for humans and MCP servers for AI agents automatically.
            </p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <!-- REST API Example -->
              <div class="group">
                <div class="flex items-center mb-4">
                  <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center mr-3">
                    <span class="text-sm">👥</span>
                  </div>
                  <h3 class="text-2xl font-bold text-gray-900 dark:text-white">REST API</h3>
                  <span class="ml-3 text-sm text-gray-500 dark:text-gray-400">For humans & applications</span>
                </div>
                <div class="bg-gray-900 dark:bg-gray-950 rounded-xl p-8 shadow-lg group-hover:shadow-xl transition-shadow">
                  <div class="text-sm text-gray-400 mb-3">GET /api/restify/posts</div>
                  <pre class="text-green-400 text-base overflow-x-auto"><code>{
  "data": [
    {
      "id": "1",
      "type": "posts",
      "attributes": {
        "title": "Laravel Restify Guide",
        "content": "Build APIs fast...",
        "published_at": "2024-01-15"
      }
    }
  ],
  "links": {
    "self": "/api/restify/posts",
    "next": "/api/restify/posts?page=2"
  }
}</code></pre>
                </div>
              </div>

              <!-- MCP Server Example -->
              <div class="group">
                <div class="flex items-center mb-4">
                  <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center mr-3">
                    <span class="text-sm">🤖</span>
                  </div>
                  <h3 class="text-2xl font-bold text-gray-900 dark:text-white">MCP Server</h3>
                  <span class="ml-3 text-sm text-gray-500 dark:text-gray-400">For AI agents</span>
                </div>
                <div class="bg-gray-900 dark:bg-gray-950 rounded-xl p-8 shadow-lg group-hover:shadow-xl transition-shadow">
                  <div class="text-sm text-gray-400 mb-3">AI Agent Request</div>
                  <pre class="text-blue-400 text-base overflow-x-auto"><code>{
  "method": "tools/call",
  "params": {
    "name": "posts-index-tool",
    "arguments": {
      "page": 1,
      "perPage": 5,
      "published_at": "2024-01-15"
    }
  }
}

// AI Agent receives:
{
  "tools": [
    {
      "name": "posts-index-tool",
      "description": "Retrieve a paginated list of Post records from the posts repository with filtering, sorting, and search capabilities.",
      "inputSchema": {
        "type": "object",
        "properties": {
          "page": {
            "type": "number",
            "description": "Page number for pagination"
          },
          "perPage": {
            "type": "number",
            "description": "Number of posts per page"
          },
          "search": {
            "type": "string",
            "description": "Search term to filter posts by title or content"
          },
          "sort": {
            "type": "string",
            "description": "Sorting criteria (e.g., sort=title or sort=-published_at for descending)"
          },
          "title": {
            "type": "string",
            "description": "Filter by exact match for title (e.g., title=Laravel). Accepts negation with -title=value"
          },
          "published_at": {
            "type": "string",
            "description": "Filter by publication date (e.g., published_at=2024-01-15)"
          }
        }
      }
    }
  ]
}</code></pre>
                </div>
              </div>
            </div>

            <!-- Simple Model Code -->
            <div class="mt-16 text-center">
              <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">
                All from this simple model:
              </h3>
              <div class="max-w-5xl mx-auto bg-gray-900 dark:bg-gray-950 rounded-xl p-10 shadow-xl">
                <pre class="text-emerald-400 text-base text-left overflow-x-auto"><code>#[Model(Post::class)]
class PostRepository extends Repository
{
    use HasMcpTools;

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title')->required()->matchable(),
            field('content'),
            field('published_at')->rules('date')->matchable(),
        ];
    }
}</code></pre>
              </div>
              <p class="text-lg text-gray-600 dark:text-gray-400 mt-6">
                That's it! No configuration needed.
              </p>
            </div>
          </div>
        </section>

        <!-- Contributors Section -->
        <section class="py-16 overflow-hidden">
          <div class="max-w-6xl mx-auto text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Built by Amazing Contributors
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400">
              Join the community of developers building Laravel Restify
            </p>
          </div>

          <!-- Scrolling Contributors -->
          <div class="relative">
            <div class="flex animate-scroll space-x-6">
              <div
                v-for="contributor in [...contributors, ...contributors]"
                :key="`${contributor.login}-${Math.random()}`"
                class="flex-shrink-0"
              >
                <a
                  :href="contributor.html_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="block group"
                >
                  <div class="relative">
                    <img
                      :src="contributor.avatar_url"
                      :alt="contributor.login"
                      class="w-16 h-16 rounded-full border-2 border-gray-200 dark:border-gray-700 group-hover:border-primary-400 transition-all duration-300 group-hover:scale-110 shadow-lg"
                    >
                    <div class="absolute inset-0 rounded-full bg-primary-500/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                  </div>
                  <p class="text-sm text-gray-700 dark:text-gray-300 mt-2 text-center font-medium">
                    {{ contributor.login }}
                  </p>
                </a>
              </div>
            </div>
          </div>

          <!-- Call to Action -->
          <div class="text-center mt-12">
            <a
              href="https://github.com/BinarCode/laravel-restify"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center px-6 py-3 text-base font-medium rounded-lg text-white bg-gradient-to-r from-primary-600 to-blue-600 hover:from-primary-700 hover:to-blue-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1"
            >
              <svg class="mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
              </svg>
              Become a Contributor
            </a>
          </div>
        </section>

        <!-- Other content from index.md (templates, playground, etc.) -->
        <div class="prose-docs max-w-4xl mx-auto py-16">
          <ContentRenderer :value="doc" />
        </div>
      </template>

      <!-- Fallback if no content found -->
      <template #not-found>
        <div class="text-center py-16">
          <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Welcome to Laravel Restify
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mb-8">
            Build amazing REST APIs with Laravel.
          </p>
          <NuxtLink
            to="/quickstart"
            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
          >
            Get Started
          </NuxtLink>
        </div>
      </template>
    </ContentDoc>
  </NuxtLayout>
</template>

<script setup lang="ts">
import Hero from '~/components/Hero.vue'
import FeatureGrid from '~/components/FeatureGrid.vue'
import HowItWorks from '~/components/HowItWorks.vue'

interface Contributor {
  login: string
  avatar_url: string
  html_url: string
  contributions: number
}

const contributors = ref<Contributor[]>([])

// Fetch contributors from GitHub API
onMounted(async () => {
  try {
    const response = await fetch('https://api.github.com/repos/BinarCode/laravel-restify/contributors?per_page=50')
    if (response.ok) {
      contributors.value = await response.json()
    }
  } catch (error) {
    console.warn('Failed to fetch contributors:', error)
    // Fallback data if API fails
    contributors.value = [
      { login: 'BinarCode', avatar_url: 'https://github.com/BinarCode.png', html_url: 'https://github.com/BinarCode', contributions: 100 },
      { login: 'eduardlupacescu', avatar_url: 'https://github.com/eduardlupacescu.png', html_url: 'https://github.com/eduardlupacescu', contributions: 50 }
    ]
  }
})

useHead({
  title: 'Laravel Restify - Build amazing REST APIs with Laravel',
  meta: [
    {
      name: 'description',
      content: 'Laravel Restify provides a simple, powerful way to create JSON:API compliant REST APIs with Laravel. Fast, efficient, and flexible.'
    }
  ]
})
</script>

<style scoped>
@keyframes scroll {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%);
  }
}

.animate-scroll {
  animation: scroll 30s linear infinite;
  width: calc(200%);
}

.animate-scroll:hover {
  animation-play-state: paused;
}
</style>
