import { useStorage } from "#internal/nitro";
export const payloadCache = import.meta.prerender ? useStorage("internal:nuxt:prerender:payload") : null;
export const islandCache = import.meta.prerender ? useStorage("internal:nuxt:prerender:island") : null;
export const islandPropCache = import.meta.prerender ? useStorage("internal:nuxt:prerender:island-props") : null;
export const sharedPrerenderPromises = import.meta.prerender && process.env.NUXT_SHARED_DATA ? /* @__PURE__ */ new Map() : null;
const sharedPrerenderKeys = /* @__PURE__ */ new Set();
export const sharedPrerenderCache = import.meta.prerender && process.env.NUXT_SHARED_DATA ? {
  get(key) {
    if (sharedPrerenderKeys.has(key)) {
      return sharedPrerenderPromises.get(key) ?? useStorage("internal:nuxt:prerender:shared").getItem(key);
    }
  },
  async set(key, value) {
    sharedPrerenderKeys.add(key);
    sharedPrerenderPromises.set(key, value);
    useStorage("internal:nuxt:prerender:shared").setItem(key, await value).finally(() => sharedPrerenderPromises.delete(key));
  }
} : null;
