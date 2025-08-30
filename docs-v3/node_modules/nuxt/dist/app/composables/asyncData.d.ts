import type { MaybeRefOrGetter, MultiWatchSources, Ref } from 'vue';
import type { DedupeOption, DefaultAsyncDataErrorValue, DefaultAsyncDataValue } from 'nuxt/app/defaults';
import type { NuxtApp } from '../nuxt.js';
import type { NuxtError } from './error.js';
export type AsyncDataRequestStatus = 'idle' | 'pending' | 'success' | 'error';
export type _Transform<Input = any, Output = any> = (input: Input) => Output | Promise<Output>;
export type PickFrom<T, K extends Array<string>> = T extends Array<any> ? T : T extends Record<string, any> ? keyof T extends K[number] ? T : K[number] extends never ? T : Pick<T, K[number]> : T;
export type KeysOf<T> = Array<T extends T ? keyof T extends string ? keyof T : never : never>;
export type KeyOfRes<Transform extends _Transform> = KeysOf<ReturnType<Transform>>;
export type { MultiWatchSources };
export type NoInfer<T> = [T][T extends any ? 0 : never];
export type AsyncDataRefreshCause = 'initial' | 'refresh:hook' | 'refresh:manual' | 'watch';
export interface AsyncDataOptions<ResT, DataT = ResT, PickKeys extends KeysOf<DataT> = KeysOf<DataT>, DefaultT = DefaultAsyncDataValue> {
    /**
     * Whether to fetch on the server side.
     * @default true
     */
    server?: boolean;
    /**
     * Whether to resolve the async function after loading the route, instead of blocking client-side navigation
     * @default false
     */
    lazy?: boolean;
    /**
     * a factory function to set the default value of the data, before the async function resolves - useful with the `lazy: true` or `immediate: false` options
     */
    default?: () => DefaultT | Ref<DefaultT>;
    /**
     * Provide a function which returns cached data.
     * An `undefined` return value will trigger a fetch.
     * Default is `key => nuxt.isHydrating ? nuxt.payload.data[key] : nuxt.static.data[key]` which only caches data when payloadExtraction is enabled.
     */
    getCachedData?: (key: string, nuxtApp: NuxtApp, context: {
        cause: AsyncDataRefreshCause;
    }) => NoInfer<DataT> | undefined;
    /**
     * A function that can be used to alter handler function result after resolving.
     * Do not use it along with the `pick` option.
     */
    transform?: _Transform<ResT, DataT>;
    /**
     * Only pick specified keys in this array from the handler function result.
     * Do not use it along with the `transform` option.
     */
    pick?: PickKeys;
    /**
     * Watch reactive sources to auto-refresh when changed
     */
    watch?: MultiWatchSources;
    /**
     * When set to false, will prevent the request from firing immediately
     * @default true
     */
    immediate?: boolean;
    /**
     * Return data in a deep ref object (it is true by default). It can be set to false to return data in a shallow ref object, which can improve performance if your data does not need to be deeply reactive.
     */
    deep?: boolean;
    /**
     * Avoid fetching the same key more than once at a time
     * @default 'cancel'
     */
    dedupe?: 'cancel' | 'defer';
}
export interface AsyncDataExecuteOptions {
    /**
     * Force a refresh, even if there is already a pending request. Previous requests will
     * not be cancelled, but their result will not affect the data/pending state - and any
     * previously awaited promises will not resolve until this new request resolves.
     *
     * Instead of using `boolean` values, use `cancel` for `true` and `defer` for `false`.
     * Boolean values will be removed in a future release.
     */
    dedupe?: DedupeOption;
    cause?: AsyncDataRefreshCause;
    /** @internal */
    cachedData?: any;
}
export interface _AsyncData<DataT, ErrorT> {
    data: Ref<DataT>;
    pending: Ref<boolean>;
    refresh: (opts?: AsyncDataExecuteOptions) => Promise<void>;
    execute: (opts?: AsyncDataExecuteOptions) => Promise<void>;
    clear: () => void;
    error: Ref<ErrorT | DefaultAsyncDataErrorValue>;
    status: Ref<AsyncDataRequestStatus>;
}
export type AsyncData<Data, Error> = _AsyncData<Data, Error> & Promise<_AsyncData<Data, Error>>;
/**
 * Provides access to data that resolves asynchronously in an SSR-friendly composable.
 * See {@link https://nuxt.com/docs/api/composables/use-async-data}
 * @since 3.0.0
 * @param handler An asynchronous function that must return a truthy value (for example, it should not be `undefined` or `null`) or the request may be duplicated on the client side.
 * @param options customize the behavior of useAsyncData
 */
