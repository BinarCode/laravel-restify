import type { SerializableHead } from '@unhead/vue/types';
import type { NuxtSSRContext } from '#app/nuxt';
/**
 * remove the root node from the html body
 */
export declare function getServerComponentHTML(body: string): string;
export declare function getSlotIslandResponse(ssrContext: NuxtSSRContext): NuxtIslandResponse['slots'];
export declare function getClientIslandResponse(ssrContext: NuxtSSRContext): NuxtIslandResponse['components'];
export declare function getComponentSlotTeleport(clientUid: string, teleports: Record<string, string>): Record<string, string>;
export declare function replaceIslandTeleports(ssrContext: NuxtSSRContext, html: string): string;
export interface NuxtIslandSlotResponse {
    props: Array<unknown>;
    fallback?: string;
}
export interface NuxtIslandContext {
    id?: string;
    name: string;
    props?: Record<string, any>;
    url: string;
    slots: Record<string, Omit<NuxtIslandSlotResponse, 'html' | 'fallback'>>;
    components: Record<string, Omit<NuxtIslandClientResponse, 'html'>>;
}
export interface NuxtIslandResponse {
    id?: string;
    html: string;
    head: SerializableHead;
    props?: Record<string, Record<string, any>>;
    components?: Record<string, NuxtIslandClientResponse>;
    slots?: Record<string, NuxtIslandSlotResponse>;
}
export interface NuxtIslandClientResponse {
    html: string;
    props: unknown;
    chunk: string;
    slots?: Record<string, string>;
}
