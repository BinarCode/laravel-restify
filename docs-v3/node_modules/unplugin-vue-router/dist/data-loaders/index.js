import {
  toLazyValue
} from "./chunk-4MKZ4JDN.js";

// src/data-loaders/navigation-guard.ts
import { isNavigationFailure } from "vue-router";
import {
  effectScope,
  inject,
  shallowRef
} from "vue";

// src/data-loaders/symbols.ts
var LOADER_SET_KEY = Symbol("loaders");
var LOADER_ENTRIES_KEY = Symbol("loaderEntries");
var IS_USE_DATA_LOADER_KEY = Symbol();
var PENDING_LOCATION_KEY = Symbol();
var STAGED_NO_VALUE = Symbol();
var APP_KEY = Symbol();
var ABORT_CONTROLLER_KEY = Symbol();
var NAVIGATION_RESULTS_KEY = Symbol();
var IS_SSR_KEY = Symbol();

// src/data-loaders/utils.ts
function isDataLoader(loader) {
  return loader && loader[IS_USE_DATA_LOADER_KEY];
}
var currentContext;
function getCurrentContext() {
  return currentContext || [];
}
function setCurrentContext(context) {
  currentContext = context ? context.length ? context : null : null;
}
function withLoaderContext(promise) {
  const context = currentContext;
  return promise.finally(() => currentContext = context);
}
var assign = Object.assign;
function trackRoute(route) {
  const [params, paramReads] = trackObjectReads(route.params);
  const [query, queryReads] = trackObjectReads(route.query);
  let hash = { v: null };
  return [
    {
      ...route,
      // track the hash
      get hash() {
        return hash.v = route.hash;
      },
      params,
      query
    },
    paramReads,
    queryReads,
    hash
  ];
}
function trackObjectReads(obj) {
  const reads = {};
  return [
    new Proxy(obj, {
      get(target, p, receiver) {
        const value = Reflect.get(target, p, receiver);
        reads[p] = value;
        return value;
      }
    }),
    reads
  ];
}
function isSubsetOf(inner, outer) {
  for (const key in inner) {
    const innerValue = inner[key];
    const outerValue = outer[key];
    if (typeof innerValue === "string") {
      if (innerValue !== outerValue) return false;
    } else if (!innerValue || !outerValue) {
      if (innerValue !== outerValue) return false;
    } else {
      if (!Array.isArray(outerValue) || outerValue.length !== innerValue.length || innerValue.some((value, i) => value !== outerValue[i]))
        return false;
    }
  }
  return true;
}

