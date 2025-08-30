import {
  toLazyValue
} from "./chunk-4MKZ4JDN.js";

// src/data-loaders/defineColadaLoader.ts
import {
  useRoute,
  useRouter
} from "vue-router";
import {
  ABORT_CONTROLLER_KEY,
  APP_KEY,
  IS_USE_DATA_LOADER_KEY,
  LOADER_ENTRIES_KEY,
  NAVIGATION_RESULTS_KEY,
  PENDING_LOCATION_KEY,
  STAGED_NO_VALUE,
  NavigationResult,
  assign,
  getCurrentContext,
  isSubsetOf,
  setCurrentContext,
  trackRoute,
  IS_SSR_KEY
} from "unplugin-vue-router/data-loaders";
import { shallowRef, watch } from "vue";
import {
  useQuery
} from "@pinia/colada";
function defineColadaLoader(nameOrOptions, _options) {
  _options = _options || nameOrOptions;
  const loader = _options.query;
  const options = {
    ...DEFAULT_DEFINE_LOADER_OPTIONS,
    ..._options,
    commit: _options?.commit || "after-load"
  };
  let isInitial = true;
  function load(to, router, from, parent, reload) {
    const entries = router[LOADER_ENTRIES_KEY];
    const isSSR = router[IS_SSR_KEY];
    const key = serializeQueryKey(options.key, to);
    if (!entries.has(loader)) {
      const route = shallowRef(to);
      entries.set(loader, {
        // force the type to match
        data: shallowRef(),
        isLoading: shallowRef(false),
        error: shallowRef(),
        to,
        options,
        children: /* @__PURE__ */ new Set(),
        resetPending() {
          this.pendingTo = null;
          this.pendingLoad = null;
          this.isLoading.value = false;
        },
        staged: STAGED_NO_VALUE,
        stagedError: null,
        commit,
        tracked: /* @__PURE__ */ new Map(),
        ext: null,
        route,
        pendingTo: null,
        pendingLoad: null
      });
    }
    const entry = entries.get(loader);
    if (entry.pendingTo === to && entry.pendingLoad) {
      return entry.pendingLoad;
    }
    const currentContext = getCurrentContext();
    if (process.env.NODE_ENV === "development") {
      if (parent !== currentContext[0]) {
        console.warn(
          `\u274C\u{1F476} "${key}" has a different parent than the current context. This shouldn't be happening. Please report a bug with a reproduction to https://github.com/posva/unplugin-vue-router/`
        );
      }
    }
    setCurrentContext([entry, router, to]);
    const app = router[APP_KEY];
    if (!entry.ext) {
      entry.ext = useQuery({
        ...options,
        query: () => {
          const route = entry.route.value;
          const [trackedRoute, params, query, hash] = trackRoute(route);
          entry.tracked.set(
            joinKeys(serializeQueryKey(options.key, trackedRoute)),
            {
              ready: false,
              params,
              query,
              hash
            }
          );
          return app.runWithContext(
            () => loader(trackedRoute, {
              signal: route.meta[ABORT_CONTROLLER_KEY]?.signal
            })
          );
        },
        key: () => toValueWithParameters(options.key, entry.route.value)
        // TODO: cleanup if gc
        // onDestroy() {
        //   entries.delete(loader)
        // }
      });
      if (entry.ext.asyncStatus.value === "loading") {
        reload = false;
      }
    }
    const { isLoading, data, error, ext } = entry;
    if (isInitial) {
      isInitial = false;
      if (ext.data.value !== void 0) {
        data.value = ext.data.value;
        setCurrentContext(currentContext);
        return entry.pendingLoad = Promise.resolve();
      }
    }
    if (entry.route.value !== to) {
      const tracked = entry.tracked.get(joinKeys(key));
      reload = !tracked || hasRouteChanged(to, tracked);
    }
    entry.route.value = entry.pendingTo = to;
    isLoading.value = true;
    entry.staged = STAGED_NO_VALUE;
    entry.stagedError = error.value;
    const currentLoad = ext[reload ? "refetch" : "refresh"]().then(() => {
      if (entry.pendingLoad === currentLoad) {
        const newError = ext.error.value;
        if (newError) {
          entry.stagedError = newError;
          if (!toLazyValue(options.lazy, to, from) || isSSR) {
            throw newError;
          }
        } else {
          const newData = ext.data.value;
          if (newData instanceof NavigationResult) {
            to.meta[NAVIGATION_RESULTS_KEY].push(newData);
          } else {
            entry.staged = newData;
            entry.stagedError = null;
          }
        }
      }
    }).finally(() => {
      setCurrentContext(currentContext);
      if (entry.pendingLoad === currentLoad) {
        isLoading.value = false;
        if (options.commit === "immediate" || // outside of a navigation
        !router[PENDING_LOCATION_KEY]) {
          entry.commit(to);
        }
      } else {
      }
    });
    setCurrentContext(currentContext);
    entry.pendingLoad = currentLoad;
    return currentLoad;
  }
  function commit(to) {
    const key = serializeQueryKey(options.key, to);
    if (this.pendingTo === to) {
      if (process.env.NODE_ENV === "development") {
        if (this.staged === STAGED_NO_VALUE) {
          console.warn(
            `Loader "${key}"'s "commit()" was called but there is no staged data.`
          );
        }
      }
      if (this.staged !== STAGED_NO_VALUE) {
        this.data.value = this.staged;
        if (process.env.NODE_ENV === "development" && !this.tracked.has(joinKeys(key))) {
          console.warn(
            `A query was defined with the same key as the loader "[${key.join(", ")}]". If the "key" is meant to be the same, you should directly use the data loader instead. If not, change the key of the "useQuery()".
See https://pinia-colada.esm.dev/#TODO`
          );
          return;
        }
        this.tracked.get(joinKeys(key)).ready = true;
      }
      this.error.value = this.stagedError;
      this.staged = STAGED_NO_VALUE;
      this.stagedError = this.error.value;
      this.to = to;
      this.pendingTo = null;
      for (const childEntry of this.children) {
        childEntry.commit(to);
      }
    } else {
    }
  }
  const useDataLoader = () => {
    const currentEntry = getCurrentContext();
    const [parentEntry, _router, _route] = currentEntry;
    const router = _router || useRouter();
    const route = _route || useRoute();
    const app = router[APP_KEY];
    const entries = router[LOADER_ENTRIES_KEY];
    let entry = entries.get(loader);
    if (
      // if the entry doesn't exist, create it with load and ensure it's loading
      !entry || // we are nested and the parent is loading a different route than us
      parentEntry && entry.pendingTo !== route || // The user somehow rendered the page without a navigation
      !entry.pendingLoad
    ) {
      app.runWithContext(
        () => (
          // in this case we always need to run the functions for nested loaders consistency
          load(route, router, void 0, parentEntry, true)
        )
      );
    }
    entry = entries.get(loader);
    if (parentEntry) {
      if (parentEntry !== entry) {
        parentEntry.children.add(entry);
      } else {
      }
    }
    const { data, error, isLoading, ext } = entry;
    watch(ext.data, (newData) => {
      if (!router[PENDING_LOCATION_KEY]) {
        data.value = newData;
      }
    });
    watch(ext.isLoading, (isFetching) => {
      if (!router[PENDING_LOCATION_KEY]) {
        isLoading.value = isFetching;
      }
    });
    watch(ext.error, (newError) => {
      if (!router[PENDING_LOCATION_KEY]) {
        error.value = newError;
      }
    });
    const useDataLoaderResult = {
      data,
      error,
      isLoading,
      reload: (to = router.currentRoute.value) => app.runWithContext(() => load(to, router, void 0, void 0, true)).then(() => entry.commit(to)),
      // pinia colada
      refetch: (to = router.currentRoute.value) => app.runWithContext(() => load(to, router, void 0, void 0, true)).then(() => (entry.commit(to), entry.ext.state.value)),
      refresh: (to = router.currentRoute.value) => app.runWithContext(() => load(to, router)).then(() => (entry.commit(to), entry.ext.state.value)),
      status: ext.status,
      asyncStatus: ext.asyncStatus,
      state: ext.state,
      isPending: ext.isPending
    };
    const promise = entry.pendingLoad.then(() => {
      return entry.staged === STAGED_NO_VALUE ? ext.data.value : entry.staged;
    }).catch((e) => parentEntry ? Promise.reject(e) : null);
    setCurrentContext(currentEntry);
    return assign(promise, useDataLoaderResult);
  };
  useDataLoader[IS_USE_DATA_LOADER_KEY] = true;
  useDataLoader._ = {
    load,
    options,
    // @ts-expect-error: return type has the generics
    getEntry(router) {
      return router[LOADER_ENTRIES_KEY].get(loader);
    }
  };
  return useDataLoader;
}
var joinKeys = (keys) => keys.join("|");
function hasRouteChanged(to, tracked) {
  return !tracked.ready || !isSubsetOf(tracked.params, to.params) || !isSubsetOf(tracked.query, to.query) || tracked.hash.v != null && tracked.hash.v !== to.hash;
}
var DEFAULT_DEFINE_LOADER_OPTIONS = {
  lazy: false,
  server: true,
  commit: "after-load"
};
var toValueWithParameters = (optionValue, arg) => {
  return typeof optionValue === "function" ? (
    // This should work in TS without a cast
    optionValue(arg)
  ) : optionValue;
};
function serializeQueryKey(keyOption, to) {
  const key = toValueWithParameters(keyOption, to);
  const keys = Array.isArray(key) ? key : [key];
  return keys.map(stringifyFlatObject);
}
function stringifyFlatObject(obj) {
  return obj && typeof obj === "object" ? JSON.stringify(obj, Object.keys(obj).sort()) : String(obj);
}
export {
  defineColadaLoader
};
