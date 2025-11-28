<template>
  <div>
    <!-- Header/Navbar -->
    <WebsiteNavbar />

    <!-- Hero Section -->
    <HeroSection />

    <!-- Features Section -->
    <FeaturesSection />

    <!-- Community Section -->
    <CommunitySection />

    <!-- Contributors Section -->
    <ContributorsSection />

    <!-- CTA Section -->
    <CtaSection />

    <!-- Footer -->
    <WebsiteFooter />
  </div>
</template>

<script setup>
// Import components
import WebsiteNavbar from '~/components/website/WebsiteNavbar.vue'
import HeroSection from '~/components/website/HeroSection.vue'
import FeaturesSection from '~/components/website/FeaturesSection.vue'
import CommunitySection from '~/components/website/CommunitySection.vue'
import ContributorsSection from '~/components/website/ContributorsSection.vue'
import CtaSection from '~/components/website/CtaSection.vue'
import WebsiteFooter from '~/components/website/WebsiteFooter.vue'

// Page metadata
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
