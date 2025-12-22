<template>
  <section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="relative overflow-hidden">
        <div class="relative bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-3xl p-8 md:p-12 lg:p-16">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
            <!-- Content -->
            <div>
              <div class="inline-flex items-center px-3 py-1 bg-orange-500/10 border border-orange-500/20 rounded-full text-orange-500 text-sm font-medium mb-4">
                <WrenchScrewdriverIcon class="w-4 h-4 mr-2" />
                Custom Development
              </div>

              <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                Need Something <span class="bg-gradient-to-r from-orange-500 to-amber-500 bg-clip-text text-transparent">Unique</span>?
              </h2>

              <p class="text-lg text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                Our team at BinarCode has built enterprise-grade Laravel applications for companies worldwide. 
                We can create a custom template tailored specifically to your project requirements.
              </p>

              <ul class="space-y-3 mb-8">
                <li v-for="benefit in benefits" :key="benefit" class="flex items-start gap-3">
                  <CheckCircleIcon class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" />
                  <span class="text-gray-700 dark:text-gray-300">{{ benefit }}</span>
                </li>
              </ul>

              <div class="flex flex-col sm:flex-row gap-4">
                <AppButton
                  variant="primary"
                  size="md"
                  href="https://www.binarcode.com/estimate-project"
                  :icon="ChatBubbleLeftRightIcon"
                >
                  Start a Conversation
                </AppButton>
                <AppButton
                  variant="secondary"
                  size="md"
                  href="https://www.binarcode.com/recent-work"
                  :icon="FolderOpenIcon"
                >
                  View Our Work
                </AppButton>
              </div>
            </div>

            <!-- Visual Element -->
            <div class="hidden lg:block">
              <div class="relative">
                <!-- Code Preview Card -->
                <TerminalWindow title="CustomRepository.php" :tilted="true">
                  <pre class="text-sm font-mono overflow-hidden"><code class="text-gray-300"><span class="text-purple-400">class</span> <span class="text-yellow-400">CustomRepository</span> <span class="text-purple-400">extends</span> <span class="text-blue-400">Repository</span>
{
    <span class="text-gray-500">// Your custom business logic</span>
    <span class="text-purple-400">public function</span> <span class="text-yellow-400">actions</span>(): array
    {
        <span class="text-purple-400">return</span> [
            <span class="text-orange-400">CustomAction</span>::<span class="text-purple-400">class</span>,
            <span class="text-orange-400">YourWorkflow</span>::<span class="text-purple-400">class</span>,
        ];
    }
}</code></pre>
                </TerminalWindow>

                <!-- Floating Badge -->
                <div class="absolute -top-4 -right-4 bg-gradient-to-r from-orange-500 to-amber-500 text-white px-4 py-2 rounded-full text-sm font-bold shadow-lg z-10">
                  100% Custom
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { 
  WrenchScrewdriverIcon, 
  CheckCircleIcon,
  ChatBubbleLeftRightIcon,
  FolderOpenIcon
} from '@heroicons/vue/24/outline'
import AppButton from '~/components/ui/AppButton.vue'
import TerminalWindow from '~/components/website/TerminalWindow.vue'

const benefits = [
  'Architecture designed for your specific use case',
  'Custom integrations with your existing tools',
  'White-label ready with your branding',
  'Priority support and maintenance options',
  'Full source code ownership'
]
</script>