export declare function useAsyncData<ResT, NuxtErrorDataT = unknown, DataT = ResT, PickKeys extends KeysOf<DataT> = KeysOf<DataT>, DefaultT = DefaultAsyncDataValue>(handler: (ctx?: NuxtApp) => Promise<ResT>, options?: AsyncDataOptions<ResT, DataT, PickKeys, DefaultT>): AsyncData<PickFrom<DataT, PickKeys> | DefaultT, (NuxtErrorDataT extends Error | NuxtError ? NuxtErrorDataT : NuxtError<NuxtErrorDataT>) | DefaultAsyncDataErrorValue>;
export declare function useAsyncData<ResT, NuxtErrorDataT = unknown, DataT = ResT, PickKeys extends KeysOf<DataT> = KeysOf<DataT>, DefaultT = DataT>(handler: (ctx?: NuxtApp) => Promise<ResT>, options?: AsyncDataOptions<ResT, DataT, PickKeys, DefaultT>): AsyncData<PickFrom<DataT, PickKeys> | DefaultT, (NuxtErrorDataT extends Error | NuxtError ? NuxtErrorDataT : NuxtError<NuxtErrorDataT>) | DefaultAsyncDataErrorValue>;
/**
 * Provides access to data that resolves asynchronously in an SSR-friendly composable.
 * See {@link https://nuxt.com/docs/api/composables/use-async-data}
 * @param key A unique key to ensure that data fetching can be properly de-duplicated across requests.
 * @param handler An asynchronous function that must return a truthy value (for example, it should not be `undefined` or `null`) or the request may be duplicated on the client side.
 * @param options customize the behavior of useAsyncData
 */
export declare function useAsyncData<ResT, NuxtErrorDataT = unknown, DataT = ResT, PickKeys extends KeysOf<DataT> = KeysOf<DataT>, DefaultT = DefaultAsyncDataValue>(key: MaybeRefOrGetter<string>, handler: (ctx?: NuxtApp) => Promise<ResT>, options?: AsyncDataOptions<ResT, DataT, PickKeys, DefaultT>): AsyncData<PickFrom<DataT, PickKeys> | DefaultT, (NuxtErrorDataT extends Error | NuxtError ? NuxtErrorDataT : NuxtError<NuxtErrorDataT>) | DefaultAsyncDataErrorValue>;
export declare function useAsyncData<ResT, NuxtErrorDataT = unknown, DataT = ResT, PickKeys extends KeysOf<DataT> = KeysOf<DataT>, DefaultT = DataT>(key: MaybeRefOrGetter<string>, handler: (ctx?: NuxtApp) => Promise<ResT>, options?: AsyncDataOptions<ResT, DataT, PickKeys, DefaultT>): AsyncData<PickFrom<DataT, PickKeys> | DefaultT, (NuxtErrorDataT extends Error | NuxtError ? NuxtErrorDataT : NuxtError<NuxtErrorDataT>) | DefaultAsyncDataErrorValue>;
/**
 * Provides access to data that resolves asynchronously in an SSR-friendly composable.
 * See {@link https://nuxt.com/docs/api/composables/use-lazy-async-data}
 * @since 3.0.0
 * @param handler An asynchronous function that must return a truthy value (for example, it should not be `undefined` or `null`) or the request may be duplicated on the client side.
 * @param options customize the behavior of useLazyAsyncData
 */
