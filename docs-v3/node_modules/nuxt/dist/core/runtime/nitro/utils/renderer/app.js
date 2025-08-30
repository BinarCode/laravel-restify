import { createHead } from "@unhead/vue/server";
import { sharedPrerenderCache } from "../cache.js";
import { useRuntimeConfig } from "#internal/nitro";
import unheadOptions from "#internal/unhead-options.mjs";
const PRERENDER_NO_SSR_ROUTES = /* @__PURE__ */ new Set(["/index.html", "/200.html", "/404.html"]);
export function createSSRContext(event) {
  const ssrContext = {
    url: event.path,
    event,
    runtimeConfig: useRuntimeConfig(event),
    noSSR: !!process.env.NUXT_NO_SSR || event.context.nuxt?.noSSR || (import.meta.prerender ? PRERENDER_NO_SSR_ROUTES.has(event.path) : false),
    head: createHead(unheadOptions),
    error: false,
    nuxt: void 0,
    /* NuxtApp */
    payload: {},
    _payloadReducers: /* @__PURE__ */ Object.create(null),
    modules: /* @__PURE__ */ new Set()
  };
  if (import.meta.prerender) {
    if (process.env.NUXT_SHARED_DATA) {
      ssrContext._sharedPrerenderCache = sharedPrerenderCache;
    }
    ssrContext.payload.prerenderedAt = Date.now();
  }
  return ssrContext;
}
export function setSSRError(ssrContext, error) {
  ssrContext.error = true;
  ssrContext.payload = { error };
  ssrContext.url = error.url;
}
