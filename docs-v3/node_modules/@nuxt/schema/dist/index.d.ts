import { AppConfig as AppConfig$1, TransitionProps, KeepAliveProps } from 'vue';
import { ViteDevServer, UserConfig, ServerOptions } from 'vite';
import { Options as Options$5 } from '@vitejs/plugin-vue';
import { Options as Options$6 } from '@vitejs/plugin-vue-jsx';
import * as untyped from 'untyped';
import { SchemaDefinition, Schema } from 'untyped';
export { SchemaDefinition } from 'untyped';
import { NitroOptions, NitroConfig, Nitro, NitroEventHandler, NitroDevEventHandler, NitroRuntimeConfigApp, NitroRuntimeConfig } from 'nitropack';
import { SnakeCase } from 'scule';
import * as c12 from 'c12';
import { SourceOptions, ResolvedConfig } from 'c12';
import * as esbuild from 'esbuild';
import { Server, IncomingMessage, ServerResponse } from 'node:http';
import { AssetURLTagConfig } from '@vue/compiler-sfc';
import { CompilerOptions } from '@vue/compiler-core';
import { SerializableHead, GlobalAttributes, AriaAttributes, DataKeys, RenderSSRHeadOptions } from '@unhead/vue/types';
import { BundleAnalyzerPlugin } from 'webpack-bundle-analyzer';
import { PluginVisualizerOptions } from 'rollup-plugin-visualizer';
import { TransformerOptions } from 'unctx/transform';
import { CompatibilityDateSpec } from 'compatx';
import { Ignore, Options } from 'ignore';
import { ChokidarOptions } from 'chokidar';
import { EventHandler, H3CorsOptions } from 'h3';
import { NuxtLinkOptions } from 'nuxt/app';
import { FetchOptions } from 'ofetch';
import { Options as Options$1 } from 'autoprefixer';
import { Options as Options$2 } from 'cssnano';
import { TSConfig } from 'pkg-types';
import { RawVueCompilerOptions } from '@vue/language-core';
import { PluginOptions } from 'mini-css-extract-plugin';
import { LoaderOptions } from 'esbuild-loader';
import { Options as Options$3 } from 'pug';
import { VueLoaderOptions } from 'vue-loader';
import { BasePluginOptions, DefinedDefaultMinimizerAndOptions } from 'css-minimizer-webpack-plugin';
import { Configuration, Compiler, Stats, WebpackError } from 'webpack';
import { ProcessOptions } from 'postcss';
import { Options as Options$4 } from 'webpack-dev-middleware';
import { MiddlewareOptions, ClientOptions } from 'webpack-hot-middleware';
import { RouterOptions as RouterOptions$1, RouterHistory, RouteRecordRaw, RouteLocationRaw } from 'vue-router';
import { Server as Server$1 } from 'node:https';
import { Manifest } from 'vue-bundle-renderer';
import { InlinePreset, Import, Unimport, UnimportOptions } from 'unimport';
import { AsyncLocalStorage } from 'node:async_hooks';
import { Hookable } from 'hookable';
import { Defu } from 'defu';

interface NuxtCompatibility {
    /**
     * Required nuxt version in semver format.
     * @example `^2.14.0` or `>=3.0.0-27219851.6e49637`.
     */
    nuxt?: string;
    /**
     * Bridge constraint for Nuxt 2 support.
     *
     * - `true`:  When using Nuxt 2, using bridge module is required.
     * - `false`: When using Nuxt 2, using bridge module is not supported.
     */
    bridge?: boolean;
    /**
     * Mark a builder as incompatible, or require a particular version.
     *
     * @example
     * ```ts
     * export default defineNuxtModule({
     *   meta: {
     *     name: 'my-module',
     *     compatibility: {
     *       builder: {
     *         // marking as incompatible
     *         webpack: false,
     *         // you can require a (semver-compatible) version
     *         vite: '^5'
     *       }
     *     }
     *   }
     *   // ...
     * })
     * ```
     */
    builder?: Partial<Record<'vite' | 'webpack' | 'rspack' | (string & {}), false | string>>;
}
interface NuxtCompatibilityIssue {
    name: string;
    message: string;
}
interface NuxtCompatibilityIssues extends Array<NuxtCompatibilityIssue> {
    /**
     * Return formatted error message.
     */
    toString(): string;
}

interface ComponentMeta {
    [key: string]: unknown;
}
interface Component {
    pascalName: string;
    kebabName: string;
    export: string;
    filePath: string;
    shortPath: string;
    chunkName: string;
    prefetch: boolean;
    preload: boolean;
    global?: boolean | 'sync';
    island?: boolean;
    meta?: ComponentMeta;
    mode?: 'client' | 'server' | 'all';
    /**
     * This number allows configuring the behavior of overriding Nuxt components.
     * If multiple components are provided with the same name, then higher priority
     * components will be used instead of lower priority components.
     */
    priority?: number;
    /**
     * Allow bypassing client/server transforms for internal Nuxt components like
     * ServerPlaceholder and NuxtClientFallback.
     *
     * @internal
     */
    _raw?: boolean;
}
interface ScanDir {
    /**
     * Path (absolute or relative) to the directory containing your components.
     * You can use Nuxt aliases (~ or @) to refer to directories inside project or directly use an npm package path similar to require.
     */
    path: string;
    /**
     * Accept Pattern that will be run against specified path.
     */
    pattern?: string | string[];
    /**
     * Ignore patterns that will be run against specified path.
     */
    ignore?: string[];
    /**
     * Prefix all matched components.
     */
    prefix?: string;
    /**
     * Prefix component name by its path.
     */
    pathPrefix?: boolean;
    /**
     * Ignore scanning this directory if set to `false`
     */
    enabled?: boolean;
    /**
     * These properties (prefetch/preload) are used in production to configure how components with Lazy prefix are handled by webpack via its magic comments.
     * Learn more on webpack documentation: https://webpack.js.org/api/module-methods/#magic-comments
     */
    prefetch?: boolean;
    /**
     * These properties (prefetch/preload) are used in production to configure how components with Lazy prefix are handled by webpack via its magic comments.
     * Learn more on webpack documentation: https://webpack.js.org/api/module-methods/#magic-comments
     */
    preload?: boolean;
    /**
     * This flag indicates, component should be loaded async (with a separate chunk) regardless of using Lazy prefix or not.
     */
    isAsync?: boolean;
    extendComponent?: (component: Component) => Promise<Component | void> | (Component | void);
    /**
     * If enabled, registers components to be globally available.
     *
     */
    global?: boolean;
    /**
     * If enabled, registers components as islands
     */
    island?: boolean;
}
interface ComponentsDir extends ScanDir {
    /**
     * Watch specified path for changes, including file additions and file deletions.
     */
    watch?: boolean;
    /**
     * Extensions supported by Nuxt builder.
     */
    extensions?: string[];
    /**
     * Transpile specified path using build.transpile.
     * By default ('auto') it will set transpile: true if node_modules/ is in path.
     */
    transpile?: 'auto' | boolean;
    /**
     * This number allows configuring the behavior of overriding Nuxt components.
     * It will be inherited by any components within the directory.
     *
     * If multiple components are provided with the same name, then higher priority
     * components will be used instead of lower priority components.
     */
    priority?: number;
}
interface ComponentsOptions {
    dirs: (string | ComponentsDir)[];
    /**
     * The default value for whether to globally register components.
     *
     * When components are registered globally, they will still be directly imported where used,
     * but they can also be used dynamically, for example `<component :is="`icon-${myIcon}`">`.
     *
     * This can be overridden by an individual component directory entry.
     * @default false
     */
    global?: boolean;
    /**
     * Whether to write metadata to the build directory with information about the components that
     * are auto-registered in your app.
     */
    generateMetadata?: boolean;
    loader?: boolean;
    transform?: {
        exclude?: RegExp[];
        include?: RegExp[];
    };
}

type RouterOptions = Partial<Omit<RouterOptions$1, 'history' | 'routes'>> & {
    history?: (baseURL?: string) => RouterHistory | null | undefined;
    routes?: (_routes: RouterOptions$1['routes']) => RouterOptions$1['routes'] | Promise<RouterOptions$1['routes']>;
    hashMode?: boolean;
    scrollBehaviorType?: 'smooth' | 'auto';
};
type RouterConfig = RouterOptions;
/**
 * Only JSON serializable router options are configurable from nuxt config
 */
type RouterConfigSerializable = Pick<RouterConfig, 'linkActiveClass' | 'linkExactActiveClass' | 'end' | 'sensitive' | 'strict' | 'hashMode' | 'scrollBehaviorType'>;

interface ModuleMeta {
    /** Module name. */
    name?: string;
    /** Module version. */
    version?: string;
    /**
     * The configuration key used within `nuxt.config` for this module's options.
     * For example, `@nuxtjs/axios` uses `axios`.
     */
    configKey?: string;
    /**
     * Constraints for the versions of Nuxt or features this module requires.
     */
    compatibility?: NuxtCompatibility;
    /**
     * Fully resolved path used internally by Nuxt. Do not depend on this value.
     * @internal
     */
    rawPath?: string;
    [key: string]: unknown;
}
/** The options received.  */
type ModuleOptions = Record<string, any>;
type ModuleSetupInstallResult = {
    /**
     * Timing information for the initial setup
     */
    timings?: {
        /** Total time took for module setup in ms */
        setup?: number;
        [key: string]: number | undefined;
    };
};
type Awaitable<T> = T | Promise<T>;
type Prettify<T> = {
    [K in keyof T]: T[K];
} & {};
type ModuleSetupReturn = Awaitable<false | void | ModuleSetupInstallResult>;
type ResolvedModuleOptions<TOptions extends ModuleOptions, TOptionsDefaults extends Partial<TOptions>> = Prettify<Defu<Partial<TOptions>, [
    Partial<TOptions>,
    TOptionsDefaults
]>>;
/** Module definition passed to 'defineNuxtModule(...)' or 'defineNuxtModule().with(...)'. */
interface ModuleDefinition<TOptions extends ModuleOptions, TOptionsDefaults extends Partial<TOptions>, TWith extends boolean> {
    meta?: ModuleMeta;
    defaults?: TOptionsDefaults | ((nuxt: Nuxt) => Awaitable<TOptionsDefaults>);
    schema?: TOptions;
    hooks?: Partial<NuxtHooks>;
    setup?: (this: void, resolvedOptions: TWith extends true ? ResolvedModuleOptions<TOptions, TOptionsDefaults> : TOptions, nuxt: Nuxt) => ModuleSetupReturn;
}
interface NuxtModule<TOptions extends ModuleOptions = ModuleOptions, TOptionsDefaults extends Partial<TOptions> = Partial<TOptions>, TWith extends boolean = false> {
    (this: void, resolvedOptions: TWith extends true ? ResolvedModuleOptions<TOptions, TOptionsDefaults> : TOptions, nuxt: Nuxt): ModuleSetupReturn;
    getOptions?: (inlineOptions?: Partial<TOptions>, nuxt?: Nuxt) => Promise<TWith extends true ? ResolvedModuleOptions<TOptions, TOptionsDefaults> : TOptions>;
    getMeta?: () => Promise<ModuleMeta>;
}

interface NuxtDebugContext {
    /**
     * Module mutation records to the `nuxt` instance.
     */
    moduleMutationRecords?: NuxtDebugModuleMutationRecord[];
}
interface NuxtDebugModuleMutationRecord {
    module: NuxtModule;
    keys: (string | symbol)[];
    target: 'nuxt.options';
    value: any;
    method?: string;
    timestamp: number;
}
interface NuxtDebugOptions {
    /** Debug for Nuxt templates */
    templates?: boolean;
    /** Debug for modules setup timings */
    modules?: boolean;
    /** Debug for file watchers */
    watchers?: boolean;
    /** Debug options for Nitro */
    nitro?: NitroOptions['debug'];
    /** Debug for production hydration mismatch */
    hydration?: boolean;
    /** Debug for Vue Router */
    router?: boolean;
    /** Debug for hooks, can be set to `true` or an object with `server` and `client` keys */
    hooks?: boolean | {
        server?: boolean;
        client?: boolean;
    };
}

