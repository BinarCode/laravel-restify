import * as vue_router from 'vue-router';
import { NavigationGuard, Router, NavigationGuardReturn, RouteLocationNormalizedLoaded as RouteLocationNormalizedLoaded$1, LocationQuery, RouteRecordRaw } from 'vue-router';
import { App, ShallowRef, EffectScope } from 'vue';

/**
 * Retrieves the internal version of loaders.
 * @internal
 */
declare const LOADER_SET_KEY: unique symbol;
/**
 * Retrieves the internal version of loader entries.
 * @internal
 */
declare const LOADER_ENTRIES_KEY: unique symbol;
/**
 * Added to the loaders returned by `defineLoader()` to identify them.
 * Allows to extract exported useData() from a component.
 * @internal
 */
declare const IS_USE_DATA_LOADER_KEY: unique symbol;
/**
 * Symbol used to save the pending location on the router.
 * @internal
 */
declare const PENDING_LOCATION_KEY: unique symbol;
/**
 * Symbol used to know there is no value staged for the loader and that commit should be skipped.
 * @internal
 */
declare const STAGED_NO_VALUE: unique symbol;
/**
 * Gives access to the current app and it's `runWithContext` method.
 * @internal
 */
declare const APP_KEY: unique symbol;
/**
 * Gives access to an AbortController that aborts when the navigation is canceled.
 * @internal
 */
declare const ABORT_CONTROLLER_KEY: unique symbol;
/**
 * Gives access to the navigation results when the navigation is aborted by the user within a data loader.
 * @internal
 */
declare const NAVIGATION_RESULTS_KEY: unique symbol;
/**
 * Symbol used to save the initial data on the router.
 * @internal
 */
declare const IS_SSR_KEY: unique symbol;

/**
 * Maybe a promise maybe not
 * @internal
 */
type _Awaitable<T> = T | PromiseLike<T>;

/**
 * Options to initialize the data loader guard.
 */
interface SetupLoaderGuardOptions extends DataLoaderPluginOptions {
    /**
     * The Vue app instance. Used to access the `provide` and `inject` APIs.
     */
    app: App<unknown>;
    /**
     * The effect scope to use for the data loaders.
     */
    effect: EffectScope;
}
/**
 * Possible values to change the result of a navigation within a loader
 * @internal
 */
type _DataLoaderRedirectResult = Exclude<ReturnType<NavigationGuard>, Promise<unknown> | Function | true | void | undefined>;
/**
 * Possible values to change the result of a navigation within a loader. Can be returned from a data loader and will
 * appear in `selectNavigationResult`. If thrown, it will immediately cancel the navigation. It can only contain values
 * that cancel the navigation.
 *
 * @example
 * ```ts
 * export const useUserData = defineLoader(async (to) => {
 *   const user = await fetchUser(to.params.id)
 *   if (!user) {
 *     return new NavigationResult('/404')
 *   }
 *   return user
 * })
 * ```
 */
declare class NavigationResult {
    readonly value: _DataLoaderRedirectResult;
    constructor(value: _DataLoaderRedirectResult);
}
/**
 * Data Loader plugin to add data loading support to Vue Router.
 *
 * @example
 * ```ts
 * const router = createRouter({
 *   routes,
 *   history: createWebHistory(),
 * })
 *
 * const app = createApp({})
 * app.use(DataLoaderPlugin, { router })
 * app.use(router)
 * ```
 */
declare function DataLoaderPlugin(app: App, options: DataLoaderPluginOptions): void;
/**
 * Options passed to the DataLoaderPlugin.
 */
interface DataLoaderPluginOptions {
    /**
     * The router instance. Adds the guards to it
     */
    router: Router;
    isSSR?: boolean;
    /**
     * Called if any data loader returns a `NavigationResult` with an array of them. Should decide what is the outcome of
     * the data fetching guard. Note this isn't called if no data loaders return a `NavigationResult` or if an error is thrown.
     * @defaultValue `(results) => results[0].value`
     */
    selectNavigationResult?: (results: NavigationResult[]) => _Awaitable<Exclude<NavigationGuardReturn, Function | Promise<unknown>>>;
    /**
     * List of _expected_ errors that shouldn't abort the navigation (for non-lazy loaders). Provide a list of
     * constructors that can be checked with `instanceof` or a custom function that returns `true` for expected errors.
     */
    errors?: Array<new (...args: any) => any> | ((reason?: unknown) => boolean);
}
/**
 * Return a ref that reflects the global loading state of all loaders within a navigation.
 * This state doesn't update if `refresh()` is manually called.
 */
