import { hasProtocol, joinURL, withoutTrailingSlash } from "ufo";
import { parse } from "devalue";
import { getCurrentInstance, onServerPrefetch, reactive } from "vue";
import { useNuxtApp, useRuntimeConfig } from "../nuxt.js";
import { useHead } from "./head.js";
import { useRoute } from "./router.js";
import { getAppManifest, getRouteRules } from "./manifest.js";
import { appId, appManifest, multiApp, payloadExtraction, renderJsonPayloads } from "#build/nuxt.config.mjs";
export async function loadPayload(url, opts = {}) {
  if (import.meta.server || !payloadExtraction) {
    return null;
  }
  const shouldLoadPayload = await isPrerendered(url);
  if (!shouldLoadPayload) {
    return null;
  }
  const payloadURL = await _getPayloadURL(url, opts);
  return await _importPayload(payloadURL) || null;
}
let linkRelType;
function detectLinkRelType() {
  if (import.meta.server) {
    return "preload";
  }
  if (linkRelType) {
    return linkRelType;
  }
  const relList = document.createElement("link").relList;
  linkRelType = relList && relList.supports && relList.supports("prefetch") ? "prefetch" : "preload";
  return linkRelType;
}
export function preloadPayload(url, opts = {}) {
  const nuxtApp = useNuxtApp();
  const promise = _getPayloadURL(url, opts).then((payloadURL) => {
    const link = renderJsonPayloads ? { rel: detectLinkRelType(), as: "fetch", crossorigin: "anonymous", href: payloadURL } : { rel: "modulepreload", crossorigin: "", href: payloadURL };
    if (import.meta.server) {
      nuxtApp.runWithContext(() => useHead({ link: [link] }));
    } else {
      const linkEl = document.createElement("link");
      for (const key of Object.keys(link)) {
        linkEl[key === "crossorigin" ? "crossOrigin" : key] = link[key];
      }
      document.head.appendChild(linkEl);
      return new Promise((resolve, reject) => {
        linkEl.addEventListener("load", () => resolve());
        linkEl.addEventListener("error", () => reject());
      });
    }
  });
  if (import.meta.server) {
    onServerPrefetch(() => promise);
  }
  return promise;
}
const filename = renderJsonPayloads ? "_payload.json" : "_payload.js";
async function _getPayloadURL(url, opts = {}) {
  const u = new URL(url, "http://localhost");
  if (u.host !== "localhost" || hasProtocol(u.pathname, { acceptRelative: true })) {
    throw new Error("Payload URL must not include hostname: " + url);
  }
  const config = useRuntimeConfig();
  const hash = opts.hash || (opts.fresh ? Date.now() : config.app.buildId);
  const cdnURL = config.app.cdnURL;
  const baseOrCdnURL = cdnURL && await isPrerendered(url) ? cdnURL : config.app.baseURL;
  return joinURL(baseOrCdnURL, u.pathname, filename + (hash ? `?${hash}` : ""));
}
async function _importPayload(payloadURL) {
  if (import.meta.server || !payloadExtraction) {
    return null;
  }
  const payloadPromise = renderJsonPayloads ? fetch(payloadURL, { cache: "force-cache" }).then((res) => res.text().then(parsePayload)) : import(
    /* webpackIgnore: true */
    /* @vite-ignore */
    payloadURL
  ).then((r) => r.default || r);
  try {
    return await payloadPromise;
  } catch (err) {
    console.warn("[nuxt] Cannot load payload ", payloadURL, err);
  }
  return null;
}
export async function isPrerendered(url = useRoute().path) {
  const nuxtApp = useNuxtApp();
  if (!appManifest) {
    return !!nuxtApp.payload.prerenderedAt;
  }
  url = withoutTrailingSlash(url);
  const manifest = await getAppManifest();
  if (manifest.prerendered.includes(url)) {
    return true;
  }
  return nuxtApp.runWithContext(async () => {
    const rules = await getRouteRules({ path: url });
    return !!rules.prerender && !rules.redirect;
  });
}
let payloadCache = null;
export async function getNuxtClientPayload() {
  if (import.meta.server) {
    return null;
  }
  if (payloadCache) {
    return payloadCache;
  }
  const el = multiApp ? document.querySelector(`[data-nuxt-data="${appId}"]`) : document.getElementById("__NUXT_DATA__");
  if (!el) {
    return {};
  }
  const inlineData = await parsePayload(el.textContent || "");
  const externalData = el.dataset.src ? await _importPayload(el.dataset.src) : void 0;
  payloadCache = {
    ...inlineData,
    ...externalData,
    ...multiApp ? window.__NUXT__?.[appId] : window.__NUXT__
  };
  if (payloadCache.config?.public) {
    payloadCache.config.public = reactive(payloadCache.config.public);
  }
  return payloadCache;
}
export async function parsePayload(payload) {
  return await parse(payload, useNuxtApp()._payloadRevivers);
}
export function definePayloadReducer(name, reduce) {
  if (import.meta.server) {
    useNuxtApp().ssrContext._payloadReducers[name] = reduce;
  }
}
export function definePayloadReviver(name, revive) {
  if (import.meta.dev && getCurrentInstance()) {
    console.warn("[nuxt] [definePayloadReviver] This function must be called in a Nuxt plugin that is `unshift`ed to the beginning of the Nuxt plugins array.");
  }
  if (import.meta.client) {
    useNuxtApp()._payloadRevivers[name] = revive;
  }
}