interface NuxtPlugin {
    /** @deprecated use mode */
    ssr?: boolean;
    src: string;
    mode?: 'all' | 'server' | 'client';
    /**
     * This allows more granular control over plugin order and should only be used by advanced users.
     * Lower numbers run first, and user plugins default to `0`.
     *
     * Default Nuxt priorities can be seen at [here](https://github.com/nuxt/nuxt/blob/9904849bc87c53dfbd3ea3528140a5684c63c8d8/packages/nuxt/src/core/plugins/plugin-metadata.ts#L15-L34).
     */
    order?: number;
    /**
     * @internal
     */
    name?: string;
}
type TemplateDefaultOptions = Record<string, any>;
interface NuxtTemplate<Options = TemplateDefaultOptions> {
    /** resolved output file path (generated) */
    dst?: string;
    /** The target filename once the template is copied into the Nuxt buildDir */
    filename?: string;
    /** An options object that will be accessible within the template via `<% options %>` */
    options?: Options;
    /** The resolved path to the source file to be template */
    src?: string;
    /** Provided compile option instead of src */
    getContents?: (data: {
        nuxt: Nuxt;
        app: NuxtApp;
        options: Options;
    }) => string | Promise<string>;
    /** Write to filesystem */
    write?: boolean;
    /**
     * The source path of the template (to try resolving dependencies from).
     * @internal
     */
    _path?: string;
}
interface NuxtServerTemplate {
    /** The target filename once the template is copied into the Nuxt buildDir */
    filename: string;
    getContents: () => string | Promise<string>;
}
interface ResolvedNuxtTemplate<Options = TemplateDefaultOptions> extends NuxtTemplate<Options> {
    filename: string;
    dst: string;
    modified?: boolean;
}
interface NuxtTypeTemplate<Options = TemplateDefaultOptions> extends Omit<NuxtTemplate<Options>, 'write' | 'filename'> {
    filename: `${string}.d.ts`;
    write?: true;
}
type _TemplatePlugin<Options> = Omit<NuxtPlugin, 'src'> & NuxtTemplate<Options>;
interface NuxtPluginTemplate<Options = TemplateDefaultOptions> extends _TemplatePlugin<Options> {
}
interface NuxtApp {
    mainComponent?: string | null;
    rootComponent?: string | null;
    errorComponent?: string | null;
    dir: string;
    extensions: string[];
    plugins: NuxtPlugin[];
    components: Component[];
    layouts: Record<string, NuxtLayout>;
    middleware: NuxtMiddleware[];
    templates: NuxtTemplate[];
    configs: string[];
    pages?: NuxtPage[];
}
interface Nuxt {
    __name: string;
    _version: string;
    _ignore?: Ignore;
    _dependencies?: Set<string>;
    _debug?: NuxtDebugContext;
    /** Async local storage for current running Nuxt module instance. */
    _asyncLocalStorageModule?: AsyncLocalStorage<NuxtModule>;
    /** The resolved Nuxt configuration. */
    options: NuxtOptions;
    hooks: Hookable<NuxtHooks>;
    hook: Nuxt['hooks']['hook'];
    callHook: Nuxt['hooks']['callHook'];
    addHooks: Nuxt['hooks']['addHooks'];
    runWithContext: <T extends (...args: any[]) => any>(fn: T) => ReturnType<T>;
    ready: () => Promise<void>;
    close: () => Promise<void>;
    /** The production or development server. */
    server?: any;
    vfs: Record<string, string>;
    apps: Record<string, NuxtApp>;
}

type HookResult = Promise<void> | void;
type TSReference = {
    types: string;
} | {
    path: string;
};
type WatchEvent = 'add' | 'addDir' | 'change' | 'unlink' | 'unlinkDir';
type VueTSConfig = 0 extends 1 & RawVueCompilerOptions ? TSConfig : TSConfig & {
    vueCompilerOptions?: RawVueCompilerOptions;
};
type NuxtPage = {
    name?: string;
    path: string;
    props?: RouteRecordRaw['props'];
    file?: string;
    meta?: Record<string, any>;
    alias?: string[] | string;
    redirect?: RouteLocationRaw;
    children?: NuxtPage[];
    middleware?: string[] | string;
    /**
     * Set the render mode.
     *
     * `all` means the page will be rendered isomorphically - with JavaScript both on client and server.
     *
     * `server` means pages are automatically rendered with server components, so there will be no JavaScript to render the page in your client bundle.
     *
     * `client` means that page will render on the client-side only.
     * @default 'all'
     */
    mode?: 'client' | 'server' | 'all';
    /** @internal */
    _sync?: boolean;
};
type NuxtMiddleware = {
    name: string;
    path: string;
    global?: boolean;
};
type NuxtLayout = {
    name: string;
    file: string;
};
interface ImportPresetWithDeprecation extends InlinePreset {
}
interface GenerateAppOptions {
    filter?: (template: ResolvedNuxtTemplate<any>) => boolean;
}
interface NuxtAnalyzeMeta {
    name: string;
    slug: string;
    startTime: number;
    endTime: number;
    analyzeDir: string;
    buildDir: string;
    outDir: string;
}
/**
 * The listeners to Nuxt build time events
 */
interface NuxtHooks {
    /**
     * Allows extending compatibility checks.
     * @param compatibility Compatibility object
     * @param issues Issues to be mapped
     * @returns Promise
     */
    'kit:compatibility': (compatibility: NuxtCompatibility, issues: NuxtCompatibilityIssues) => HookResult;
    /**
     * Called after Nuxt initialization, when the Nuxt instance is ready to work.
     * @param nuxt The configured Nuxt object
     * @returns Promise
     */
    'ready': (nuxt: Nuxt) => HookResult;
    /**
     * Called when Nuxt instance is gracefully closing.
     * @param nuxt The configured Nuxt object
     * @returns Promise
     */
    'close': (nuxt: Nuxt) => HookResult;
    /**
     * Called to restart the current Nuxt instance.
     * @returns Promise
     */
    'restart': (options?: {
        /**
         * Try to restart the whole process if supported
         */
        hard?: boolean;
    }) => HookResult;
    /**
     * Called during Nuxt initialization, before installing user modules.
     * @returns Promise
     */
    'modules:before': () => HookResult;
    /**
     * Called during Nuxt initialization, after installing user modules.
     * @returns Promise
     */
    'modules:done': () => HookResult;
    /**
     * Called after resolving the `app` instance.
     * @param app The resolved `NuxtApp` object
     * @returns Promise
     */
    'app:resolve': (app: NuxtApp) => HookResult;
    /**
     * Called during `NuxtApp` generation, to allow customizing, modifying or adding new files to the build directory (either virtually or to written to `.nuxt`).
     * @param app The configured `NuxtApp` object
     * @returns Promise
     */
    'app:templates': (app: NuxtApp) => HookResult;
    /**
     * Called after templates are compiled into the [virtual file system](https://nuxt.com/docs/guide/directory-structure/nuxt#virtual-file-system) (vfs).
     * @param app The configured `NuxtApp` object
     * @returns Promise
     */
    'app:templatesGenerated': (app: NuxtApp, templates: ResolvedNuxtTemplate[], options?: GenerateAppOptions) => HookResult;
    /**
     * Called before Nuxt bundle builder.
     * @returns Promise
     */
    'build:before': () => HookResult;
    /**
     * Called after Nuxt bundle builder is complete.
     * @returns Promise
     */
    'build:done': () => HookResult;
    /**
     * Called during the manifest build by Vite and Webpack. This allows customizing the manifest that Nitro will use to render `<script>` and `<link>` tags in the final HTML.
     * @param manifest The manifest object to build
     * @returns Promise
     */
    'build:manifest': (manifest: Manifest) => HookResult;
    /**
     * Called when `nuxt analyze` is finished
     * @param meta the analyze meta object, mutations will be saved to `meta.json`
     * @returns Promise
     */
    'build:analyze:done': (meta: NuxtAnalyzeMeta) => HookResult;
    /**
     * Called before generating the app.
     * @param options GenerateAppOptions object
     * @returns Promise
     */
    'builder:generateApp': (options?: GenerateAppOptions) => HookResult;
    /**
     * Called at build time in development when the watcher spots a change to a file or directory in the project.
     * @param event "add" | "addDir" | "change" | "unlink" | "unlinkDir"
     * @param path the path to the watched file
     * @returns Promise
     */
    'builder:watch': (event: WatchEvent, path: string) => HookResult;
    /**
     * Called after page routes are scanned from the file system.
     * @param pages Array containing scanned pages
     * @returns Promise
     */
    'pages:extend': (pages: NuxtPage[]) => HookResult;
    /**
     * Called after page routes have been augmented with scanned metadata.
     * @param pages Array containing resolved pages
     * @returns Promise
     */
    'pages:resolved': (pages: NuxtPage[]) => HookResult;
    /**
     * Called when resolving `app/router.options` files. It allows modifying the detected router options files
     * and adding new ones.
     *
     * Later items in the array override earlier ones.
     *
     * Adding a router options file will switch on page-based routing, unless `optional` is set, in which case
     * it will only apply when page-based routing is already enabled.
     * @param context An object with `files` containing an array of router options files.
     * @returns Promise
     */
    'pages:routerOptions': (context: {
        files: Array<{
            path: string;
            optional?: boolean;
        }>;
    }) => HookResult;
    /**
     * Called when the dev middleware is being registered on the Nitro dev server.
     * @param handler the Vite or Webpack event handler
     * @returns Promise
     */
    'server:devHandler': (handler: EventHandler) => HookResult;
    /**
     * Called at setup allowing modules to extend sources.
     * @param presets Array containing presets objects
     * @returns Promise
     */
    'imports:sources': (presets: ImportPresetWithDeprecation[]) => HookResult;
    /**
     * Called at setup allowing modules to extend imports.
     * @param imports Array containing the imports to extend
     * @returns Promise
     */
    'imports:extend': (imports: Import[]) => HookResult;
    /**
     * Called when the [unimport](https://github.com/unjs/unimport) context is created.
     * @param context The Unimport context
     * @returns Promise
     */
    'imports:context': (context: Unimport) => HookResult;
    /**
     * Allows extending import directories.
     * @param dirs Array containing directories as string
     * @returns Promise
     */
    'imports:dirs': (dirs: string[]) => HookResult;
    /**
     * Called within `app:resolve` allowing to extend the directories that are scanned for auto-importable components.
     * @param dirs The `dirs` option to push new items
     * @returns Promise
     */
    'components:dirs': (dirs: ComponentsOptions['dirs']) => HookResult;
    /**
     * Allows extending new components.
     * @param components The `components` array to push new items
     * @returns Promise
     */
    'components:extend': (components: Component[]) => HookResult;
    /**
     * Called before Nitro writes `.nuxt/tsconfig.server.json`, allowing addition of custom references and declarations.
     * @param options Objects containing `references`, `declarations`
     * @returns Promise
     */
    'nitro:prepare:types': (options: {
        references: TSReference[];
        declarations: string[];
    }) => HookResult;
    /**
     * Called before initializing Nitro, allowing customization of Nitro's configuration.
     * @param nitroConfig The nitro config to be extended
     * @returns Promise
     */
    'nitro:config': (nitroConfig: NitroConfig) => HookResult;
    /**
     * Called after Nitro is initialized, which allows registering Nitro hooks and interacting directly with Nitro.
     * @param nitro The created nitro object
     * @returns Promise
     */
    'nitro:init': (nitro: Nitro) => HookResult;
    /**
     * Called before building the Nitro instance.
     * @param nitro The created nitro object
     * @returns Promise
     */
    'nitro:build:before': (nitro: Nitro) => HookResult;
    /**
     * Called after copying public assets. Allows modifying public assets before Nitro server is built.
     * @param nitro The created nitro object
     * @returns Promise
     */
    'nitro:build:public-assets': (nitro: Nitro) => HookResult;
    /**
     * Allows extending the routes to be pre-rendered.
     * @param ctx Nuxt context
     * @returns Promise
     */
    'prerender:routes': (ctx: {
        routes: Set<string>;
    }) => HookResult;
    /**
     * Called when an error occurs at build time.
     * @param error Error object
     * @returns Promise
     */
    'build:error': (error: Error) => HookResult;
    /**
     * Called before @nuxt/cli writes `.nuxt/tsconfig.json` and `.nuxt/nuxt.d.ts`, allowing addition of custom references and declarations in `nuxt.d.ts`, or directly modifying the options in `tsconfig.json`
     * @param options Objects containing `references`, `declarations`, `tsConfig`
     * @returns Promise
     */
    'prepare:types': (options: {
        references: TSReference[];
        declarations: string[];
        tsConfig: VueTSConfig;
    }) => HookResult;
    /**
     * Called when the dev server is loading.
     * @param listenerServer The HTTP/HTTPS server object
     * @param listener The server's listener object
     * @returns Promise
     */
    'listen': (listenerServer: Server | Server$1, listener: any) => HookResult;
    /**
     * Allows extending default schemas.
     * @param schemas Schemas to be extend
     * @returns void
     */
    'schema:extend': (schemas: SchemaDefinition[]) => void;
    /**
     * Allows extending resolved schema.
     * @param schema Schema object
     * @returns void
     */
    'schema:resolved': (schema: Schema) => void;
    /**
     * Called before writing the given schema.
     * @param schema Schema object
     * @returns void
     */
    'schema:beforeWrite': (schema: Schema) => void;
    /**
     * Called after the schema is written.
     * @returns void
     */
    'schema:written': () => void;
    /**
     * Allows to extend Vite default context.
     * @param viteBuildContext The vite build context object
     * @returns Promise
     */
    'vite:extend': (viteBuildContext: {
        nuxt: Nuxt;
        config: ViteConfig;
    }) => HookResult;
    /**
     * Allows to extend Vite default config.
     * @param viteInlineConfig The vite inline config object
     * @param env Server or client
     * @returns Promise
     */
    'vite:extendConfig': (viteInlineConfig: ViteConfig, env: {
        isClient: boolean;
        isServer: boolean;
    }) => HookResult;
    /**
     * Allows to read the resolved Vite config.
     * @param viteInlineConfig The vite inline config object
     * @param env Server or client
     * @returns Promise
     */
    'vite:configResolved': (viteInlineConfig: Readonly<ViteConfig>, env: {
        isClient: boolean;
        isServer: boolean;
    }) => HookResult;
    /**
     * Called when the Vite server is created.
     * @param viteServer Vite development server
     * @param env Server or client
     * @returns Promise
     */
    'vite:serverCreated': (viteServer: ViteDevServer, env: {
        isClient: boolean;
        isServer: boolean;
    }) => HookResult;
    /**
     * Called after Vite server is compiled.
     * @returns Promise
     */
    'vite:compiled': () => HookResult;
    /**
     * Called before configuring the webpack compiler.
     * @param webpackConfigs Configs objects to be pushed to the compiler
     * @returns Promise
     */
    'webpack:config': (webpackConfigs: Configuration[]) => HookResult;
    /**
     * Allows to read the resolved webpack config
     * @param webpackConfigs Configs objects to be pushed to the compiler
     * @returns Promise
     */
    'webpack:configResolved': (webpackConfigs: Readonly<Configuration>[]) => HookResult;
    /**
     * Called right before compilation.
     * @param options The options to be added
     * @returns Promise
     */
    'webpack:compile': (options: {
        name: string;
        compiler: Compiler;
    }) => HookResult;
    /**
     * Called after resources are loaded.
     * @param options The compiler options
     * @returns Promise
     */
    'webpack:compiled': (options: {
        name: string;
        compiler: Compiler;
        stats: Stats;
    }) => HookResult;
    /**
     * Called on `change` on WebpackBar.
     * @param shortPath the short path
     * @returns void
     */
    'webpack:change': (shortPath: string) => void;
    /**
     * Called on `done` if has errors on WebpackBar.
     * @returns void
     */
    'webpack:error': () => void;
    /**
     * Called on `allDone` on WebpackBar.
     * @returns void
     */
    'webpack:done': () => void;
    /**
     * Called on `progress` on WebpackBar.
     * @param statesArray The array containing the states on progress
     * @returns void
     */
    'webpack:progress': (statesArray: any[]) => void;
    /**
     * Called before configuring the webpack compiler.
     * @param webpackConfigs Configs objects to be pushed to the compiler
     * @returns Promise
     */
    'rspack:config': (webpackConfigs: Configuration[]) => HookResult;
    /**
     * Allows to read the resolved webpack config
     * @param webpackConfigs Configs objects to be pushed to the compiler
     * @returns Promise
     */
    'rspack:configResolved': (webpackConfigs: Readonly<Configuration>[]) => HookResult;
    /**
     * Called right before compilation.
     * @param options The options to be added
     * @returns Promise
     */
    'rspack:compile': (options: {
        name: string;
        compiler: Compiler;
    }) => HookResult;
    /**
     * Called after resources are loaded.
     * @param options The compiler options
     * @returns Promise
     */
    'rspack:compiled': (options: {
        name: string;
        compiler: Compiler;
        stats: Stats;
    }) => HookResult;
    /**
     * Called on `change` on WebpackBar.
     * @param shortPath the short path
     * @returns void
     */
    'rspack:change': (shortPath: string) => void;
    /**
     * Called on `done` if has errors on WebpackBar.
     * @returns void
     */
    'rspack:error': () => void;
    /**
     * Called on `allDone` on WebpackBar.
     * @returns void
     */
    'rspack:done': () => void;
    /**
     * Called on `progress` on WebpackBar.
     * @param statesArray The array containing the states on progress
     * @returns void
     */
    'rspack:progress': (statesArray: any[]) => void;
}
type NuxtHookName = keyof NuxtHooks;

