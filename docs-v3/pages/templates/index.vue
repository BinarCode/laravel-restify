<template>
  <NuxtLayout name="website">
    <div class="py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <TemplatesHeader />

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
          <TemplateCard
            v-for="template in templates"
            :key="template.name"
            :template="template"
          />
        </div>

        <TemplatesCustomCta />
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import TemplatesHeader from '~/components/templates/TemplatesHeader.vue'
import TemplateCard from '~/components/templates/TemplateCard.vue'
import TemplatesCustomCta from '~/components/templates/TemplatesCustomCta.vue'

useHead({
  title: 'Templates - Laravel Restify',
  meta: [
    {
      name: 'description',
      content: 'Jump-start your API development with ready-to-use Laravel Restify templates. CRUD, authentication, MCP integration, and more.'
    }
  ]
})

interface TemplateFeature {
  text: string
  highlighted?: boolean
}

interface Template {
  name: string
  price: string
  description: string
  badge?: string
  badgeColor?: 'green' | 'blue' | 'purple' | 'orange'
  borderColor: string
  icon: string
  iconColor: string
  features: TemplateFeature[]
  primaryAction: {
    label: string
    href: string
    disabled?: boolean
  }
  docsHref: string
}

const templates: Template[] = [
  {
    name: 'Laravel Restify Starter Kit',
    price: 'FREE',
    description: 'Production-ready API starter with auth, teams & permissions',
    badge: 'FREE',
    badgeColor: 'green',
    borderColor: 'border-green-500/50',
    icon: 'rocket',
    iconColor: 'text-red-400',
    features: [
      { text: 'Complete Auth System' },
      { text: 'Advanced Teams with Member Management' },
      { text: 'Email Invitations with Approval Flow' },
      { text: 'Full User CRUD with Profile Management' },
      { text: 'Role-Based Permissions (Spatie)' },
      { text: 'Production-Ready API Architecture' }
    ],
    primaryAction: {
      label: 'Start Building Free',
      href: 'https://restifytemplates.com/download/free/bOcviUe7kYdm'
    },
    docsHref: 'https://restifytemplates.com/documentation/starter-kit'
  },
  {
    name: 'Essential',
    price: '€19.99',
    description: 'Great for solo devs or MVPs',
    borderColor: 'border-blue-500/50',
    icon: 'star',
    iconColor: 'text-yellow-400',
    features: [
      { text: 'Everything from Free + Advanced Features' },
      { text: 'CI/CD setup (GitHub Actions)' },
      { text: 'File uploads with S3/local storage' },
      { text: 'Custom middlewares per repository' },
      { text: 'Versioned API endpoints (v1, v2)' },
      { text: 'Advanced filtering & search' },
      { text: 'Activity logs & audit trails' },
      { text: 'Export data to CSV/Excel' },
      { text: '6 months of security updates' }
    ],
    primaryAction: {
      label: 'Unlock Essential Features',
      href: 'https://restifytemplates.com/checkout/essential'
    },
    docsHref: 'https://restifytemplates.com/documentation/essential'
  },
  {
    name: 'Pro',
    price: '€49.00',
    description: 'For real projects with payments & scaling',
    borderColor: 'border-purple-500/50',
    icon: 'trophy',
    iconColor: 'text-yellow-500',
    features: [
      { text: 'Everything in Essential' },
      { text: 'OpenAI Integration' },
      { text: 'Stripe integration (Laravel Cashier)' },
      { text: 'Multi-tenancy support' },
      { text: 'Laravel Scout search' },
      { text: 'Webhook event broadcasting' },
      { text: 'Basic Nova setup' },
      { text: '1 year of security updates' }
    ],
    primaryAction: {
      label: 'Unlock Pro Features',
      href: 'https://restifytemplates.com/checkout/pro'
    },
    docsHref: 'https://restifytemplates.com/documentation/pro'
  },
  {
    name: 'Premium',
    price: '€99.99',
    description: 'For teams who want to move fast',
    badge: 'COMING SOON',
    badgeColor: 'orange',
    borderColor: 'border-orange-500/50',
    icon: 'rocket',
    iconColor: 'text-orange-400',
    features: [
      { text: 'Everything in Pro' },
      { text: 'Anthropic & Gemini AI integration' },
      { text: '🔥 MCP Server with your data', highlighted: true },
      { text: '🔥 MCP Client for smart AI Chat', highlighted: true },
      { text: 'Laravel Nova full admin prebuilt' },
      { text: 'Laravel Telescope pre-configured' },
      { text: 'Laravel Horizon for queue monitoring' },
      { text: 'Priority support (email or Discord)' },
      { text: 'Lifetime security updates' }
    ],
    primaryAction: {
      label: 'Get Early Access Today 🚀',
      href: '#',
      disabled: true
    },
    docsHref: 'https://restifytemplates.com/documentation/premium'
  }
]
</script>
