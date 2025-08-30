import { createError } from "h3";
import MiniSearch from "minisearch";
import { useRuntimeConfig, useFetch, ref, computed, toValue } from "#imports";
export const defineMiniSearchOptions = (options) => {
  return ref(options);
};
export const searchContent = async (search, options = {}) => {
  const runtimeConfig = useRuntimeConfig();
  const { content } = runtimeConfig.public;
  const { integrity, api: { baseURL: baseAPI }, search: searchOptions } = content;
  const { indexed: useIndexedSearch } = searchOptions || {};
  const searchRoute = `${baseAPI}/search${integrity ? "-" + integrity : ""}`;
  if (useIndexedSearch) {
    const { options: miniSearchOptions } = searchOptions || {};
    const { data: data2 } = await useFetch(searchRoute, { responseType: "text" });
    if (!data2.value) {
      throw createError({
        statusCode: 500,
        message: "Missing search data"
      });
    }
    const results2 = useIndexedMiniSearch(search, data2, miniSearchOptions);
    return results2;
  }
  if (!options.miniSearch) {
    throw createError({
      statusCode: 500,
      message: "Missing miniSearch options"
    });
  }
  const { data } = await useFetch(searchRoute);
  if (!data.value) {
    throw createError({
      statusCode: 500,
      message: "Missing search data"
    });
  }
  const results = useMiniSearch(search, data, options.miniSearch);
  return results;
};
const useIndexedMiniSearch = (search, indexedData, options) => {
  const indexedMiniSearch = computed(() => {
    return MiniSearch.loadJSON(toValue(indexedData), toValue(options));
  });
  const results = computed(() => {
    return indexedMiniSearch.value.search(toValue(search));
  });
  return results;
};
const useMiniSearch = function(search, data, options) {
  const miniSearch = computed(() => {
    return new MiniSearch(toValue(options));
  });
  miniSearch.value.addAll(toValue(data));
  const results = computed(() => {
    return miniSearch.value.search(toValue(search));
  });
  return results;
};