type MetaObjectRaw = SerializableHead;
type MetaObject = MetaObjectRaw;
type AppHeadMetaObject = MetaObjectRaw & {
    /**
     * The character encoding in which the document is encoded => `<meta charset="<value>" />`
     * @default `'utf-8'`
     */
    charset?: string;
    /**
     * Configuration of the viewport (the area of the window in which web content can be seen),
     * mapped to => `<meta name="viewport" content="<value>" />`
     * @default `'width=device-width, initial-scale=1'`
     */
    viewport?: string;
};
type SerializableHtmlAttributes = GlobalAttributes & AriaAttributes & DataKeys;

interface ImportsOptions extends UnimportOptions {
    /**
     * Enable implicit auto import from Vue, Nuxt and module contributed utilities.
     * Generate global TypeScript definitions.
     * @default true
     */
    autoImport?: boolean;
    /**
     * Directories to scan for auto imports.
     * @see https://nuxt.com/docs/guide/directory-structure/composables#how-files-are-scanned
     * @default ['./composables', './utils']
     */
    dirs?: string[];
    /**
     * Enabled scan for local directories for auto imports.
     * When this is disabled, `dirs` options will be ignored.
     * @default true
     */
    scan?: boolean;
    /**
     * Assign auto imported utilities to `globalThis` instead of using built time transformation.
     * @default false
     */
    global?: boolean;
    transform?: {
        exclude?: RegExp[];
        include?: RegExp[];
    };
    /**
     * Add polyfills for setInterval, requestIdleCallback, and others
     * @default true
     */
    polyfills?: boolean;
}

