import type { NavigationFailure, NavigationGuard, RouteLocationNormalized, RouteLocationRaw, useRoute as _useRoute, useRouter as _useRouter } from 'vue-router';
import type { PageMeta } from '../../pages/runtime/composables.js';
import type { NuxtError } from './error.js';
/** @since 3.0.0 */
export declare const useRouter: typeof _useRouter;
/** @since 3.0.0 */
export declare const useRoute: typeof _useRoute;
/** @since 3.0.0 */
export declare const onBeforeRouteLeave: (guard: NavigationGuard) => void;
/** @since 3.0.0 */
export declare const onBeforeRouteUpdate: (guard: NavigationGuard) => void;
export interface RouteMiddleware {
    (to: RouteLocationNormalized, from: RouteLocationNormalized): ReturnType<NavigationGuard>;
}
/** @since 3.0.0 */
export declare function defineNuxtRouteMiddleware(middleware: RouteMiddleware): RouteMiddleware;
export interface AddRouteMiddlewareOptions {
    global?: boolean;
}
interface AddRouteMiddleware {
    (name: string, middleware: RouteMiddleware, options?: AddRouteMiddlewareOptions): void;
    (middleware: RouteMiddleware): void;
}
/** @since 3.0.0 */
export declare const addRouteMiddleware: AddRouteMiddleware;
type Without<T, U> = {
    [P in Exclude<keyof T, keyof U>]?: never;
};
type XOR<T, U> = (T | U) extends object ? (Without<T, U> & U) | (Without<U, T> & T) : T | U;
export type OpenWindowFeatures = {
    popup?: boolean;
    noopener?: boolean;
    noreferrer?: boolean;
} & XOR<{
    width?: number;
}, {
    innerWidth?: number;
}> & XOR<{
    height?: number;
}, {
    innerHeight?: number;
}> & XOR<{
    left?: number;
}, {
    screenX?: number;
}> & XOR<{
    top?: number;
}, {
    screenY?: number;
}>;
export type OpenOptions = {
    target: '_blank' | '_parent' | '_self' | '_top' | (string & {});
    windowFeatures?: OpenWindowFeatures;
};
export interface NavigateToOptions {
    replace?: boolean;
    redirectCode?: number;
    external?: boolean;
    open?: OpenOptions;
}
/** @since 3.0.0 */
export declare const navigateTo: (to: RouteLocationRaw | undefined | null, options?: NavigateToOptions) => Promise<void | NavigationFailure | false> | false | void | RouteLocationRaw;
/**
 * This will abort navigation within a Nuxt route middleware handler.
 * @since 3.0.0
 */
export declare const abortNavigation: (err?: string | Partial<NuxtError>) => boolean;
/** @since 3.0.0 */
export declare const setPageLayout: (layout: unknown extends PageMeta["layout"] ? string : PageMeta["layout"]) => void;
/**
 * @internal
 */
export declare function resolveRouteObject(to: Exclude<RouteLocationRaw, string>): string;
/**
 * @internal
 */
export declare function encodeURL(location: string, isExternalHost?: boolean): string;
export {};
