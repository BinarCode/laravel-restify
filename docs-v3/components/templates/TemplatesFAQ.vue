<template>
  <section class="py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Section Header -->
      <div class="text-center mb-12">
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
          Frequently Asked <span class="bg-gradient-to-r from-blue-500 to-cyan-500 bg-clip-text text-transparent">Questions</span>
        </h2>
        <p class="text-gray-600 dark:text-gray-400">
          Everything you need to know about our templates
        </p>
      </div>

      <!-- FAQ Accordion -->
      <div class="bg-white/50 dark:bg-gray-900/30 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-2xl p-6">
        <UAccordion :items="faqItems" />
      </div>

      <!-- Contact CTA -->
      <div class="mt-10 text-center">
        <p class="text-gray-600 dark:text-gray-400 mb-4">
          Still have questions?
        </p>
        <a 
          href="https://github.com/BinarCode/laravel-restify/discussions"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center text-blue-500 hover:text-blue-600 font-medium transition-colors"
        >
          <ChatBubbleLeftRightIcon class="w-5 h-5 mr-2" />
          Ask in GitHub Discussions
          <ArrowTopRightOnSquareIcon class="w-4 h-4 ml-1" />
        </a>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { 
  ChatBubbleLeftRightIcon,
  ArrowTopRightOnSquareIcon 
} from '@heroicons/vue/24/outline'

const faqItems = [
  {
    label: 'What happens after I purchase a template?',
    content: 'After purchase, you\'ll receive immediate access to download the template along with complete documentation. You\'ll also get access to our private Discord channel for support and future updates based on your plan\'s security update period.'
  },
  {
    label: 'Can I use the template for multiple projects?',
    content: 'Yes! Each template license allows you to use it on unlimited personal projects. For commercial projects where you\'re building for clients, each client project requires a separate license. The Free Starter Kit can be used on unlimited projects without restrictions.'
  },
  {
    label: 'Do I need Laravel Restify installed separately?',
    content: 'No, the templates come with Laravel Restify already integrated and configured. Everything is set up and ready to go. You just need to run the installation commands and you\'re ready to start building.'
  },
  {
    label: 'What\'s included in the security updates?',
    content: 'Security updates include patches for any vulnerabilities discovered in the template code, dependency updates, and compatibility fixes for new Laravel and PHP versions. The duration varies by plan: 3 months for Starter, 6 months for Essential, 1 year for Pro, and lifetime for Premium.'
  },
  {
    label: 'Can I request custom features or modifications?',
    content: 'Absolutely! We offer custom template development services. Click the "Get Custom Template" button to start a conversation about your specific requirements. We\'ve built enterprise-grade solutions for companies worldwide.'
  },
  {
    label: 'Is there a refund policy?',
    content: 'Yes, we offer a 14-day money-back guarantee. If the template doesn\'t meet your expectations or doesn\'t work as documented, contact us for a full refund. The Free Starter Kit is, of course, always free.'
  },
  {
    label: 'What\'s the difference between MCP Server and MCP Client?',
    content: 'The MCP Server exposes your Laravel data through the Model Context Protocol, allowing AI agents (like Claude) to interact with your application. The MCP Client is a pre-built chat interface that connects to AI models and can use your MCP tools to query and modify data intelligently.'
  }
]
</script>
