import { destr } from "destr";
import { defineEventHandler, getQuery, readBody, setResponseHeaders } from "h3";
import { resolveUnrefHeadInput } from "@unhead/vue";
import { getRequestDependencies } from "vue-bundle-renderer/runtime";
import { getQuery as getURLQuery } from "ufo";
import { islandCache, islandPropCache } from "../utils/cache.js";
import { createSSRContext } from "../utils/renderer/app.js";
import { getSSRRenderer } from "../utils/renderer/build-files.js";
import { renderInlineStyles } from "../utils/renderer/inline-styles.js";
import { getClientIslandResponse, getServerComponentHTML, getSlotIslandResponse } from "../utils/renderer/islands.js";
import { useNitroApp } from "#internal/nitro";
const ISLAND_SUFFIX_RE = /\.json(\?.*)?$/;
export default defineEventHandler(async (event) => {
  const nitroApp = useNitroApp();
  setResponseHeaders(event, {
    "content-type": "application/json;charset=utf-8",
    "x-powered-by": "Nuxt"
  });
  if (import.meta.prerender && event.path && await islandCache.hasItem(event.path)) {
    return islandCache.getItem(event.path);
  }
  const islandContext = await getIslandContext(event);
  const ssrContext = {
    ...createSSRContext(event),
    islandContext,
    noSSR: false,
    url: islandContext.url
  };
  const renderer = await getSSRRenderer();
  const renderResult = await renderer.renderToString(ssrContext).catch(async (error) => {
    await ssrContext.nuxt?.hooks.callHook("app:error", error);
    throw error;
  });
  const inlinedStyles = await renderInlineStyles(ssrContext.modules ?? []);
  await ssrContext.nuxt?.hooks.callHook("app:rendered", { ssrContext, renderResult });
  if (inlinedStyles.length) {
    ssrContext.head.push({ style: inlinedStyles });
  }
  if (import.meta.dev) {
    const { styles } = getRequestDependencies(ssrContext, renderer.rendererContext);
    const link = [];
    for (const resource of Object.values(styles)) {
      if ("inline" in getURLQuery(resource.file)) {
        continue;
      }
      if (resource.file.includes("scoped") && !resource.file.includes("pages/")) {
        link.push({ rel: "stylesheet", href: renderer.rendererContext.buildAssetsURL(resource.file), crossorigin: "" });
      }
    }
    if (link.length) {
      ssrContext.head.push({ link }, { mode: "server" });
    }
  }
  const islandHead = {};
  for (const entry of ssrContext.head.entries.values()) {
    for (const [key, value] of Object.entries(resolveUnrefHeadInput(entry.input))) {
      const currentValue = islandHead[key];
      if (Array.isArray(currentValue)) {
        currentValue.push(...value);
      }
      islandHead[key] = value;
    }
  }
  islandHead.link ||= [];
  islandHead.style ||= [];
  const islandResponse = {
    id: islandContext.id,
    head: islandHead,
    html: getServerComponentHTML(renderResult.html),
    components: getClientIslandResponse(ssrContext),
    slots: getSlotIslandResponse(ssrContext)
  };
  await nitroApp.hooks.callHook("render:island", islandResponse, { event, islandContext });
  if (import.meta.prerender) {
    await islandCache.setItem(`/__nuxt_island/${islandContext.name}_${islandContext.id}.json`, islandResponse);
    await islandPropCache.setItem(`/__nuxt_island/${islandContext.name}_${islandContext.id}.json`, event.path);
  }
  return islandResponse;
});
async function getIslandContext(event) {
  let url = event.path || "";
  if (import.meta.prerender && event.path && await islandPropCache.hasItem(event.path)) {
    url = await islandPropCache.getItem(event.path);
  }
  const componentParts = url.substring("/__nuxt_island".length + 1).replace(ISLAND_SUFFIX_RE, "").split("_");
  const hashId = componentParts.length > 1 ? componentParts.pop() : void 0;
  const componentName = componentParts.join("_");
  const context = event.method === "GET" ? getQuery(event) : await readBody(event);
  const ctx = {
    url: "/",
    ...context,
    id: hashId,
    name: componentName,
    props: destr(context.props) || {},
    slots: {},
    components: {}
  };
  return ctx;
}
