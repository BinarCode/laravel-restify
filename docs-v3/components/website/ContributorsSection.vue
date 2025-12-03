<template>
  <div class="relative py-12">
    <!-- Glass container -->
    <div class="relative bg-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-8 overflow-hidden">
      <!-- Background gradient effects -->
      <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-transparent to-cyan-500/5" />
      
      <div class="relative">
        <div class="text-center mb-10">
          <h2 class="text-3xl font-bold text-white mb-4">
            Built by Amazing Contributors
          </h2>
          <p class="text-lg text-gray-400 max-w-2xl mx-auto">
            Laravel Restify is made possible by our incredible community of contributors who help make it better every day
          </p>
        </div>
        
        <div class="relative overflow-hidden mb-8">
          <div class="absolute left-0 top-0 z-10 w-16 h-full bg-gradient-to-r from-gray-900/80 to-transparent pointer-events-none"></div>
          <div class="absolute right-0 top-0 z-10 w-16 h-full bg-gradient-to-l from-gray-900/80 to-transparent pointer-events-none"></div>
          
          <div class="flex animate-scroll-left space-x-4">
            <div class="flex space-x-4 shrink-0">
              <ContributorAvatar
                v-for="contributor in contributors"
                :key="contributor.username"
                :username="contributor.username"
                :github-id="contributor.githubId"
                :contributions="contributor.contributions"
              />
            </div>
            <div class="flex space-x-4 shrink-0">
              <ContributorAvatar
                v-for="contributor in contributors"
                :key="`dup-${contributor.username}`"
                :username="contributor.username"
                :github-id="contributor.githubId"
                :contributions="contributor.contributions"
              />
            </div>
          </div>
        </div>
        
        <div class="text-center">
          <a
            href="https://github.com/BinarCode/laravel-restify/graphs/contributors"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center text-red-400 hover:text-red-300 font-medium transition-colors group"
          >
            View All Contributors
            <ArrowRightIcon class="ml-2 w-4 h-4 group-hover:translate-x-1 transition-transform" />
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowRightIcon } from '@heroicons/vue/24/outline'
import ContributorAvatar from './ContributorAvatar.vue'

interface Contributor {
  username: string
  githubId: string
  contributions: number
}

const contributors: Contributor[] = [
  { username: 'binaryk', githubId: '6833714', contributions: 940 },
  { username: 'arthurkirkosa', githubId: '1099791', contributions: 26 },
  { username: 'CaReS0107', githubId: '32643188', contributions: 9 },
  { username: 'dsindrilaru', githubId: '87819215', contributions: 6 },
  { username: 'daniel-banciulea', githubId: '29707399', contributions: 4 },
  { username: 'cristijora', githubId: '15955045', contributions: 2 },
  { username: 'maicol07', githubId: '9463142', contributions: 2 },
  { username: 'ttungbmt', githubId: '12232155', contributions: 2 },
  { username: 'gabrielpetrescu', githubId: '33344024', contributions: 2 },
  { username: 'adam-code-labx', githubId: '53559175', contributions: 1 },
  { username: 'alexstewartja', githubId: '6935309', contributions: 1 },
  { username: 'Nonines', githubId: '105732838', contributions: 1 },
  { username: 'Eighke', githubId: '2734125', contributions: 1 },
  { username: 'ManukMinasyan', githubId: '2556185', contributions: 1 },
  { username: 'msucevan', githubId: '57497646', contributions: 1 }
]
</script>

<style scoped>
@keyframes scroll-left {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%);
  }
}

.animate-scroll-left {
  animation: scroll-left 30s linear infinite;
}

.animate-scroll-left:hover {
  animation-play-state: paused;
}
</style>
