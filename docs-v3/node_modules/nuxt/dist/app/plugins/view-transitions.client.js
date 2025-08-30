import { isChangingPage } from "../components/utils.js";
import { useRouter } from "../composables/router.js";
import { defineNuxtPlugin } from "../nuxt.js";
import { appViewTransition as defaultViewTransition } from "#build/nuxt.config.mjs";
export default defineNuxtPlugin((nuxtApp) => {
  if (!document.startViewTransition) {
    return;
  }
  let transition;
  let hasUAVisualTransition = false;
  let finishTransition;
  let abortTransition;
  const resetTransitionState = () => {
    transition = void 0;
    hasUAVisualTransition = false;
    abortTransition = void 0;
    finishTransition = void 0;
  };
  window.addEventListener("popstate", (event) => {
    hasUAVisualTransition = event.hasUAVisualTransition;
    if (hasUAVisualTransition) {
      transition?.skipTransition();
    }
  });
  const router = useRouter();
  router.beforeResolve(async (to, from) => {
    const viewTransitionMode = to.meta.viewTransition ?? defaultViewTransition;
    const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const prefersNoTransition = prefersReducedMotion && viewTransitionMode !== "always";
    if (viewTransitionMode === false || prefersNoTransition || hasUAVisualTransition || !isChangingPage(to, from)) {
      return;
    }
    const promise = new Promise((resolve, reject) => {
      finishTransition = resolve;
      abortTransition = reject;
    });
    let changeRoute;
    const ready = new Promise((resolve) => changeRoute = resolve);
    transition = document.startViewTransition(() => {
      changeRoute();
      return promise;
    });
    transition.finished.then(resetTransitionState);
    await nuxtApp.callHook("page:view-transition:start", transition);
    return ready;
  });
  nuxtApp.hook("vue:error", () => {
    abortTransition?.();
    resetTransitionState();
  });
  nuxtApp.hook("page:finish", () => {
    finishTransition?.();
    resetTransitionState();
  });
});