export declare function useLazyAsyncData<ResT, NuxtErrorDataT = unknown, DataT = ResT, PickKeys extends KeysOf<DataT> = KeysOf<DataT>, DefaultT = DefaultAsyncDataValue>(handler: (ctx?: NuxtApp) => Promise<ResT>, options?: Omit<AsyncDataOptions<ResT, DataT, PickKeys, DefaultT>, 'lazy'>): AsyncData<PickFrom<DataT, PickKeys> | DefaultT, (NuxtErrorDataT extends Error | NuxtError ? NuxtErrorDataT : NuxtError<NuxtErrorDataT>) | DefaultAsyncDataErrorValue>;
export declare function useLazyAsyncData<ResT, NuxtErrorDataT = unknown, DataT = ResT, PickKeys extends KeysOf<DataT> = KeysOf<DataT>, DefaultT = DataT>(handler: (ctx?: NuxtApp) => Promise<ResT>, options?: Omit<AsyncDataOptions<ResT, DataT, PickKeys, DefaultT>, 'lazy'>): AsyncData<PickFrom<DataT, PickKeys> | DefaultT, (NuxtErrorDataT extends Error | NuxtError ? NuxtErrorDataT : NuxtError<NuxtErrorDataT>) | DefaultAsyncDataErrorValue>;
/**
 * Provides access to data that resolves asynchronously in an SSR-friendly composable.
 * See {@link https://nuxt.com/docs/api/composables/use-lazy-async-data}
 * @param key A unique key to ensure that data fetching can be properly de-duplicated across requests.
 * @param handler An asynchronous function that must return a truthy value (for example, it should not be `undefined` or `null`) or the request may be duplicated on the client side.
 * @param options customize the behavior of useLazyAsyncData
 */
export declare function useLazyAsyncData<ResT, NuxtErrorDataT = unknown, DataT = ResT, PickKeys extends KeysOf<DataT> = KeysOf<DataT>, DefaultT = DefaultAsyncDataValue>(key: MaybeRefOrGetter<string>, handler: (ctx?: NuxtApp) => Promise<ResT>, options?: Omit<AsyncDataOptions<ResT, DataT, PickKeys, DefaultT>, 'lazy'>): AsyncData<PickFrom<DataT, PickKeys> | DefaultT, (NuxtErrorDataT extends Error | NuxtError ? NuxtErrorDataT : NuxtError<NuxtErrorDataT>) | DefaultAsyncDataErrorValue>;
export declare function useLazyAsyncData<ResT, NuxtErrorDataT = unknown, DataT = ResT, PickKeys extends KeysOf<DataT> = KeysOf<DataT>, DefaultT = DataT>(key: MaybeRefOrGetter<string>, handler: (ctx?: NuxtApp) => Promise<ResT>, options?: Omit<AsyncDataOptions<ResT, DataT, PickKeys, DefaultT>, 'lazy'>): AsyncData<PickFrom<DataT, PickKeys> | DefaultT, (NuxtErrorDataT extends Error | NuxtError ? NuxtErrorDataT : NuxtError<NuxtErrorDataT>) | DefaultAsyncDataErrorValue>;
/** @since 3.1.0 */
export declare function useNuxtData<DataT = any>(key: string): {
    data: Ref<DataT | DefaultAsyncDataValue>;
};
/** @since 3.0.0 */
export declare function refreshNuxtData(keys?: string | string[]): Promise<void>;
/** @since 3.0.0 */
export declare function clearNuxtData(keys?: string | string[] | ((key: string) => boolean)): void;
export type CreatedAsyncData<ResT, NuxtErrorDataT = unknown, DataT = ResT, DefaultT = undefined> = Omit<_AsyncData<DataT | DefaultT, (NuxtErrorDataT extends Error | NuxtError ? NuxtErrorDataT : NuxtError<NuxtErrorDataT>)>, 'clear' | 'refresh'> & {
    _off: () => void;
    _hash?: Record<string, string | undefined>;
    _default: () => unknown;
    _init: boolean;
    _deps: number;
    _execute: (opts?: AsyncDataExecuteOptions) => Promise<void>;
};
