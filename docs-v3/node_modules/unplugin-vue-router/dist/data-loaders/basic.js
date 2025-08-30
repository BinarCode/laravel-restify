import {
  toLazyValue
} from "./chunk-4MKZ4JDN.js";

// src/data-loaders/defineLoader.ts
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
  getCurrentContext,
  setCurrentContext,
  IS_SSR_KEY
} from "unplugin-vue-router/data-loaders";
import { shallowRef } from "vue";
function defineBasicLoader(nameOrLoader, _loaderOrOptions, opts) {
  const loader = typeof nameOrLoader === "function" ? nameOrLoader : _loaderOrOptions;
  opts = typeof _loaderOrOptions === "object" ? _loaderOrOptions : opts;
  const options = {
    ...DEFAULT_DEFINE_LOADER_OPTIONS,
    ...opts,
    // avoid opts overriding with `undefined`
    commit: opts?.commit || DEFAULT_DEFINE_LOADER_OPTIONS.commit
  };
  function load(to, router, from, parent) {
    const entries = router[LOADER_ENTRIES_KEY];
    const isSSR = router[IS_SSR_KEY];
    if (!entries.has(loader)) {
      entries.set(loader, {
        // force the type to match
        data: shallowRef(),
        isLoading: shallowRef(false),
        error: shallowRef(),
        to,
        options,
        children: /* @__PURE__ */ new Set(),
        resetPending() {
          this.pendingLoad = null;
          this.pendingTo = null;
        },
        pendingLoad: null,
        pendingTo: null,
        staged: STAGED_NO_VALUE,
        stagedError: null,
        commit
      });
    }
    const entry = entries.get(loader);
    if (entry.pendingTo === to && entry.pendingLoad) {
      return entry.pendingLoad;
    }
    const { error, isLoading, data } = entry;
    const initialRootData = router[INITIAL_DATA_KEY];
    const key = options.key || "";
    let initialData = STAGED_NO_VALUE;
    if (initialRootData && key in initialRootData) {
      initialData = initialRootData[key];
      delete initialRootData[key];
    }
    if (initialData !== STAGED_NO_VALUE) {
      data.value = initialData;
      return entry.pendingLoad = Promise.resolve();
    }
    entry.pendingTo = to;
    isLoading.value = true;
    const currentContext = getCurrentContext();
    if (process.env.NODE_ENV === "development") {
      if (parent !== currentContext[0]) {
        console.warn(
          `\u274C\u{1F476} "${options.key}" has a different parent than the current context. This shouldn't be happening. Please report a bug with a reproduction to https://github.com/posva/unplugin-vue-router/`
        );
      }
    }
    setCurrentContext([entry, router, to]);
    entry.staged = STAGED_NO_VALUE;
    entry.stagedError = error.value;
    const currentLoad = Promise.resolve(
      loader(to, { signal: to.meta[ABORT_CONTROLLER_KEY]?.signal })
    ).then((d) => {
      if (entry.pendingLoad === currentLoad) {
        if (d instanceof NavigationResult) {
          to.meta[NAVIGATION_RESULTS_KEY].push(d);
        } else {
          entry.staged = d;
          entry.stagedError = null;
        }
      }
    }).catch((e) => {
      if (entry.pendingLoad === currentLoad) {
        entry.stagedError = e;
        if (!toLazyValue(options.lazy, to, from) || isSSR) {
          throw e;
        }
      }
    }).finally(() => {
      setCurrentContext(currentContext);
      if (entry.pendingLoad === currentLoad) {
        isLoading.value = false;
        if (options.commit === "immediate" || // outside of navigation
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
    if (this.pendingTo === to) {
      if (process.env.NODE_ENV === "development") {
        if (this.staged === STAGED_NO_VALUE) {
          console.warn(
            `Loader "${options.key}"'s "commit()" was called but there is no staged data.`
          );
        }
      }
      if (this.staged !== STAGED_NO_VALUE) {
        this.data.value = this.staged;
      }
      this.error.value = this.stagedError;
      this.staged = STAGED_NO_VALUE;
      this.stagedError = this.error.value;
      this.pendingTo = null;
      this.to = to;
      for (const childEntry of this.children) {
        childEntry.commit(to);
      }
    }
  }
  const useDataLoader = () => {
    const currentContext = getCurrentContext();
    const [parentEntry, _router, _route] = currentContext;
    const router = _router || useRouter();
    const route = _route || useRoute();
    const entries = router[LOADER_ENTRIES_KEY];
    let entry = entries.get(loader);
    if (
      // if the entry doesn't exist, create it with load and ensure it's loading
      !entry || // the existing pending location isn't good, we need to load again
      parentEntry && entry.pendingTo !== route || // we could also check for: but that would break nested loaders since they need to be always called to be associated with the parent
      // && entry.to !== route
      // the user managed to render the router view after a valid navigation + a failed navigation
      // https://github.com/posva/unplugin-vue-router/issues/495
      !entry.pendingLoad
    ) {
      router[APP_KEY].runWithContext(
        () => load(route, router, void 0, parentEntry)
      );
    }
    entry = entries.get(loader);
    if (parentEntry) {
      if (parentEntry === entry) {
        console.warn(
          `\u{1F476}\u274C "${options.key}" has itself as parent. This shouldn't be happening. Please report a bug with a reproduction to https://github.com/posva/unplugin-vue-router/`
        );
      }
      parentEntry.children.add(entry);
    }
    const { data, error, isLoading } = entry;
    const useDataLoaderResult = {
      data,
      error,
      isLoading,
      reload: (to = router.currentRoute.value) => router[APP_KEY].runWithContext(() => load(to, router)).then(
        () => entry.commit(to)
      )
    };
    const promise = entry.pendingLoad.then(() => {
      return entry.staged === STAGED_NO_VALUE ? data.value : entry.staged;
    }).catch((e) => parentEntry ? Promise.reject(e) : null);
    setCurrentContext(currentContext);
    return Object.assign(promise, useDataLoaderResult);
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
var DEFAULT_DEFINE_LOADER_OPTIONS = {
  lazy: false,
  server: true,
  commit: "after-load"
};
var SERVER_INITIAL_DATA_KEY = Symbol();
var INITIAL_DATA_KEY = Symbol();
export {
  defineBasicLoader
};
