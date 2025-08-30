import memoryDriver from "unstorage/drivers/memory";
import { createStorage, prefixStorage } from "unstorage";
import { withBase } from "ufo";
import { createPipelineFetcherLegacy } from "../../query/match/pipeline-legacy.js";
import { createQuery } from "../../query/query.js";
import { createNav } from "../../server/navigation.js";
import { useContentPreview } from "../../composables/preview.js";
import { useRuntimeConfig, useNuxtApp } from "#imports";
const withContentBase = (url) => withBase(url, useRuntimeConfig().public.content.api.baseURL);
export const contentStorage = prefixStorage(createStorage({ driver: memoryDriver() }), "@content");
export function createDB(storage) {
  async function getItems() {
    const keys = new Set(await storage.getKeys("cache:"));
    const previewToken = useContentPreview().getPreviewToken();
    if (previewToken) {
      const previewMeta = await storage.getItem(`${previewToken}$`).then((data) => data || {});
      if (Array.isArray(previewMeta.ignoreSources)) {
        const sources = previewMeta.ignoreSources.map((s) => `cache:${s.trim()}:`);
        for (const key of keys) {
          if (sources.some((s) => key.startsWith(s))) {
            keys.delete(key);
          }
        }
      }
      const previewKeys = await storage.getKeys(`${previewToken}:`);
      const previewContents = await Promise.all(previewKeys.map((key) => storage.getItem(key)));
      for (const pItem of previewContents) {
        keys.delete(`cache:${pItem._id}`);
        if (!pItem.__deleted) {
          keys.add(`${previewToken}:${pItem._id}`);
        }
      }
    }
    const items = await Promise.all(Array.from(keys).map((key) => storage.getItem(key)));
    return items;
  }
  return {
    storage,
    fetch: createPipelineFetcherLegacy(getItems),
    query: (query) => createQuery(createPipelineFetcherLegacy(getItems), {
      initialParams: query,
      legacy: true
    })
  };
}
let contentDatabase = null;
let contentDatabaseInitPromise = null;
export async function useContentDatabase() {
  if (contentDatabaseInitPromise) {
    await contentDatabaseInitPromise;
  } else if (!contentDatabase) {
    contentDatabaseInitPromise = initContentDatabase();
    contentDatabase = await contentDatabaseInitPromise;
  }
  return contentDatabase;
}
async function initContentDatabase() {
  const nuxtApp = useNuxtApp();
  const { content } = useRuntimeConfig().public;
  const _contentDatabase = createDB(contentStorage);
  const integrity = await _contentDatabase.storage.getItem("integrity");
  if (content.integrity !== +(integrity || 0)) {
    const { contents, navigation } = await $fetch(withContentBase(content.integrity ? `cache.${content.integrity}.json` : "cache.json"));
    await Promise.all(
      contents.map((content2) => _contentDatabase.storage.setItem(`cache:${content2._id}`, content2))
    );
    await _contentDatabase.storage.setItem("navigation", navigation);
    await _contentDatabase.storage.setItem("integrity", content.integrity);
  }
  await nuxtApp.callHook("content:storage", _contentDatabase.storage);
  return _contentDatabase;
}
export async function generateNavigation(query) {
  const db = await useContentDatabase();
  if (!useContentPreview().getPreviewToken() && Object.keys(query || {}).length === 0) {
    return db.storage.getItem("navigation");
  }
  const contents = await db.query(query).where({
    /**
     * Partial contents are not included in the navigation
     * A partial content is a content that has `_` prefix in its path
     */
    _partial: false,
    /**
    * Exclude any pages which have opted out of navigation via frontmatter.
    */
    navigation: {
      $ne: false
    }
  }).find();
  const dirConfigs = await db.query().where({ _path: /\/_dir$/i, _partial: true }).find();
  const configs = dirConfigs.reduce((configs2, conf) => {
    if (conf.title?.toLowerCase() === "dir") {
      conf.title = void 0;
    }
    const key = conf._path.split("/").slice(0, -1).join("/") || "/";
    configs2[key] = {
      ...conf,
      // Extract meta from body. (non MD files)
      ...conf.body
    };
    return configs2;
  }, {});
  return createNav(contents, configs);
}