declare function useIsDataLoading(): ShallowRef<boolean>;

/**
 * Map type for the entries used by data loaders.
 * @internal
 */
type _DefineLoaderEntryMap<DataLoaderEntry extends DataLoaderEntryBase<unknown> = DataLoaderEntryBase<unknown>> = WeakMap<object, DataLoaderEntry>;

declare module 'vue-router' {
    interface Router {
        /**
         * The entries used by data loaders. Put on the router for convenience.
         * @internal
         */
        [LOADER_ENTRIES_KEY]: _DefineLoaderEntryMap;
        /**
         * Pending navigation that is waiting for data loaders to resolve.
         * @internal
         */
        [PENDING_LOCATION_KEY]: RouteLocationNormalizedLoaded | null;
        /**
         * The app instance that is used by the router.
         * @internal
         */
        [APP_KEY]: App<unknown>;
        /**
         * Whether the router is running in server-side rendering mode.
         * @internal
         */
        [IS_SSR_KEY]: boolean;
    }
    interface RouteMeta {
        /**
         * The data loaders for a route record. Add any data loader to this array to have it called when the route is
         * navigated to. Note this is only needed when **not** using lazy components (`() => import('./pages/Home.vue')`) or
         * when not explicitly exporting data loaders from page components.
         */
        loaders?: UseDataLoader[];
        /**
         * Set of loaders for the current route. This is built once during navigation and is used to merge the loaders from
         * the lazy import in components or the `loaders` array in the route record.
         * @internal
         */
        [LOADER_SET_KEY]?: Set<UseDataLoader>;
        /**
         * The signal that is aborted when the navigation is canceled or an error occurs.
         * @internal
         */
        [ABORT_CONTROLLER_KEY]?: AbortController;
        /**
         * The navigation results when the navigation is canceled by the user within a data loader.
         * @internal
         */
        [NAVIGATION_RESULTS_KEY]?: NavigationResult[];
    }
}

/**
 * @internal: data loaders authoring only. Use `getCurrentContext` instead.
 */
declare let currentContext: readonly [
    entry: DataLoaderEntryBase,
    router: Router,
    route: RouteLocationNormalizedLoaded$1
] | undefined | null;
declare function getCurrentContext(): readonly [entry: DataLoaderEntryBase<unknown, unknown, unknown>, router: Router, route: vue_router.RouteLocationNormalizedLoadedGeneric] | readonly [];
/**
 * Sets the current context for data loaders. This allows for nested loaders to be aware of their parent context.
 * INTERNAL ONLY.
 *
 * @param context - the context to set
 * @internal
 */
declare function setCurrentContext(context?: typeof currentContext | readonly []): void;
/**
 * Restore the current context after a promise is resolved.
 * @param promise - promise to wrap
 */
declare function withLoaderContext<P extends Promise<unknown>>(promise: P): P;
/**
 * Object and promise of the object itself. Used when we can await some of the properties of an object to be loaded.
 * @internal
 */
type _PromiseMerged<PromiseType, RawType = PromiseType> = RawType & Promise<PromiseType>;
declare const assign: {
    <T extends {}, U>(target: T, source: U): T & U;
    <T extends {}, U, V>(target: T, source1: U, source2: V): T & U & V;
    <T extends {}, U, V, W>(target: T, source1: U, source2: V, source3: W): T & U & V & W;
    (target: object, ...sources: any[]): any;
};
/**
 * Track the reads of a route and its properties
 * @internal
 * @param route - route to track
 */