// src/data-loaders/navigation-guard.ts
var IS_DATA_LOADING_KEY = Symbol();
function setupLoaderGuard({
  router,
  app,
  effect: scope,
  isSSR,
  errors: globalErrors = [],
  selectNavigationResult = (results) => results[0].value
}) {
  if (router[LOADER_ENTRIES_KEY] != null) {
    if (process.env.NODE_ENV !== "production") {
      console.warn(
        "[vue-router]: Data Loader was setup twice. Make sure to setup only once."
      );
    }
    return () => {
    };
  }
  if (process.env.NODE_ENV === "development" && !isSSR) {
    console.warn(
      "[vue-router]: Data Loader is experimental and subject to breaking changes in the future."
    );
  }
  router[LOADER_ENTRIES_KEY] = /* @__PURE__ */ new WeakMap();
  router[APP_KEY] = app;
  router[IS_SSR_KEY] = !!isSSR;
  const isDataLoading = scope.run(() => shallowRef(false));
  app.provide(IS_DATA_LOADING_KEY, isDataLoading);
  const removeLoaderGuard = router.beforeEach((to) => {
    if (router[PENDING_LOCATION_KEY]) {
      router[PENDING_LOCATION_KEY].meta[ABORT_CONTROLLER_KEY]?.abort();
    }
    router[PENDING_LOCATION_KEY] = to;
    to.meta[LOADER_SET_KEY] = /* @__PURE__ */ new Set();
    to.meta[ABORT_CONTROLLER_KEY] = new AbortController();
    to.meta[NAVIGATION_RESULTS_KEY] = [];
    const lazyLoadingPromises = [];
    for (const record of to.matched) {
      if (!record.meta[LOADER_SET_KEY]) {
        record.meta[LOADER_SET_KEY] = new Set(record.meta.loaders || []);
        for (const componentName in record.components) {
          const component = record.components[componentName];
          const promise = (isAsyncModule(component) ? component() : (
            // we also support __loaders exported as an option to get around some temporary limitations
            Promise.resolve(
              component
            )
          )).then((viewModule) => {
            if (typeof viewModule === "function") return;
            for (const exportName in viewModule) {
              const exportValue = viewModule[exportName];
              if (isDataLoader(exportValue)) {
                record.meta[LOADER_SET_KEY].add(exportValue);
              }
            }
            if (Array.isArray(viewModule.__loaders)) {
              for (const loader of viewModule.__loaders) {
                if (isDataLoader(loader)) {
                  record.meta[LOADER_SET_KEY].add(loader);
                }
              }
            }
          });
          lazyLoadingPromises.push(promise);
        }
      }
    }
    return Promise.all(lazyLoadingPromises).then(() => {
      for (const record of to.matched) {
        for (const loader of record.meta[LOADER_SET_KEY]) {
          to.meta[LOADER_SET_KEY].add(loader);
        }
      }
    });
  });
  const removeDataLoaderGuard = router.beforeResolve((to, from) => {
    const loaders = Array.from(to.meta[LOADER_SET_KEY]);
    setCurrentContext([]);
    isDataLoading.value = true;
    return Promise.all(
      loaders.map((loader) => {
        const { server, lazy, errors } = loader._.options;
        if (!server && isSSR) {
          return;
        }
        const ret = scope.run(
          () => app.runWithContext(
            () => loader._.load(to, router, from)
          )
        );
        return !isSSR && toLazyValue(lazy, to, from) ? void 0 : (
          // return the non-lazy loader to commit changes after all loaders are done
          ret.catch((reason) => {
            if (!errors) throw reason;
            if (errors === true) {
              if (Array.isArray(globalErrors) ? globalErrors.some((Err) => reason instanceof Err) : globalErrors(reason))
                return;
            } else if (
              // use local error option if it exists first and then the global one
              Array.isArray(errors) ? errors.some((Err) => reason instanceof Err) : errors(reason)
            ) {
              return;
            }
            throw reason;
          })
        );
      })
    ).then(() => {
      if (to.meta[NAVIGATION_RESULTS_KEY].length) {
        return selectNavigationResult(to.meta[NAVIGATION_RESULTS_KEY]);
      }
    }).catch(
      (error) => error instanceof NavigationResult ? error.value : (
        // let the error propagate to router.onError()
        // we use never because the rejection means we never resolve a value and using anything else
        // will not be valid from the navigation guard's perspective
        Promise.reject(error)
      )
    ).finally(() => {
      setCurrentContext([]);
      isDataLoading.value = false;
    });
  });
  const removeAfterEach = router.afterEach((to, from, failure) => {
    if (failure) {
      to.meta[ABORT_CONTROLLER_KEY]?.abort(failure);
      if (
        // NOTE: using a smaller version to cutoff some bytes
        isNavigationFailure(
          failure,
          16
          /* NavigationFailureType.duplicated */
        )
      ) {
        for (const loader of to.meta[LOADER_SET_KEY]) {
          const entry = loader._.getEntry(router);
          entry.resetPending();
        }
      }
    } else {
      for (const loader of to.meta[LOADER_SET_KEY]) {
        const { commit, lazy } = loader._.options;
        if (commit === "after-load") {
          const entry = loader._.getEntry(router);
          if (entry && (!toLazyValue(lazy, to, from) || !entry.isLoading.value)) {
            entry.commit(to);
          }
        }
      }
    }
    if (router[PENDING_LOCATION_KEY] === to) {
      router[PENDING_LOCATION_KEY] = null;
    }
  });
  const removeOnError = router.onError((error, to) => {
    to.meta[ABORT_CONTROLLER_KEY]?.abort(error);
    if (router[PENDING_LOCATION_KEY] === to) {
      router[PENDING_LOCATION_KEY] = null;
    }
  });
  return () => {
    delete router[LOADER_ENTRIES_KEY];
    delete router[APP_KEY];
    removeLoaderGuard();
    removeDataLoaderGuard();
    removeAfterEach();
    removeOnError();
  };
}
function isAsyncModule(asyncMod) {
  return typeof asyncMod === "function" && // vue functional components
  !("displayName" in asyncMod) && !("props" in asyncMod) && !("emits" in asyncMod) && !("__vccOpts" in asyncMod);
}
var NavigationResult = class {
  constructor(value) {
    this.value = value;
  }
};
function DataLoaderPlugin(app, options) {
  const effect = effectScope(true);
  const removeGuards = setupLoaderGuard(assign({ app, effect }, options));
  const { unmount } = app;
  app.unmount = () => {
    effect.stop();
    removeGuards();
    unmount.call(app);
  };
}
function useIsDataLoading() {
  return inject(IS_DATA_LOADING_KEY);
}
export {
  ABORT_CONTROLLER_KEY,
  APP_KEY,
  DataLoaderPlugin,
  IS_SSR_KEY,
  IS_USE_DATA_LOADER_KEY,
  LOADER_ENTRIES_KEY,
  LOADER_SET_KEY,
  NAVIGATION_RESULTS_KEY,
  NavigationResult,
  PENDING_LOCATION_KEY,
  STAGED_NO_VALUE,
  assign,
  currentContext,
  getCurrentContext,
  isSubsetOf,
  setCurrentContext,
  toLazyValue,
  trackRoute,
  useIsDataLoading,
  withLoaderContext
};
