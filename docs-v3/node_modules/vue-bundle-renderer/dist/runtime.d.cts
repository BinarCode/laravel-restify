import { R as ResourceMeta, M as Manifest } from './shared/vue-bundle-renderer.DITmAFNH.cjs';

interface ModuleDependencies {
    scripts: Record<string, ResourceMeta>;
    styles: Record<string, ResourceMeta>;
    preload: Record<string, ResourceMeta>;
    prefetch: Record<string, ResourceMeta>;
}
interface SSRContext {
    renderResourceHints?: (...args: unknown[]) => unknown;
    renderScripts?: (...args: unknown[]) => unknown;
    renderStyles?: (...args: unknown[]) => unknown;
    modules?: Set<string>;
    _registeredComponents?: Set<string>;
    _requestDependencies?: ModuleDependencies;
    [key: string]: unknown;
}
interface RenderOptions {
    buildAssetsURL?: (id: string) => string;
    manifest: Manifest;
}
interface RendererContext extends Required<RenderOptions> {
    _dependencies: Record<string, ModuleDependencies>;
    _dependencySets: Record<string, ModuleDependencies>;
    _entrypoints: string[];
    updateManifest: (manifest: Manifest) => void;
}
interface LinkAttributes {
    rel: string | null;
    href: string;
    as?: string | null;
    type?: string | null;
    crossorigin?: '' | null;
}
declare function createRendererContext({ manifest, buildAssetsURL }: RenderOptions): RendererContext;
declare function getModuleDependencies(id: string, rendererContext: RendererContext): ModuleDependencies;
declare function getAllDependencies(ids: Set<string>, rendererContext: RendererContext): ModuleDependencies;
declare function getRequestDependencies(ssrContext: SSRContext, rendererContext: RendererContext): ModuleDependencies;
declare function renderStyles(ssrContext: SSRContext, rendererContext: RendererContext): string;
declare function getResources(ssrContext: SSRContext, rendererContext: RendererContext): LinkAttributes[];
declare function renderResourceHints(ssrContext: SSRContext, rendererContext: RendererContext): string;
declare function renderResourceHeaders(ssrContext: SSRContext, rendererContext: RendererContext): Record<string, string>;
declare function getPreloadLinks(ssrContext: SSRContext, rendererContext: RendererContext): LinkAttributes[];
declare function getPrefetchLinks(ssrContext: SSRContext, rendererContext: RendererContext): LinkAttributes[];
declare function renderScripts(ssrContext: SSRContext, rendererContext: RendererContext): string;
type RenderFunction = (ssrContext: SSRContext, rendererContext: RendererContext) => unknown;
type CreateApp<App> = (ssrContext: SSRContext) => App | Promise<App>;
type ImportOf<T> = T | {
    default: T;
} | Promise<T> | Promise<{
    default: T;
}>;
type RenderToString<App> = (app: App, ssrContext: SSRContext) => string | Promise<string>;
declare function createRenderer<App>(createApp: ImportOf<CreateApp<App>>, renderOptions: RenderOptions & {
    renderToString: RenderToString<App>;
}): {
    rendererContext: RendererContext;
    renderToString(ssrContext: SSRContext): Promise<{
        html: string;
        renderResourceHeaders: () => Record<string, string>;
        renderResourceHints: () => string;
        renderStyles: () => string;
        renderScripts: () => string;
    }>;
};

export { createRenderer, createRendererContext, getAllDependencies, getModuleDependencies, getPrefetchLinks, getPreloadLinks, getRequestDependencies, getResources, renderResourceHeaders, renderResourceHints, renderScripts, renderStyles };
export type { ModuleDependencies, RenderFunction, RenderOptions, RendererContext, SSRContext };