declare function trackRoute(route: RouteLocationNormalizedLoaded$1): readonly [{
    readonly hash: string;
    readonly params: vue_router.RouteParamsGeneric;
    readonly query: LocationQuery;
    readonly matched: vue_router.RouteLocationMatched[];
    readonly name: vue_router.RouteRecordNameGeneric;
    readonly fullPath: string;
    readonly redirectedFrom: vue_router.RouteLocation | undefined;
    readonly path: string;
    readonly meta: vue_router.RouteMeta;
}, Partial<vue_router.RouteParamsGeneric>, Partial<LocationQuery>, {
    v: string | null;
}];
/**
 * Returns `true` if `inner` is a subset of `outer`. Used to check if a tr
 *
 * @internal
 * @param outer - the bigger params
 * @param inner - the smaller params
 */
declare function isSubsetOf(inner: Partial<LocationQuery>, outer: LocationQuery): boolean;

/**
 * Allows you to extend the default types of the library.
 *
 * @example
 * ```ts
 * // types-extension.d.ts
 * import 'unplugin-vue-router/data-loaders'
 * export {}
 * declare module 'unplugin-vue-router/data-loaders' {
 *   interface TypesConfig {
 *     Error: MyCustomError
 *   }
 * }
 * ```
 */
interface TypesConfig {
}
/**
 * The default error type used.
 * @internal
 */
type ErrorDefault = TypesConfig extends Record<'Error', infer E> ? E : Error;

/**
 * Base type for a data loader entry. Each Data Loader has its own entry in the `loaderEntries` (accessible via `[LOADER_ENTRIES_KEY]`) map.
 */
interface DataLoaderEntryBase<TData = unknown, TError = unknown, TDataInitial extends TData | undefined = TData | undefined> {
    /**
     * Data stored in the entry.
     */
    data: ShallowRef<TData | TDataInitial>;
    /**
     * Error if there was an error.
     */
    error: ShallowRef<TError | null>;
    /**
     * Location the data was loaded for or `null` if the data is not loaded.
     */
    to: RouteLocationNormalizedLoaded$1 | null;
    /**
     * Whether there is an ongoing request.
     */
    isLoading: ShallowRef<boolean>;
    options: DefineDataLoaderOptionsBase_LaxData;
    /**
     * Called by the navigation guard when the navigation is duplicated. Should be used to reset pendingTo and pendingLoad and any other property that should be reset.
     */
    resetPending: () => void;
    /**
     * The latest pending load. Allows to verify if the load is still valid when it resolves.
     */
    pendingLoad: Promise<void> | null;
    /**
     * The latest pending navigation's `to` route. Used to verify if the navigation is still valid when it resolves.
     */
    pendingTo: RouteLocationNormalizedLoaded$1 | null;
    /**
     * Data that was staged by a loader. This is used to avoid showing the old data while the new data is loading. Calling
     * the internal `commit()` function will replace the data with the staged data.
     */
    staged: TData | typeof STAGED_NO_VALUE;
    /**
     * Error that was staged by a loader. This is used to avoid showing the old error while the new data is loading.
     * Calling the internal `commit()` function will replace the error with the staged error.
     */
    stagedError: TError | null;
    /**
     * Other data loaders that depend on this one. This is used to invalidate the data when a dependency is invalidated.
     */
    children: Set<DataLoaderEntryBase>;
    /**
     * Commits the pending data to the entry. This is called by the navigation guard when all non-lazy loaders have
     * finished loading. It should be implemented by the loader. It **must be called** from the entry itself:
     * `entry.commit(to)`.
     */
    commit(to: RouteLocationNormalizedLoaded$1): void;
}
/**
 * Common properties for the options of `defineLoader()`. Types are `unknown` to allow for more specific types in the
 * extended types while having documentation in one single place.
 * @internal
 */