interface ConfigSchema {
    /**
     * Configure Nuxt component auto-registration.
     *
     * Any components in the directories configured here can be used throughout your pages, layouts (and other components) without needing to explicitly import them.
     *
     * @see [`components/` directory documentation](https://nuxt.com/docs/guide/directory-structure/components)
     */
    components: boolean | ComponentsOptions | ComponentsOptions['dirs'];
    /**
     * Configure how Nuxt auto-imports composables into your application.
     *
     * @see [Nuxt documentation](https://nuxt.com/docs/guide/directory-structure/composables)
     */
    imports: ImportsOptions;
    /**
     * Whether to use the vue-router integration in Nuxt 3. If you do not provide a value it will be enabled if you have a `pages/` directory in your source folder.
     *
     * Additionally, you can provide a glob pattern or an array of patterns to scan only certain files for pages.
     *
     * @example
     * ```js
     * pages: {
     *   pattern: ['**\/*\/*.vue', '!**\/*.spec.*'],
     * }
     * ```
     */
    pages: boolean | {
        enabled?: boolean;
        pattern?: string | string[];
    };
    /**
     * Manually disable nuxt telemetry.
     *
     * @see [Nuxt Telemetry](https://github.com/nuxt/telemetry) for more information.
     */
    telemetry: boolean | Record<string, any>;
    /**
     * Enable Nuxt DevTools for development.
     *
     * Breaking changes for devtools might not reflect on the version of Nuxt.
     *
     * @see  [Nuxt DevTools](https://devtools.nuxt.com/) for more information.
     */
    devtools: boolean | {
        enabled: boolean;
        [key: string]: any;
    };
    /**
     * Vue.js config
     */
    vue: {
        transformAssetUrls: AssetURLTagConfig;
        /**
         * Options for the Vue compiler that will be passed at build time.
         *
         * @see [Vue documentation](https://vuejs.org/api/application.html#app-config-compileroptions)
         */
        compilerOptions: CompilerOptions;
        /**
         * Include Vue compiler in runtime bundle.
         *
         * @default false
         */
        runtimeCompiler: boolean;
        /**
         * Enable reactive destructure for `defineProps`
         *
         * @default true
         */
        propsDestructure: boolean;
        /**
         * It is possible to pass configure the Vue app globally. Only serializable options may be set in your `nuxt.config`. All other options should be set at runtime in a Nuxt plugin..
         *
         * @see [Vue app config documentation](https://vuejs.org/api/application.html#app-config)
         */
        config: Serializable<AppConfig$1>;
    };
    /**
     * Nuxt App configuration.
     */
    app: {
        /**
         * The base path of your Nuxt application.
         *
         * For example:
         *
         * @default "/"
         *
         * @example
         * ```ts
         * export default defineNuxtConfig({
         *   app: {
         *     baseURL: '/prefix/'
         *   }
         * })
         * ```
         *
         * This can also be set at runtime by setting the NUXT_APP_BASE_URL environment variable.
         *
         * @example
         * ```bash
         * NUXT_APP_BASE_URL=/prefix/ node .output/server/index.mjs
         * ```
         */
        baseURL: string;
        /**
         * The folder name for the built site assets, relative to `baseURL` (or `cdnURL` if set). This is set at build time and should not be customized at runtime.
         *
         * @default "/_nuxt/"
         */
        buildAssetsDir: string;
        /**
         * An absolute URL to serve the public folder from (production-only).
         *
         * For example:
         *
         * @default ""
         *
         * @example
         * ```ts
         * export default defineNuxtConfig({
         *   app: {
         *     cdnURL: 'https://mycdn.org/'
         *   }
         * })
         * ```
         *
         * This can be set to a different value at runtime by setting the `NUXT_APP_CDN_URL` environment variable.
         *
         * @example
         * ```bash
         * NUXT_APP_CDN_URL=https://mycdn.org/ node .output/server/index.mjs
         * ```
         */
        cdnURL: string;
        /**
         * Set default configuration for `<head>` on every page.
         *
         * @example
         * ```js
         * app: {
         *   head: {
         *     meta: [
         *       // <meta name="viewport" content="width=device-width, initial-scale=1">
         *       { name: 'viewport', content: 'width=device-width, initial-scale=1' }
         *     ],
         *     script: [
         *       // <script src="https://myawesome-lib.js"></script>
         *       { src: 'https://awesome-lib.js' }
         *     ],
         *     link: [
         *       // <link rel="stylesheet" href="https://myawesome-lib.css">
         *       { rel: 'stylesheet', href: 'https://awesome-lib.css' }
         *     ],
         *     // please note that this is an area that is likely to change
         *     style: [
         *       // <style>:root { color: red }</style>
         *       { textContent: ':root { color: red }' }
         *     ],
         *     noscript: [
         *       // <noscript>JavaScript is required</noscript>
         *       { textContent: 'JavaScript is required' }
         *     ]
         *   }
         * }
         * ```
         */
        head: NuxtAppConfig['head'];
        /**
         * Default values for layout transitions.
         *
         * This can be overridden with `definePageMeta` on an individual page. Only JSON-serializable values are allowed.
         *
         * @default false
         *
         * @see [Vue Transition docs](https://vuejs.org/api/built-in-components.html#transition)
         */
        layoutTransition: NuxtAppConfig['layoutTransition'];
        /**
         * Default values for page transitions.
         *
         * This can be overridden with `definePageMeta` on an individual page. Only JSON-serializable values are allowed.
         *
         * @default false
         *
         * @see [Vue Transition docs](https://vuejs.org/api/built-in-components.html#transition)
         */
        pageTransition: NuxtAppConfig['pageTransition'];
        /**
         * Default values for view transitions.
         *
         * This only has an effect when **experimental** support for View Transitions is [enabled in your nuxt.config file](/docs/getting-started/transitions#view-transitions-api-experimental).
         * This can be overridden with `definePageMeta` on an individual page.
         *
         * @default false
         *
         * @see [Nuxt View Transition API docs](https://nuxt.com/docs/getting-started/transitions#view-transitions-api-experimental)
         */
        viewTransition: NuxtAppConfig['viewTransition'];
        /**
         * Default values for KeepAlive configuration between pages.
         *
         * This can be overridden with `definePageMeta` on an individual page. Only JSON-serializable values are allowed.
         *
         * @default false
         *
         * @see [Vue KeepAlive](https://vuejs.org/api/built-in-components.html#keepalive)
         */
        keepalive: NuxtAppConfig['keepalive'];
        /**
         * Customize Nuxt root element id.
         *
         * @default "__nuxt"
         *
         * @deprecated Prefer `rootAttrs.id` instead
         */
        rootId: string | false;
        /**
         * Customize Nuxt root element tag.
         *
         * @default "div"
         */
        rootTag: string;
        /**
         * Customize Nuxt root element id.
         *
         */
        rootAttrs: SerializableHtmlAttributes;
        /**
         * Customize Nuxt Teleport element tag.
         *
         * @default "div"
         */
        teleportTag: string;
        /**
         * Customize Nuxt Teleport element id.
         *
         * @default "teleports"
         *
         * @deprecated Prefer `teleportAttrs.id` instead
         */
        teleportId: string | false;
        /**
         * Customize Nuxt Teleport element attributes.
         *
         */
        teleportAttrs: SerializableHtmlAttributes;
        /**
         * Customize Nuxt SpaLoader element tag.
         *
         * @default "div"
         */
        spaLoaderTag: string;
        /**
         * Customize Nuxt Nuxt SpaLoader element attributes.
         *
         */
        spaLoaderAttrs: SerializableHtmlAttributes;
    };
    /**
     * Boolean or a path to an HTML file with the contents of which will be inserted into any HTML page rendered with `ssr: false`.
     *
     * - If it is unset, it will use `~/app/spa-loading-template.html` file in one of your layers, if it exists. - If it is false, no SPA loading indicator will be loaded. - If true, Nuxt will look for `~/app/spa-loading-template.html` file in one of your layers, or a
     *   default Nuxt image will be used.
     * Some good sources for spinners are [SpinKit](https://github.com/tobiasahlin/SpinKit) or [SVG Spinners](https://icones.js.org/collection/svg-spinners).
     *
     * @example ~/app/spa-loading-template.html
     * ```html
     * <!-- https://github.com/barelyhuman/snips/blob/dev/pages/css-loader.md -->
     * <div class="loader"></div>
     * <style>
     * .loader {
     *   display: block;
     *   position: fixed;
     *   z-index: 1031;
     *   top: 50%;
     *   left: 50%;
     *   transform: translate(-50%, -50%);
     *   width: 18px;
     *   height: 18px;
     *   box-sizing: border-box;
     *   border: solid 2px transparent;
     *   border-top-color: #000;
     *   border-left-color: #000;
     *   border-bottom-color: #efefef;
     *   border-right-color: #efefef;
     *   border-radius: 50%;
     *   -webkit-animation: loader 400ms linear infinite;
     *   animation: loader 400ms linear infinite;
     * }
     *
     * @-webkit-keyframes loader {
     *   0% {
     *     -webkit-transform: translate(-50%, -50%) rotate(0deg);
     *   }
     *   100% {
     *     -webkit-transform: translate(-50%, -50%) rotate(360deg);
     *   }
     * }
     * @keyframes loader {
     *   0% {
     *     transform: translate(-50%, -50%) rotate(0deg);
     *   }
     *   100% {
     *     transform: translate(-50%, -50%) rotate(360deg);
     *   }
     * }
     * </style>
     * ```
     */
    spaLoadingTemplate: string | boolean | undefined | null;
    /**
     * An array of nuxt app plugins.
     *
     * Each plugin can be a string (which can be an absolute or relative path to a file). If it ends with `.client` or `.server` then it will be automatically loaded only in the appropriate context.
     * It can also be an object with `src` and `mode` keys.
     *
     * @note Plugins are also auto-registered from the `~/plugins` directory
     * and these plugins do not need to be listed in `nuxt.config` unless you
     * need to customize their order. All plugins are deduplicated by their src path.
     *
     * @see [`plugins/` directory documentation](https://nuxt.com/docs/guide/directory-structure/plugins)
     *
     * @example
     * ```js
     * plugins: [
     *   '~/plugins/foo.client.js', // only in client side
     *   '~/plugins/bar.server.js', // only in server side
     *   '~/plugins/baz.js', // both client & server
     *   { src: '~/plugins/both-sides.js' },
     *   { src: '~/plugins/client-only.js', mode: 'client' }, // only on client side
     *   { src: '~/plugins/server-only.js', mode: 'server' } // only on server side
     * ]
     * ```
     */
    plugins: (NuxtPlugin | string)[];
    /**
     * You can define the CSS files/modules/libraries you want to set globally (included in every page).
     *
     * Nuxt will automatically guess the file type by its extension and use the appropriate pre-processor. You will still need to install the required loader if you need to use them.
     *
     * @example
     * ```js
     * css: [
     *   // Load a Node.js module directly (here it's a Sass file).
     *   'bulma',
     *   // CSS file in the project
     *   '~/assets/css/main.css',
     *   // SCSS file in the project
     *   '~/assets/css/main.scss'
     * ]
     * ```
     */
    css: string[];
    /**
     * An object that allows us to configure the `unhead` nuxt module.
     */
    unhead: {
        /**
         * Enable the legacy compatibility mode for `unhead` module. This applies the following changes: - Disables Capo.js sorting - Adds the `DeprecationsPlugin`: supports `hid`, `vmid`, `children`, `body` - Adds the `PromisesPlugin`: supports promises as input
         *
         * @default false
         *
         * @see [`unhead` migration documentation](https://unhead.unjs.io/docs/typescript/head/guides/get-started/migration)
         *
         * @example
         * ```ts
         * export default defineNuxtConfig({
         *  unhead: {
         *   legacy: true
         * })
         * ```
         */
        legacy: boolean;
        /**
         * An object that will be passed to `renderSSRHead` to customize the output.
         *
         * @example
         * ```ts
         * export default defineNuxtConfig({
         *  unhead: {
         *   renderSSRHeadOptions: {
         *    omitLineBreaks: true
         *   }
         * })
         * ```
         */
        renderSSRHeadOptions: RenderSSRHeadOptions;
    };
    /**
     * The builder to use for bundling the Vue part of your application.
     *
     * @default "@nuxt/vite-builder"
     */
    builder: 'vite' | 'webpack' | 'rspack' | {
        bundle: (nuxt: Nuxt) => Promise<void>;
    };
    /**
     * Configures whether and how sourcemaps are generated for server and/or client bundles.
     *
     * If set to a single boolean, that value applies to both server and client. Additionally, the `'hidden'` option is also available for both server and client.
     * Available options for both client and server: - `true`: Generates sourcemaps and includes source references in the final bundle. - `false`: Does not generate any sourcemaps. - `'hidden'`: Generates sourcemaps but does not include references in the final bundle.
     */
    sourcemap: boolean | {
        server?: boolean | 'hidden';
        client?: boolean | 'hidden';
    };
    /**
     * Log level when building logs.
     *
     * Defaults to 'silent' when running in CI or when a TTY is not available. This option is then used as 'silent' in Vite and 'none' in Webpack
     *
     * @default "info"
     */
    logLevel: 'silent' | 'info' | 'verbose';
    /**
     * Shared build configuration.
     */
    build: {
        /**
         * If you want to transpile specific dependencies with Babel, you can add them here. Each item in transpile can be a package name, a function, a string or regex object matching the dependency's file name.
         *
         * You can also use a function to conditionally transpile. The function will receive an object ({ isDev, isServer, isClient, isModern, isLegacy }).
         *
         * @example
         * ```js
         * transpile: [({ isLegacy }) => isLegacy && 'ky']
         * ```
         */
        transpile: Array<string | RegExp | ((ctx: {
            isClient?: boolean;
            isServer?: boolean;
            isDev: boolean;
        }) => string | RegExp | false)>;
        /**
         * It is recommended to use `addTemplate` from `@nuxt/kit` instead of this option.
         *
         * @example
         * ```js
         * templates: [
         *   {
         *     src: '~/modules/support/plugin.js', // `src` can be absolute or relative
         *     dst: 'support.js', // `dst` is relative to project `.nuxt` dir
         *   }
         * ]
         * ```
         */
        templates: NuxtTemplate<any>[];
        /**
         * Nuxt allows visualizing your bundles and how to optimize them.
         *
         * Set to `true` to enable bundle analysis, or pass an object with options: [for webpack](https://github.com/webpack-contrib/webpack-bundle-analyzer#options-for-plugin) or [for vite](https://github.com/btd/rollup-plugin-visualizer#options).
         *
         * @example
         * ```js
         * analyze: {
         *   analyzerMode: 'static'
         * }
         * ```
         */
        analyze: boolean | {
            enabled?: boolean;
        } & ((0 extends 1 & BundleAnalyzerPlugin.Options ? Record<string, unknown> : BundleAnalyzerPlugin.Options) | PluginVisualizerOptions);
    };
    /**
     * Build time optimization configuration.
     */
    optimization: {
        /**
         * Functions to inject a key for.
         *
         * As long as the number of arguments passed to the function is less than `argumentLength`, an additional magic string will be injected that can be used to deduplicate requests between server and client. You will need to take steps to handle this additional key.
         * The key will be unique based on the location of the function being invoked within the file.
         *
         * @default [{"name":"callOnce","argumentLength":3},{"name":"defineNuxtComponent","argumentLength":2},{"name":"useState","argumentLength":2},{"name":"useFetch","argumentLength":3},{"name":"useAsyncData","argumentLength":3},{"name":"useLazyAsyncData","argumentLength":3},{"name":"useLazyFetch","argumentLength":3}]
         */
        keyedComposables: Array<{
            name: string;
            source?: string | RegExp;
            argumentLength: number;
        }>;
        /**
         * Tree shake code from specific builds.
         *
         */
        treeShake: {
            /**
             * Tree shake composables from the server or client builds.
             *
             *
             * @example
             * ```js
             * treeShake: { client: { myPackage: ['useServerOnlyComposable'] } }
             * ```
             */
            composables: {
                server: Record<string, string[]>;
                client: Record<string, string[]>;
            };
        };
        /**
         * Options passed directly to the transformer from `unctx` that preserves async context after `await`.
         *
         */
        asyncTransforms: TransformerOptions;
    };
    /**
     * Extend project from multiple local or remote sources.
     *
     * Value should be either a string or array of strings pointing to source directories or config path relative to current config.
     * You can use `github:`, `gh:` `gitlab:` or `bitbucket:`
     *
     * @see [`c12` docs on extending config layers](https://github.com/unjs/c12#extending-config-layer-from-remote-sources)
     *
     * @see [`giget` documentation](https://github.com/unjs/giget)
     */
    extends: string | [string, SourceOptions?] | (string | [string, SourceOptions?])[];
    /**
     * Specify a compatibility date for your app.
     *
     * This is used to control the behavior of presets in Nitro, Nuxt Image and other modules that may change behavior without a major version bump.
     * We plan to improve the tooling around this feature in the future.
     */
    compatibilityDate: CompatibilityDateSpec;
    /**
     * Extend project from a local or remote source.
     *
     * Value should be a string pointing to source directory or config path relative to current config.
     * You can use `github:`, `gitlab:`, `bitbucket:` or `https://` to extend from a remote git repository.
     */
    theme: string;
    /**
     * Define the root directory of your application.
     *
     * This property can be overwritten (for example, running `nuxt ./my-app/` will set the `rootDir` to the absolute path of `./my-app/` from the current/working directory.
     * It is normally not needed to configure this option.
     *
     * @default "/<rootDir>"
     */
    rootDir: string;
    /**
     * Define the workspace directory of your application.
     *
     * Often this is used when in a monorepo setup. Nuxt will attempt to detect your workspace directory automatically, but you can override it here.
     * It is normally not needed to configure this option.
     *
     * @default "/<workspaceDir>"
     */
    workspaceDir: string;
    /**
     * Define the source directory of your Nuxt application.
     *
     * If a relative path is specified, it will be relative to the `rootDir`.
     *
     * @default "/<srcDir>"
     *
     * @example
     * ```js
     * export default {
     *   srcDir: 'src/'
     * }
     * ```
     * This would work with the following folder structure:
     * ```bash
     * -| app/
     * ---| node_modules/
     * ---| nuxt.config.js
     * ---| package.json
     * ---| src/
     * ------| assets/
     * ------| components/
     * ------| layouts/
     * ------| middleware/
     * ------| pages/
     * ------| plugins/
     * ------| public/
     * ------| store/
     * ------| server/
     * ------| app.config.ts
     * ------| app.vue
     * ------| error.vue
     * ```
     */
    srcDir: string;
    /**
     * Define the server directory of your Nuxt application, where Nitro routes, middleware and plugins are kept.
     *
     * If a relative path is specified, it will be relative to your `rootDir`.
     *
     * @default "/<srcDir>/server"
     */
    serverDir: string;
    /**
     * Define the directory where your built Nuxt files will be placed.
     *
     * Many tools assume that `.nuxt` is a hidden directory (because it starts with a `.`). If that is a problem, you can use this option to prevent that.
     *
     * @default "/<rootDir>/.nuxt"
     *
     * @example
     * ```js
     * export default {
     *   buildDir: 'nuxt-build'
     * }
     * ```
     */
    buildDir: string;
    /**
     * For multi-app projects, the unique id of the Nuxt application.
     *
     * Defaults to `nuxt-app`.
     *
     * @default "nuxt-app"
     */
    appId: string;
    /**
     * A unique identifier matching the build. This may contain the hash of the current state of the project.
     *
     * @default "f9b9e91a-b239-45f5-b381-a81997def0c5"
     */
    buildId: string;
    /**
     * Used to set the modules directories for path resolving (for example, webpack's `resolveLoading`, `nodeExternals` and `postcss`).
     *
     * The configuration path is relative to `options.rootDir` (default is current working directory).
     * Setting this field may be necessary if your project is organized as a yarn workspace-styled mono-repository.
     *
     * @default ["/<rootDir>/node_modules"]
     *
     * @example
     * ```js
     * export default {
     *   modulesDir: ['../../node_modules']
     * }
     * ```
     */
    modulesDir: Array<string>;
    /**
     * The directory where Nuxt will store the generated files when running `nuxt analyze`.
     *
     * If a relative path is specified, it will be relative to your `rootDir`.
     *
     * @default "/<rootDir>/.nuxt/analyze"
     */
    analyzeDir: string;
    /**
     * Whether Nuxt is running in development mode.
     *
     * Normally, you should not need to set this.
     *
     * @default false
     */
    dev: boolean;
    /**
     * Whether your app is being unit tested.
     *
     * @default false
     */
    test: boolean;
    /**
     * Set to `true` to enable debug mode.
     *
     * At the moment, it prints out hook names and timings on the server, and logs hook arguments as well in the browser.
     * You can also set this to an object to enable specific debug options.
     *
     * @default false
     */
    debug: boolean | (NuxtDebugOptions) | undefined;
    /**
     * Whether to enable rendering of HTML - either dynamically (in server mode) or at generate time. If set to `false` generated pages will have no content.
     *
     * @default true
     */
    ssr: boolean;
    /**
     * Modules are Nuxt extensions which can extend its core functionality and add endless integrations.
     *
     * Each module is either a string (which can refer to a package, or be a path to a file), a tuple with the module as first string and the options as a second object, or an inline module function.
     * Nuxt tries to resolve each item in the modules array using node require path (in `node_modules`) and then will be resolved from project `srcDir` if `~` alias is used.
     *
     * @note Modules are executed sequentially so the order is important. First, the modules defined in `nuxt.config.ts` are loaded. Then, modules found in the `modules/`
     * directory are executed, and they load in alphabetical order.
     *
     * @example
     * ```js
     * modules: [
     *   // Using package name
     *   '@nuxtjs/axios',
     *   // Relative to your project srcDir
     *   '~/modules/awesome.js',
     *   // Providing options
     *   ['@nuxtjs/google-analytics', { ua: 'X1234567' }],
     *   // Inline definition
     *   function () {}
     * ]
     * ```
     */
    modules: (NuxtModule<any> | string | [NuxtModule | string, Record<string, any>] | undefined | null | false)[];
    /**
     * Customize default directory structure used by Nuxt.
     *
     * It is better to stick with defaults unless needed.
     */
    dir: {
        /** @default "app" */
        app: string;
        /**
         * The assets directory (aliased as `~assets` in your build).
         *
         * @default "assets"
         */
        assets: string;
        /**
         * The layouts directory, each file of which will be auto-registered as a Nuxt layout.
         *
         * @default "layouts"
         */
        layouts: string;
        /**
         * The middleware directory, each file of which will be auto-registered as a Nuxt middleware.
         *
         * @default "middleware"
         */
        middleware: string;
        /**
         * The modules directory, each file in which will be auto-registered as a Nuxt module.
         *
         * @default "modules"
         */
        modules: string;
        /**
         * The directory which will be processed to auto-generate your application page routes.
         *
         * @default "pages"
         */
        pages: string;
        /**
         * The plugins directory, each file of which will be auto-registered as a Nuxt plugin.
         *
         * @default "plugins"
         */
        plugins: string;
        /**
         * The shared directory. This directory is shared between the app and the server.
         *
         * @default "shared"
         */
        shared: string;
        /**
         * The directory containing your static files, which will be directly accessible via the Nuxt server and copied across into your `dist` folder when your app is generated.
         *
         * @default "public"
         */
        public: string;
        /**
         * @default "public"
         *
         * @deprecated use `dir.public` option instead
         */
        static: string;
    };
    /**
     * The extensions that should be resolved by the Nuxt resolver.
     *
     * @default [".js",".jsx",".mjs",".ts",".tsx",".vue"]
     */
    extensions: Array<string>;
    /**
     * You can improve your DX by defining additional aliases to access custom directories within your JavaScript and CSS.
     *
     * @note Within a webpack context (image sources, CSS - but not JavaScript) you _must_ access
     * your alias by prefixing it with `~`.
     *
     * @note These aliases will be automatically added to the generated `.nuxt/tsconfig.json` so you can get full
     * type support and path auto-complete. In case you need to extend options provided by `./.nuxt/tsconfig.json`
     * further, make sure to add them here or within the `typescript.tsConfig` property in `nuxt.config`.
     *
     * @example
     * ```js
     * export default {
     *   alias: {
     *     'images': fileURLToPath(new URL('./assets/images', import.meta.url)),
     *     'style': fileURLToPath(new URL('./assets/style', import.meta.url)),
     *     'data': fileURLToPath(new URL('./assets/other/data', import.meta.url))
     *   }
     * }
     * ```
     *
     * ```html
     * <template>
     *   <img src="~images/main-bg.jpg">
     * </template>
     *
     * <script>
     * import data from 'data/test.json'
     * </script>
     *
     * <style>
     * // Uncomment the below
     * //@import '~style/variables.scss';
     * //@import '~style/utils.scss';
     * //@import '~style/base.scss';
     * body {
     *   background-image: url('~images/main-bg.jpg');
     * }
     * </style>
     * ```
     */
    alias: Record<string, string>;
    /**
     * Pass options directly to `node-ignore` (which is used by Nuxt to ignore files).
     *
     * @see [node-ignore](https://github.com/kaelzhang/node-ignore)
     *
     * @example
     * ```js
     * ignoreOptions: {
     *   ignorecase: false
     * }
     * ```
     */
    ignoreOptions: Options;
    /**
     * Any file in `pages/`, `layouts/`, `middleware/`, and `public/` directories will be ignored during the build process if its filename starts with the prefix specified by `ignorePrefix`. This is intended to prevent certain files from being processed or served in the built application. By default, the `ignorePrefix` is set to '-', ignoring any files starting with '-'.
     *
     * @default "-"
     */
    ignorePrefix: string;
    /**
     * More customizable than `ignorePrefix`: all files matching glob patterns specified inside the `ignore` array will be ignored in building.
     *
     * @default ["**\/*.stories.{js,cts,mts,ts,jsx,tsx}","**\/*.{spec,test}.{js,cts,mts,ts,jsx,tsx}","**\/*.d.{cts,mts,ts}","**\/.{pnpm-store,vercel,netlify,output,git,cache,data}","**\/*.sock",".nuxt/analyze",".nuxt","**\/-*.*"]
     */
    ignore: Array<string>;
    /**
     * The watch property lets you define patterns that will restart the Nuxt dev server when changed.
     *
     * It is an array of strings or regular expressions. Strings should be either absolute paths or relative to the `srcDir` (and the `srcDir` of any layers). Regular expressions will be matched against the path relative to the project `srcDir` (and the `srcDir` of any layers).
     */
    watch: Array<string | RegExp>;
    /**
     * The watchers property lets you overwrite watchers configuration in your `nuxt.config`.
     */
    watchers: {
        /**
         * An array of event types, which, when received, will cause the watcher to restart.
         */
        rewatchOnRawEvents: string[];
        /**
         * `watchOptions` to pass directly to webpack.
         *
         * @see [webpack@4 watch options](https://v4.webpack.js.org/configuration/watch/#watchoptions).
         */
        webpack: {
            /** @default 1000 */
            aggregateTimeout: number;
        };
        /**
         * Options to pass directly to `chokidar`.
         *
         * @see [chokidar](https://github.com/paulmillr/chokidar#api)
         */
        chokidar: ChokidarOptions;
    };
    /**
     * Hooks are listeners to Nuxt events that are typically used in modules, but are also available in `nuxt.config`.
     *
     * Internally, hooks follow a naming pattern using colons (e.g., build:done).
     * For ease of configuration, you can also structure them as an hierarchical object in `nuxt.config` (as below).
     *
     * @example
     * ```js
     * import fs from 'node:fs'
     * import path from 'node:path'
     * export default {
     *   hooks: {
     *     build: {
     *       done(builder) {
     *         const extraFilePath = path.join(
     *           builder.nuxt.options.buildDir,
     *           'extra-file'
     *         )
     *         fs.writeFileSync(extraFilePath, 'Something extra')
     *       }
     *     }
     *   }
     * }
     * ```
     */
    hooks: NuxtHooks;
    /**
     * Runtime config allows passing dynamic config and environment variables to the Nuxt app context.
     *
     * The value of this object is accessible from server only using `useRuntimeConfig`.
     * It mainly should hold _private_ configuration which is not exposed on the frontend. This could include a reference to your API secret tokens.
     * Anything under `public` and `app` will be exposed to the frontend as well.
     * Values are automatically replaced by matching env variables at runtime, e.g. setting an environment variable `NUXT_API_KEY=my-api-key NUXT_PUBLIC_BASE_URL=/foo/` would overwrite the two values in the example below.
     *
     * @example
     * ```js
     * export default {
     *  runtimeConfig: {
     *     apiKey: '', // Default to an empty string, automatically set at runtime using process.env.NUXT_API_KEY
     *     public: {
     *        baseURL: '' // Exposed to the frontend as well.
     *     }
     *   }
     * }
     * ```
     */
    runtimeConfig: RuntimeConfig;
    /**
     * Additional app configuration
     *
     * For programmatic usage and type support, you can directly provide app config with this option. It will be merged with `app.config` file as default value.
     */
    appConfig: AppConfig;
    devServer: {
        /**
         * Whether to enable HTTPS.
         *
         * @default false
         *
         * @example
         * ```ts
         * export default defineNuxtConfig({
         *   devServer: {
         *     https: {
         *       key: './server.key',
         *       cert: './server.crt'
         *     }
         *   }
         * })
         * ```
         */
        https: boolean | {
            key: string;
            cert: string;
        } | {
            pfx: string;
            passphrase: string;
        };
        /**
         * Dev server listening port
         *
         * @default 3000
         */
        port: number;
        /**
         * Dev server listening host
         *
         */
        host: string | undefined;
        /**
         * Listening dev server URL.
         *
         * This should not be set directly as it will always be overridden by the dev server with the full URL (for module and internal use).
         *
         * @default "http://localhost:3000"
         */
        url: string;
        /**
         * Template to show a loading screen
         *
         */
        loadingTemplate: (data: {
            loading?: string;
        }) => string;
        /**
         * Set CORS options for the dev server
         *
         */
        cors: H3CorsOptions;
    };
    /**
     * `future` is for early opting-in to new features that will become default in a future (possibly major) version of the framework.
     */
    future: {
        /**
         * Enable early access to Nuxt v4 features or flags.
         *
         * Setting `compatibilityVersion` to `4` changes defaults throughout your Nuxt configuration, but you can granularly re-enable Nuxt v3 behaviour when testing (see example). Please file issues if so, so that we can address in Nuxt or in the ecosystem.
         *
         * @default 3
         *
         * @example
         * ```ts
         * export default defineNuxtConfig({
         *   future: {
         *     compatibilityVersion: 4,
         *   },
         *   // To re-enable _all_ Nuxt v3 behaviour, set the following options:
         *   srcDir: '.',
         *   dir: {
         *     app: 'app'
         *   },
         *   experimental: {
         *     compileTemplate: true,
         *     templateUtils: true,
         *     relativeWatchPaths: true,
         *     resetAsyncDataToUndefined: true,
         *     defaults: {
         *       useAsyncData: {
         *         deep: true
         *       }
         *     }
         *   },
         *   unhead: {
         *     renderSSRHeadOptions: {
         *       omitLineBreaks: false
         *     }
         *   }
         * })
         * ```
         */
        compatibilityVersion: 3 | 4;
        /**
         * This enables early access to the experimental multi-app support.
         *
         * @default false
         *
         * @see [Nuxt Issue #21635](https://github.com/nuxt/nuxt/issues/21635)
         */
        multiApp: boolean;
        /**
         * This enables 'Bundler' module resolution mode for TypeScript, which is the recommended setting for frameworks like Nuxt and Vite.
         *
         * It improves type support when using modern libraries with `exports`.
         * You can set it to false to use the legacy 'Node' mode, which is the default for TypeScript.
         *
         * @default true
         *
         * @see [TypeScript PR implementing `bundler` module resolution](https://github.com/microsoft/TypeScript/pull/51669)
         */
        typescriptBundlerResolution: boolean;
    };
    /**
     * Some features of Nuxt are available on an opt-in basis, or can be disabled based on your needs.
     */
    features: {
        /**
         * Inline styles when rendering HTML (currently vite only).
         *
         * You can also pass a function that receives the path of a Vue component and returns a boolean indicating whether to inline the styles for that component.
         *
         * @default true
         */
        inlineStyles: boolean | ((id?: string) => boolean);
        /**
         * Stream server logs to the client as you are developing. These logs can be handled in the `dev:ssr-logs` hook.
         *
         * If set to `silent`, the logs will not be printed to the browser console.
         *
         * @default false
         */
        devLogs: boolean | 'silent';
        /**
         * Turn off rendering of Nuxt scripts and JS resource hints. You can also disable scripts more granularly within `routeRules`.
         *
         * If set to 'production' or `true`, JS will be disabled in production mode only.
         *
         * @default false
         */
        noScripts: 'production' | 'all' | boolean;
    };
    experimental: {
        /**
         * Enable to use experimental decorators in Nuxt and Nitro.
         *
         * @default false
         *
         * @see https://github.com/tc39/proposal-decorators
         */
        decorators: boolean;
        /**
         * Set to true to generate an async entry point for the Vue bundle (for module federation support).
         *
         * @default false
         */
        asyncEntry: boolean;
        /**
         * Externalize `vue`, `@vue/*` and `vue-router` when building.
         *
         * @default true
         *
         * @see [Nuxt Issue #13632](https://github.com/nuxt/nuxt/issues/13632)
         */
        externalVue: boolean;
        /**
         * Tree shakes contents of client-only components from server bundle.
         *
         * @default true
         *
         * @see [Nuxt PR #5750](https://github.com/nuxt/framework/pull/5750)
         *
         * @deprecated This option will no longer be configurable in Nuxt v4
         */
        treeshakeClientOnly: boolean;
        /**
         * Emit `app:chunkError` hook when there is an error loading vite/webpack chunks.
         *
         * By default, Nuxt will also perform a reload of the new route when a chunk fails to load when navigating to a new route (`automatic`).
         * Setting `automatic-immediate` will lead Nuxt to perform a reload of the current route right when a chunk fails to load (instead of waiting for navigation).
         * You can disable automatic handling by setting this to `false`, or handle chunk errors manually by setting it to `manual`.
         *
         * @default "automatic"
         *
         * @see [Nuxt PR #19038](https://github.com/nuxt/nuxt/pull/19038)
         */
        emitRouteChunkError: false | 'manual' | 'automatic' | 'automatic-immediate';
        /**
         * By default the route object returned by the auto-imported `useRoute()` composable is kept in sync with the current page in view in `<NuxtPage>`. This is not true for `vue-router`'s exported `useRoute` or for the default `$route` object available in your Vue templates.
         *
         * By enabling this option a mixin will be injected to keep the `$route` template object in sync with Nuxt's managed `useRoute()`.
         *
         * @default true
         */
        templateRouteInjection: boolean;
        /**
         * Whether to restore Nuxt app state from `sessionStorage` when reloading the page after a chunk error or manual `reloadNuxtApp()` call.
         *
         * To avoid hydration errors, it will be applied only after the Vue app has been mounted, meaning there may be a flicker on initial load.
         * Consider carefully before enabling this as it can cause unexpected behavior, and consider providing explicit keys to `useState` as auto-generated keys may not match across builds.
         *
         * @default false
         */
        restoreState: boolean;
        /**
         * Render JSON payloads with support for revivifying complex types.
         *
         * @default true
         */
        renderJsonPayloads: boolean;
        /**
         * Disable vue server renderer endpoint within nitro.
         *
         * @default false
         */
        noVueServer: boolean;
        /**
         * When this option is enabled (by default) payload of pages that are prerendered are extracted
         *
         * @default true
         */
        payloadExtraction: boolean | undefined;
        /**
         * Whether to enable the experimental `<NuxtClientFallback>` component for rendering content on the client if there's an error in SSR.
         *
         * @default false
         */
        clientFallback: boolean;
        /**
         * Enable cross-origin prefetch using the Speculation Rules API.
         *
         * @default false
         */
        crossOriginPrefetch: boolean;
        /**
         * Enable View Transition API integration with client-side router.
         *
         * @default false
         *
         * @see [View Transitions API](https://developer.chrome.com/docs/web-platform/view-transitions)
         */
        viewTransition: boolean | 'always';
        /**
         * Write early hints when using node server.
         *
         * @default false
         *
         * @note nginx does not support 103 Early hints in the current version.
         */
        writeEarlyHints: boolean;
        /**
         * Experimental component islands support with `<NuxtIsland>` and `.island.vue` files.
         *
         * By default it is set to 'auto', which means it will be enabled only when there are islands, server components or server pages in your app.
         *
         * @default "auto"
         */
        componentIslands: true | 'auto' | 'local' | 'local+remote' | Partial<{
            remoteIsland: boolean;
            selectiveClient: boolean | 'deep';
        }> | false;
        /**
         * Config schema support
         *
         * @default true
         *
         * @see [Nuxt Issue #15592](https://github.com/nuxt/nuxt/issues/15592)
         *
         * @deprecated This option will no longer be configurable in Nuxt v4
         */
        configSchema: boolean;
        /**
         * Whether or not to add a compatibility layer for modules, plugins or user code relying on the old `@vueuse/head` API.
         *
         * This is disabled to reduce the client-side bundle by ~0.5kb.
         *
         * @default false
         *
         * @deprecated This feature will be removed in Nuxt v4.
         */
        polyfillVueUseHead: boolean;
        /**
         * Allow disabling Nuxt SSR responses by setting the `x-nuxt-no-ssr` header.
         *
         * @default false
         *
         * @deprecated This feature will be removed in Nuxt v4.
         */
        respectNoSSRHeader: boolean;
        /**
         * Resolve `~`, `~~`, `@` and `@@` aliases located within layers with respect to their layer source and root directories.
         *
         * @default true
         */
        localLayerAliases: boolean;
        /**
         * Enable the new experimental typed router using [unplugin-vue-router](https://github.com/posva/unplugin-vue-router).
         *
         * @default false
         */
        typedPages: boolean;
        /**
         * Use app manifests to respect route rules on client-side.
         *
         * @default true
         */
        appManifest: boolean;
        /**
         * Set the time interval (in ms) to check for new builds. Disabled when `experimental.appManifest` is `false`.
         *
         * Set to `false` to disable.
         *
         * @default 3600000
         */
        checkOutdatedBuildInterval: number | false;
        /**
         * Set an alternative watcher that will be used as the watching service for Nuxt.
         *
         * Nuxt uses 'chokidar-granular' if your source directory is the same as your root directory . This will ignore top-level directories (like `node_modules` and `.git`) that are excluded from watching.
         * You can set this instead to `parcel` to use `@parcel/watcher`, which may improve performance in large projects or on Windows platforms.
         * You can also set this to `chokidar` to watch all files in your source directory.
         *
         * @default "chokidar"
         *
         * @see [chokidar](https://github.com/paulmillr/chokidar)
         *
         * @see [@parcel/watcher](https://github.com/parcel-bundler/watcher)
         */
        watcher: 'chokidar' | 'parcel' | 'chokidar-granular';
        /**
         * Enable native async context to be accessible for nested composables
         *
         * @default false
         *
         * @see [Nuxt PR #20918](https://github.com/nuxt/nuxt/pull/20918)
         */
        asyncContext: boolean;
        /**
         * Use new experimental head optimisations:
         *
         * - Add the capo.js head plugin in order to render tags in of the head in a more performant way. - Uses the hash hydration plugin to reduce initial hydration
         *
         * @default true
         *
         * @see [Nuxt Discussion #22632](https://github.com/nuxt/nuxt/discussions/22632)
         */
        headNext: boolean;
        /**
         * Allow defining `routeRules` directly within your `~/pages` directory using `defineRouteRules`.
         *
         * Rules are converted (based on the path) and applied for server requests. For example, a rule defined in `~/pages/foo/bar.vue` will be applied to `/foo/bar` requests. A rule in `~/pages/foo/[id].vue` will be applied to `/foo/**` requests.
         * For more control, such as if you are using a custom `path` or `alias` set in the page's `definePageMeta`, you should set `routeRules` directly within your `nuxt.config`.
         *
         * @default false
         */
        inlineRouteRules: boolean;
        /**
         * Allow exposing some route metadata defined in `definePageMeta` at build-time to modules (alias, name, path, redirect, props, middleware).
         *
         * This only works with static or strings/arrays rather than variables or conditional assignment.
         *
         * @default true
         *
         * @see [Nuxt Issues #24770](https://github.com/nuxt/nuxt/issues/24770)
         */
        scanPageMeta: boolean | 'after-resolve';
        /**
         * Configure additional keys to extract from the page metadata when using `scanPageMeta`.
         *
         * This allows modules to access additional metadata from the page metadata. It's recommended to augment the NuxtPage types with your keys.
         *
         */
        extraPageMetaExtractionKeys: string[];
        /**
         * Automatically share payload _data_ between pages that are prerendered. This can result in a significant performance improvement when prerendering sites that use `useAsyncData` or `useFetch` and fetch the same data in different pages.
         *
         * It is particularly important when enabling this feature to make sure that any unique key of your data is always resolvable to the same data. For example, if you are using `useAsyncData` to fetch data related to a particular page, you should provide a key that uniquely matches that data. (`useFetch` should do this automatically for you.)
         *
         * @default false
         *
         * @example
         * ```ts
         * // This would be unsafe in a dynamic page (e.g. `[slug].vue`) because the route slug makes a difference
         * // to the data fetched, but Nuxt can't know that because it's not reflected in the key.
         * const route = useRoute()
         * const { data } = await useAsyncData(async () => {
         *   return await $fetch(`/api/my-page/${route.params.slug}`)
         * })
         * // Instead, you should use a key that uniquely identifies the data fetched.
         * const { data } = await useAsyncData(route.params.slug, async () => {
         *   return await $fetch(`/api/my-page/${route.params.slug}`)
         * })
         * ```
         */
        sharedPrerenderData: boolean;
        /**
         * Enables CookieStore support to listen for cookie updates (if supported by the browser) and refresh `useCookie` ref values.
         *
         * @default true
         *
         * @see [CookieStore](https://developer.mozilla.org/en-US/docs/Web/API/CookieStore)
         */
        cookieStore: boolean;
        /**
         * This allows specifying the default options for core Nuxt components and composables.
         *
         * These options will likely be moved elsewhere in the future, such as into `app.config` or into the `app/` directory.
         *
         */
        defaults: {
            nuxtLink: NuxtLinkOptions;
            /**
             * Options that apply to `useAsyncData` (and also therefore `useFetch`)
             */
            useAsyncData: {
                /** @default "null" */
                value: 'undefined' | 'null';
                /** @default "null" */
                errorValue: 'undefined' | 'null';
                /** @default true */
                deep: boolean;
            };
            useFetch: Pick<FetchOptions, 'timeout' | 'retry' | 'retryDelay' | 'retryStatusCodes'>;
        };
        /**
         * Automatically polyfill Node.js imports in the client build using `unenv`.
         *
         * @default false
         *
         * @see [unenv](https://github.com/unjs/unenv)
         *
         * **Note:** To make globals like `Buffer` work in the browser, you need to manually inject them.
         *
         * ```ts
         * import { Buffer } from 'node:buffer'
         *
         * globalThis.Buffer = globalThis.Buffer || Buffer
         * ```
         */
        clientNodeCompat: boolean;
        /**
         * Whether to use `lodash.template` to compile Nuxt templates.
         *
         * This flag will be removed with the release of v4 and exists only for advance testing within Nuxt v3.12+ or in [the nightly release channel](/docs/guide/going-further/nightly-release-channel).
         *
         * @default true
         */
        compileTemplate: boolean;
        /**
         * Whether to provide a legacy `templateUtils` object (with `serialize`, `importName` and `importSources`) when compiling Nuxt templates.
         *
         * This flag will be removed with the release of v4 and exists only for advance testing within Nuxt v3.12+ or in [the nightly release channel](/docs/guide/going-further/nightly-release-channel).
         *
         * @default true
         */
        templateUtils: boolean;
        /**
         * Whether to provide relative paths in the `builder:watch` hook.
         *
         * This flag will be removed with the release of v4 and exists only for advance testing within Nuxt v3.12+ or in [the nightly release channel](/docs/guide/going-further/nightly-release-channel).
         *
         * @default true
         */
        relativeWatchPaths: boolean;
        /**
         * Whether `clear` and `clearNuxtData` should reset async data to its _default_ value or update it to `null`/`undefined`.
         *
         * @default true
         */
        resetAsyncDataToUndefined: boolean;
        /**
         * Wait for a single animation frame before navigation, which gives an opportunity for the browser to repaint, acknowledging user interaction.
         *
         * It can reduce INP when navigating on prerendered routes.
         *
         * @default true
         */
        navigationRepaint: boolean;
        /**
         * Cache Nuxt/Nitro build artifacts based on a hash of the configuration and source files.
         *
         * This only works for source files within `srcDir` and `serverDir` for the Vue/Nitro parts of your app.
         *
         * @default false
         */
        buildCache: boolean;
        /**
         * Ensure that auto-generated Vue component names match the full component name you would use to auto-import the component.
         *
         * @default false
         */
        normalizeComponentNames: boolean;
        /**
         * Keep showing the spa-loading-template until suspense:resolve
         *
         * @default "within"
         *
         * @see [Nuxt Issues #24770](https://github.com/nuxt/nuxt/issues/21721)
         */
        spaLoadingTemplateLocation: 'body' | 'within';
        /**
         * Enable timings for Nuxt application hooks in the performance panel of Chromium-based browsers.
         *
         * This feature adds performance markers for Nuxt hooks, allowing you to track their execution time in the browser's Performance tab. This is particularly useful for debugging performance issues.
         *
         * @default false
         *
         * @example
         * ```ts
         * // nuxt.config.ts
         * export default defineNuxtConfig({
         *   experimental: {
         *     // Enable performance markers for Nuxt hooks in browser devtools
         *     browserDevtoolsTiming: true
         *   }
         * })
         * ```
         *
         * @see [PR #29922](https://github.com/nuxt/nuxt/pull/29922)
         *
         * @see [Chrome DevTools Performance API](https://developer.chrome.com/docs/devtools/performance/extension#tracks)
         */
        browserDevtoolsTiming: boolean;
        /**
         * Record mutations to `nuxt.options` in module context, helping to debug configuration changes made by modules during the Nuxt initialization phase.
         *
         * When enabled, Nuxt will track which modules modify configuration options, making it easier to trace unexpected configuration changes.
         *
         * @default false
         *
         * @example
         * ```ts
         * // nuxt.config.ts
         * export default defineNuxtConfig({
         *   experimental: {
         *     // Enable tracking of config mutations by modules
         *     debugModuleMutation: true
         *   }
         * })
         * ```
         *
         * @see [PR #30555](https://github.com/nuxt/nuxt/pull/30555)
         */
        debugModuleMutation: boolean;
        /**
         * Enable automatic configuration of hydration strategies for `<Lazy>` components.
         *
         * This feature intelligently determines when to hydrate lazy components based on visibility, idle time, or other triggers, improving performance by deferring hydration of components until they're needed.
         *
         * @default true
         *
         * @example
         * ```ts
         * // nuxt.config.ts
         * export default defineNuxtConfig({
         *   experimental: {
         *     lazyHydration: true // Enable smart hydration strategies for Lazy components
         *   }
         * })
         *
         * // In your Vue components
         * <template>
         *   <Lazy>
         *     <ExpensiveComponent />
         *   </Lazy>
         * </template>
         * ```
         *
         * @see [PR #26468](https://github.com/nuxt/nuxt/pull/26468)
         */
        lazyHydration: boolean;
        /**
         * Disable resolving imports into Nuxt templates from the path of the module that added the template.
         *
         * By default, Nuxt attempts to resolve imports in templates relative to the module that added them. Setting this to `false` disables this behavior, which may be useful if you're experiencing resolution conflicts in certain environments.
         *
         * @default true
         *
         * @example
         * ```ts
         * // nuxt.config.ts
         * export default defineNuxtConfig({
         *   experimental: {
         *     // Disable template import resolution from module path
         *     templateImportResolution: false
         *   }
         * })
         * ```
         *
         * @see [PR #31175](https://github.com/nuxt/nuxt/pull/31175)
         */
        templateImportResolution: boolean;
        /**
         * Whether to clean up Nuxt static and asyncData caches on route navigation.
         *
         * Nuxt will automatically purge cached data from `useAsyncData` and `nuxtApp.static.data`. This helps prevent memory leaks and ensures fresh data is loaded when needed, but it is possible to disable it.
         *
         * @default true
         *
         * @example
         * ```ts
         * // nuxt.config.ts
         * export default defineNuxtConfig({
         *   experimental: {
         *     // Disable automatic cache cleanup (default is true)
         *     purgeCachedData: false
         *   }
         * })
         * ```
         *
         * @see [PR #31379](https://github.com/nuxt/nuxt/pull/31379)
         */
        purgeCachedData: boolean;
        /**
         * Whether to call and use the result from `getCachedData` on manual refresh for `useAsyncData` and `useFetch`.
         *
         * @default false
         */
        granularCachedData: boolean;
        /**
         * Whether to run `useFetch` when the key changes, even if it is set to `immediate: false` and it has not been triggered yet.
         *
         * `useFetch` and `useAsyncData` will always run when the key changes if `immediate: true` or if it has been already triggered.
         *
         * @default false
         */
        alwaysRunFetchOnKeyChange: boolean;
        /**
         * Whether to parse `error.data` when rendering a server error page.
         *
         * @default false
         */
        parseErrorData: boolean;
        /**
         * Whether Nuxt should stop if a Nuxt module is incompatible.
         *
         * @default false
         */
        enforceModuleCompatibility: boolean;
        /**
         * For `useAsyncData` and `useFetch`, whether `pending` should be `true` when data has not yet started to be fetched.
         *
         * @default true
         */
        pendingWhenIdle: boolean;
    };
    generate: {
        /**
         * The routes to generate.
         *
         * If you are using the crawler, this will be only the starting point for route generation. This is often necessary when using dynamic routes.
         * It is preferred to use `nitro.prerender.routes`.
         *
         * @example
         * ```js
         * routes: ['/users/1', '/users/2', '/users/3']
         * ```
         */
        routes: string | string[];
        /**
         * This option is no longer used. Instead, use `nitro.prerender.ignore`.
         *
         * @deprecated
         */
        exclude: Array<any>;
    };
    /**
     * @default 3
     *
     * @private
     */
    _majorVersion: number;
    /**
     * @default false
     *
     * @private
     */
    _legacyGenerate: boolean;
    /**
     * @default false
     *
     * @private
     */
    _start: boolean;
    /**
     * @default false
     *
     * @private
     */
    _build: boolean;
    /**
     * @default false
     *
     * @private
     */
    _generate: boolean;
    /**
     * @default false
     *
     * @private
     */
    _prepare: boolean;
    /**
     * @default false
     *
     * @private
     */
    _cli: boolean;
    /**
     *
     * @private
     */
    _requiredModules: Record<string, boolean>;
    /**
     *
     * @private
     */
    _loadOptions: {
        dotenv?: boolean | c12.DotenvOptions;
    };
    /**
     *
     * @private
     */
    _nuxtConfigFile: string;
    /**
     *
     * @private
     */
    _nuxtConfigFiles: Array<string>;
    /**
     * @default ""
     *
     * @private
     */
    appDir: string;
    /**
     *
     * @private
     */
    _installedModules: Array<{
        meta: ModuleMeta;
        module: NuxtModule;
        timings?: Record<string, number | undefined>;
        entryPath?: string;
    }>;
    /**
     *
     * @private
     */
    _modules: Array<any>;
    /**
     * Configuration for Nitro.
     *
     * @see [Nitro configuration docs](https://nitro.build/config/)
     */
    nitro: NitroConfig;
    /**
     * Global route options applied to matching server routes.
     *
     * @experimental This is an experimental feature and API may change in the future.
     *
     * @see [Nitro route rules documentation](https://nitro.build/config/#routerules)
     */
    routeRules: NitroConfig['routeRules'];
    /**
     * Nitro server handlers.
     *
     * Each handler accepts the following options:
     * - handler: The path to the file defining the handler. - route: The route under which the handler is available. This follows the conventions of [rou3](https://github.com/unjs/rou3). - method: The HTTP method of requests that should be handled. - middleware: Specifies whether it is a middleware handler. - lazy: Specifies whether to use lazy loading to import the handler.
     *
     * @see [`server/` directory documentation](https://nuxt.com/docs/guide/directory-structure/server)
     *
     * @note Files from `server/api`, `server/middleware` and `server/routes` will be automatically registered by Nuxt.
     *
     * @example
     * ```js
     * serverHandlers: [
     *   { route: '/path/foo/**:name', handler: '~/server/foohandler.ts' }
     * ]
     * ```
     */
    serverHandlers: NitroEventHandler[];
    /**
     * Nitro development-only server handlers.
     *
     * @see [Nitro server routes documentation](https://nitro.build/guide/routing)
     */
    devServerHandlers: NitroDevEventHandler[];
    postcss: {
        /**
         * A strategy for ordering PostCSS plugins.
         */
        order: 'cssnanoLast' | 'autoprefixerLast' | 'autoprefixerAndCssnanoLast' | string[] | ((names: string[]) => string[]);
        /**
         * Options for configuring PostCSS plugins.
         *
         * @see [PostCSS docs](https://postcss.org/)
         */
        plugins: Record<string, unknown> & {
            autoprefixer?: Options$1;
            cssnano?: Options$2;
        };
    };
    router: {
        /**
         * Additional router options passed to `vue-router`. On top of the options for `vue-router`, Nuxt offers additional options to customize the router (see below).
         *
         * @note Only JSON serializable options should be passed by Nuxt config.
         * For more control, you can use `app/router.options.ts` file.
         *
         * @see [Vue Router documentation](https://router.vuejs.org/api/interfaces/routeroptions.html).
         */
        options: RouterConfigSerializable;
    };
    /**
     * Configuration for Nuxt's TypeScript integration.
     */
    typescript: {
        /**
         * TypeScript comes with certain checks to give you more safety and analysis of your program. Once you’ve converted your codebase to TypeScript, you can start enabling these checks for greater safety. [Read More](https://www.typescriptlang.org/docs/handbook/migrating-from-javascript.html#getting-stricter-checks)
         *
         * @default true
         */
        strict: boolean;
        /**
         * Which builder types to include for your project.
         *
         * By default Nuxt infers this based on your `builder` option (defaulting to 'vite') but you can either turn off builder environment types (with `false`) to handle this fully yourself, or opt for a 'shared' option.
         * The 'shared' option is advised for module authors, who will want to support multiple possible builders.
         *
         */
        builder: 'vite' | 'webpack' | 'rspack' | 'shared' | false | undefined | null;
        /**
         * Modules to generate deep aliases for within `compilerOptions.paths`. This does not yet support subpaths. It may be necessary when using Nuxt within a pnpm monorepo with `shamefully-hoist=false`.
         *
         * @default ["nitropack/types","nitropack/runtime","nitropack","defu","h3","consola","ofetch","@unhead/vue","@nuxt/devtools","vue","@vue/runtime-core","@vue/compiler-sfc","vue-router","vue-router/auto-routes","unplugin-vue-router/client","@nuxt/schema","nuxt"]
         */
        hoist: Array<string>;
        /**
         * Include parent workspace in the Nuxt project. Mostly useful for themes and module authors.
         *
         * @default false
         */
        includeWorkspace: boolean;
        /**
         * Enable build-time type checking.
         *
         * If set to true, this will type check in development. You can restrict this to build-time type checking by setting it to `build`. Requires to install `typescript` and `vue-tsc` as dev dependencies.
         *
         * @default false
         *
         * @see [Nuxt TypeScript docs](https://nuxt.com/docs/guide/concepts/typescript)
         */
        typeCheck: boolean | 'build';
        /**
         * You can extend generated `.nuxt/tsconfig.json` using this option.
         *
         */
        tsConfig: 0 extends 1 & RawVueCompilerOptions ? TSConfig : TSConfig & {
            vueCompilerOptions?: RawVueCompilerOptions;
        };
        /**
         * Generate a `*.vue` shim.
         *
         * We recommend instead letting the [official Vue extension](https://marketplace.visualstudio.com/items?itemName=Vue.volar) generate accurate types for your components.
         * Note that you may wish to set this to `true` if you are using other libraries, such as ESLint, that are unable to understand the type of `.vue` files.
         *
         * @default false
         */
        shim: boolean;
    };
    esbuild: {
        /**
         * Configure shared esbuild options used within Nuxt and passed to other builders, such as Vite or Webpack.
         */
        options: esbuild.TransformOptions;
    };
    /**
     * Configuration that will be passed directly to Vite.
     *
     * @see [Vite configuration docs](https://vite.dev/config) for more information.
     * Please note that not all vite options are supported in Nuxt.
     */
    vite: ViteConfig & {
        $client?: ViteConfig;
        $server?: ViteConfig;
    };
    webpack: {
        /**
         * Nuxt uses `webpack-bundle-analyzer` to visualize your bundles and how to optimize them.
         *
         * Set to `true` to enable bundle analysis, or pass an object with options: [for webpack](https://github.com/webpack-contrib/webpack-bundle-analyzer#options-for-plugin) or [for vite](https://github.com/btd/rollup-plugin-visualizer#options).
         *
         * @example
         * ```js
         * analyze: {
         *   analyzerMode: 'static'
         * }
         * ```
         */
        analyze: boolean | {
            enabled?: boolean;
        } & BundleAnalyzerPlugin.Options;
        /**
         * Enable the profiler in webpackbar.
         *
         * It is normally enabled by CLI argument `--profile`.
         *
         * @default false
         *
         * @see [webpackbar](https://github.com/unjs/webpackbar#profile).
         */
        profile: boolean;
        /**
         * Enables Common CSS Extraction.
         *
         * Using [mini-css-extract-plugin](https://github.com/webpack-contrib/mini-css-extract-plugin) under the hood, your CSS will be extracted into separate files, usually one per component. This allows caching your CSS and JavaScript separately.
         *
         * @default true
         *
         * @example
         * ```js
         * export default {
         *   webpack: {
         *     extractCSS: true,
         *     // or
         *     extractCSS: {
         *       ignoreOrder: true
         *     }
         *   }
         * }
         * ```
         *
         * If you want to extract all your CSS to a single file, there is a workaround for this.
         * However, note that it is not recommended to extract everything into a single file.
         * Extracting into multiple CSS files is better for caching and preload isolation. It
         * can also improve page performance by downloading and resolving only those resources
         * that are needed.
         *
         * @example
         * ```js
         * export default {
         *   webpack: {
         *     extractCSS: true,
         *     optimization: {
         *       splitChunks: {
         *         cacheGroups: {
         *           styles: {
         *             name: 'styles',
         *             test: /\.(css|vue)$/,
         *             chunks: 'all',
         *             enforce: true
         *           }
         *         }
         *       }
         *     }
         *   }
         * }
         * ```
         */
        extractCSS: boolean | PluginOptions;
        /**
         * Enables CSS source map support (defaults to `true` in development).
         *
         * @default false
         */
        cssSourceMap: boolean;
        /**
         * The polyfill library to load to provide URL and URLSearchParams.
         *
         * Defaults to `'url'` ([see package](https://www.npmjs.com/package/url)).
         *
         * @default "url"
         */
        serverURLPolyfill: string;
        /**
         * Customize bundle filenames.
         *
         * To understand a bit more about the use of manifests, take a look at [webpack documentation](https://webpack.js.org/guides/code-splitting/).
         *
         * @note Be careful when using non-hashed based filenames in production
         * as most browsers will cache the asset and not detect the changes on first load.
         *
         * This example changes fancy chunk names to numerical ids:
         *
         * @example
         * ```js
         * filenames: {
         *   chunk: ({ isDev }) => (isDev ? '[name].js' : '[id].[contenthash].js')
         * }
         * ```
         */
        filenames: Record<string, string | ((ctx: {
            nuxt: Nuxt;
            options: NuxtOptions;
            name: string;
            isDev: boolean;
            isServer: boolean;
            isClient: boolean;
            alias: {
                [index: string]: string | false | string[];
            };
            transpile: RegExp[];
        }) => string)>;
        /**
         * Customize the options of Nuxt's integrated webpack loaders.
         *
         */
        loaders: {
            /**
             * @see [esbuild loader](https://github.com/esbuild-kit/esbuild-loader)
             */
            esbuild: Omit<LoaderOptions, 'loader'>;
            /**
             * @see [`file-loader` Options](https://github.com/webpack-contrib/file-loader#options)
             *
             * @default
             * ```ts
             * { esModule: false }
             * ```
             */
            file: {
                /** @default false */
                esModule: boolean;
                /** @default 1000 */
                limit: number;
            };
            /**
             * @see [`file-loader` Options](https://github.com/webpack-contrib/file-loader#options)
             *
             * @default
             * ```ts
             * { esModule: false }
             * ```
             */
            fontUrl: {
                /** @default false */
                esModule: boolean;
                /** @default 1000 */
                limit: number;
            };
            /**
             * @see [`file-loader` Options](https://github.com/webpack-contrib/file-loader#options)
             *
             * @default
             * ```ts
             * { esModule: false }
             * ```
             */
            imgUrl: {
                /** @default false */
                esModule: boolean;
                /** @default 1000 */
                limit: number;
            };
            /**
             * @see [`pug` options](https://pugjs.org/api/reference.html#options)
             */
            pugPlain: Options$3;
            /**
             * See [vue-loader](https://github.com/vuejs/vue-loader) for available options.
             */
            vue: Partial<VueLoaderOptions>;
            /**
             * See [css-loader](https://github.com/webpack-contrib/css-loader) for available options.
             */
            css: {
                /** @default 0 */
                importLoaders: number;
                url: boolean | {
                    filter: (url: string, resourcePath: string) => boolean;
                };
                /** @default false */
                esModule: boolean;
            };
            /**
             * See [css-loader](https://github.com/webpack-contrib/css-loader) for available options.
             */
            cssModules: {
                /** @default 0 */
                importLoaders: number;
                url: boolean | {
                    filter: (url: string, resourcePath: string) => boolean;
                };
                /** @default false */
                esModule: boolean;
                modules: {
                    /** @default "[local]_[hash:base64:5]" */
                    localIdentName: string;
                };
            };
            /**
             * @see [`less-loader` Options](https://github.com/webpack-contrib/less-loader#options)
             */
            less: any;
            /**
             * @see [`sass-loader` Options](https://github.com/webpack-contrib/sass-loader#options)
             *
             * @default
             * ```ts
             * {
             *   sassOptions: {
             *     indentedSyntax: true
             *   }
             * }
             * ```
             */
            sass: {
                sassOptions: {
                    /** @default true */
                    indentedSyntax: boolean;
                };
            };
            /**
             * @see [`sass-loader` Options](https://github.com/webpack-contrib/sass-loader#options)
             */
            scss: any;
            /**
             * @see [`stylus-loader` Options](https://github.com/webpack-contrib/stylus-loader#options)
             */
            stylus: any;
            vueStyle: any;
        };
        /**
         * Add webpack plugins.
         *
         * @example
         * ```js
         * import webpack from 'webpack'
         * import { version } from './package.json'
         * // ...
         * plugins: [
         *   new webpack.DefinePlugin({
         *     'process.VERSION': version
         *   })
         * ]
         * ```
         */
        plugins: Array<any>;
        /**
         * Hard-replaces `typeof process`, `typeof window` and `typeof document` to tree-shake bundle.
         *
         * @default false
         */
        aggressiveCodeRemoval: boolean;
        /**
         * OptimizeCSSAssets plugin options.
         *
         * Defaults to true when `extractCSS` is enabled.
         *
         * @default false
         *
         * @see [css-minimizer-webpack-plugin documentation](https://github.com/webpack-contrib/css-minimizer-webpack-plugin).
         */
        optimizeCSS: false | BasePluginOptions & DefinedDefaultMinimizerAndOptions<{}>;
        /**
         * Configure [webpack optimization](https://webpack.js.org/configuration/optimization/).
         *
         */
        optimization: false | Configuration['optimization'];
        /**
         * Customize PostCSS Loader. same options as [`postcss-loader` options](https://github.com/webpack-contrib/postcss-loader#options)
         *
         */
        postcss: {
            execute?: boolean;
            postcssOptions: ProcessOptions & {
                plugins: Record<string, unknown> & {
                    autoprefixer?: Options$1;
                    cssnano?: Options$2;
                };
            };
            sourceMap?: boolean;
            implementation?: any;
        };
        /**
         * See [webpack-dev-middleware](https://github.com/webpack/webpack-dev-middleware) for available options.
         *
         */
        devMiddleware: Options$4<IncomingMessage, ServerResponse>;
        /**
         * See [webpack-hot-middleware](https://github.com/webpack-contrib/webpack-hot-middleware) for available options.
         *
         */
        hotMiddleware: MiddlewareOptions & {
            client?: ClientOptions;
        };
        /**
         * Set to `false` to disable the overlay provided by [FriendlyErrorsWebpackPlugin](https://github.com/nuxt/friendly-errors-webpack-plugin).
         *
         * @default true
         */
        friendlyErrors: boolean;
        /**
         * Filters to hide build warnings.
         *
         */
        warningIgnoreFilters: Array<(warn: WebpackError | Error) => boolean>;
        /**
         * Configure [webpack experiments](https://webpack.js.org/configuration/experiments/)
         *
         */
        experiments: false | Configuration['experiments'];
    };
}

