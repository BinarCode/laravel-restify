<template>
  <section class="py-16">
    <h2 class="text-4xl font-bold text-center text-gray-900 dark:text-white mb-16">
      Key Features
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <FeatureCard
        :icon="Database"
        title="JSON:API Endpoints"
        color="from-primary-500 to-primary-600"
      >
        Automatically generate JSON:API compliant endpoints from your Laravel Eloquent models with zero configuration.
      </FeatureCard>
      <FeatureCard
        :icon="Bot"
        title="MCP Server Generation"
        color="from-slate-500 to-slate-600"
      >
        Enable AI agents to interact with your APIs using the Model Context Protocol for seamless AI integration.
      </FeatureCard>
      <FeatureCard
        :icon="Lock"
        title="Laravel Sanctum Auth"
        color="from-primary-500 to-primary-600"
      >
        Built-in authentication using Laravel Sanctum for secure API access with token-based authentication.
      </FeatureCard>
      <FeatureCard
        :icon="Search"
        title="Advanced Search & Filtering"
        color="from-slate-500 to-slate-600"
      >
        Powerful query capabilities with filtering, sorting, and search functionality built right into your API endpoints.
      </FeatureCard>
      <FeatureCard
        :icon="Layers"
        title="GraphQL Schema"
        color="from-primary-500 to-primary-600"
      >
        Automatic GraphQL schema generation from your models for flexible data querying and modern API interactions.
      </FeatureCard>
      <FeatureCard
        :icon="ShieldCheck"
        title="Policy-Based Authorization"
        color="from-slate-500 to-slate-600"
      >
        Secure your APIs with Laravel's built-in authorization system using policies for fine-grained access control.
      </FeatureCard>
    </div>
  </section>
</template>

<script setup lang="ts">
import { Database, Bot, Lock, Search, Layers, ShieldCheck } from 'lucide-vue-next'
import FeatureCard from './FeatureCard.vue'
</script>
