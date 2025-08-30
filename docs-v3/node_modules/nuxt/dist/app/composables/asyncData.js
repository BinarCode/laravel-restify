import { computed, getCurrentInstance, getCurrentScope, inject, isShallow, nextTick, onBeforeMount, onScopeDispose, onServerPrefetch, onUnmounted, ref, shallowRef, toRef, toValue, unref, watch } from "vue";
import { captureStackTrace } from "errx";
import { debounce } from "perfect-debounce";
import { hash } from "ohash";
import { useNuxtApp } from "../nuxt.js";
import { toArray } from "../utils.js";
import { clientOnlySymbol } from "../components/client-only.js";
import { createError } from "./error.js";
import { onNuxtReady } from "./ready.js";
import { asyncDataDefaults, granularCachedData, pendingWhenIdle, purgeCachedData, resetAsyncDataToUndefined } from "#build/nuxt.config.mjs";
const isDefer = (dedupe) => dedupe === "defer" || dedupe === false;
export function useAsyncData(...args) {
  const autoKey = typeof args[args.length - 1] === "string" ? args.pop() : void 0;
  if (_isAutoKeyNeeded(args[0], args[1])) {
    args.unshift(autoKey);
  }
  let [_key, _handler, options = {}] = args;
  const key = computed(() => toValue(_key));
  if (typeof key.value !== "string") {
    throw new TypeError("[nuxt] [useAsyncData] key must be a string.");
  }
  if (typeof _handler !== "function") {
    throw new TypeError("[nuxt] [useAsyncData] handler must be a function.");
  }
  const nuxtApp = useNuxtApp();
  options.server ??= true;
  options.default ??= getDefault;
  options.getCachedData ??= getDefaultCachedData;
  options.lazy ??= false;
  options.immediate ??= true;
  options.deep ??= asyncDataDefaults.deep;
  options.dedupe ??= "cancel";
  const functionName = options._functionName || "useAsyncData";
  if (import.meta.dev && typeof options.dedupe === "boolean") {
    console.warn(`[nuxt] \`boolean\` values are deprecated for the \`dedupe\` option of \`${functionName}\` and will be removed in the future. Use 'cancel' or 'defer' instead.`);
  }
  const currentData = nuxtApp._asyncData[key.value];
  if (isDev && currentData) {
    const warnings = [];
    const values = createHash(_handler, options);
    if (values.handler !== currentData._hash?.handler) {
      warnings.push(`different handler`);
    }
    for (const opt of ["transform", "pick", "getCachedData"]) {
      if (values[opt] !== currentData._hash[opt]) {
        warnings.push(`different \`${opt}\` option`);
      }
    }
    if (currentData._default.toString() !== options.default.toString()) {
      warnings.push(`different \`default\` value`);
    }
    if (options.deep && isShallow(currentData.data)) {
      warnings.push(`mismatching \`deep\` option`);
    }
    if (warnings.length) {
      const distURL = import.meta.url.replace(/\/app\/.*$/, "/app");
      const { source, line, column } = captureStackTrace().find((entry) => !entry.source.startsWith(distURL)) ?? {};
      const explanation = source ? ` (used at ${source.replace(/^file:\/\//, "")}:${line}:${column})` : "";
      console.warn(`[nuxt] [${functionName}] Incompatible options detected for "${key.value}"${explanation}:
${warnings.map((w) => `- ${w}`).join("\n")}
You can use a different key or move the call to a composable to ensure the options are shared across calls.`);
    }
  }
  const initialFetchOptions = { cause: "initial", dedupe: options.dedupe };
  if (!nuxtApp._asyncData[key.value]?._init) {
    initialFetchOptions.cachedData = options.getCachedData(key.value, nuxtApp, { cause: "initial" });
    nuxtApp._asyncData[key.value] = createAsyncData(nuxtApp, key.value, _handler, options, initialFetchOptions.cachedData);
  }
  const asyncData = nuxtApp._asyncData[key.value];
  asyncData._deps++;
  const initialFetch = () => nuxtApp._asyncData[key.value].execute(initialFetchOptions);
  const fetchOnServer = options.server !== false && nuxtApp.payload.serverRendered;
  if (import.meta.server && fetchOnServer && options.immediate) {
    const promise = initialFetch();
    if (getCurrentInstance()) {
      onServerPrefetch(() => promise);
    } else {
      nuxtApp.hook("app:created", async () => {
        await promise;
      });
    }
  }
  if (import.meta.client) {
    let unregister = function(key2) {
      const data = nuxtApp._asyncData[key2];
      if (data?._deps) {
        data._deps--;
        if (data._deps === 0) {
          data?._off();
        }
      }
    };
    const instance = getCurrentInstance();
    if (instance && fetchOnServer && options.immediate && !instance.sp) {
      instance.sp = [];
    }
    if (import.meta.dev && !nuxtApp.isHydrating && !nuxtApp._processingMiddleware && (!instance || instance?.isMounted)) {
      console.warn(`[nuxt] [${functionName}] Component is already mounted, please use $fetch instead. See https://nuxt.com/docs/getting-started/data-fetching`);
    }
    if (instance && !instance._nuxtOnBeforeMountCbs) {
      instance._nuxtOnBeforeMountCbs = [];
      const cbs = instance._nuxtOnBeforeMountCbs;
      onBeforeMount(() => {
        cbs.forEach((cb) => {
          cb();
        });
        cbs.splice(0, cbs.length);
      });
      onUnmounted(() => cbs.splice(0, cbs.length));
    }
    const isWithinClientOnly = instance && (instance._nuxtClientOnly || inject(clientOnlySymbol, false));
    if (fetchOnServer && nuxtApp.isHydrating && (asyncData.error.value || asyncData.data.value != null)) {
      if (pendingWhenIdle) {
        asyncData.pending.value = false;
      }
      asyncData.status.value = asyncData.error.value ? "error" : "success";
    } else if (instance && (!isWithinClientOnly && nuxtApp.payload.serverRendered && nuxtApp.isHydrating || options.lazy) && options.immediate) {
      instance._nuxtOnBeforeMountCbs.push(initialFetch);
    } else if (options.immediate) {
      initialFetch();
    }
    const hasScope = getCurrentScope();
    const unsubExecute = watch([key, ...options.watch || []], ([newKey], [oldKey]) => {
      if ((newKey || oldKey) && newKey !== oldKey) {
        const hasRun = nuxtApp._asyncData[oldKey]?.data.value !== asyncDataDefaults.value;
        if (oldKey) {
          unregister(oldKey);
        }
        const initialFetchOptions2 = { cause: "initial", dedupe: options.dedupe };
        if (!nuxtApp._asyncData[newKey]?._init) {
          initialFetchOptions2.cachedData = options.getCachedData(newKey, nuxtApp, { cause: "initial" });
          nuxtApp._asyncData[newKey] = createAsyncData(nuxtApp, newKey, _handler, options, initialFetchOptions2.cachedData);
        }
        nuxtApp._asyncData[newKey]._deps++;
        if (options.immediate || hasRun) {
          nuxtApp._asyncData[newKey].execute(initialFetchOptions2);
        }
      } else {
        asyncData._execute({ cause: "watch", dedupe: options.dedupe });
      }
    }, { flush: "sync" });
    if (hasScope) {
      onScopeDispose(() => {
        unsubExecute();
        unregister(key.value);
      });
    }
  }
  const asyncReturn = {
    data: writableComputedRef(() => nuxtApp._asyncData[key.value]?.data),
    pending: writableComputedRef(() => nuxtApp._asyncData[key.value]?.pending),
    status: writableComputedRef(() => nuxtApp._asyncData[key.value]?.status),
    error: writableComputedRef(() => nuxtApp._asyncData[key.value]?.error),
    refresh: (...args2) => nuxtApp._asyncData[key.value].execute(...args2),
    execute: (...args2) => nuxtApp._asyncData[key.value].execute(...args2),
    clear: () => clearNuxtDataByKey(nuxtApp, key.value)
  };
  const asyncDataPromise = Promise.resolve(nuxtApp._asyncDataPromises[key.value]).then(() => asyncReturn);
  Object.assign(asyncDataPromise, asyncReturn);
  return asyncDataPromise;
}
function writableComputedRef(getter) {
  return computed({
    get() {
      return getter()?.value;
    },
    set(value) {
      const ref2 = getter();
      if (ref2) {
        ref2.value = value;
      }
    }
  });
}
export function useLazyAsyncData(...args) {
  const autoKey = typeof args[args.length - 1] === "string" ? args.pop() : void 0;
  if (_isAutoKeyNeeded(args[0], args[1])) {
    args.unshift(autoKey);
  }
  const [key, handler, options = {}] = args;
  if (import.meta.dev) {
    options._functionName ||= "useLazyAsyncData";
  }
  return useAsyncData(key, handler, { ...options, lazy: true }, null);
}
function _isAutoKeyNeeded(keyOrFetcher, fetcher) {
  if (typeof keyOrFetcher === "string") {
    return false;
  }
  if (typeof keyOrFetcher === "object" && keyOrFetcher !== null) {
    return false;
  }
  if (typeof keyOrFetcher === "function" && typeof fetcher === "function") {
    return false;
  }
  return true;
}
export function useNuxtData(key) {
  const nuxtApp = useNuxtApp();
  if (!(key in nuxtApp.payload.data)) {
    nuxtApp.payload.data[key] = asyncDataDefaults.value;
  }
  if (nuxtApp._asyncData[key]) {
    const data = nuxtApp._asyncData[key];
    data._deps++;
    if (getCurrentScope()) {
      onScopeDispose(() => {
        data._deps--;
        if (data._deps === 0) {
          data?._off();
        }
      });
    }
  }
  return {
    data: computed({
      get() {
        return nuxtApp._asyncData[key]?.data.value ?? nuxtApp.payload.data[key];
      },
      set(value) {
        if (nuxtApp._asyncData[key]) {
          nuxtApp._asyncData[key].data.value = value;
        } else {
          nuxtApp.payload.data[key] = value;
        }
      }
    })
  };
}
export async function refreshNuxtData(keys) {
  if (import.meta.server) {
    return Promise.resolve();
  }
  await new Promise((resolve) => onNuxtReady(resolve));
  const _keys = keys ? toArray(keys) : void 0;
  await useNuxtApp().hooks.callHookParallel("app:data:refresh", _keys);
}
export function clearNuxtData(keys) {
  const nuxtApp = useNuxtApp();
  const _allKeys = Object.keys(nuxtApp.payload.data);
  const _keys = !keys ? _allKeys : typeof keys === "function" ? _allKeys.filter(keys) : toArray(keys);
  for (const key of _keys) {
    clearNuxtDataByKey(nuxtApp, key);
  }
}
function clearNuxtDataByKey(nuxtApp, key) {
  if (key in nuxtApp.payload.data) {
    nuxtApp.payload.data[key] = void 0;
  }
  if (key in nuxtApp.payload._errors) {
    nuxtApp.payload._errors[key] = asyncDataDefaults.errorValue;
  }
  if (nuxtApp._asyncData[key]) {
    nuxtApp._asyncData[key].data.value = resetAsyncDataToUndefined ? void 0 : unref(nuxtApp._asyncData[key]._default());
    nuxtApp._asyncData[key].error.value = asyncDataDefaults.errorValue;
    if (pendingWhenIdle) {
      nuxtApp._asyncData[key].pending.value = false;
    }
    nuxtApp._asyncData[key].status.value = "idle";
  }
  if (key in nuxtApp._asyncDataPromises) {
    if (nuxtApp._asyncDataPromises[key]) {
      nuxtApp._asyncDataPromises[key].cancelled = true;
    }
    nuxtApp._asyncDataPromises[key] = void 0;
  }
}
function pick(obj, keys) {
  const newObj = {};
  for (const key of keys) {
    newObj[key] = obj[key];
  }
  return newObj;
}
const isDev = import.meta.dev;
function createAsyncData(nuxtApp, key, _handler, options, initialCachedData) {
  nuxtApp.payload._errors[key] ??= asyncDataDefaults.errorValue;
  const hasCustomGetCachedData = options.getCachedData !== getDefaultCachedData;
  const handler = import.meta.client || !import.meta.prerender || !nuxtApp.ssrContext?._sharedPrerenderCache ? _handler : () => {
    const value = nuxtApp.ssrContext._sharedPrerenderCache.get(key);
    if (value) {
      return value;
    }
    const promise = Promise.resolve().then(() => nuxtApp.runWithContext(() => _handler(nuxtApp)));
    nuxtApp.ssrContext._sharedPrerenderCache.set(key, promise);
    return promise;
  };
  const _ref = options.deep ? ref : shallowRef;
  const hasCachedData = initialCachedData != null;
  const unsubRefreshAsyncData = nuxtApp.hook("app:data:refresh", async (keys) => {
    if (!keys || keys.includes(key)) {
      await asyncData.execute({ cause: "refresh:hook" });
    }
  });
  const asyncData = {
    data: _ref(hasCachedData ? initialCachedData : options.default()),
    pending: pendingWhenIdle ? shallowRef(!hasCachedData) : computed(() => asyncData.status.value === "pending"),
    error: toRef(nuxtApp.payload._errors, key),
    status: shallowRef("idle"),
    execute: (opts = {}) => {
      if (nuxtApp._asyncDataPromises[key]) {
        if (isDefer(opts.dedupe ?? options.dedupe)) {
          return nuxtApp._asyncDataPromises[key];
        }
        nuxtApp._asyncDataPromises[key].cancelled = true;
      }
      if (granularCachedData || opts.cause === "initial" || nuxtApp.isHydrating) {
        const cachedData = "cachedData" in opts ? opts.cachedData : options.getCachedData(key, nuxtApp, { cause: opts.cause ?? "refresh:manual" });
        if (cachedData != null) {
          nuxtApp.payload.data[key] = asyncData.data.value = cachedData;
          asyncData.error.value = asyncDataDefaults.errorValue;
          asyncData.status.value = "success";
          return Promise.resolve(cachedData);
        }
      }
      if (pendingWhenIdle) {
        asyncData.pending.value = true;
      }
      asyncData.status.value = "pending";
      const promise = new Promise(
        (resolve, reject) => {
          try {
            resolve(handler(nuxtApp));
          } catch (err) {
            reject(err);
          }
        }
      ).then(async (_result) => {
        if (promise.cancelled) {
          return nuxtApp._asyncDataPromises[key];
        }
        let result = _result;
        if (options.transform) {
          result = await options.transform(_result);
        }
        if (options.pick) {
          result = pick(result, options.pick);
        }
        if (import.meta.dev && import.meta.server && typeof result === "undefined") {
          const stack = captureStackTrace();
          const { source, line, column } = stack[stack.length - 1] ?? {};
          const explanation = source ? ` (used at ${source.replace(/^file:\/\//, "")}:${line}:${column})` : "";
          console.warn(`[nuxt] \`${options._functionName || "useAsyncData"}${explanation}\` must return a value (it should not be \`undefined\`) or the request may be duplicated on the client side.`);
        }
        nuxtApp.payload.data[key] = result;
        asyncData.data.value = result;
        asyncData.error.value = asyncDataDefaults.errorValue;
        asyncData.status.value = "success";
      }).catch((error) => {
        if (promise.cancelled) {
          return nuxtApp._asyncDataPromises[key];
        }
        asyncData.error.value = createError(error);
        asyncData.data.value = unref(options.default());
        asyncData.status.value = "error";
      }).finally(() => {
        if (promise.cancelled) {
          return;
        }
        if (pendingWhenIdle) {
          asyncData.pending.value = false;
        }
        delete nuxtApp._asyncDataPromises[key];
      });
      nuxtApp._asyncDataPromises[key] = promise;
      return nuxtApp._asyncDataPromises[key];
    },
    _execute: debounce((...args) => asyncData.execute(...args), 0, { leading: true }),
    _default: options.default,
    _deps: 0,
    _init: true,
    _hash: isDev ? createHash(_handler, options) : void 0,
    _off: () => {
      unsubRefreshAsyncData();
      if (nuxtApp._asyncData[key]?._init) {
        nuxtApp._asyncData[key]._init = false;
      }
      if (purgeCachedData && !hasCustomGetCachedData) {
        nextTick(() => {
          if (!nuxtApp._asyncData[key]?._init) {
            clearNuxtDataByKey(nuxtApp, key);
            asyncData.execute = () => Promise.resolve();
            asyncData.data.value = asyncDataDefaults.value;
          }
        });
      }
    }
  };
  return asyncData;
}
const getDefault = () => asyncDataDefaults.value;
const getDefaultCachedData = (key, nuxtApp, ctx) => {
  if (nuxtApp.isHydrating) {
    return nuxtApp.payload.data[key];
  }
  if (ctx.cause !== "refresh:manual" && ctx.cause !== "refresh:hook") {
    return nuxtApp.static.data[key];
  }
};
function createHash(_handler, options) {
  return {
    handler: hash(_handler),
    transform: options.transform ? hash(options.transform) : void 0,
    pick: options.pick ? hash(options.pick) : void 0,
    getCachedData: options.getCachedData ? hash(options.getCachedData) : void 0
  };
}
