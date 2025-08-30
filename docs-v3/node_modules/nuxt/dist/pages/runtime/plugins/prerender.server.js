import { joinURL } from "ufo";
import { createRouter as createRadixRouter, toRouteMatcher } from "radix3";
import defu from "defu";
import { defineNuxtPlugin, useRuntimeConfig } from "#app/nuxt";
import { prerenderRoutes } from "#app/composables/ssr";
import _routes from "#build/routes";
import routerOptions, { hashMode } from "#build/router.options";
import { crawlLinks } from "#build/nuxt.config.mjs";
let routes;
let _routeRulesMatcher = void 0;
export default defineNuxtPlugin(async () => {
  if (!import.meta.server || !import.meta.prerender || hashMode) {
    return;
  }
  if (routes && !routes.length) {
    return;
  }
  const routeRules = useRuntimeConfig().nitro.routeRules;
  if (!crawlLinks && routeRules && Object.values(routeRules).some((r) => r.prerender)) {
    _routeRulesMatcher = toRouteMatcher(createRadixRouter({ routes: routeRules }));
  }
  routes ||= Array.from(processRoutes(await routerOptions.routes?.(_routes) ?? _routes));
  const batch = routes.splice(0, 10);
  prerenderRoutes(batch);
});
const OPTIONAL_PARAM_RE = /^\/?:.*(?:\?|\(\.\*\)\*)$/;
function shouldPrerender(path) {
  return !_routeRulesMatcher || defu({}, ..._routeRulesMatcher.matchAll(path).reverse()).prerender;
}
function processRoutes(routes2, currentPath = "/", routesToPrerender = /* @__PURE__ */ new Set()) {
  for (const route of routes2) {
    if (OPTIONAL_PARAM_RE.test(route.path) && !route.children?.length && shouldPrerender(currentPath)) {
      routesToPrerender.add(currentPath);
    }
    if (route.path.includes(":")) {
      continue;
    }
    const fullPath = joinURL(currentPath, route.path);
    if (shouldPrerender(fullPath)) {
      routesToPrerender.add(fullPath);
    }
    if (route.children) {
      processRoutes(route.children, fullPath, routesToPrerender);
    }
  }
  return routesToPrerender;
}
