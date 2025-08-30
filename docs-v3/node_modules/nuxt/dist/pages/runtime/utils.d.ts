import type { RouteLocationNormalizedLoaded, RouterView } from 'vue-router';
type InstanceOf<T> = T extends new (...args: any[]) => infer R ? R : never;
type RouterViewSlot = Exclude<InstanceOf<typeof RouterView>['$slots']['default'], undefined>;
export type RouterViewSlotProps = Parameters<RouterViewSlot>[0];
export declare const generateRouteKey: (routeProps: RouterViewSlotProps, override?: string | ((route: RouteLocationNormalizedLoaded) => string)) => string | false | undefined;
export declare const wrapInKeepAlive: (props: any, children: any) => {
    default: () => any;
};
/** @since 3.9.0 */
export declare function toArray<T>(value: T | T[]): T[];
export {};
