import type { MatcherExport } from 'radix3';
import type { H3Event } from 'h3';
import type { NitroRouteRules } from 'nitropack';
export interface NuxtAppManifestMeta {
    id: string;
    timestamp: number;
}
export interface NuxtAppManifest extends NuxtAppManifestMeta {
    matcher: MatcherExport;
    prerendered: string[];
}
/** @since 3.7.4 */
export declare function getAppManifest(): Promise<NuxtAppManifest>;
/** @since 3.7.4 */
export declare function getRouteRules(event: H3Event): Promise<NitroRouteRules>;
export declare function getRouteRules(options: {
    path: string;
}): Promise<Record<string, any>>;
/** @deprecated use `getRouteRules({ path })` instead */
export declare function getRouteRules(url: string): Promise<Record<string, any>>;
