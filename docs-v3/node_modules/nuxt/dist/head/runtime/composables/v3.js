import { hasInjectionContext, inject } from "vue";
import {
  useHead as headCore,
  useHeadSafe as headSafe,
  headSymbol,
  useSeoMeta as seoMeta,
  useServerHead as serverHead,
  useServerHeadSafe as serverHeadSafe,
  useServerSeoMeta as serverSeoMeta
} from "@unhead/vue";
import { tryUseNuxtApp } from "#app/nuxt";
export function injectHead(nuxtApp) {
  const nuxt = nuxtApp || tryUseNuxtApp();
  return nuxt?.ssrContext?.head || nuxt?.runWithContext(() => {
    if (hasInjectionContext()) {
      return inject(headSymbol);
    }
  });
}
export function useHead(input, options = {}) {
  const head = injectHead(options.nuxt);
  if (head) {
    return headCore(input, { head, ...options });
  }
}
export function useHeadSafe(input, options = {}) {
  const head = injectHead(options.nuxt);
  if (head) {
    return headSafe(input, { head, ...options });
  }
}
export function useSeoMeta(input, options = {}) {
  const head = injectHead(options.nuxt);
  if (head) {
    return seoMeta(input, { head, ...options });
  }
}
export function useServerHead(input, options = {}) {
  const head = injectHead(options.nuxt);
  if (head) {
    return serverHead(input, { head, ...options });
  }
}
export function useServerHeadSafe(input, options = {}) {
  const head = injectHead(options.nuxt);
  if (head) {
    return serverHeadSafe(input, { head, ...options });
  }
}
export function useServerSeoMeta(input, options = {}) {
  const head = injectHead(options.nuxt);
  if (head) {
    return serverSeoMeta(input, { head, ...options });
  }
}
