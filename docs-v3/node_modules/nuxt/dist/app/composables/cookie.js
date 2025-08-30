import { customRef, getCurrentScope, nextTick, onScopeDispose, ref, watch } from "vue";
import { parse, serialize } from "cookie-es";
import { deleteCookie, getCookie, getRequestHeader, setCookie } from "h3";
import destr from "destr";
import { isEqual } from "ohash";
import { klona } from "klona";
import { useNuxtApp } from "../nuxt.js";
import { useRequestEvent } from "./ssr.js";
import { cookieStore } from "#build/nuxt.config.mjs";
const CookieDefaults = {
  path: "/",
  watch: true,
  decode: (val) => destr(decodeURIComponent(val)),
  encode: (val) => encodeURIComponent(typeof val === "string" ? val : JSON.stringify(val))
};
const store = import.meta.client && cookieStore ? window.cookieStore : void 0;
export function useCookie(name, _opts) {
  const opts = { ...CookieDefaults, ..._opts };
  opts.filter ??= (key) => key === name;
  const cookies = readRawCookies(opts) || {};
  let delay;
  if (opts.maxAge !== void 0) {
    delay = opts.maxAge * 1e3;
  } else if (opts.expires) {
    delay = opts.expires.getTime() - Date.now();
  }
  const hasExpired = delay !== void 0 && delay <= 0;
  const shouldSetInitialClientCookie = import.meta.client && (hasExpired || cookies[name] === void 0 || cookies[name] === null);
  const cookieValue = klona(hasExpired ? void 0 : cookies[name] ?? opts.default?.());
  const cookie = import.meta.client && delay && !hasExpired ? cookieRef(cookieValue, delay, opts.watch && opts.watch !== "shallow") : ref(cookieValue);
  if (import.meta.dev && hasExpired) {
    console.warn(`[nuxt] not setting cookie \`${name}\` as it has already expired.`);
  }
  if (import.meta.client) {
    let channel = null;
    try {
      if (!store && typeof BroadcastChannel !== "undefined") {
        channel = new BroadcastChannel(`nuxt:cookies:${name}`);
      }
    } catch {
    }
    const callback = (force = false) => {
      if (!force) {
        if (opts.readonly || isEqual(cookie.value, cookies[name])) {
          return;
        }
      }
      writeClientCookie(name, cookie.value, opts);
      cookies[name] = klona(cookie.value);
      channel?.postMessage({ value: opts.encode(cookie.value) });
    };
    const handleChange = (data) => {
      const value = data.refresh ? readRawCookies(opts)?.[name] : opts.decode(data.value);
      watchPaused = true;
      cookie.value = value;
      cookies[name] = klona(value);
      nextTick(() => {
        watchPaused = false;
      });
    };
    let watchPaused = false;
    const hasScope = !!getCurrentScope();
    if (hasScope) {
      onScopeDispose(() => {
        watchPaused = true;
        callback();
        channel?.close();
      });
    }
    if (store) {
      const changeHandler = (event) => {
        const changedCookie = event.changed.find((c) => c.name === name);
        const removedCookie = event.deleted.find((c) => c.name === name);
        if (changedCookie) {
          handleChange({ value: changedCookie.value });
        }
        if (removedCookie) {
          handleChange({ value: null });
        }
      };
      store.addEventListener("change", changeHandler);
      if (hasScope) {
        onScopeDispose(() => store.removeEventListener("change", changeHandler));
      }
    } else if (channel) {
      channel.onmessage = ({ data }) => handleChange(data);
    }
    if (opts.watch) {
      watch(
        cookie,
        () => {
          if (watchPaused) {
            return;
          }
          callback();
        },
        { deep: opts.watch !== "shallow" }
      );
    }
    if (shouldSetInitialClientCookie) {
      callback(shouldSetInitialClientCookie);
    }
  } else if (import.meta.server) {
    const nuxtApp = useNuxtApp();
    const writeFinalCookieValue = () => {
      if (opts.readonly || isEqual(cookie.value, cookies[name])) {
        return;
      }
      nuxtApp._cookies ||= {};
      if (name in nuxtApp._cookies) {
        if (isEqual(cookie.value, nuxtApp._cookies[name])) {
          return;
        }
        if (import.meta.dev) {
          console.warn(`[nuxt] cookie \`${name}\` was previously set to \`${opts.encode(nuxtApp._cookies[name])}\` and is being overridden to \`${opts.encode(cookie.value)}\`. This may cause unexpected issues.`);
        }
      }
      nuxtApp._cookies[name] = cookie.value;
      writeServerCookie(useRequestEvent(nuxtApp), name, cookie.value, opts);
    };
    const unhook = nuxtApp.hooks.hookOnce("app:rendered", writeFinalCookieValue);
    nuxtApp.hooks.hookOnce("app:error", () => {
      unhook();
      return writeFinalCookieValue();
    });
  }
  return cookie;
}
export function refreshCookie(name) {
  if (import.meta.server || store || typeof BroadcastChannel === "undefined") {
    return;
  }
  new BroadcastChannel(`nuxt:cookies:${name}`)?.postMessage({ refresh: true });
}
function readRawCookies(opts = {}) {
  if (import.meta.server) {
    return parse(getRequestHeader(useRequestEvent(), "cookie") || "", opts);
  } else if (import.meta.client) {
    return parse(document.cookie, opts);
  }
}
function serializeCookie(name, value, opts = {}) {
  if (value === null || value === void 0) {
    return serialize(name, value, { ...opts, maxAge: -1 });
  }
  return serialize(name, value, opts);
}
function writeClientCookie(name, value, opts = {}) {
  if (import.meta.client) {
    document.cookie = serializeCookie(name, value, opts);
  }
}
function writeServerCookie(event, name, value, opts = {}) {
  if (event) {
    if (value !== null && value !== void 0) {
      return setCookie(event, name, value, opts);
    }
    if (getCookie(event, name) !== void 0) {
      return deleteCookie(event, name, opts);
    }
  }
}
const MAX_TIMEOUT_DELAY = 2147483647;
function cookieRef(value, delay, shouldWatch) {
  let timeout;
  let unsubscribe;
  let elapsed = 0;
  const internalRef = shouldWatch ? ref(value) : { value };
  if (getCurrentScope()) {
    onScopeDispose(() => {
      unsubscribe?.();
      clearTimeout(timeout);
    });
  }
  return customRef((track, trigger) => {
    if (shouldWatch) {
      unsubscribe = watch(internalRef, trigger);
    }
    function createExpirationTimeout() {
      elapsed = 0;
      clearTimeout(timeout);
      const timeRemaining = delay - elapsed;
      const timeoutLength = timeRemaining < MAX_TIMEOUT_DELAY ? timeRemaining : MAX_TIMEOUT_DELAY;
      timeout = setTimeout(() => {
        elapsed += timeoutLength;
        if (elapsed < delay) {
          return createExpirationTimeout();
        }
        internalRef.value = void 0;
        trigger();
      }, timeoutLength);
    }
    return {
      get() {
        track();
        return internalRef.value;
      },
      set(newValue) {
        createExpirationTimeout();
        internalRef.value = newValue;
        trigger();
      }
    };
  });
}
