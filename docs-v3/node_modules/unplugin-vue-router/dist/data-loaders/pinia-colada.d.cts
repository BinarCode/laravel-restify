import { RouteMap, RouteLocationNormalizedLoaded, LocationQuery } from 'vue-router';
import { ErrorDefault, DefineLoaderFn, DataLoaderContextBase, UseDataLoader, _PromiseMerged, NavigationResult, UseDataLoaderResult, DefineDataLoaderOptionsBase_LaxData, DataLoaderEntryBase } from 'unplugin-vue-router/data-loaders';
import { ShallowRef } from 'vue';
import { UseQueryOptions, EntryKey, UseQueryReturn } from '@pinia/colada';
import { DefineDataLoaderOptionsBase_DefinedData } from './index.cjs';

/**
 * Creates a Pinia Colada data loader with `data` is always defined.
 *
 * @param name - name of the route to have typed routes
 * @param options - options to configure the data loader
 */
declare function defineColadaLoader<Name extends keyof RouteMap, Data>(name: Name, options: DefineDataColadaLoaderOptions_DefinedData<Name, Data>): UseDataLoaderColada_DefinedData<Data>;
/**
 * Creates a Pinia Colada data loader with `data` is possibly `undefined`.
 *
 * @param name - name of the route to have typed routes
 * @param options - options to configure the data loader
 */
declare function defineColadaLoader<Name extends keyof RouteMap, Data>(name: Name, options: DefineDataColadaLoaderOptions_LaxData<Name, Data>): UseDataLoaderColada_LaxData<Data>;
/**
 * Creates a Pinia Colada data loader with `data` is always defined.
 * @param options - options to configure the data loader
 */
declare function defineColadaLoader<Data>(options: DefineDataColadaLoaderOptions_DefinedData<keyof RouteMap, Data>): UseDataLoaderColada_DefinedData<Data>;
/**
 * Creates a Pinia Colada data loader with `data` is possibly `undefined`.
 * @param options - options to configure the data loader
 */
declare function defineColadaLoader<Data>(options: DefineDataColadaLoaderOptions_LaxData<keyof RouteMap, Data>): UseDataLoaderColada_LaxData<Data>;
/**
 * Base type with docs for the options of `defineColadaLoader`.
 * @internal
 */
interface _DefineDataColadaLoaderOptions_Common<Name extends keyof RouteMap, Data> extends Omit<UseQueryOptions<Data, ErrorDefault, Data>, 'query' | 'key'> {
    /**
     * Key associated with the data and passed to pinia colada
     * @param to - Route to load the data
     */
    key: EntryKey | ((to: RouteLocationNormalizedLoaded<Name>) => EntryKey);
    /**
     * Function that returns a promise with the data.
     */
    query: DefineLoaderFn<Data, DataColadaLoaderContext, RouteLocationNormalizedLoaded<Name>>;
}
/**
 * Options for `defineColadaLoader` when the data is possibly `undefined`.
 */
interface DefineDataColadaLoaderOptions_LaxData<Name extends keyof RouteMap, Data> extends _DefineDataColadaLoaderOptions_Common<Name, Data>, DefineDataLoaderOptionsBase_LaxData {
}
/**
 * Options for `defineColadaLoader` when the data is always defined.
 */
interface DefineDataColadaLoaderOptions_DefinedData<Name extends keyof RouteMap, Data> extends _DefineDataColadaLoaderOptions_Common<Name, Data>, DefineDataLoaderOptionsBase_DefinedData {
}
/**
 * @deprecated Use {@link `DefineDataColadaLoaderOptions_LaxData`} instead.
 */
type DefineDataColadaLoaderOptions<Name extends keyof RouteMap, Data> = DefineDataColadaLoaderOptions_LaxData<Name, Data>;
/**
 * @internal
 */
interface DataColadaLoaderContext extends DataLoaderContextBase {
}
interface UseDataLoaderColadaResult<TData, TError = ErrorDefault, TDataInitial extends TData | undefined = TData | undefined> extends UseDataLoaderResult<TData | TDataInitial, ErrorDefault>, Pick<UseQueryReturn<TData, TError, TDataInitial>, 'isPending' | 'status' | 'asyncStatus' | 'state'> {
    refetch: (to?: RouteLocationNormalizedLoaded) => ReturnType<UseQueryReturn<TData, TError, TDataInitial>['refetch']>;
    refresh: (to?: RouteLocationNormalizedLoaded) => ReturnType<UseQueryReturn<TData, TError, TDataInitial>['refetch']>;
}
/**
 * Data Loader composable returned by `defineColadaLoader()`.
 */
interface UseDataLoaderColada_LaxData<Data> extends UseDataLoader<Data | undefined, ErrorDefault> {
    /**
     * Data Loader composable returned by `defineColadaLoader()`.
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
    (): _PromiseMerged<Exclude<Data, NavigationResult | undefined>, UseDataLoaderColadaResult<Exclude<Data, NavigationResult> | undefined>>;
}
/**
 * Data Loader composable returned by `defineColadaLoader()`.
 */
interface UseDataLoaderColada_DefinedData<TData> extends UseDataLoader<TData, ErrorDefault> {
    /**
     * Data Loader composable returned by `defineColadaLoader()`.
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
    (): _PromiseMerged<Exclude<TData, NavigationResult | undefined>, UseDataLoaderColadaResult<Exclude<TData, NavigationResult>, ErrorDefault, Exclude<TData, NavigationResult>>>;
}
interface DataLoaderColadaEntry<TData, TError = unknown, TDataInitial extends TData | undefined = TData | undefined> extends DataLoaderEntryBase<TData, TError, TDataInitial> {
    /**
     * Reactive route passed to pinia colada so it automatically refetch
     */
    route: ShallowRef<RouteLocationNormalizedLoaded>;
    /**
     * Tracked routes to know when the data should be refreshed. Key is the key of the query.
     */
    tracked: Map<string, TrackedRoute>;
    /**
     * Extended options for pinia colada
     */
    ext: UseQueryReturn<TData, TError, TDataInitial> | null;
}
interface TrackedRoute {
    ready: boolean;
    params: Partial<LocationQuery>;
    query: Partial<LocationQuery>;
    hash: {
        v: string | null;
    };
}

export { type DataColadaLoaderContext, type DataLoaderColadaEntry, type DefineDataColadaLoaderOptions, type DefineDataColadaLoaderOptions_LaxData, type UseDataLoaderColadaResult, type UseDataLoaderColada_DefinedData, type UseDataLoaderColada_LaxData, defineColadaLoader };