type DeepPartial<T> = T extends Function ? T : T extends Record<string, any> ? {
    [P in keyof T]?: DeepPartial<T[P]>;
} : T;
type UpperSnakeCase<S extends string> = Uppercase<SnakeCase<S>>;
declare const message: unique symbol;
type RuntimeValue<T, B extends string> = T & {
    [message]?: B;
};
type Overrideable<T extends Record<string, any>, Path extends string = ''> = {
    [K in keyof T]?: K extends string ? unknown extends T[K] ? unknown : T[K] extends Record<string, unknown> ? RuntimeValue<Overrideable<T[K], `${Path}_${UpperSnakeCase<K>}`>, `You can override this value at runtime with NUXT${Path}_${UpperSnakeCase<K>}`> : RuntimeValue<T[K], `You can override this value at runtime with NUXT${Path}_${UpperSnakeCase<K>}`> : K extends number ? T[K] : never;
};
type RuntimeConfigNamespace = Record<string, unknown>;
interface PublicRuntimeConfig extends RuntimeConfigNamespace {
}
interface RuntimeConfig extends RuntimeConfigNamespace {
    app: NitroRuntimeConfigApp;
    /** Only available on the server. */
    nitro?: NitroRuntimeConfig['nitro'];
    public: PublicRuntimeConfig;
}
interface NuxtConfig extends DeepPartial<Omit<ConfigSchema, 'vue' | 'vite' | 'runtimeConfig' | 'webpack' | 'nitro'>> {
    vue?: Omit<DeepPartial<ConfigSchema['vue']>, 'config'> & {
        config?: Partial<Filter<AppConfig$1, string | boolean>>;
    };
    vite?: ConfigSchema['vite'];
    nitro?: NitroConfig;
    runtimeConfig?: Overrideable<RuntimeConfig>;
    webpack?: DeepPartial<ConfigSchema['webpack']> & {
        $client?: DeepPartial<ConfigSchema['webpack']>;
        $server?: DeepPartial<ConfigSchema['webpack']>;
    };
    /**
     * Experimental custom config schema
     * @see [Nuxt Issue #15592](https://github.com/nuxt/nuxt/issues/15592)
     */
    $schema?: SchemaDefinition;
}
type NuxtConfigLayer = ResolvedConfig<NuxtConfig & {
    srcDir: ConfigSchema['srcDir'];
    rootDir: ConfigSchema['rootDir'];
}> & {
    cwd: string;
    configFile: string;
};
interface NuxtBuilder {
    bundle: (nuxt: Nuxt) => Promise<void>;
}
interface NuxtOptions extends Omit<ConfigSchema, 'vue' | 'sourcemap' | 'debug' | 'builder' | 'postcss' | 'webpack'> {
    vue: Omit<ConfigSchema['vue'], 'config'> & {
        config?: Partial<Filter<AppConfig$1, string | boolean>>;
    };
    sourcemap: Required<Exclude<ConfigSchema['sourcemap'], boolean>>;
    debug: Required<Exclude<ConfigSchema['debug'], true>>;
    builder: '@nuxt/vite-builder' | '@nuxt/webpack-builder' | '@nuxt/rspack-builder' | NuxtBuilder;
    postcss: Omit<ConfigSchema['postcss'], 'order'> & {
        order: Exclude<ConfigSchema['postcss']['order'], string>;
    };
    webpack: ConfigSchema['webpack'] & {
        $client: ConfigSchema['webpack'];
        $server: ConfigSchema['webpack'];
    };
    _layers: NuxtConfigLayer[];
    $schema: SchemaDefinition;
}
interface ViteConfig extends Omit<UserConfig, 'publicDir'> {
    /** The path to the entrypoint for the Vite build. */
    entry?: string;
    /**
     * Options passed to @vitejs/plugin-vue.
     * @see [@vitejs/plugin-vue](https://github.com/vitejs/vite-plugin-vue/tree/main/packages/plugin-vue)
     */
    vue?: Options$5;
    /**
     * Options passed to @vitejs/plugin-vue-jsx.
     * @see [@vitejs/plugin-vue-jsx.](https://github.com/vitejs/vite-plugin-vue/tree/main/packages/plugin-vue-jsx)
     */
    vueJsx?: Options$6;
    /**
     * Bundler for dev time server-side rendering.
     * @default 'vite-node'
     */
    devBundler?: 'vite-node' | 'legacy';
    /**
     * Warmup vite entrypoint caches on dev startup.
     */
    warmupEntry?: boolean;
    /**
     * Use environment variables or top level `server` options to configure Nuxt server.
     */
    server?: Omit<ServerOptions, 'port' | 'host'>;
    /**
     * Directly configuring the `vite.publicDir` option is not supported. Instead, set `dir.public`.
     *
     * You can read more in <https://nuxt.com/docs/api/nuxt-config#public>.
     * @deprecated
     */
    publicDir?: never;
}
interface CustomAppConfig {
    [key: string]: unknown;
}
interface AppConfigInput extends CustomAppConfig {
    /** @deprecated reserved */
    private?: never;
    /** @deprecated reserved */
    nuxt?: never;
    /** @deprecated reserved */
    nitro?: never;
    /** @deprecated reserved */
    server?: never;
}
type Serializable<T> = T extends Function ? never : T extends Promise<infer U> ? Serializable<U> : T extends string & {} ? T : T extends Record<string, any> ? {
    [K in keyof T]: Serializable<T[K]>;
} : T;
type ValueOf<T> = T[keyof T];
type Filter<T extends Record<string, any>, V> = Pick<T, ValueOf<{
    [K in keyof T]: NonNullable<T[K]> extends V ? K : never;
}>>;
interface NuxtAppConfig {
    head: Serializable<AppHeadMetaObject>;
    layoutTransition: boolean | Serializable<TransitionProps>;
    pageTransition: boolean | Serializable<TransitionProps>;
    viewTransition?: boolean | 'always';
    keepalive: boolean | Serializable<KeepAliveProps>;
}
interface AppConfig {
    [key: string]: unknown;
}

