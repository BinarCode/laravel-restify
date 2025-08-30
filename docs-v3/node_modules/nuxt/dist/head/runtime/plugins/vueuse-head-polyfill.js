import { defineNuxtPlugin } from "#app/nuxt";
import { useHead } from "#app/composables/head";
function polyfillAsVueUseHead(head) {
  const polyfilled = head;
  polyfilled.headTags = head.resolveTags;
  polyfilled.addEntry = head.push;
  polyfilled.addHeadObjs = head.push;
  polyfilled.addReactiveEntry = (input, options) => {
    const api = useHead(input, options);
    if (api !== void 0) {
      return api.dispose;
    }
    return () => {
    };
  };
  polyfilled.removeHeadObjs = () => {
  };
  polyfilled.updateDOM = () => {
    head.hooks.callHook("entries:updated", head);
  };
  polyfilled.unhead = head;
  return polyfilled;
}
export default defineNuxtPlugin({
  name: "nuxt:vueuse-head-polyfill",
  setup(nuxtApp) {
    polyfillAsVueUseHead(nuxtApp.vueApp._context.provides.usehead);
  }
});
