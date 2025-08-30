import { createMatcherFromExport, createRouter as createRadixRouter, toRouteMatcher } from "radix3";
import { defu } from "defu";
import { useNuxtApp, useRuntimeConfig } from "../nuxt.js";
import { appManifest as isAppManifestEnabled } from "#build/nuxt.config.mjs";
import { buildAssetsURL } from "#internal/nuxt/paths";
let manifest;
let matcher;
function fetchManifest() {
  if (!isAppManifestEnabled) {
    throw new Error("[nuxt] app manifest should be enabled with `experimental.appManifest`");
  }
  if (import.meta.server) {
    manifest = import("#app-manifest");
  } else {
    manifest = $fetch(buildAssetsURL(`builds/meta/${useRuntimeConfig().app.buildId}.json`), {
      responseType: "json"
    });
  }
  manifest.then((m) => {
    matcher = createMatcherFromExport(m.matcher);
  }).catch((e) => {
    console.error("[nuxt] Error fetching app manifest.", e);
  });
  return manifest;
}
export function getAppManifest() {
  if (!isAppManifestEnabled) {
    throw new Error("[nuxt] app manifest should be enabled with `experimental.appManifest`");
  }
  if (import.meta.server) {
    useNuxtApp().ssrContext._preloadManifest = true;
  }
  return manifest || fetchManifest();
}
export async function getRouteRules(arg) {
  const path = typeof arg === "string" ? arg : arg.path;
  if (import.meta.server) {
    useNuxtApp().ssrContext._preloadManifest = true;
    const _routeRulesMatcher = toRouteMatcher(
      createRadixRouter({ routes: useRuntimeConfig().nitro.routeRules })
    );
    return defu({}, ..._routeRulesMatcher.matchAll(path).reverse());
  }
  await getAppManifest();
  if (!matcher) {
    console.error("[nuxt] Error creating app manifest matcher.", matcher);
    return {};
  }
  try {
    return defu({}, ...matcher.matchAll(path).reverse());
  } catch (e) {
    console.error("[nuxt] Error matching route rules.", e);
    return {};
  }
}
