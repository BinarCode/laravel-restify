import { withoutTrailingSlash } from "ufo";
import { computed, shallowReactive, shallowRef, useState, useRoute } from "#imports";
export const useContentState = () => {
  const pages = useState("dd-pages", () => shallowRef(shallowReactive({})));
  const surrounds = useState("dd-surrounds", () => shallowRef(shallowReactive({})));
  const navigation = useState("dd-navigation");
  const globals = useState("dd-globals", () => shallowRef(shallowReactive({})));
  return {
    pages,
    surrounds,
    navigation,
    globals
  };
};
export const useContent = () => {
  const { navigation, pages, surrounds, globals } = useContentState();
  const _path = computed(() => withoutTrailingSlash(useRoute().path));
  const page = computed(() => pages.value[_path.value]);
  const surround = computed(() => surrounds.value[_path.value]);
  const toc = computed(() => page?.value?.body?.toc);
  const type = computed(() => page.value?._type);
  const excerpt = computed(() => page.value?.excerpt);
  const layout = computed(() => page.value?.layout);
  const next = computed(() => surround.value?.[1]);
  const prev = computed(() => surround.value?.[0]);
  return {
    // Refs
    globals,
    navigation,
    surround,
    page,
    // From page
    excerpt,
    toc,
    type,
    layout,
    // From surround
    next,
    prev
  };
};