interface _DefineDataLoaderOptionsBase_Common {
    /**
     * When the data should be committed to the entry. In the case of lazy loaders, the loader will try to commit the data
     * after all non-lazy loaders have finished loading, but it might not be able to if the lazy loader hasn't been
     * resolved yet.
     *
     * @see {@link DefineDataLoaderCommit}
     * @defaultValue `'after-load'`
     */
    commit?: DefineDataLoaderCommit;
    /**
     * Whether the data should be lazy loaded without blocking the client side navigation or not. When set to true, the loader will no longer block the navigation and the returned composable can be called even
     * without having the data ready.
     *
     * @defaultValue `false`
     */
    lazy?: unknown;
    /**
     * Whether this loader should be awaited on the server side or not. Combined with the `lazy` option, this gives full
     * control over how to await for the data.
     *
     * @defaultValue `true`
     */
    server?: unknown;
    /**
     * List of _expected_ errors that shouldn't abort the navigation (for non-lazy loaders). Provide a list of
     * constructors that can be checked with `instanceof` or a custom function that returns `true` for expected errors. Can also be set to `true` to accept all globally defined errors. Defaults to `false` to abort on any error.
     * @default `false`
     */
    errors?: unknown;
}
/**
 * Options for a data loader that returns a data that is possibly `undefined`. Available for data loaders
 * implementations so they can be used in `defineLoader()` overloads.
 */
interface DefineDataLoaderOptionsBase_LaxData extends _DefineDataLoaderOptionsBase_Common {
    lazy?: boolean | ((to: RouteLocationNormalizedLoaded$1, from?: RouteLocationNormalizedLoaded$1) => boolean);
    server?: boolean;
    errors?: boolean | Array<new (...args: any[]) => any> | ((reason?: unknown) => boolean);
}
/**
 * Options for a data loader making the data defined without it being possibly `undefined`. Available for data loaders
 * implementations so they can be used in `defineLoader()` overloads.
 */
interface DefineDataLoaderOptionsBase_DefinedData extends _DefineDataLoaderOptionsBase_Common {
    lazy?: false;
    server?: true;
    errors?: false;
}
declare const toLazyValue: (lazy: undefined | DefineDataLoaderOptionsBase_LaxData["lazy"], to: RouteLocationNormalizedLoaded$1, from?: RouteLocationNormalizedLoaded$1) => boolean | undefined;
/**
 * When the data should be committed to the entry.
 * - `immediate`: the data is committed as soon as it is loaded.
 * - `after-load`: the data is committed after all non-lazy loaders have finished loading.
 */
type DefineDataLoaderCommit = 'immediate' | 'after-load';
interface DataLoaderContextBase {
    /**
     * Signal associated with the current navigation. It is aborted when the navigation is canceled or an error occurs.
     */
    signal: AbortSignal | undefined;
}
/**
 * Data Loader composable returned by `defineLoader()`.
 * @see {@link DefineDataLoader}
 */
interface UseDataLoader<Data = unknown, TError = unknown> {
    [IS_USE_DATA_LOADER_KEY]: true;
    /**
     * Data Loader composable returned by `defineLoader()`.
     *
     * @example
     * Returns the Data loader data, isLoading, error etc. Meant to be used in `setup()` or `<script setup>` **without `await`**:
     * ```vue
     * <script setup>
     * const { data, isLoading, error } = useUserData()
     * </script>
     * ```
     *
     * @example
     * It also returns a promise of the data when used in nested loaders. Note this `data` is **not a ref**. This is not meant to be used in `setup()` or `<script setup>`.
     * ```ts
     * export const useUserConnections = defineLoader(async () => {
     *   const user = await useUserData()
     *   return fetchUserConnections(user.id)
     * })
     * ```
     */
    (): _PromiseMerged<Exclude<Data, NavigationResult | undefined>, UseDataLoaderResult<Exclude<Data, NavigationResult>, TError>>;
    /**
     * Internals of the data loader.
     * @internal
     */
    _: UseDataLoaderInternals<Exclude<Data, NavigationResult | undefined>, TError>;
}
/**
 * Internal properties of a data loader composable. Used by the internal implementation of `defineLoader()`. **Should
 * not be used in application code.**
 */
