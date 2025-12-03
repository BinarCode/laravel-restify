<template>
  <NuxtLayout name="website">
    <HeroSection />
    <FeaturesSection />
    <CommunitySection />
    <ContributorsSection />
    <CtaSection />
  </NuxtLayout>
</template>

<script setup>
import HeroSection from '~/components/website/HeroSection.vue'
import FeaturesSection from '~/components/website/FeaturesSection.vue'
import CommunitySection from '~/components/website/CommunitySection.vue'
import ContributorsSection from '~/components/website/ContributorsSection.vue'
import CtaSection from '~/components/website/CtaSection.vue'

useHead({
  title: 'Laravel Restify - Build APIs for Humans & AI Agents',
  meta: [
    {
      name: 'description',
      content: 'Transform your Laravel Eloquent models into JSON:API compliant endpoints and MCP servers automatically. No boilerplate, just results.'
    }
  ]
})
</script>