import { defineNuxtPlugin } from "../nuxt.js";
import { reloadNuxtApp } from "../composables/chunk.js";
import { addRouteMiddleware } from "../composables/router.js";
const reloadNuxtApp_ = (path) => {
  reloadNuxtApp({ persistState: true, path });
};
export default defineNuxtPlugin({
  name: "nuxt:chunk-reload-immediate",
  setup(nuxtApp) {
    let currentlyNavigationTo = null;
    addRouteMiddleware((to) => {
      currentlyNavigationTo = to.path;
    });
    nuxtApp.hook("app:chunkError", () => reloadNuxtApp_(currentlyNavigationTo ?? nuxtApp._route.path));
    nuxtApp.hook("app:manifest:update", () => reloadNuxtApp_(nuxtApp._route.path));
  }
});
