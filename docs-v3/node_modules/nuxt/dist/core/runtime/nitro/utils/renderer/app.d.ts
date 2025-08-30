import type { H3Event } from 'h3';
import type { NuxtSSRContext } from '#app';
import type { NuxtPayload } from '#app/nuxt';
export declare function createSSRContext(event: H3Event): NuxtSSRContext;
export declare function setSSRError(ssrContext: NuxtSSRContext, error: NuxtPayload['error'] & {
    url: string;
}): void;
