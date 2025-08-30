import { RouteMap, RouteLocationNormalizedLoaded } from 'vue-router';
import { DefineLoaderFn, DataLoaderContextBase, UseDataLoader, DefineDataLoaderOptionsBase_LaxData } from 'unplugin-vue-router/data-loaders';
import { DefineDataLoaderOptionsBase_DefinedData, ErrorDefault } from './index.cjs';
import 'vue';

/**
 * Creates a data loader composable that can be exported by pages to attach the data loading to a route. In this version `data` is always defined.
 *
 * @param name - name of the route
 * @param loader - function that returns a promise with the data
 * @param options - options to configure the data loader
 */
declare function defineBasicLoader<Name extends keyof RouteMap, Data>(name: Name, loader: DefineLoaderFn<Data, DataLoaderContext, RouteLocationNormalizedLoaded<Name>>, options?: DefineDataLoaderOptions_DefinedData): UseDataLoaderBasic_DefinedData<Data>;
/**
 * Creates a data loader composable that can be exported by pages to attach the data loading to a route. In this version, `data` can be `undefined`.
 *
 * @param name - name of the route
 * @param loader - function that returns a promise with the data
 * @param options - options to configure the data loader
 */
declare function defineBasicLoader<Name extends keyof RouteMap, Data>(name: Name, loader: DefineLoaderFn<Data, DataLoaderContext, RouteLocationNormalizedLoaded<Name>>, options: DefineDataLoaderOptions_LaxData): UseDataLoaderBasic_LaxData<Data>;
/**
 * Creates a data loader composable that can be exported by pages to attach the data loading to a route. In this version `data` is always defined.
 *
 * @param loader - function that returns a promise with the data
 * @param options - options to configure the data loader
 */
declare function defineBasicLoader<Data>(loader: DefineLoaderFn<Data, DataLoaderContext, RouteLocationNormalizedLoaded>, options?: DefineDataLoaderOptions_DefinedData): UseDataLoaderBasic_DefinedData<Data>;
/**
 * Creates a data loader composable that can be exported by pages to attach the data loading to a route. In this version, `data` can be `undefined`.
 *
 * @param loader - function that returns a promise with the data
 * @param options - options to configure the data loader
 */
declare function defineBasicLoader<Data>(loader: DefineLoaderFn<Data, DataLoaderContext, RouteLocationNormalizedLoaded>, options: DefineDataLoaderOptions_LaxData): UseDataLoaderBasic_LaxData<Data>;
interface DefineDataLoaderOptions_LaxData extends DefineDataLoaderOptionsBase_LaxData {
    /**
     * Key to use for SSR state. This will be used to read the initial data from `initialData`'s object.
     */
    key?: string;
}
interface DefineDataLoaderOptions_DefinedData extends DefineDataLoaderOptionsBase_DefinedData {
    key?: string;
}
/**
 * @deprecated use {@link DefineDataLoaderOptions_LaxData} instead
 */
type DefineDataLoaderOptions = DefineDataLoaderOptions_LaxData;
interface DataLoaderContext extends DataLoaderContextBase {
}
/**
 * Symbol used to store the data in the router so it can be retrieved after the initial navigation.
 * @internal
 */
declare const SERVER_INITIAL_DATA_KEY: unique symbol;
/**
 * Initial data generated on server and consumed on client.
 * @internal
 */
declare const INITIAL_DATA_KEY: unique symbol;
declare module 'vue-router' {
    interface Router {
        /**
         * Gives access to the initial state during rendering. Should be set to `false` once it's consumed.
         * @internal
         */
        [SERVER_INITIAL_DATA_KEY]?: Record<string, unknown> | false;
        [INITIAL_DATA_KEY]?: Record<string, unknown> | false;
    }
}
interface UseDataLoaderBasic_LaxData<Data> extends UseDataLoader<Data | undefined, ErrorDefault> {
}
/**
 * @deprecated use {@link UseDataLoaderBasic_LaxData} instead
 */
type UseDataLoaderBasic<Data> = UseDataLoaderBasic_LaxData<Data>;
interface UseDataLoaderBasic_DefinedData<Data> extends UseDataLoader<Data, ErrorDefault> {
}

export { type DataLoaderContext, type DefineDataLoaderOptions, type DefineDataLoaderOptions_DefinedData, type DefineDataLoaderOptions_LaxData, type UseDataLoaderBasic, type UseDataLoaderBasic_DefinedData, type UseDataLoaderBasic_LaxData, defineBasicLoader };