interface UseDataLoaderInternals<Data = unknown, TError = unknown> {
    /**
     * Loads the data from the cache if possible, otherwise loads it from the loader and awaits it.
     *
     * @param to - route location to load the data for
     * @param router - router instance
     * @param from - route location we are coming from
     * @param parent - parent data loader entry
     */
    load: (to: RouteLocationNormalizedLoaded$1, router: Router, from?: RouteLocationNormalizedLoaded$1, parent?: DataLoaderEntryBase) => Promise<void>;
    /**
     * Resolved options for the loader.
     */
    options: DefineDataLoaderOptionsBase_LaxData;
    /**
     * Gets the entry associated with the router instance. Assumes the data loader has been loaded and that the entry
     * exists.
     *
     * @param router - router instance
     */
    getEntry(router: Router): DataLoaderEntryBase<Data, TError>;
}
/**
 * Return value of a loader composable defined with `defineLoader()`.
 */
interface UseDataLoaderResult<TData = unknown, TError = ErrorDefault> {
    /**
     * Data returned by the loader. If the data loader is lazy, it will be undefined until the first load.
     */
    data: ShallowRef<TData>;
    /**
     * Whether there is an ongoing request.
     */
    isLoading: ShallowRef<boolean>;
    /**
     * Error if there was an error.
     */
    error: ShallowRef<TError | null>;
    /**
     * Reload the data using the current route location. Returns a promise that resolves when the data is reloaded. This
     * method should not be called during a navigation as it can conflict with an ongoing load and lead to
     * inconsistencies.
     */
    reload(): Promise<void>;
    /**
     * Reload the data using the route location passed as argument. Returns a promise that resolves when the data is reloaded.
     *
     * @param route - route location to load the data for
     */
    reload(route: RouteLocationNormalizedLoaded$1): Promise<void>;
}
/**
 * Loader function that can be passed to `defineLoader()`.
 */
interface DefineLoaderFn<Data, Context extends DataLoaderContextBase = DataLoaderContextBase, Route = RouteLocationNormalizedLoaded$1> {
    (route: Route, context: Context): Promise<Data>;
}
/**
 * @deprecated Use `DefineDataLoaderOptionsBase_LaxData` instead.
 */
type DefineDataLoaderOptionsBase = DefineDataLoaderOptionsBase_LaxData;

/**
 * Defines properties of the route for the current page component.
 *
 * @param route - route information to be added to this page
 * @deprecated - use `definePage` instead
 */
declare const _definePage: (route: DefinePage) => DefinePage;
/**
 * Defines properties of the route for the current page component.
 *
 * @param route - route information to be added to this page
 */
declare const definePage: (route: DefinePage) => DefinePage;
/**
 * Merges route records.
 *
 * @internal
 *
 * @param main - main route record
 * @param routeRecords - route records to merge
 * @returns merged route record
 */
declare function _mergeRouteRecord(main: RouteRecordRaw, ...routeRecords: Partial<RouteRecordRaw>[]): RouteRecordRaw;
/**
 * Type to define a page. Can be augmented to add custom properties.
 */
interface DefinePage extends Partial<Omit<RouteRecordRaw, 'children' | 'components' | 'component'>> {
}

export { ABORT_CONTROLLER_KEY, APP_KEY, type DataLoaderContextBase, type DataLoaderEntryBase, DataLoaderPlugin, type DataLoaderPluginOptions, type DefineDataLoaderOptionsBase, type DefineDataLoaderOptionsBase_DefinedData, type DefineDataLoaderOptionsBase_LaxData, type DefineLoaderFn, type DefinePage, type ErrorDefault, IS_SSR_KEY, IS_USE_DATA_LOADER_KEY, LOADER_ENTRIES_KEY, LOADER_SET_KEY, NAVIGATION_RESULTS_KEY, NavigationResult, PENDING_LOCATION_KEY, STAGED_NO_VALUE, type SetupLoaderGuardOptions, type TypesConfig, type UseDataLoader, type UseDataLoaderInternals, type UseDataLoaderResult, type _DataLoaderRedirectResult, type _DefineLoaderEntryMap, type _PromiseMerged, _definePage, _mergeRouteRecord, assign, currentContext, definePage, getCurrentContext, isSubsetOf, setCurrentContext, toLazyValue, trackRoute, useIsDataLoading, withLoaderContext };
