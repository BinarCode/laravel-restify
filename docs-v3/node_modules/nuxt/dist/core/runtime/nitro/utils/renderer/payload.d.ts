import type { Script } from '@unhead/vue';
import type { NuxtSSRContext } from 'nuxt/app';
export declare function renderPayloadResponse(ssrContext: NuxtSSRContext): {
    body: string;
    statusCode: number;
    statusMessage: string;
    headers: {
        'content-type': string;
        'x-powered-by': string;
    };
};
export declare function renderPayloadJsonScript(opts: {
    ssrContext: NuxtSSRContext;
    data?: any;
    src?: string;
}): Script[];
export declare function renderPayloadScript(opts: {
    ssrContext: NuxtSSRContext;
    data?: any;
    src?: string;
}): Script[];
export declare function splitPayload(ssrContext: NuxtSSRContext): {
    initial: {
        prerenderedAt: number | undefined;
        path?: string | undefined;
        serverRendered?: boolean | undefined;
        state?: Record<string, any> | undefined;
        once?: Set<string> | undefined;
        config?: Pick<import("@nuxt/schema").RuntimeConfig, "public" | "app"> | undefined;
        error?: (import("nuxt/app").NuxtError | import("../../../../../../app/defaults.js").DefaultErrorValue) | undefined;
        _errors?: Record<string, import("nuxt/app").NuxtError<unknown> | null> | undefined;
    };
    payload: {
        data: Record<string, any> | undefined;
        prerenderedAt: number | undefined;
    };
};