declare const _default: {
    [x: string]: untyped.JSValue | untyped.InputObject;
};

export { _default as NuxtConfigSchema };
export type { AppConfig, AppConfigInput, AppHeadMetaObject, Component, ComponentMeta, ComponentsDir, ComponentsOptions, CustomAppConfig, GenerateAppOptions, HookResult, ImportPresetWithDeprecation, ImportsOptions, MetaObject, MetaObjectRaw, ModuleDefinition, ModuleMeta, ModuleOptions, ModuleSetupInstallResult, ModuleSetupReturn, Nuxt, NuxtAnalyzeMeta, NuxtApp, NuxtAppConfig, NuxtBuilder, NuxtCompatibility, NuxtCompatibilityIssue, NuxtCompatibilityIssues, NuxtConfig, NuxtConfigLayer, NuxtDebugContext, NuxtDebugModuleMutationRecord, NuxtHookName, NuxtHooks, NuxtLayout, NuxtMiddleware, NuxtModule, NuxtOptions, NuxtPage, NuxtPlugin, NuxtPluginTemplate, NuxtServerTemplate, NuxtTemplate, NuxtTypeTemplate, PublicRuntimeConfig, ResolvedModuleOptions, ResolvedNuxtTemplate, RouterConfig, RouterConfigSerializable, RouterOptions, RuntimeConfig, RuntimeValue, ScanDir, TSReference, UpperSnakeCase, ViteConfig, VueTSConfig, WatchEvent };
