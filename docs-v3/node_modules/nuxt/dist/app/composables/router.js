import { getCurrentInstance, hasInjectionContext, inject, onScopeDispose } from "vue";
import { sanitizeStatusCode } from "h3";
import { hasProtocol, isScriptProtocol, joinURL, parseQuery, parseURL, withQuery } from "ufo";
import { useNuxtApp, useRuntimeConfig } from "../nuxt.js";
import { PageRouteSymbol } from "../components/injections.js";
import { createError, showError } from "./error.js";
export const useRouter = () => {
  return useNuxtApp()?.$router;
};
export const useRoute = () => {
  if (import.meta.dev && !getCurrentInstance() && isProcessingMiddleware()) {
    console.warn("[nuxt] Calling `useRoute` within middleware may lead to misleading results. Instead, use the (to, from) arguments passed to the middleware to access the new and old routes.");
  }
  if (hasInjectionContext()) {
    return inject(PageRouteSymbol, useNuxtApp()._route);
  }
  return useNuxtApp()._route;
};
export const onBeforeRouteLeave = (guard) => {
  const unsubscribe = useRouter().beforeEach((to, from, next) => {
    if (to === from) {
      return;
    }
    return guard(to, from, next);
  });
  onScopeDispose(unsubscribe);
};
export const onBeforeRouteUpdate = (guard) => {
  const unsubscribe = useRouter().beforeEach(guard);
  onScopeDispose(unsubscribe);
};
// @__NO_SIDE_EFFECTS__
export function defineNuxtRouteMiddleware(middleware) {
  return middleware;
}
export const addRouteMiddleware = (name, middleware, options = {}) => {
  const nuxtApp = useNuxtApp();
  const global = options.global || typeof name !== "string";
  const mw = typeof name !== "string" ? name : middleware;
  if (!mw) {
    console.warn("[nuxt] No route middleware passed to `addRouteMiddleware`.", name);
    return;
  }
  if (global) {
    nuxtApp._middleware.global.push(mw);
  } else {
    nuxtApp._middleware.named[name] = mw;
  }
};
const isProcessingMiddleware = () => {
  try {
    if (useNuxtApp()._processingMiddleware) {
      return true;
    }
  } catch {
    return false;
  }
  return false;
};
const URL_QUOTE_RE = /"/g;
export const navigateTo = (to, options) => {
  to ||= "/";
  const toPath = typeof to === "string" ? to : "path" in to ? resolveRouteObject(to) : useRouter().resolve(to).href;
  if (import.meta.client && options?.open) {
    const { target = "_blank", windowFeatures = {} } = options.open;
    const features = Object.entries(windowFeatures).filter(([_, value]) => value !== void 0).map(([feature, value]) => `${feature.toLowerCase()}=${value}`).join(", ");
    open(toPath, target, features);
    return Promise.resolve();
  }
  const isExternalHost = hasProtocol(toPath, { acceptRelative: true });
  const isExternal = options?.external || isExternalHost;
  if (isExternal) {
    if (!options?.external) {
      throw new Error("Navigating to an external URL is not allowed by default. Use `navigateTo(url, { external: true })`.");
    }
    const { protocol } = new URL(toPath, import.meta.client ? window.location.href : "http://localhost");
    if (protocol && isScriptProtocol(protocol)) {
      throw new Error(`Cannot navigate to a URL with '${protocol}' protocol.`);
    }
  }
  const inMiddleware = isProcessingMiddleware();
  if (import.meta.client && !isExternal && inMiddleware) {
    if (options?.replace) {
      if (typeof to === "string") {
        const { pathname, search, hash } = parseURL(to);
        return {
          path: pathname,
          ...search && { query: parseQuery(search) },
          ...hash && { hash },
          replace: true
        };
      }
      return { ...to, replace: true };
    }
    return to;
  }
  const router = useRouter();
  const nuxtApp = useNuxtApp();
  if (import.meta.server) {
    if (nuxtApp.ssrContext) {
      const fullPath = typeof to === "string" || isExternal ? toPath : router.resolve(to).fullPath || "/";
      const location2 = isExternal ? toPath : joinURL(useRuntimeConfig().app.baseURL, fullPath);
      const redirect = async function(response) {
        await nuxtApp.callHook("app:redirected");
        const encodedLoc = location2.replace(URL_QUOTE_RE, "%22");
        const encodedHeader = encodeURL(location2, isExternalHost);
        nuxtApp.ssrContext._renderResponse = {
          statusCode: sanitizeStatusCode(options?.redirectCode || 302, 302),
          body: `<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0; url=${encodedLoc}"></head></html>`,
          headers: { location: encodedHeader }
        };
        return response;
      };
      if (!isExternal && inMiddleware) {
        router.afterEach((final) => final.fullPath === fullPath ? redirect(false) : void 0);
        return to;
      }
      return redirect(!inMiddleware ? void 0 : (
        /* abort route navigation */
        false
      ));
    }
  }
  if (isExternal) {
    nuxtApp._scope.stop();
    if (options?.replace) {
      location.replace(toPath);
    } else {
      location.href = toPath;
    }
    if (inMiddleware) {
      if (!nuxtApp.isHydrating) {
        return false;
      }
      return new Promise(() => {
      });
    }
    return Promise.resolve();
  }
  return options?.replace ? router.replace(to) : router.push(to);
};
export const abortNavigation = (err) => {
  if (import.meta.dev && !isProcessingMiddleware()) {
    throw new Error("abortNavigation() is only usable inside a route middleware handler.");
  }
  if (!err) {
    return false;
  }
  err = createError(err);
  if (err.fatal) {
    useNuxtApp().runWithContext(() => showError(err));
  }
  throw err;
};
export const setPageLayout = (layout) => {
  const nuxtApp = useNuxtApp();
  if (import.meta.server) {
    if (import.meta.dev && getCurrentInstance() && nuxtApp.payload.state._layout !== layout) {
      console.warn("[warn] [nuxt] `setPageLayout` should not be called to change the layout on the server within a component as this will cause hydration errors.");
    }
    nuxtApp.payload.state._layout = layout;
  }
  if (import.meta.dev && nuxtApp.isHydrating && nuxtApp.payload.serverRendered && nuxtApp.payload.state._layout !== layout) {
    console.warn("[warn] [nuxt] `setPageLayout` should not be called to change the layout during hydration as this will cause hydration errors.");
  }
  const inMiddleware = isProcessingMiddleware();
  if (inMiddleware || import.meta.server || nuxtApp.isHydrating) {
    const unsubscribe = useRouter().beforeResolve((to) => {
      to.meta.layout = layout;
      unsubscribe();
    });
  }
  if (!inMiddleware) {
    useRoute().meta.layout = layout;
  }
};
export function resolveRouteObject(to) {
  return withQuery(to.path || "", to.query || {}) + (to.hash || "");
}
export function encodeURL(location2, isExternalHost = false) {
  const url = new URL(location2, "http://localhost");
  if (!isExternalHost) {
    return url.pathname + url.search + url.hash;
  }
  if (location2.startsWith("//")) {
    return url.toString().replace(url.protocol, "");
  }
  return url.toString();
}
