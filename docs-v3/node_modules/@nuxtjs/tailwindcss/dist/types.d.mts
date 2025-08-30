import type { ModuleHooks } from './module.js'

declare module '@nuxt/schema' {
  interface NuxtHooks extends ModuleHooks {}
}

export { type ModuleHooks, type ModuleOptions, default } from './module.js'
