import type { NuxtSSRContext } from 'nuxt/app';
export declare const getSSRRenderer: () => Promise<{
    rendererContext: import("vue-bundle-renderer/runtime").RendererContext;
    renderToString(ssrContext: import("vue-bundle-renderer/runtime").SSRContext): Promise<{
        html: string;
        renderResourceHeaders: () => Record<string, string>;
        renderResourceHints: () => string;
        renderStyles: () => string;
        renderScripts: () => string;
    }>;
}>;
export declare function getRenderer(ssrContext: NuxtSSRContext): Promise<{
    rendererContext: import("vue-bundle-renderer/runtime").RendererContext;
    renderToString(ssrContext: import("vue-bundle-renderer/runtime").SSRContext): Promise<{
        html: string;
        renderResourceHeaders: () => Record<string, string>;
        renderResourceHints: () => string;
        renderStyles: () => string;
        renderScripts: () => string;
    }>;
}>;
export declare const getSSRStyles: () => Promise<Record<string, () => Promise<string[]>>>;
export declare const getEntryIds: () => Promise<string[]>;
