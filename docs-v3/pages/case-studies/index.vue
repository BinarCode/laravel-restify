<template>
  <NuxtLayout name="website">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <!-- Header -->
      <div class="text-center mb-16">
        <h1 class="text-5xl font-bold text-gray-900 dark:text-white mb-6">
          Case Studies
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 max-w-4xl mx-auto">
          Real-world applications built with Laravel Restify and the BinarCode ecosystem.
          See how leading companies leverage our tools to build scalable, robust
          solutions.
        </p>
      </div>

      <!-- Case Studies Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-20">
        <!-- GiveLegacy -->
        <CaseStudyCard
          title="GiveLegacy"
          subtitle="Healthcare Legacy Platform"
          category="Healthcare"
          description="A comprehensive healthcare platform that helps families preserve and share medical histories, creating digital legacies for future generations."
          stats="Trusted by families worldwide"
          :tags="['Laravel', 'Vue.js', 'Healthcare APIs']"
          href="https://www.givelegacy.com/"
          accent="rose"
        >
          <template #icon>
            <svg class="h-6 w-6 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
          </template>
        </CaseStudyCard>

        <!-- Growee.ai -->
        <CaseStudyCard
          title="Growee.ai"
          subtitle="AI-Powered HR Management"
          category="Human Resources"
          description="Advanced HR tool leveraging artificial intelligence to streamline recruitment, employee management, and organizational growth for modern businesses."
          stats="100+ companies using the platform"
          :tags="['Laravel Restify', 'AI Integration', 'React']"
          href="https://www.growee.ai/"
          accent="blue"
        >
          <template #icon>
            <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </template>
        </CaseStudyCard>

        <!-- Construction Partner -->
        <CaseStudyCard
          title="Construction Partner"
          subtitle="Enterprise Accounting Platform"
          category="Construction & Finance"
          description="Comprehensive accounting and project management solution designed specifically for large construction companies, handling complex financial workflows and compliance."
          stats="Large enterprise clients"
          :tags="['Laravel', 'Vue.js', 'Financial Systems']"
          href="https://www.binarcode.com/recent-work"
          accent="amber"
        >
          <template #icon>
            <svg class="h-6 w-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3-3 3 3 3-3 3 3V5a2 2 0 00-2-2z" />
            </svg>
          </template>
        </CaseStudyCard>

        <!-- JobMatix -->
        <CaseStudyCard
          title="JobMatix"
          subtitle="Job Advertising Platform"
          category="Recruitment Technology"
          description="Advanced job advertising and filtering platform that connects employers with candidates through intelligent matching algorithms and comprehensive job management tools."
          stats="100,000+ jobs filtered daily"
          :tags="['Laravel Restify', 'Advanced Filtering', 'API Integration']"
          href="https://www.jobmatix.com/nl/"
          accent="green"
        >
          <template #icon>
            <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </template>
        </CaseStudyCard>
      </div>

      <!-- Call to Action -->
      <CaseStudiesCta />
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import CaseStudyCard from '~/components/case-studies/CaseStudyCard.vue';

useHead({
  title: 'Case Studies - Laravel Restify',
  meta: [
    {
      name: 'description',
      content: 'Real-world applications built with Laravel Restify and the BinarCode ecosystem. See how leading companies leverage our tools to build scalable, robust solutions.'
    }
  ]
})
</script>