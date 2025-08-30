import 'scule';
import defu$1, { defu } from 'defu';
import { resolve, join, relative, basename } from 'pathe';
import { isTest, isDevelopment, isDebug } from 'std-env';
import { consola } from 'consola';
import { existsSync } from 'node:fs';
import { readdir } from 'node:fs/promises';
import { randomUUID } from 'node:crypto';
import { findWorkspaceDir } from 'pkg-types';
import { escapeHtml } from '@vue/shared';

function defineUntypedSchema(options) {
  return options;
}

function defineResolvers(config) {
  return defineUntypedSchema(config);
}

const adhoc = defineResolvers({
  /**
   * Configure Nuxt component auto-registration.
   *
   * Any components in the directories configured here can be used throughout your
   * pages, layouts (and other components) without needing to explicitly import them.
   * @see [`components/` directory documentation](https://nuxt.com/docs/guide/directory-structure/components)
   * @type {boolean | typeof import('../src/types/components').ComponentsOptions | typeof import('../src/types/components').ComponentsOptions['dirs']}
   */
  components: {
    $resolve: (val) => {
      if (Array.isArray(val)) {
        return { dirs: val };
      }
      if (val === false) {
        return { dirs: [] };
      }
      return {
        dirs: [{ path: "~/components/global", global: true }, "~/components"],
        ...typeof val === "object" ? val : {}
      };
    }
  },
  /**
   * Configure how Nuxt auto-imports composables into your application.
   * @see [Nuxt documentation](https://nuxt.com/docs/guide/directory-structure/composables)
   * @type {typeof import('../src/types/imports').ImportsOptions}
   */
  imports: {
    global: false,
    /**
     * Whether to scan your `composables/` and `utils/` directories for composables to auto-import.
     * Auto-imports registered by Nuxt or other modules, such as imports from `vue` or `nuxt`, will still be enabled.
     */
    scan: true,
    /**
     * An array of custom directories that will be auto-imported.
     * Note that this option will not override the default directories (~/composables, ~/utils).
     * @example
     * ```js
     * imports: {
     *   // Auto-import pinia stores defined in `~/stores`
     *   dirs: ['stores']
     * }
     * ```
     */
    dirs: []
  },
  /**
   * Whether to use the vue-router integration in Nuxt 3. If you do not provide a value it will be
   * enabled if you have a `pages/` directory in your source folder.
   *
   * Additionally, you can provide a glob pattern or an array of patterns
   * to scan only certain files for pages.
   * @example
   * ```js
   * pages: {
   *   pattern: ['**\/*\/*.vue', '!**\/*.spec.*'],
   * }
   * ```
   * @type {boolean | { enabled?: boolean, pattern?: string | string[] }}
   */
  pages: void 0,
  /**
   * Manually disable nuxt telemetry.
   * @see [Nuxt Telemetry](https://github.com/nuxt/telemetry) for more information.
   * @type {boolean | Record<string, any>}
   */
  telemetry: void 0,
  /**
   * Enable Nuxt DevTools for development.
   *
   * Breaking changes for devtools might not reflect on the version of Nuxt.
   * @see  [Nuxt DevTools](https://devtools.nuxt.com/) for more information.
   * @type { { enabled: boolean, [key: string]: any } }
   */
  devtools: {}
});

const app = defineResolvers({
  /**
   * Vue.js config
   */
  vue: {
    /** @type {typeof import('@vue/compiler-sfc').AssetURLTagConfig} */
    transformAssetUrls: {
      video: ["src", "poster"],
      source: ["src"],
      img: ["src"],
      image: ["xlink:href", "href"],
      use: ["xlink:href", "href"]
    },
    /**
     * Options for the Vue compiler that will be passed at build time.
     * @see [Vue documentation](https://vuejs.org/api/application.html#app-config-compileroptions)
     * @type {typeof import('@vue/compiler-core').CompilerOptions}
     */
    compilerOptions: {},
    /**
     * Include Vue compiler in runtime bundle.
     */
    runtimeCompiler: {
      $resolve: async (val, get) => {
        if (typeof val === "boolean") {
          return val;
        }
        const legacyProperty = await get("experimental.runtimeVueCompiler");
        if (typeof legacyProperty === "boolean") {
          return legacyProperty;
        }
        return false;
      }
    },
    /**
     * Enable reactive destructure for `defineProps`
     * @type {boolean}
     */
    propsDestructure: true,
    /**
     * It is possible to pass configure the Vue app globally. Only serializable options
     * may be set in your `nuxt.config`. All other options should be set at runtime in a Nuxt plugin..
     * @see [Vue app config documentation](https://vuejs.org/api/application.html#app-config)
     */
    config: {}
  },
  /**
   * Nuxt App configuration.
   */
  app: {
    /**
     * The base path of your Nuxt application.
     *
     * For example:
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
     * @example
     * ```bash
     * NUXT_APP_BASE_URL=/prefix/ node .output/server/index.mjs
     * ```
     */
    baseURL: {
      $resolve: (val) => {
        if (typeof val === "string") {
          return val;
        }
        return process.env.NUXT_APP_BASE_URL || "/";
      }
    },
    /** The folder name for the built site assets, relative to `baseURL` (or `cdnURL` if set). This is set at build time and should not be customized at runtime. */
    buildAssetsDir: {
      $resolve: (val) => {
        if (typeof val === "string") {
          return val;
        }
        return process.env.NUXT_APP_BUILD_ASSETS_DIR || "/_nuxt/";
      }
    },
    /**
     * An absolute URL to serve the public folder from (production-only).
     *
     * For example:
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
     * @example
     * ```bash
     * NUXT_APP_CDN_URL=https://mycdn.org/ node .output/server/index.mjs
     * ```
     */
    cdnURL: {
      $resolve: async (val, get) => {
        if (await get("dev")) {
          return "";
        }
        return process.env.NUXT_APP_CDN_URL || (typeof val === "string" ? val : "");
      }
    },
    /**
     * Set default configuration for `<head>` on every page.
     * @example
     * ```js
     * app: {
     *   head: {
     *     meta: [
     *       // <meta name="viewport" content="width=device-width, initial-scale=1">
     *       { name: 'viewport', content: 'width=device-width, initial-scale=1' }
     *     ],
     *     script: [
     *       // <script src="https://myawesome-lib.js"><\/script>
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
     * @type {typeof import('../src/types/config').NuxtAppConfig['head']}
     */
    head: {
      $resolve: async (_val, get) => {
        const legacyMetaValues = await get("meta");
        const val = _val && typeof _val === "object" ? _val : {};
        const resolved = defu(val, legacyMetaValues, {
          meta: [],
          link: [],
          style: [],
          script: [],
          noscript: []
        });
        if (!resolved.meta.find((m) => m?.charset)?.charset) {
          resolved.meta.unshift({ charset: resolved.charset || "utf-8" });
        }
        if (!resolved.meta.find((m) => m?.name === "viewport")?.content) {
          resolved.meta.unshift({ name: "viewport", content: resolved.viewport || "width=device-width, initial-scale=1" });
        }
        resolved.meta = resolved.meta.filter(Boolean);
        resolved.link = resolved.link.filter(Boolean);
        resolved.style = resolved.style.filter(Boolean);
        resolved.script = resolved.script.filter(Boolean);
        resolved.noscript = resolved.noscript.filter(Boolean);
        return resolved;
      }
    },
    /**
     * Default values for layout transitions.
     *
     * This can be overridden with `definePageMeta` on an individual page.
     * Only JSON-serializable values are allowed.
     * @see [Vue Transition docs](https://vuejs.org/api/built-in-components.html#transition)
     * @type {typeof import('../src/types/config').NuxtAppConfig['layoutTransition']}
     */
    layoutTransition: false,
    /**
     * Default values for page transitions.
     *
     * This can be overridden with `definePageMeta` on an individual page.
     * Only JSON-serializable values are allowed.
     * @see [Vue Transition docs](https://vuejs.org/api/built-in-components.html#transition)
     * @type {typeof import('../src/types/config').NuxtAppConfig['pageTransition']}
     */
    pageTransition: false,
    /**
     * Default values for view transitions.
     *
     * This only has an effect when **experimental** support for View Transitions is
     * [enabled in your nuxt.config file](/docs/getting-started/transitions#view-transitions-api-experimental).
     *
     * This can be overridden with `definePageMeta` on an individual page.
     * @see [Nuxt View Transition API docs](https://nuxt.com/docs/getting-started/transitions#view-transitions-api-experimental)
     * @type {typeof import('../src/types/config').NuxtAppConfig['viewTransition']}
     */
    viewTransition: {
      $resolve: async (val, get) => {
        if (val === "always" || typeof val === "boolean") {
          return val;
        }
        return await get("experimental").then((e) => e.viewTransition) ?? false;
      }
    },
    /**
     * Default values for KeepAlive configuration between pages.
     *
     * This can be overridden with `definePageMeta` on an individual page.
     * Only JSON-serializable values are allowed.
     * @see [Vue KeepAlive](https://vuejs.org/api/built-in-components.html#keepalive)
     * @type {typeof import('../src/types/config').NuxtAppConfig['keepalive']}
     */
    keepalive: false,
    /**
     * Customize Nuxt root element id.
     * @type {string | false}
     * @deprecated Prefer `rootAttrs.id` instead
     */
    rootId: {
      $resolve: (val) => val === false ? false : val && typeof val === "string" ? val : "__nuxt"
    },
    /**
     * Customize Nuxt root element tag.
     */
    rootTag: {
      $resolve: (val) => val && typeof val === "string" ? val : "div"
    },
    /**
     * Customize Nuxt root element id.
     * @type {typeof import('../src/types/head').SerializableHtmlAttributes}
     */
    rootAttrs: {
      $resolve: async (val, get) => {
        const rootId = await get("app.rootId");
        return {
          id: rootId === false ? void 0 : rootId || "__nuxt",
          ...typeof val === "object" ? val : {}
        };
      }
    },
    /**
     * Customize Nuxt Teleport element tag.
     */
    teleportTag: {
      $resolve: (val) => val && typeof val === "string" ? val : "div"
    },
    /**
     * Customize Nuxt Teleport element id.
     * @type {string | false}
     * @deprecated Prefer `teleportAttrs.id` instead
     */
    teleportId: {
      $resolve: (val) => val === false ? false : val && typeof val === "string" ? val : "teleports"
    },
    /**
     * Customize Nuxt Teleport element attributes.
     * @type {typeof import('../src/types/head').SerializableHtmlAttributes}
     */
    teleportAttrs: {
      $resolve: async (val, get) => {
        const teleportId = await get("app.teleportId");
        return {
          id: teleportId === false ? void 0 : teleportId || "teleports",
          ...typeof val === "object" ? val : {}
        };
      }
    },
    /**
     * Customize Nuxt SpaLoader element tag.
     */
    spaLoaderTag: {
      $resolve: (val) => val && typeof val === "string" ? val : "div"
    },
    /**
     * Customize Nuxt Nuxt SpaLoader element attributes.
     * @type {typeof import('../src/types/head').SerializableHtmlAttributes}
     */
    spaLoaderAttrs: {
      id: "__nuxt-loader"
    }
  },
  /**
   * Boolean or a path to an HTML file with the contents of which will be inserted into any HTML page
   * rendered with `ssr: false`.
   *
   * - If it is unset, it will use `~/app/spa-loading-template.html` file in one of your layers, if it exists.
   * - If it is false, no SPA loading indicator will be loaded.
   * - If true, Nuxt will look for `~/app/spa-loading-template.html` file in one of your layers, or a
   *   default Nuxt image will be used.
   *
   * Some good sources for spinners are [SpinKit](https://github.com/tobiasahlin/SpinKit) or [SVG Spinners](https://icones.js.org/collection/svg-spinners).
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
   * @type {string | boolean | undefined | null}
   */
  spaLoadingTemplate: {
    $resolve: async (val, get) => {
      if (typeof val === "string") {
        return resolve(await get("srcDir"), val);
      }
      if (typeof val === "boolean") {
        return val;
      }
      return null;
    }
  },
  /**
   * An array of nuxt app plugins.
   *
   * Each plugin can be a string (which can be an absolute or relative path to a file).
   * If it ends with `.client` or `.server` then it will be automatically loaded only
   * in the appropriate context.
   *
   * It can also be an object with `src` and `mode` keys.
   * @note Plugins are also auto-registered from the `~/plugins` directory
   * and these plugins do not need to be listed in `nuxt.config` unless you
   * need to customize their order. All plugins are deduplicated by their src path.
   * @see [`plugins/` directory documentation](https://nuxt.com/docs/guide/directory-structure/plugins)
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
   * @type {(typeof import('../src/types/nuxt').NuxtPlugin | string)[]}
   */
  plugins: [],
  /**
   * You can define the CSS files/modules/libraries you want to set globally
   * (included in every page).
   *
   * Nuxt will automatically guess the file type by its extension and use the
   * appropriate pre-processor. You will still need to install the required
   * loader if you need to use them.
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
   * @type {string[]}
   */
  css: {
    $resolve: (val) => {
      if (!Array.isArray(val)) {
        return [];
      }
      const css = [];
      for (const item of val) {
        if (typeof item === "string") {
          css.push(item);
        } else if (item && "src" in item) {
          css.push(item.src);
        }
      }
      return css;
    }
  },
  /**
   * An object that allows us to configure the `unhead` nuxt module.
   */
  unhead: {
    /***
     * Enable the legacy compatibility mode for `unhead` module. This applies the following changes:
     * - Disables Capo.js sorting
     * - Adds the `DeprecationsPlugin`: supports `hid`, `vmid`, `children`, `body`
     * - Adds the `PromisesPlugin`: supports promises as input
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
     * @type {boolean}
     */
    legacy: false,
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
     * @type {typeof import('@unhead/vue/types').RenderSSRHeadOptions}
     */
    renderSSRHeadOptions: {
      $resolve: async (val, get) => {
        const isV4 = (await get("future")).compatibilityVersion === 4;
        return {
          ...typeof val === "object" ? val : {},
          omitLineBreaks: isV4
        };
      }
    }
  }
});

const build = defineResolvers({
  /**
   * The builder to use for bundling the Vue part of your application.
   * @type {'vite' | 'webpack' | 'rspack' | { bundle: (nuxt: typeof import('../src/types/nuxt').Nuxt) => Promise<void> }}
   */
  builder: {
    $resolve: async (val, get) => {
      if (val && typeof val === "object" && "bundle" in val) {
        return val;
      }
      const map = {
        rspack: "@nuxt/rspack-builder",
        vite: "@nuxt/vite-builder",
        webpack: "@nuxt/webpack-builder"
      };
      if (typeof val === "string" && val in map) {
        return map[val];
      }
      if (await get("vite") === false) {
        return map.webpack;
      }
      return map.vite;
    }
  },
  /**
   * Configures whether and how sourcemaps are generated for server and/or client bundles.
   *
   * If set to a single boolean, that value applies to both server and client.
   * Additionally, the `'hidden'` option is also available for both server and client.
   *
   * Available options for both client and server:
   * - `true`: Generates sourcemaps and includes source references in the final bundle.
   * - `false`: Does not generate any sourcemaps.
   * - `'hidden'`: Generates sourcemaps but does not include references in the final bundle.
   *
   * @type {boolean | { server?: boolean | 'hidden', client?: boolean | 'hidden' }}
   */
  sourcemap: {
    $resolve: async (val, get) => {
      if (typeof val === "boolean") {
        return { server: val, client: val };
      }
      return {
        server: true,
        client: await get("dev"),
        ...typeof val === "object" ? val : {}
      };
    }
  },
  /**
   * Log level when building logs.
   *
   * Defaults to 'silent' when running in CI or when a TTY is not available.
   * This option is then used as 'silent' in Vite and 'none' in Webpack
   * @type {'silent' | 'info' | 'verbose'}
   */
  logLevel: {
    $resolve: (val) => {
      if (val && typeof val === "string" && !["silent", "info", "verbose"].includes(val)) {
        consola.warn(`Invalid \`logLevel\` option: \`${val}\`. Must be one of: \`silent\`, \`info\`, \`verbose\`.`);
      }
      return val && typeof val === "string" ? val : isTest ? "silent" : "info";
    }
  },
  /**
   * Shared build configuration.
   */
  build: {
    /**
     * If you want to transpile specific dependencies with Babel, you can add them here.
     * Each item in transpile can be a package name, a function, a string or regex object matching the
     * dependency's file name.
     *
     * You can also use a function to conditionally transpile. The function will receive an object ({ isDev, isServer, isClient, isModern, isLegacy }).
     * @example
     * ```js
     * transpile: [({ isLegacy }) => isLegacy && 'ky']
     * ```
     * @type {Array<string | RegExp | ((ctx: { isClient?: boolean; isServer?: boolean; isDev: boolean }) => string | RegExp | false)>}
     */
    transpile: {
      $resolve: (val) => {
        const transpile = [];
        if (Array.isArray(val)) {
          for (const pattern of val) {
            if (!pattern) {
              continue;
            }
            if (typeof pattern === "string" || typeof pattern === "function" || pattern instanceof RegExp) {
              transpile.push(pattern);
            }
          }
        }
        return transpile;
      }
    },
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
     * @type {typeof import('../src/types/nuxt').NuxtTemplate<any>[]}
     */
    templates: [],
    /**
     * Nuxt allows visualizing your bundles and how to optimize them.
     *
     * Set to `true` to enable bundle analysis, or pass an object with options: [for webpack](https://github.com/webpack-contrib/webpack-bundle-analyzer#options-for-plugin) or [for vite](https://github.com/btd/rollup-plugin-visualizer#options).
     * @example
     * ```js
     * analyze: {
     *   analyzerMode: 'static'
     * }
     * ```
     * @type {boolean | { enabled?: boolean } & ((0 extends 1 & typeof import('webpack-bundle-analyzer').BundleAnalyzerPlugin.Options ? Record<string, unknown> : typeof import('webpack-bundle-analyzer').BundleAnalyzerPlugin.Options) | typeof import('rollup-plugin-visualizer').PluginVisualizerOptions)}
     */
    analyze: {
      $resolve: async (val, get) => {
        const [rootDir, analyzeDir] = await Promise.all([get("rootDir"), get("analyzeDir")]);
        return {
          template: "treemap",
          projectRoot: rootDir,
          filename: join(analyzeDir, "{name}.html"),
          ...typeof val === "boolean" ? { enabled: val } : typeof val === "object" ? val : {}
        };
      }
    }
  },
  /**
   * Build time optimization configuration.
   */
  optimization: {
    /**
     * Functions to inject a key for.
     *
     * As long as the number of arguments passed to the function is less than `argumentLength`, an
     * additional magic string will be injected that can be used to deduplicate requests between server
     * and client. You will need to take steps to handle this additional key.
     *
     * The key will be unique based on the location of the function being invoked within the file.
     * @type {Array<{ name: string, source?: string | RegExp, argumentLength: number }>}
     */
    keyedComposables: {
      $resolve: (val) => [
        { name: "callOnce", argumentLength: 3 },
        { name: "defineNuxtComponent", argumentLength: 2 },
        { name: "useState", argumentLength: 2 },
        { name: "useFetch", argumentLength: 3 },
        { name: "useAsyncData", argumentLength: 3 },
        { name: "useLazyAsyncData", argumentLength: 3 },
        { name: "useLazyFetch", argumentLength: 3 },
        ...Array.isArray(val) ? val : []
      ].filter(Boolean)
    },
    /**
     * Tree shake code from specific builds.
     */
    treeShake: {
      /**
       * Tree shake composables from the server or client builds.
       * @example
       * ```js
       * treeShake: { client: { myPackage: ['useServerOnlyComposable'] } }
       * ```
       */
      composables: {
        server: {
          $resolve: async (val, get) => defu(
            typeof val === "object" ? val || {} : {},
            await get("dev") ? {} : {
              "vue": ["onMounted", "onUpdated", "onUnmounted", "onBeforeMount", "onBeforeUpdate", "onBeforeUnmount", "onRenderTracked", "onRenderTriggered", "onActivated", "onDeactivated"],
              "#app": ["definePayloadReviver", "definePageMeta"]
            }
          )
        },
        client: {
          $resolve: async (val, get) => defu(
            typeof val === "object" ? val || {} : {},
            await get("dev") ? {} : {
              "vue": ["onRenderTracked", "onRenderTriggered", "onServerPrefetch"],
              "#app": ["definePayloadReducer", "definePageMeta", "onPrehydrate"]
            }
          )
        }
      }
    },
    /**
     * Options passed directly to the transformer from `unctx` that preserves async context
     * after `await`.
     * @type {typeof import('unctx/transform').TransformerOptions}
     */
    asyncTransforms: {
      asyncFunctions: ["defineNuxtPlugin", "defineNuxtRouteMiddleware"],
      objectDefinitions: {
        defineNuxtComponent: ["asyncData", "setup"],
        defineNuxtPlugin: ["setup"],
        definePageMeta: ["middleware", "validate"]
      }
    }
  }
});

const common = defineResolvers({
  /**
   * Extend project from multiple local or remote sources.
   *
   * Value should be either a string or array of strings pointing to source directories or config path relative to current config.
   *
   * You can use `github:`, `gh:` `gitlab:` or `bitbucket:`
   * @see [`c12` docs on extending config layers](https://github.com/unjs/c12#extending-config-layer-from-remote-sources)
   * @see [`giget` documentation](https://github.com/unjs/giget)
   * @type {string | [string, typeof import('c12').SourceOptions?] | (string | [string, typeof import('c12').SourceOptions?])[]}
   */
  extends: void 0,
  /**
   * Specify a compatibility date for your app.
   *
   * This is used to control the behavior of presets in Nitro, Nuxt Image
   * and other modules that may change behavior without a major version bump.
   *
   * We plan to improve the tooling around this feature in the future.
   *
   * @type {typeof import('compatx').CompatibilityDateSpec}
   */
  compatibilityDate: void 0,
  /**
   * Extend project from a local or remote source.
   *
   * Value should be a string pointing to source directory or config path relative to current config.
   *
   * You can use `github:`, `gitlab:`, `bitbucket:` or `https://` to extend from a remote git repository.
   * @type {string}
   */
  theme: void 0,
  /**
   * Define the root directory of your application.
   *
   * This property can be overwritten (for example, running `nuxt ./my-app/`
   * will set the `rootDir` to the absolute path of `./my-app/` from the
   * current/working directory.
   *
   * It is normally not needed to configure this option.
   */
  rootDir: {
    $resolve: (val) => typeof val === "string" ? resolve(val) : process.cwd()
  },
  /**
   * Define the workspace directory of your application.
   *
   * Often this is used when in a monorepo setup. Nuxt will attempt to detect
   * your workspace directory automatically, but you can override it here.
   *
   * It is normally not needed to configure this option.
   */
  workspaceDir: {
    $resolve: async (val, get) => {
      const rootDir = await get("rootDir");
      return val && typeof val === "string" ? resolve(rootDir, val) : await findWorkspaceDir(rootDir, {
        gitConfig: "closest",
        try: true
      }).catch(() => rootDir);
    }
  },
  /**
   * Define the source directory of your Nuxt application.
   *
   * If a relative path is specified, it will be relative to the `rootDir`.
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
  srcDir: {
    $resolve: async (val, get) => {
      if (val && typeof val === "string") {
        return resolve(await get("rootDir"), val);
      }
      const [rootDir, isV4] = await Promise.all([
        get("rootDir"),
        get("future").then((r) => r.compatibilityVersion === 4)
      ]);
      if (!isV4) {
        return rootDir;
      }
      const srcDir = resolve(rootDir, "app");
      if (!existsSync(srcDir)) {
        return rootDir;
      }
      const srcDirFiles = /* @__PURE__ */ new Set();
      const files = await readdir(srcDir).catch(() => []);
      for (const file of files) {
        if (file !== "spa-loading-template.html" && !file.startsWith("router.options")) {
          srcDirFiles.add(file);
        }
      }
      if (srcDirFiles.size === 0) {
        for (const file of ["app.vue", "App.vue"]) {
          if (existsSync(resolve(rootDir, file))) {
            return rootDir;
          }
        }
        const keys = ["assets", "layouts", "middleware", "pages", "plugins"];
        const dirs = await Promise.all(keys.map((key) => get(`dir.${key}`)));
        for (const dir of dirs) {
          if (existsSync(resolve(rootDir, dir))) {
            return rootDir;
          }
        }
      }
      return srcDir;
    }
  },
  /**
   * Define the server directory of your Nuxt application, where Nitro
   * routes, middleware and plugins are kept.
   *
   * If a relative path is specified, it will be relative to your `rootDir`.
   *
   */
  serverDir: {
    $resolve: async (val, get) => {
      if (val && typeof val === "string") {
        const rootDir = await get("rootDir");
        return resolve(rootDir, val);
      }
      const isV4 = (await get("future")).compatibilityVersion === 4;
      return join(isV4 ? await get("rootDir") : await get("srcDir"), "server");
    }
  },
  /**
   * Define the directory where your built Nuxt files will be placed.
   *
   * Many tools assume that `.nuxt` is a hidden directory (because it starts
   * with a `.`). If that is a problem, you can use this option to prevent that.
   * @example
   * ```js
   * export default {
   *   buildDir: 'nuxt-build'
   * }
   * ```
   */
  buildDir: {
    $resolve: async (val, get) => {
      const rootDir = await get("rootDir");
      return resolve(rootDir, val && typeof val === "string" ? val : ".nuxt");
    }
  },
  /**
   * For multi-app projects, the unique id of the Nuxt application.
   *
   * Defaults to `nuxt-app`.
   */
  appId: {
    $resolve: (val) => val && typeof val === "string" ? val : "nuxt-app"
  },
  /**
   * A unique identifier matching the build. This may contain the hash of the current state of the project.
   */
  buildId: {
    $resolve: async (val, get) => {
      if (typeof val === "string") {
        return val;
      }
      const [isDev, isTest2] = await Promise.all([get("dev"), get("test")]);
      return isDev ? "dev" : isTest2 ? "test" : randomUUID();
    }
  },
  /**
   * Used to set the modules directories for path resolving (for example, webpack's
   * `resolveLoading`, `nodeExternals` and `postcss`).
   *
   * The configuration path is relative to `options.rootDir` (default is current working directory).
   *
   * Setting this field may be necessary if your project is organized as a yarn workspace-styled mono-repository.
   * @example
   * ```js
   * export default {
   *   modulesDir: ['../../node_modules']
   * }
   * ```
   */
  modulesDir: {
    $default: ["node_modules"],
    $resolve: async (val, get) => {
      const rootDir = await get("rootDir");
      const modulesDir = /* @__PURE__ */ new Set([resolve(rootDir, "node_modules")]);
      if (Array.isArray(val)) {
        for (const dir of val) {
          if (dir && typeof dir === "string") {
            modulesDir.add(resolve(rootDir, dir));
          }
        }
      }
      return [...modulesDir];
    }
  },
  /**
   * The directory where Nuxt will store the generated files when running `nuxt analyze`.
   *
   * If a relative path is specified, it will be relative to your `rootDir`.
   */
  analyzeDir: {
    $resolve: async (val, get) => val && typeof val === "string" ? resolve(await get("rootDir"), val) : resolve(await get("buildDir"), "analyze")
  },
  /**
   * Whether Nuxt is running in development mode.
   *
   * Normally, you should not need to set this.
   */
  dev: {
    $resolve: (val) => typeof val === "boolean" ? val : Boolean(isDevelopment)
  },
  /**
   * Whether your app is being unit tested.
   */
  test: {
    $resolve: (val) => typeof val === "boolean" ? val : Boolean(isTest)
  },
  /**
   * Set to `true` to enable debug mode.
   *
   * At the moment, it prints out hook names and timings on the server, and
   * logs hook arguments as well in the browser.
   *
   * You can also set this to an object to enable specific debug options.
   *
   * @type {boolean | (typeof import('../src/types/debug').NuxtDebugOptions) | undefined}
   */
  debug: {
    $resolve: (val) => {
      val ??= isDebug;
      if (val === true) {
        return {
          templates: true,
          modules: true,
          watchers: true,
          hooks: {
            client: true,
            server: true
          },
          nitro: true,
          router: true,
          hydration: true
        };
      }
      if (val && typeof val === "object") {
        return val;
      }
      return false;
    }
  },
  /**
   * Whether to enable rendering of HTML - either dynamically (in server mode) or at generate time.
   * If set to `false` generated pages will have no content.
   */
  ssr: {
    $resolve: (val) => typeof val === "boolean" ? val : true
  },
  /**
   * Modules are Nuxt extensions which can extend its core functionality and add endless integrations.
   *
   * Each module is either a string (which can refer to a package, or be a path to a file), a
   * tuple with the module as first string and the options as a second object, or an inline module function.
   *
   * Nuxt tries to resolve each item in the modules array using node require path
   * (in `node_modules`) and then will be resolved from project `srcDir` if `~` alias is used.
   * @note Modules are executed sequentially so the order is important. First, the modules defined in `nuxt.config.ts` are loaded. Then, modules found in the `modules/`
   * directory are executed, and they load in alphabetical order.
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
   * @type {(typeof import('../src/types/module').NuxtModule<any> | string | [typeof import('../src/types/module').NuxtModule | string, Record<string, any>] | undefined | null | false)[]}
   */
  modules: {
    $resolve: (val) => {
      const modules = [];
      if (Array.isArray(val)) {
        for (const mod of val) {
          if (!mod) {
            continue;
          }
          if (typeof mod === "string" || typeof mod === "function" || Array.isArray(mod) && mod[0]) {
            modules.push(mod);
          }
        }
      }
      return modules;
    }
  },
  /**
   * Customize default directory structure used by Nuxt.
   *
   * It is better to stick with defaults unless needed.
   */
  dir: {
    app: {
      $resolve: async (val, get) => {
        const isV4 = (await get("future")).compatibilityVersion === 4;
        if (isV4) {
          const [srcDir, rootDir] = await Promise.all([get("srcDir"), get("rootDir")]);
          return resolve(await get("srcDir"), val && typeof val === "string" ? val : srcDir === rootDir ? "app" : ".");
        }
        return val && typeof val === "string" ? val : "app";
      }
    },
    /**
     * The assets directory (aliased as `~assets` in your build).
     */
    assets: "assets",
    /**
     * The layouts directory, each file of which will be auto-registered as a Nuxt layout.
     */
    layouts: "layouts",
    /**
     * The middleware directory, each file of which will be auto-registered as a Nuxt middleware.
     */
    middleware: "middleware",
    /**
     * The modules directory, each file in which will be auto-registered as a Nuxt module.
     */
    modules: {
      $resolve: async (val, get) => {
        const isV4 = (await get("future")).compatibilityVersion === 4;
        if (isV4) {
          return resolve(await get("rootDir"), val && typeof val === "string" ? val : "modules");
        }
        return val && typeof val === "string" ? val : "modules";
      }
    },
    /**
     * The directory which will be processed to auto-generate your application page routes.
     */
    pages: "pages",
    /**
     * The plugins directory, each file of which will be auto-registered as a Nuxt plugin.
     */
    plugins: "plugins",
    /**
     * The shared directory. This directory is shared between the app and the server.
     */
    shared: {
      $resolve: (val) => {
        return val && typeof val === "string" ? val : "shared";
      }
    },
    /**
     * The directory containing your static files, which will be directly accessible via the Nuxt server
     * and copied across into your `dist` folder when your app is generated.
     */
    public: {
      $resolve: async (val, get) => {
        const isV4 = (await get("future")).compatibilityVersion === 4;
        if (isV4) {
          return resolve(await get("rootDir"), val && typeof val === "string" ? val : await get("dir.static") || "public");
        }
        return val && typeof val === "string" ? val : await get("dir.static") || "public";
      }
    },
    // TODO: remove in v4
    static: {
      // @ts-expect-error schema has invalid types
      $schema: { deprecated: "use `dir.public` option instead" },
      $resolve: async (val, get) => {
        if (val && typeof val === "string") {
          return val;
        }
        return await get("dir.public") || "public";
      }
    }
  },
  /**
   * The extensions that should be resolved by the Nuxt resolver.
   */
  extensions: {
    $resolve: (val) => {
      const extensions = [".js", ".jsx", ".mjs", ".ts", ".tsx", ".vue"];
      if (Array.isArray(val)) {
        for (const item of val) {
          if (item && typeof item === "string") {
            extensions.push(item);
          }
        }
      }
      return extensions;
    }
  },
  /**
   * You can improve your DX by defining additional aliases to access custom directories
   * within your JavaScript and CSS.
   * @note Within a webpack context (image sources, CSS - but not JavaScript) you _must_ access
   * your alias by prefixing it with `~`.
   * @note These aliases will be automatically added to the generated `.nuxt/tsconfig.json` so you can get full
   * type support and path auto-complete. In case you need to extend options provided by `./.nuxt/tsconfig.json`
   * further, make sure to add them here or within the `typescript.tsConfig` property in `nuxt.config`.
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
   * <\/script>
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
   * @type {Record<string, string>}
   */
  alias: {
    $resolve: async (val, get) => {
      const [srcDir, rootDir, assetsDir, publicDir, buildDir, sharedDir] = await Promise.all([get("srcDir"), get("rootDir"), get("dir.assets"), get("dir.public"), get("buildDir"), get("dir.shared")]);
      return {
        "~": srcDir,
        "@": srcDir,
        "~~": rootDir,
        "@@": rootDir,
        "#shared": resolve(rootDir, sharedDir),
        [basename(assetsDir)]: resolve(srcDir, assetsDir),
        [basename(publicDir)]: resolve(srcDir, publicDir),
        "#build": buildDir,
        "#internal/nuxt/paths": resolve(buildDir, "paths.mjs"),
        ...typeof val === "object" ? val : {}
      };
    }
  },
  /**
   * Pass options directly to `node-ignore` (which is used by Nuxt to ignore files).
   * @see [node-ignore](https://github.com/kaelzhang/node-ignore)
   * @example
   * ```js
   * ignoreOptions: {
   *   ignorecase: false
   * }
   * ```
   * @type {typeof import('ignore').Options}
   */
  ignoreOptions: void 0,
  /**
   * Any file in `pages/`, `layouts/`, `middleware/`, and `public/` directories will be ignored during
   * the build process if its filename starts with the prefix specified by `ignorePrefix`. This is intended to prevent
   * certain files from being processed or served in the built application.
   * By default, the `ignorePrefix` is set to '-', ignoring any files starting with '-'.
   */
  ignorePrefix: {
    $resolve: (val) => val && typeof val === "string" ? val : "-"
  },
  /**
   * More customizable than `ignorePrefix`: all files matching glob patterns specified
   * inside the `ignore` array will be ignored in building.
   */
  ignore: {
    $resolve: async (val, get) => {
      const [rootDir, ignorePrefix, analyzeDir, buildDir] = await Promise.all([get("rootDir"), get("ignorePrefix"), get("analyzeDir"), get("buildDir")]);
      const ignore = /* @__PURE__ */ new Set([
        "**/*.stories.{js,cts,mts,ts,jsx,tsx}",
        // ignore storybook files
        "**/*.{spec,test}.{js,cts,mts,ts,jsx,tsx}",
        // ignore tests
        "**/*.d.{cts,mts,ts}",
        // ignore type declarations
        "**/.{pnpm-store,vercel,netlify,output,git,cache,data}",
        "**/*.sock",
        relative(rootDir, analyzeDir),
        relative(rootDir, buildDir)
      ]);
      if (ignorePrefix) {
        ignore.add(`**/${ignorePrefix}*.*`);
      }
      if (Array.isArray(val)) {
        for (const pattern of val) {
          if (pattern) {
            ignore.add(pattern);
          }
        }
      }
      return [...ignore];
    }
  },
  /**
   * The watch property lets you define patterns that will restart the Nuxt dev server when changed.
   *
   * It is an array of strings or regular expressions. Strings should be either absolute paths or
   * relative to the `srcDir` (and the `srcDir` of any layers). Regular expressions will be matched
   * against the path relative to the project `srcDir` (and the `srcDir` of any layers).
   * @type {Array<string | RegExp>}
   */
  watch: {
    $resolve: (val) => {
      if (Array.isArray(val)) {
        return val.filter((b) => typeof b === "string" || b instanceof RegExp);
      }
      return [];
    }
  },
  /**
   * The watchers property lets you overwrite watchers configuration in your `nuxt.config`.
   */
  watchers: {
    /** An array of event types, which, when received, will cause the watcher to restart. */
    rewatchOnRawEvents: void 0,
    /**
     * `watchOptions` to pass directly to webpack.
     * @see [webpack@4 watch options](https://v4.webpack.js.org/configuration/watch/#watchoptions).
     */
    webpack: {
      aggregateTimeout: 1e3
    },
    /**
     * Options to pass directly to `chokidar`.
     * @see [chokidar](https://github.com/paulmillr/chokidar#api)
     * @type {typeof import('chokidar').ChokidarOptions}
     */
    chokidar: {
      ignoreInitial: true,
      ignorePermissionErrors: true
    }
  },
  /**
   * Hooks are listeners to Nuxt events that are typically used in modules,
   * but are also available in `nuxt.config`.
   *
   * Internally, hooks follow a naming pattern using colons (e.g., build:done).
   *
   * For ease of configuration, you can also structure them as an hierarchical
   * object in `nuxt.config` (as below).
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
   * @type {typeof import('../src/types/hooks').NuxtHooks}
   */
  hooks: void 0,
  /**
   * Runtime config allows passing dynamic config and environment variables to the Nuxt app context.
   *
   * The value of this object is accessible from server only using `useRuntimeConfig`.
   *
   * It mainly should hold _private_ configuration which is not exposed on the frontend.
   * This could include a reference to your API secret tokens.
   *
   * Anything under `public` and `app` will be exposed to the frontend as well.
   *
   * Values are automatically replaced by matching env variables at runtime, e.g. setting an environment
   * variable `NUXT_API_KEY=my-api-key NUXT_PUBLIC_BASE_URL=/foo/` would overwrite the two values in the example below.
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
   * @type {typeof import('../src/types/config').RuntimeConfig}
   */
  runtimeConfig: {
    $resolve: async (_val, get) => {
      const val = _val && typeof _val === "object" ? _val : {};
      const [app, buildId] = await Promise.all([get("app"), get("buildId")]);
      provideFallbackValues(val);
      return defu(val, {
        public: {},
        app: {
          buildId,
          baseURL: app.baseURL,
          buildAssetsDir: app.buildAssetsDir,
          cdnURL: app.cdnURL
        }
      });
    }
  },
  /**
   * Additional app configuration
   *
   * For programmatic usage and type support, you can directly provide app config with this option.
   * It will be merged with `app.config` file as default value.
   * @type {typeof import('../src/types/config').AppConfig}
   */
  appConfig: {
    nuxt: {}
  },
  $schema: {}
});
function provideFallbackValues(obj) {
  for (const key in obj) {
    if (typeof obj[key] === "undefined" || obj[key] === null) {
      obj[key] = "";
    } else if (typeof obj[key] === "object") {
      provideFallbackValues(obj[key]);
    }
  }
}

const _messages = { "appName": "Nuxt", "version": "", "loading": "Loading" };
const template = (messages) => {
  messages = { ..._messages, ...messages };
  return '<!DOCTYPE html><html lang="en"><head><title>' + escapeHtml(messages.loading) + " | " + escapeHtml(messages.appName) + '</title><meta charset="utf-8"><meta content="width=device-width,initial-scale=1.0,minimum-scale=1.0" name="viewport"><style>.nuxt-loader-bar{animation:gradient 2s infinite;animation-fill-mode:forwards;animation-timing-function:linear;background:repeating-linear-gradient(90deg,#36e4da 0,#1de0b1 25%,#00dc82 50%,#1de0b1 75%,#36e4da);background-position:0 0;background-size:200% auto;bottom:0;height:100px;height:5px;left:0;position:fixed;right:0}.visual-effects .nuxt-loader-bar{bottom:-50px;filter:blur(100px);height:100px;left:-50px;right:-50px}.visual-effects .mouse-gradient{background:repeating-linear-gradient(90deg,#00dc82 0,#1de0b1 50%,#36e4da);filter:blur(100px);opacity:.5}#animation-toggle{opacity:0;padding:10px;position:fixed;right:0;top:0;transition:opacity .4s ease-in}#animation-toggle:hover{opacity:.8}@keyframes gradient{0%{background-position:0 0}to{background-position:-200% 0}}@media (prefers-color-scheme:dark){body,html{color:#fff;color-scheme:dark}.nuxt-loader-bar{opacity:.5}}*,:after,:before{border-color:var(--un-default-border-color,#e5e7eb);border-style:solid;border-width:0;box-sizing:border-box}:after,:before{--un-content:""}html{line-height:1.5;-webkit-text-size-adjust:100%;font-family:ui-sans-serif,system-ui,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;font-feature-settings:normal;font-variation-settings:normal;-moz-tab-size:4;tab-size:4;-webkit-tap-highlight-color:transparent}body{line-height:inherit;margin:0}a{text-decoration:inherit}a,button{color:inherit}button{font-family:inherit;font-feature-settings:inherit;font-size:100%;font-variation-settings:inherit;font-weight:inherit;line-height:inherit;margin:0;padding:0;text-transform:none}[type=button],button{-webkit-appearance:button;background-color:transparent;background-image:none}button{cursor:pointer}svg{display:block;vertical-align:middle}*,:after,:before{--un-rotate:0;--un-rotate-x:0;--un-rotate-y:0;--un-rotate-z:0;--un-scale-x:1;--un-scale-y:1;--un-scale-z:1;--un-skew-x:0;--un-skew-y:0;--un-translate-x:0;--un-translate-y:0;--un-translate-z:0;--un-pan-x: ;--un-pan-y: ;--un-pinch-zoom: ;--un-scroll-snap-strictness:proximity;--un-ordinal: ;--un-slashed-zero: ;--un-numeric-figure: ;--un-numeric-spacing: ;--un-numeric-fraction: ;--un-border-spacing-x:0;--un-border-spacing-y:0;--un-ring-offset-shadow:0 0 transparent;--un-ring-shadow:0 0 transparent;--un-shadow-inset: ;--un-shadow:0 0 transparent;--un-ring-inset: ;--un-ring-offset-width:0px;--un-ring-offset-color:#fff;--un-ring-width:0px;--un-ring-color:rgba(147,197,253,.5);--un-blur: ;--un-brightness: ;--un-contrast: ;--un-drop-shadow: ;--un-grayscale: ;--un-hue-rotate: ;--un-invert: ;--un-saturate: ;--un-sepia: ;--un-backdrop-blur: ;--un-backdrop-brightness: ;--un-backdrop-contrast: ;--un-backdrop-grayscale: ;--un-backdrop-hue-rotate: ;--un-backdrop-invert: ;--un-backdrop-opacity: ;--un-backdrop-saturate: ;--un-backdrop-sepia: }.absolute{position:absolute}.relative{position:relative}.top-0{top:0}.z-20{z-index:20}.h-\\[200px\\]{height:200px}.min-h-screen{min-height:100vh}.w-\\[200px\\]{width:200px}.flex{display:flex}.flex-col{flex-direction:column}.items-center{align-items:center}.justify-center{justify-content:center}.overflow-hidden{overflow:hidden}.rounded-full{border-radius:9999px}.bg-white{--un-bg-opacity:1;background-color:rgb(255 255 255/var(--un-bg-opacity))}.text-center{text-align:center}.transition-opacity{transition-duration:.15s;transition-property:opacity;transition-timing-function:cubic-bezier(.4,0,.2,1)}@media (prefers-color-scheme:dark){.dark\\:bg-black{--un-bg-opacity:1;background-color:rgb(0 0 0/var(--un-bg-opacity))}}</style><script>!function(){const e=document.createElement("link").relList;if(!(e&&e.supports&&e.supports("modulepreload"))){for(const e of document.querySelectorAll(\'link[rel="modulepreload"]\'))r(e);new MutationObserver((e=>{for(const o of e)if("childList"===o.type)for(const e of o.addedNodes)"LINK"===e.tagName&&"modulepreload"===e.rel&&r(e)})).observe(document,{childList:!0,subtree:!0})}function r(e){if(e.ep)return;e.ep=!0;const r=function(e){const r={};return e.integrity&&(r.integrity=e.integrity),e.referrerPolicy&&(r.referrerPolicy=e.referrerPolicy),"use-credentials"===e.crossOrigin?r.credentials="include":"anonymous"===e.crossOrigin?r.credentials="omit":r.credentials="same-origin",r}(e);fetch(e.href,r)}}();<\/script></head><body class="bg-white dark:bg-black flex flex-col items-center justify-center min-h-screen overflow-hidden relative text-center visual-effects"><div id="mouseLight" class="absolute h-[200px] mouse-gradient rounded-full top-0 transition-opacity w-[200px]"></div><a href="https://nuxt.com" target="_blank" rel="noopener" class="nuxt-logo z-20" aria-label="Nuxt"> <svg xmlns="http://www.w3.org/2000/svg" width="214" height="53" fill="none" class="nuxt-img" viewBox="0 0 800 200"><path fill="#00DC82" d="M168.303 200h111.522c3.543 0 7.022-.924 10.09-2.679A20.1 20.1 0 0 0 297.3 190a19.86 19.86 0 0 0 2.7-10.001 19.86 19.86 0 0 0-2.709-9.998L222.396 41.429a20.1 20.1 0 0 0-7.384-7.32 20.3 20.3 0 0 0-10.088-2.679c-3.541 0-7.02.925-10.087 2.68a20.1 20.1 0 0 0-7.384 7.32l-19.15 32.896L130.86 9.998a20.1 20.1 0 0 0-7.387-7.32A20.3 20.3 0 0 0 113.384 0c-3.542 0-7.022.924-10.09 2.679a20.1 20.1 0 0 0-7.387 7.319L2.709 170A19.85 19.85 0 0 0 0 179.999c-.002 3.511.93 6.96 2.7 10.001a20.1 20.1 0 0 0 7.385 7.321A20.3 20.3 0 0 0 20.175 200h70.004c27.737 0 48.192-12.075 62.266-35.633l34.171-58.652 18.303-31.389 54.93 94.285h-73.233zm-79.265-31.421-48.854-.011 73.232-125.706 36.541 62.853-24.466 42.01c-9.347 15.285-19.965 20.854-36.453 20.854"/><path fill="currentColor" d="M377 200a4 4 0 0 0 4-4v-93s5.244 8.286 15 25l38.707 66.961c1.789 3.119 5.084 5.039 8.649 5.039H470V50h-27a4 4 0 0 0-4 4v94l-17-30-36.588-62.98c-1.792-3.108-5.081-5.02-8.639-5.02H350v150zm299.203-56.143L710.551 92h-25.73a9.97 9.97 0 0 0-8.333 4.522L660.757 120.5l-15.731-23.978A9.97 9.97 0 0 0 636.693 92h-25.527l34.348 51.643L608.524 200h24.966a9.97 9.97 0 0 0 8.29-4.458l19.18-28.756 18.981 28.72a9.97 9.97 0 0 0 8.313 4.494h24.736zM724.598 92h19.714V60.071h28.251V92H800v24.857h-27.437V159.5c0 10.5 5.284 15.429 14.43 15.429H800V200h-16.869c-23.576 0-38.819-14.143-38.819-39.214v-43.929h-19.714zM590 92h-15c-3.489 0-6.218.145-8.5 2.523-2.282 2.246-2.5 3.63-2.5 7.066v52.486c0 8.058-.376 12.962-4 16.925-3.624 3.831-8.619 5-16 5-7.247 0-12.376-1.169-16-5-3.624-3.963-4-8.867-4-16.925v-52.486c0-3.435-.218-4.82-2.5-7.066C519.218 92.145 516.489 92 513 92h-15v62.422q0 21.006 11.676 33.292C517.594 195.905 529.103 200 544 200s26.204-4.095 34.123-12.286Q590 175.428 590 154.422z"/></svg> </a><button id="animation-toggle" type="button">Animation Enabled</button><div class="nuxt-loader-bar"></div><script>const ANIMATION_KEY="nuxt-loading-enable-animation",isSafari=/^((?!chrome|android).)*safari/i.test(navigator.userAgent);let isLowPerformance=checkIsLowPerformance(),enableAnimation="false"!==localStorage.getItem(ANIMATION_KEY)&&("true"===localStorage.getItem(ANIMATION_KEY)||!isLowPerformance);const mouseLight=window.document.getElementById("mouseLight"),nuxtImg=window.document.querySelector(".nuxt-img"),animationToggle=window.document.getElementById("animation-toggle"),body=window.document.body;let bodyRect;function checkIsLowPerformance(){return window.matchMedia("(prefers-reduced-motion: reduce)").matches||navigator.hardwareConcurrency<2||navigator.deviceMemory<1||isSafari}function calculateDistance(e,t,n){return Math.floor(Math.sqrt(Math.pow(t-(e.x+e.width/2),2)+Math.pow(n-(e.top+e.height/2),2)))}function onFocusOut(){enableAnimation&&(mouseLight.style.opacity=0,nuxtImg.style.opacity=.7)}function onMouseMove(e){if(!enableAnimation)return;const t=nuxtImg.getBoundingClientRect();bodyRect||(bodyRect=body.getBoundingClientRect());const n=calculateDistance(t,e.pageX,e.pageY),o=Math.max((1e3-n)/2/100,1);mouseLight.style.top=e.clientY-bodyRect.y-mouseLight.clientHeight/2+"px",mouseLight.style.left=e.clientX-mouseLight.clientWidth/2+"px",mouseLight.style.width=mouseLight.style.height=`${Math.max(Math.round(100*o),300)}px`,mouseLight.style.filter=`blur(${Math.min(Math.max(50*o,100),160)}px)`,mouseLight.style.opacity=Math.min(Math.max(o/4,.6),1);const i=`radial-gradient(circle at ${e.pageX-t.left}px ${e.pageY-t.top}px, black 75%, transparent 100%)`;nuxtImg.style["-webkit-mask-image"]=i,nuxtImg.style["mask-image"]=i,nuxtImg.style.opacity=Math.min(Math.max(o/4,.7),1)}function toggleAnimation(e=!enableAnimation){enableAnimation=e,document.body.classList.toggle("visual-effects",enableAnimation),e?(onFocusOut(),animationToggle.innerText="Animation Enabled"):(mouseLight.style.opacity=0,nuxtImg.style.opacity=1,nuxtImg.style["mask-image"]="",nuxtImg.style["-webkit-mask-image"]="",animationToggle.innerText="Animation Disabled"),localStorage.setItem(ANIMATION_KEY,enableAnimation?"true":"false")}if(animationToggle.addEventListener("click",(()=>toggleAnimation()),{passive:!0}),body.addEventListener("mousemove",onMouseMove,{passive:!0}),body.addEventListener("mouseleave",onFocusOut,{passive:!0}),toggleAnimation(enableAnimation),void 0===window.fetch)setTimeout((()=>window.location.reload()),1e3);else{const e=async()=>{try{if(!(await window.fetch(window.location.href).then((e=>e.text()))).includes("__NUXT_LOADING__"))return window.location.reload()}catch{}setTimeout(e,1e3)};e()}<\/script><script>function whatHemisphere(){let e=new Date;if(null==e.getTimezoneOffset)return null;e=e.getFullYear();let t=-new Date(e,0,1,0,0,0,0).getTimezoneOffset()- -new Date(e,6,1,0,0,0,0).getTimezoneOffset();return t<0?"N":t>0?"S":null}const months={N:[10,11,0],S:[4,5,6]},hemisphere=whatHemisphere();if(hemisphere&&months[hemisphere].includes((new Date).getMonth())){const e=document.createElement("canvas");e.style="position: fixed; inset: 0; z-index: -10; pointer-events: none; opacity: 0; transition: opacity 2s ease-in; filter: blur(4px);",document.body.appendChild(e),e.style.opacity=1;const t=25e-5,n={current:0,maxCurrent:4,force:.1,target:.1,min:.1,max:.4,easing:.01},r=1.25;let a=!1;function resize(){e.width=window.innerWidth,e.height=window.innerHeight,a=!0}function mod(e,t){return(e%t+t)%t}window.addEventListener("resize",resize),resize();let i=[],o=Date.now();const h=e.getContext("2d");function draw(){if(h.clearRect(0,0,e.width,e.height),!enableAnimation)return requestAnimationFrame(draw);a&&(i=Array.from({length:Math.floor(e.width*e.height*t)},(()=>({x:Math.random()*e.width,y:Math.random()*e.height,vx:1+Math.random(),vy:1+Math.random(),vsin:10*Math.random(),rangle:2*Math.random()*Math.PI,rsin:10*Math.random(),color:`rgba(255, 255, 255, ${.1+.15*Math.random()})`,size:5*Math.random()*4*(e.height/1e3)}))),a=!1);const m=Date.now(),s=m-o;o=m,n.force+=(n.target-n.force)*n.easing,n.current+=n.force*(.05*s),n.current=Math.max(-n.maxCurrent,Math.min(n.current,n.maxCurrent)),Math.random()>.995&&(n.target=(n.min+Math.random()*(n.max-n.min))*(Math.random()>.5?-1:1));const d=.2*s;i.forEach((t=>{t.x=mod(t.x+d+n.current*t.vx,e.width),t.y=mod(t.y+d*t.vy*r,e.height),t.x+=Math.sin(d*t.vsin)*t.rsin*.5,t.rangle+=.01*d,h.fillStyle=t.color,h.beginPath(),h.ellipse(t.x,t.y,t.size,.66*t.size,t.rangle,0,2*Math.PI),h.fill()})),requestAnimationFrame(draw)}draw()}<\/script></body></html>';
};

const dev = defineResolvers({
  devServer: {
    /**
     * Whether to enable HTTPS.
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
     * @type {boolean | { key: string; cert: string } | { pfx: string; passphrase: string }}
     */
    https: false,
    /** Dev server listening port */
    port: Number(process.env.NUXT_PORT || process.env.NITRO_PORT || process.env.PORT || 3e3),
    /**
     * Dev server listening host
     * @type {string | undefined}
     */
    host: process.env.NUXT_HOST || process.env.NITRO_HOST || process.env.HOST || void 0,
    /**
     * Listening dev server URL.
     *
     * This should not be set directly as it will always be overridden by the
     * dev server with the full URL (for module and internal use).
     */
    url: "http://localhost:3000",
    /**
     * Template to show a loading screen
     * @type {(data: { loading?: string }) => string}
     */
    loadingTemplate: template,
    /**
     * Set CORS options for the dev server
     * @type {typeof import('h3').H3CorsOptions}
     */
    cors: {
      origin: [/^https?:\/\/(?:(?:[^:]+\.)?localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$/]
    }
  }
});

const esbuild = defineResolvers({
  esbuild: {
    /**
     * Configure shared esbuild options used within Nuxt and passed to other builders, such as Vite or Webpack.
     * @type {import('esbuild').TransformOptions}
     */
    options: {
      target: {
        $resolve: async (val, get) => {
          if (typeof val === "string") {
            return val;
          }
          const useDecorators = await get("experimental").then((r) => r?.decorators === true);
          if (useDecorators) {
            return "es2024";
          }
          return "esnext";
        }
      },
      jsxFactory: "h",
      jsxFragment: "Fragment",
      tsconfigRaw: {
        $resolve: async (_val, get) => {
          const val = typeof _val === "string" ? JSON.parse(_val) : _val && typeof _val === "object" ? _val : {};
          const useDecorators = await get("experimental").then((r) => r?.decorators === true);
          if (!useDecorators) {
            return val;
          }
          return defu({
            compilerOptions: {
              experimentalDecorators: false
            }
          }, val);
        }
      }
    }
  }
});

const experimental = defineResolvers({
  /**
   * `future` is for early opting-in to new features that will become default in a future
   * (possibly major) version of the framework.
   */
  future: {
    /**
     * Enable early access to Nuxt v4 features or flags.
     *
     * Setting `compatibilityVersion` to `4` changes defaults throughout your
     * Nuxt configuration, but you can granularly re-enable Nuxt v3 behaviour
     * when testing (see example). Please file issues if so, so that we can
     * address in Nuxt or in the ecosystem.
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
     * @type {3 | 4}
     */
    compatibilityVersion: 3,
    /**
     * This enables early access to the experimental multi-app support.
     * @see [Nuxt Issue #21635](https://github.com/nuxt/nuxt/issues/21635)
     */
    multiApp: false,
    /**
     * This enables 'Bundler' module resolution mode for TypeScript, which is the recommended setting
     * for frameworks like Nuxt and Vite.
     *
     * It improves type support when using modern libraries with `exports`.
     *
     * You can set it to false to use the legacy 'Node' mode, which is the default for TypeScript.
     *
     * @see [TypeScript PR implementing `bundler` module resolution](https://github.com/microsoft/TypeScript/pull/51669)
     */
    typescriptBundlerResolution: {
      async $resolve(val, get) {
        val = typeof val === "boolean" ? val : await get("experimental").then((e) => e?.typescriptBundlerResolution);
        if (typeof val === "boolean") {
          return val;
        }
        const setting = await get("typescript.tsConfig").then((r) => r?.compilerOptions?.moduleResolution);
        if (setting) {
          return setting.toLowerCase() === "bundler";
        }
        return true;
      }
    }
  },
  /**
   * Some features of Nuxt are available on an opt-in basis, or can be disabled based on your needs.
   */
  features: {
    /**
     * Inline styles when rendering HTML (currently vite only).
     *
     * You can also pass a function that receives the path of a Vue component
     * and returns a boolean indicating whether to inline the styles for that component.
     * @type {boolean | ((id?: string) => boolean)}
     */
    inlineStyles: {
      async $resolve(_val, get) {
        const val = typeof _val === "boolean" || typeof _val === "function" ? _val : await get("experimental").then((e) => e?.inlineSSRStyles);
        if (val === false || await get("dev") || await get("ssr") === false || // @ts-expect-error TODO: handled normalised types
        await get("builder") === "@nuxt/webpack-builder") {
          return false;
        }
        return val ?? ((await get("future")).compatibilityVersion === 4 ? (id) => !!id && id.includes(".vue") : true);
      }
    },
    /**
     * Stream server logs to the client as you are developing. These logs can
     * be handled in the `dev:ssr-logs` hook.
     *
     * If set to `silent`, the logs will not be printed to the browser console.
     * @type {boolean | 'silent'}
     */
    devLogs: {
      async $resolve(val, get) {
        if (typeof val === "boolean" || val === "silent") {
          return val;
        }
        const [isDev, isTest] = await Promise.all([get("dev"), get("test")]);
        return isDev && !isTest;
      }
    },
    /**
     * Turn off rendering of Nuxt scripts and JS resource hints.
     * You can also disable scripts more granularly within `routeRules`.
     *
     * If set to 'production' or `true`, JS will be disabled in production mode only.
     * @type {'production' | 'all' | boolean}
     */
    noScripts: {
      async $resolve(val, get) {
        const isValidLiteral = (val2) => {
          return typeof val2 === "string" && ["production", "all"].includes(val2);
        };
        return val === true ? "production" : val === false || isValidLiteral(val) ? val : await get("experimental").then((e) => e?.noScripts && "production") ?? false;
      }
    }
  },
  experimental: {
    /**
     * Enable to use experimental decorators in Nuxt and Nitro.
     *
     * @see https://github.com/tc39/proposal-decorators
     */
    decorators: false,
    /**
     * Set to true to generate an async entry point for the Vue bundle (for module federation support).
     */
    asyncEntry: {
      $resolve: (val) => typeof val === "boolean" ? val : false
    },
    // TODO: Remove when nitro has support for mocking traced dependencies
    // https://github.com/nitrojs/nitro/issues/1118
    /**
     * Externalize `vue`, `@vue/*` and `vue-router` when building.
     * @see [Nuxt Issue #13632](https://github.com/nuxt/nuxt/issues/13632)
     */
    externalVue: true,
    /**
     * Tree shakes contents of client-only components from server bundle.
     * @see [Nuxt PR #5750](https://github.com/nuxt/framework/pull/5750)
     * @deprecated This option will no longer be configurable in Nuxt v4
     */
    treeshakeClientOnly: {
      async $resolve(val, get) {
        const isV4 = (await get("future")).compatibilityVersion === 4;
        if (isV4 && val === false) {
          console.warn("Enabling `experimental.treeshakeClientOnly` in v4 compatibility mode as it will no longer be configurable in Nuxt v4.");
          return true;
        }
        return typeof val === "boolean" ? val : true;
      }
    },
    /**
     * Emit `app:chunkError` hook when there is an error loading vite/webpack
     * chunks.
     *
     * By default, Nuxt will also perform a reload of the new route
     * when a chunk fails to load when navigating to a new route (`automatic`).
     *
     * Setting `automatic-immediate` will lead Nuxt to perform a reload of the current route
     * right when a chunk fails to load (instead of waiting for navigation).
     *
     * You can disable automatic handling by setting this to `false`, or handle
     * chunk errors manually by setting it to `manual`.
     * @see [Nuxt PR #19038](https://github.com/nuxt/nuxt/pull/19038)
     * @type {false | 'manual' | 'automatic' | 'automatic-immediate'}
     */
    emitRouteChunkError: {
      $resolve: (val) => {
        if (val === true) {
          return "manual";
        }
        if (val === "reload") {
          return "automatic";
        }
        if (val === false) {
          return false;
        }
        const validOptions = /* @__PURE__ */ new Set(["manual", "automatic", "automatic-immediate"]);
        if (typeof val === "string" && validOptions.has(val)) {
          return val;
        }
        return "automatic";
      }
    },
    /**
     * By default the route object returned by the auto-imported `useRoute()` composable
     * is kept in sync with the current page in view in `<NuxtPage>`. This is not true for
     * `vue-router`'s exported `useRoute` or for the default `$route` object available in your
     * Vue templates.
     *
     * By enabling this option a mixin will be injected to keep the `$route` template object
     * in sync with Nuxt's managed `useRoute()`.
     */
    templateRouteInjection: true,
    /**
     * Whether to restore Nuxt app state from `sessionStorage` when reloading the page
     * after a chunk error or manual `reloadNuxtApp()` call.
     *
     * To avoid hydration errors, it will be applied only after the Vue app has been mounted,
     * meaning there may be a flicker on initial load.
     *
     * Consider carefully before enabling this as it can cause unexpected behavior, and
     * consider providing explicit keys to `useState` as auto-generated keys may not match
     * across builds.
     * @type {boolean}
     */
    restoreState: false,
    /** Render JSON payloads with support for revivifying complex types. */
    renderJsonPayloads: true,
    /**
     * Disable vue server renderer endpoint within nitro.
     */
    noVueServer: false,
    /**
     * When this option is enabled (by default) payload of pages that are prerendered are extracted
     * @type {boolean | undefined}
     */
    payloadExtraction: true,
    /**
     * Whether to enable the experimental `<NuxtClientFallback>` component for rendering content on the client
     * if there's an error in SSR.
     */
    clientFallback: false,
    /** Enable cross-origin prefetch using the Speculation Rules API. */
    crossOriginPrefetch: false,
    /**
     * Enable View Transition API integration with client-side router.
     * @see [View Transitions API](https://developer.chrome.com/docs/web-platform/view-transitions)
     * @type {boolean | 'always'}
     */
    viewTransition: false,
    /**
     * Write early hints when using node server.
     * @note nginx does not support 103 Early hints in the current version.
     */
    writeEarlyHints: false,
    /**
     * Experimental component islands support with `<NuxtIsland>` and `.island.vue` files.
     *
     * By default it is set to 'auto', which means it will be enabled only when there are islands,
     * server components or server pages in your app.
     * @type {true | 'auto' | 'local' | 'local+remote' | Partial<{ remoteIsland: boolean, selectiveClient: boolean | 'deep' }> | false}
     */
    componentIslands: {
      $resolve: (val) => {
        if (val === "local+remote") {
          return { remoteIsland: true };
        }
        if (val === "local") {
          return true;
        }
        return val ?? "auto";
      }
    },
    /**
     * Config schema support
     * @see [Nuxt Issue #15592](https://github.com/nuxt/nuxt/issues/15592)
     * @deprecated This option will no longer be configurable in Nuxt v4
     */
    configSchema: {
      async $resolve(val, get) {
        const isV4 = (await get("future")).compatibilityVersion === 4;
        if (isV4 && val === false) {
          console.warn("Enabling `experimental.configSchema` in v4 compatibility mode as it will no longer be configurable in Nuxt v4.");
          return true;
        }
        return typeof val === "boolean" ? val : true;
      }
    },
    /**
     * Whether or not to add a compatibility layer for modules, plugins or user code relying on the old
     * `@vueuse/head` API.
     *
     * This is disabled to reduce the client-side bundle by ~0.5kb.
     * @deprecated This feature will be removed in Nuxt v4.
     */
    polyfillVueUseHead: {
      async $resolve(val, get) {
        const isV4 = (await get("future")).compatibilityVersion === 4;
        if (isV4 && val === true) {
          console.warn("Disabling `experimental.polyfillVueUseHead` in v4 compatibility mode as it will no longer be configurable in Nuxt v4.");
          return false;
        }
        return typeof val === "boolean" ? val : false;
      }
    },
    /**
     * Allow disabling Nuxt SSR responses by setting the `x-nuxt-no-ssr` header.
     * @deprecated This feature will be removed in Nuxt v4.
     */
    respectNoSSRHeader: {
      async $resolve(val, get) {
        const isV4 = (await get("future")).compatibilityVersion === 4;
        if (isV4 && val === true) {
          console.warn("Disabling `experimental.respectNoSSRHeader` in v4 compatibility mode as it will no longer be configurable in Nuxt v4.");
          return false;
        }
        return typeof val === "boolean" ? val : false;
      }
    },
    /** Resolve `~`, `~~`, `@` and `@@` aliases located within layers with respect to their layer source and root directories. */
    localLayerAliases: true,
    /** Enable the new experimental typed router using [unplugin-vue-router](https://github.com/posva/unplugin-vue-router). */
    typedPages: false,
    /**
     * Use app manifests to respect route rules on client-side.
     */
    appManifest: true,
    /**
     * Set the time interval (in ms) to check for new builds. Disabled when `experimental.appManifest` is `false`.
     *
     * Set to `false` to disable.
     * @type {number | false}
     */
    checkOutdatedBuildInterval: 1e3 * 60 * 60,
    /**
     * Set an alternative watcher that will be used as the watching service for Nuxt.
     *
     * Nuxt uses 'chokidar-granular' if your source directory is the same as your root
     * directory . This will ignore top-level directories (like `node_modules` and `.git`)
     * that are excluded from watching.
     *
     * You can set this instead to `parcel` to use `@parcel/watcher`, which may improve
     * performance in large projects or on Windows platforms.
     *
     * You can also set this to `chokidar` to watch all files in your source directory.
     * @see [chokidar](https://github.com/paulmillr/chokidar)
     * @see [@parcel/watcher](https://github.com/parcel-bundler/watcher)
     * @type {'chokidar' | 'parcel' | 'chokidar-granular'}
     */
    watcher: {
      $resolve: async (val, get) => {
        const validOptions = /* @__PURE__ */ new Set(["chokidar", "parcel", "chokidar-granular"]);
        if (typeof val === "string" && validOptions.has(val)) {
          return val;
        }
        const [srcDir, rootDir] = await Promise.all([get("srcDir"), get("rootDir")]);
        if (srcDir === rootDir) {
          return "chokidar-granular";
        }
        return "chokidar";
      }
    },
    /**
     * Enable native async context to be accessible for nested composables
     * @see [Nuxt PR #20918](https://github.com/nuxt/nuxt/pull/20918)
     */
    asyncContext: false,
    /**
     * Use new experimental head optimisations:
     *
     * - Add the capo.js head plugin in order to render tags in of the head in a more performant way.
     * - Uses the hash hydration plugin to reduce initial hydration
     *
     * @see [Nuxt Discussion #22632](https://github.com/nuxt/nuxt/discussions/22632)
     */
    headNext: true,
    /**
     * Allow defining `routeRules` directly within your `~/pages` directory using `defineRouteRules`.
     *
     * Rules are converted (based on the path) and applied for server requests. For example, a rule
     * defined in `~/pages/foo/bar.vue` will be applied to `/foo/bar` requests. A rule in `~/pages/foo/[id].vue`
     * will be applied to `/foo/**` requests.
     *
     * For more control, such as if you are using a custom `path` or `alias` set in the page's `definePageMeta`, you
     * should set `routeRules` directly within your `nuxt.config`.
     */
    inlineRouteRules: false,
    /**
     * Allow exposing some route metadata defined in `definePageMeta` at build-time to modules (alias, name, path, redirect, props, middleware).
     *
     * This only works with static or strings/arrays rather than variables or conditional assignment.
     *
     * @see [Nuxt Issues #24770](https://github.com/nuxt/nuxt/issues/24770)
     * @type {boolean | 'after-resolve'}
     */
    scanPageMeta: {
      async $resolve(val, get) {
        return typeof val === "boolean" || val === "after-resolve" ? val : (await get("future")).compatibilityVersion === 4 ? "after-resolve" : true;
      }
    },
    /**
     * Configure additional keys to extract from the page metadata when using `scanPageMeta`.
     *
     * This allows modules to access additional metadata from the page metadata. It's recommended
     * to augment the NuxtPage types with your keys.
     *
     * @type {string[]}
     */
    extraPageMetaExtractionKeys: [],
    /**
     * Automatically share payload _data_ between pages that are prerendered. This can result in a significant
     * performance improvement when prerendering sites that use `useAsyncData` or `useFetch` and fetch the same
     * data in different pages.
     *
     * It is particularly important when enabling this feature to make sure that any unique key of your data
     * is always resolvable to the same data. For example, if you are using `useAsyncData` to fetch
     * data related to a particular page, you should provide a key that uniquely matches that data. (`useFetch`
     * should do this automatically for you.)
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
    sharedPrerenderData: {
      async $resolve(val, get) {
        return typeof val === "boolean" ? val : (await get("future")).compatibilityVersion === 4;
      }
    },
    /**
     * Enables CookieStore support to listen for cookie updates (if supported by the browser) and refresh `useCookie` ref values.
     * @see [CookieStore](https://developer.mozilla.org/en-US/docs/Web/API/CookieStore)
     */
    cookieStore: true,
    /**
     * This allows specifying the default options for core Nuxt components and composables.
     *
     * These options will likely be moved elsewhere in the future, such as into `app.config` or into the
     * `app/` directory.
     */
    defaults: {
      /** @type {typeof import('nuxt/app')['NuxtLinkOptions']} */
      nuxtLink: {
        componentName: "NuxtLink",
        prefetch: true,
        prefetchOn: {
          visibility: true
        }
      },
      /**
       * Options that apply to `useAsyncData` (and also therefore `useFetch`)
       */
      useAsyncData: {
        /** @type {'undefined' | 'null'} */
        value: {
          async $resolve(val, get) {
            const validOptions = ["undefined", "null"];
            return typeof val === "string" && validOptions.includes(val) ? val : (await get("future")).compatibilityVersion === 4 ? "undefined" : "null";
          }
        },
        /** @type {'undefined' | 'null'} */
        errorValue: {
          async $resolve(val, get) {
            const validOptions = ["undefined", "null"];
            return typeof val === "string" && validOptions.includes(val) ? val : (await get("future")).compatibilityVersion === 4 ? "undefined" : "null";
          }
        },
        deep: {
          async $resolve(val, get) {
            return typeof val === "boolean" ? val : (await get("future")).compatibilityVersion !== 4;
          }
        }
      },
      /** @type {Pick<typeof import('ofetch')['FetchOptions'], 'timeout' | 'retry' | 'retryDelay' | 'retryStatusCodes'>} */
      useFetch: {}
    },
    /**
     * Automatically polyfill Node.js imports in the client build using `unenv`.
     * @see [unenv](https://github.com/unjs/unenv)
     *
     * **Note:** To make globals like `Buffer` work in the browser, you need to manually inject them.
     *
     * ```ts
     * import { Buffer } from 'node:buffer'
     *
     * globalThis.Buffer = globalThis.Buffer || Buffer
     * ```
     * @type {boolean}
     */
    clientNodeCompat: false,
    /**
     * Whether to use `lodash.template` to compile Nuxt templates.
     *
     * This flag will be removed with the release of v4 and exists only for
     * advance testing within Nuxt v3.12+ or in [the nightly release channel](/docs/guide/going-further/nightly-release-channel).
     */
    compileTemplate: {
      async $resolve(val, get) {
        return typeof val === "boolean" ? val : (await get("future")).compatibilityVersion !== 4;
      }
    },
    /**
     * Whether to provide a legacy `templateUtils` object (with `serialize`,
     * `importName` and `importSources`) when compiling Nuxt templates.
     *
     * This flag will be removed with the release of v4 and exists only for
     * advance testing within Nuxt v3.12+ or in [the nightly release channel](/docs/guide/going-further/nightly-release-channel).
     */
    templateUtils: {
      async $resolve(val, get) {
        return typeof val === "boolean" ? val : (await get("future")).compatibilityVersion !== 4;
      }
    },
    /**
     * Whether to provide relative paths in the `builder:watch` hook.
     *
     * This flag will be removed with the release of v4 and exists only for
     * advance testing within Nuxt v3.12+ or in [the nightly release channel](/docs/guide/going-further/nightly-release-channel).
     */
    relativeWatchPaths: {
      async $resolve(val, get) {
        return typeof val === "boolean" ? val : (await get("future")).compatibilityVersion !== 4;
      }
    },
    /**
     * Whether `clear` and `clearNuxtData` should reset async data to its _default_ value or update
     * it to `null`/`undefined`.
     */
    resetAsyncDataToUndefined: {
      async $resolve(val, get) {
        return typeof val === "boolean" ? val : (await get("future")).compatibilityVersion !== 4;
      }
    },
    /**
     * Wait for a single animation frame before navigation, which gives an opportunity
     * for the browser to repaint, acknowledging user interaction.
     *
     * It can reduce INP when navigating on prerendered routes.
     */
    navigationRepaint: true,
    /**
     * Cache Nuxt/Nitro build artifacts based on a hash of the configuration and source files.
     *
     * This only works for source files within `srcDir` and `serverDir` for the Vue/Nitro parts of your app.
     */
    buildCache: false,
    /**
     * Ensure that auto-generated Vue component names match the full component name
     * you would use to auto-import the component.
     */
    normalizeComponentNames: {
      $resolve: async (val, get) => {
        return typeof val === "boolean" ? val : (await get("future")).compatibilityVersion === 4;
      }
    },
    /**
     * Keep showing the spa-loading-template until suspense:resolve
     * @see [Nuxt Issues #24770](https://github.com/nuxt/nuxt/issues/21721)
     * @type {'body' | 'within'}
     */
    spaLoadingTemplateLocation: {
      $resolve: async (val, get) => {
        const validOptions = /* @__PURE__ */ new Set(["body", "within"]);
        return typeof val === "string" && validOptions.has(val) ? val : (await get("future")).compatibilityVersion === 4 ? "body" : "within";
      }
    },
    /**
     * Enable timings for Nuxt application hooks in the performance panel of Chromium-based browsers.
     *
     * This feature adds performance markers for Nuxt hooks, allowing you to track their execution time
     * in the browser's Performance tab. This is particularly useful for debugging performance issues.
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
     * @see [Chrome DevTools Performance API](https://developer.chrome.com/docs/devtools/performance/extension#tracks)
     */
    browserDevtoolsTiming: {
      $resolve: async (val, get) => typeof val === "boolean" ? val : await get("dev")
    },
    /**
     * Record mutations to `nuxt.options` in module context, helping to debug configuration changes
     * made by modules during the Nuxt initialization phase.
     *
     * When enabled, Nuxt will track which modules modify configuration options, making it
     * easier to trace unexpected configuration changes.
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
    debugModuleMutation: {
      $resolve: async (val, get) => {
        return typeof val === "boolean" ? val : Boolean(await get("debug"));
      }
    },
    /**
     * Enable automatic configuration of hydration strategies for `<Lazy>` components.
     *
     * This feature intelligently determines when to hydrate lazy components based on
     * visibility, idle time, or other triggers, improving performance by deferring
     * hydration of components until they're needed.
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
    lazyHydration: {
      $resolve: (val) => {
        return typeof val === "boolean" ? val : true;
      }
    },
    /**
     * Disable resolving imports into Nuxt templates from the path of the module that added the template.
     *
     * By default, Nuxt attempts to resolve imports in templates relative to the module that added them.
     * Setting this to `false` disables this behavior, which may be useful if you're experiencing
     * resolution conflicts in certain environments.
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
    templateImportResolution: true,
    /**
     * Whether to clean up Nuxt static and asyncData caches on route navigation.
     *
     * Nuxt will automatically purge cached data from `useAsyncData` and `nuxtApp.static.data`. This helps prevent memory leaks
     * and ensures fresh data is loaded when needed, but it is possible to disable it.
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
    purgeCachedData: {
      $resolve: (val) => {
        return typeof val === "boolean" ? val : true;
      }
    },
    /**
     * Whether to call and use the result from `getCachedData` on manual refresh for `useAsyncData` and `useFetch`.
     */
    granularCachedData: {
      $resolve: async (val, get) => {
        return typeof val === "boolean" ? val : (await get("future")).compatibilityVersion === 4;
      }
    },
    /**
     * Whether to run `useFetch` when the key changes, even if it is set to `immediate: false` and it has not been triggered yet.
     *
     * `useFetch` and `useAsyncData` will always run when the key changes if `immediate: true` or if it has been already triggered.
     */
    alwaysRunFetchOnKeyChange: {
      $resolve: async (val, get) => {
        return typeof val === "boolean" ? val : (await get("future")).compatibilityVersion !== 4;
      }
    },
    /**
     * Whether to parse `error.data` when rendering a server error page.
     */
    parseErrorData: {
      $resolve: async (val, get) => {
        return typeof val === "boolean" ? val : (await get("future")).compatibilityVersion === 4;
      }
    },
    /**
     * Whether Nuxt should stop if a Nuxt module is incompatible.
     */
    enforceModuleCompatibility: false,
    /**
     * For `useAsyncData` and `useFetch`, whether `pending` should be `true` when data has not yet started to be fetched.
     */
    pendingWhenIdle: {
      $resolve: async (val, get) => {
        return typeof val === "boolean" ? val : (await get("future")).compatibilityVersion !== 4;
      }
    }
  }
});

const generate = defineResolvers({
  generate: {
    /**
     * The routes to generate.
     *
     * If you are using the crawler, this will be only the starting point for route generation.
     * This is often necessary when using dynamic routes.
     *
     * It is preferred to use `nitro.prerender.routes`.
     * @example
     * ```js
     * routes: ['/users/1', '/users/2', '/users/3']
     * ```
     * @type {string | string[]}
     */
    routes: [],
    /**
     * This option is no longer used. Instead, use `nitro.prerender.ignore`.
     * @deprecated
     */
    exclude: []
  }
});

const internal = defineResolvers({
  /** @private */
  _majorVersion: 3,
  /** @private */
  _legacyGenerate: false,
  /** @private */
  _start: false,
  /** @private */
  _build: false,
  /** @private */
  _generate: false,
  /** @private */
  _prepare: false,
  /** @private */
  _cli: false,
  /** @private */
  _requiredModules: {},
  /**
   * @private
   * @type {{ dotenv?: boolean | import('c12').DotenvOptions }}
   */
  _loadOptions: void 0,
  /** @private */
  _nuxtConfigFile: void 0,
  /** @private */
  _nuxtConfigFiles: [],
  /** @private */
  appDir: "",
  /**
   * @private
   * @type {Array<{ meta: typeof import('../src/types/module').ModuleMeta; module: typeof import('../src/types/module').NuxtModule, timings?: Record<string, number | undefined>; entryPath?: string }>}
   */
  _installedModules: [],
  /** @private */
  _modules: []
});

const nitro = defineResolvers({
  /**
   * Configuration for Nitro.
   * @see [Nitro configuration docs](https://nitro.build/config/)
   * @type {typeof import('nitropack')['NitroConfig']}
   */
  nitro: {
    runtimeConfig: {
      $resolve: async (val, get) => {
        const runtimeConfig = await get("runtimeConfig");
        return {
          ...runtimeConfig,
          app: {
            ...runtimeConfig.app,
            baseURL: runtimeConfig.app.baseURL.startsWith("./") ? runtimeConfig.app.baseURL.slice(1) : runtimeConfig.app.baseURL
          },
          nitro: {
            envPrefix: "NUXT_",
            ...runtimeConfig.nitro
          }
        };
      }
    },
    routeRules: {
      $resolve: async (val, get) => {
        return {
          ...await get("routeRules"),
          ...val && typeof val === "object" ? val : {}
        };
      }
    }
  },
  /**
   * Global route options applied to matching server routes.
   * @experimental This is an experimental feature and API may change in the future.
   * @see [Nitro route rules documentation](https://nitro.build/config/#routerules)
   * @type {typeof import('nitropack')['NitroConfig']['routeRules']}
   */
  routeRules: {},
  /**
   * Nitro server handlers.
   *
   * Each handler accepts the following options:
   *
   * - handler: The path to the file defining the handler.
   * - route: The route under which the handler is available. This follows the conventions of [rou3](https://github.com/unjs/rou3).
   * - method: The HTTP method of requests that should be handled.
   * - middleware: Specifies whether it is a middleware handler.
   * - lazy: Specifies whether to use lazy loading to import the handler.
   *
   * @see [`server/` directory documentation](https://nuxt.com/docs/guide/directory-structure/server)
   * @note Files from `server/api`, `server/middleware` and `server/routes` will be automatically registered by Nuxt.
   * @example
   * ```js
   * serverHandlers: [
   *   { route: '/path/foo/**:name', handler: '~/server/foohandler.ts' }
   * ]
   * ```
   * @type {typeof import('nitropack')['NitroEventHandler'][]}
   */
  serverHandlers: [],
  /**
   * Nitro development-only server handlers.
   * @see [Nitro server routes documentation](https://nitro.build/guide/routing)
   * @type {typeof import('nitropack')['NitroDevEventHandler'][]}
   */
  devServerHandlers: []
});

const ensureItemIsLast = (item) => (arr) => {
  const index = arr.indexOf(item);
  if (index !== -1) {
    arr.splice(index, 1);
    arr.push(item);
  }
  return arr;
};
const orderPresets = {
  cssnanoLast: ensureItemIsLast("cssnano"),
  autoprefixerLast: ensureItemIsLast("autoprefixer"),
  autoprefixerAndCssnanoLast(names) {
    return orderPresets.cssnanoLast(orderPresets.autoprefixerLast(names));
  }
};
const postcss = defineResolvers({
  postcss: {
    /**
     * A strategy for ordering PostCSS plugins.
     *
     * @type {'cssnanoLast' | 'autoprefixerLast' | 'autoprefixerAndCssnanoLast' | string[] | ((names: string[]) => string[])}
     */
    order: {
      $resolve: (val) => {
        if (typeof val === "string") {
          if (!(val in orderPresets)) {
            throw new Error(`[nuxt] Unknown PostCSS order preset: ${val}`);
          }
          return orderPresets[val];
        }
        if (typeof val === "function") {
          return val;
        }
        if (Array.isArray(val)) {
          return val;
        }
        return orderPresets.autoprefixerAndCssnanoLast;
      }
    },
    /**
     * Options for configuring PostCSS plugins.
     *
     * @see [PostCSS docs](https://postcss.org/)
     * @type {Record<string, unknown> & { autoprefixer?: typeof import('autoprefixer').Options; cssnano?: typeof import('cssnano').Options }}
     */
    plugins: {
      /**
       * Plugin to parse CSS and add vendor prefixes to CSS rules.
       *
       * @see [`autoprefixer`](https://github.com/postcss/autoprefixer)
       */
      autoprefixer: {},
      /**
       * @see [`cssnano` configuration options](https://cssnano.github.io/cssnano/docs/config-file/#configuration-options)
       */
      cssnano: {
        $resolve: async (val, get) => {
          if (val || val === false) {
            return val;
          }
          if (await get("dev")) {
            return false;
          }
          return {};
        }
      }
    }
  }
});

const router = defineResolvers({
  router: {
    /**
     * Additional router options passed to `vue-router`. On top of the options for `vue-router`,
     * Nuxt offers additional options to customize the router (see below).
     * @note Only JSON serializable options should be passed by Nuxt config.
     * For more control, you can use `app/router.options.ts` file.
     * @see [Vue Router documentation](https://router.vuejs.org/api/interfaces/routeroptions.html).
     * @type {typeof import('../src/types/router').RouterConfigSerializable}
     */
    options: {
      /**
       * You can enable hash history in SPA mode. In this mode, router uses a hash character (#) before
       * the actual URL that is internally passed. When enabled, the
       * **URL is never sent to the server** and **SSR is not supported**.
       * @type {typeof import('../src/types/router').RouterConfigSerializable['hashMode']}
       * @default false
       */
      hashMode: false,
      /**
       * Customize the scroll behavior for hash links.
       * @type {typeof import('../src/types/router').RouterConfigSerializable['scrollBehaviorType']}
       * @default 'auto'
       */
      scrollBehaviorType: "auto"
    }
  }
});

const typescript = defineResolvers({
  /**
   * Configuration for Nuxt's TypeScript integration.
   *
   */
  typescript: {
    /**
     * TypeScript comes with certain checks to give you more safety and analysis of your program.
     * Once you’ve converted your codebase to TypeScript, you can start enabling these checks for greater safety.
     * [Read More](https://www.typescriptlang.org/docs/handbook/migrating-from-javascript.html#getting-stricter-checks)
     */
    strict: true,
    /**
     * Which builder types to include for your project.
     *
     * By default Nuxt infers this based on your `builder` option (defaulting to 'vite') but you can either turn off
     * builder environment types (with `false`) to handle this fully yourself, or opt for a 'shared' option.
     *
     * The 'shared' option is advised for module authors, who will want to support multiple possible builders.
     * @type {'vite' | 'webpack' | 'rspack' | 'shared' | false | undefined | null}
     */
    builder: {
      $resolve: (val) => {
        const validBuilderTypes = /* @__PURE__ */ new Set(["vite", "webpack", "rspack", "shared"]);
        if (typeof val === "string" && validBuilderTypes.has(val)) {
          return val;
        }
        if (val === false) {
          return false;
        }
        return null;
      }
    },
    /**
     * Modules to generate deep aliases for within `compilerOptions.paths`. This does not yet support subpaths.
     * It may be necessary when using Nuxt within a pnpm monorepo with `shamefully-hoist=false`.
     */
    hoist: {
      $resolve: (val) => {
        const defaults = [
          // Nitro auto-imported/augmented dependencies
          "nitropack/types",
          "nitropack/runtime",
          "nitropack",
          "defu",
          "h3",
          "consola",
          "ofetch",
          // Key nuxt dependencies
          "@unhead/vue",
          "@nuxt/devtools",
          "vue",
          "@vue/runtime-core",
          "@vue/compiler-sfc",
          "vue-router",
          "vue-router/auto-routes",
          "unplugin-vue-router/client",
          "@nuxt/schema",
          "nuxt"
        ];
        return val === false ? [] : Array.isArray(val) ? val.concat(defaults) : defaults;
      }
    },
    /**
     * Include parent workspace in the Nuxt project. Mostly useful for themes and module authors.
     */
    includeWorkspace: false,
    /**
     * Enable build-time type checking.
     *
     * If set to true, this will type check in development. You can restrict this to build-time type checking by setting it to `build`.
     * Requires to install `typescript` and `vue-tsc` as dev dependencies.
     * @see [Nuxt TypeScript docs](https://nuxt.com/docs/guide/concepts/typescript)
     * @type {boolean | 'build'}
     */
    typeCheck: false,
    /**
     * You can extend generated `.nuxt/tsconfig.json` using this option.
     * @type {0 extends 1 & RawVueCompilerOptions ? typeof import('pkg-types')['TSConfig'] : typeof import('pkg-types')['TSConfig'] & { vueCompilerOptions?: typeof import('@vue/language-core')['RawVueCompilerOptions'] }}
     */
    tsConfig: {},
    /**
     * Generate a `*.vue` shim.
     *
     * We recommend instead letting the [official Vue extension](https://marketplace.visualstudio.com/items?itemName=Vue.volar)
     * generate accurate types for your components.
     *
     * Note that you may wish to set this to `true` if you are using other libraries, such as ESLint,
     * that are unable to understand the type of `.vue` files.
     */
    shim: false
  }
});

const vite = defineResolvers({
  /**
   * Configuration that will be passed directly to Vite.
   *
   * @see [Vite configuration docs](https://vite.dev/config) for more information.
   * Please note that not all vite options are supported in Nuxt.
   * @type {typeof import('../src/types/config').ViteConfig & { $client?: typeof import('../src/types/config').ViteConfig, $server?: typeof import('../src/types/config').ViteConfig }}
   */
  vite: {
    root: {
      $resolve: async (val, get) => typeof val === "string" ? val : await get("srcDir")
    },
    mode: {
      $resolve: async (val, get) => typeof val === "string" ? val : await get("dev") ? "development" : "production"
    },
    define: {
      $resolve: async (_val, get) => {
        const [isDev, isDebug] = await Promise.all([get("dev"), get("debug")]);
        return {
          "__VUE_PROD_HYDRATION_MISMATCH_DETAILS__": Boolean(isDebug && (isDebug === true || isDebug.hydration)),
          "process.dev": isDev,
          "import.meta.dev": isDev,
          "process.test": isTest,
          "import.meta.test": isTest,
          ..._val && typeof _val === "object" ? _val : {}
        };
      }
    },
    resolve: {
      extensions: [".mjs", ".js", ".ts", ".jsx", ".tsx", ".json", ".vue"]
    },
    publicDir: {
      // @ts-expect-error this is missing from our `vite` types deliberately, so users do not configure it
      $resolve: (val) => {
        if (val) {
          consola.warn("Directly configuring the `vite.publicDir` option is not supported. Instead, set `dir.public`. You can read more in `https://nuxt.com/docs/api/nuxt-config#public`.");
        }
        return false;
      }
    },
    vue: {
      isProduction: {
        $resolve: async (val, get) => typeof val === "boolean" ? val : !await get("dev")
      },
      template: {
        compilerOptions: {
          $resolve: async (val, get) => val ?? (await get("vue")).compilerOptions
        },
        transformAssetUrls: {
          $resolve: async (val, get) => val ?? (await get("vue")).transformAssetUrls
        }
      },
      script: {
        hoistStatic: {
          $resolve: async (val, get) => typeof val === "boolean" ? val : (await get("vue")).compilerOptions?.hoistStatic
        }
      },
      features: {
        propsDestructure: {
          $resolve: async (val, get) => {
            if (typeof val === "boolean") {
              return val;
            }
            const vueOptions = await get("vue") || {};
            return Boolean(
              // @ts-expect-error TODO: remove in future: supporting a legacy schema
              vueOptions.script?.propsDestructure ?? vueOptions.propsDestructure
            );
          }
        }
      }
    },
    vueJsx: {
      $resolve: async (val, get) => {
        return {
          // TODO: investigate type divergence between types for @vue/compiler-core and @vue/babel-plugin-jsx
          isCustomElement: (await get("vue")).compilerOptions?.isCustomElement,
          ...typeof val === "object" ? val : {}
        };
      }
    },
    optimizeDeps: {
      esbuildOptions: {
        $resolve: async (val, get) => defu$1(val && typeof val === "object" ? val : {}, await get("esbuild.options"))
      },
      exclude: {
        $resolve: async (val, get) => [
          ...Array.isArray(val) ? val : [],
          ...(await get("build.transpile")).filter((i) => typeof i === "string"),
          "vue-demi"
        ]
      }
    },
    esbuild: {
      $resolve: async (val, get) => {
        return defu$1(val && typeof val === "object" ? val : {}, await get("esbuild.options"));
      }
    },
    clearScreen: true,
    build: {
      assetsDir: {
        $resolve: async (val, get) => typeof val === "string" ? val : (await get("app")).buildAssetsDir?.replace(/^\/+/, "")
      },
      emptyOutDir: false
    },
    server: {
      fs: {
        allow: {
          $resolve: async (val, get) => {
            const [buildDir, srcDir, rootDir, workspaceDir] = await Promise.all([get("buildDir"), get("srcDir"), get("rootDir"), get("workspaceDir")]);
            return [.../* @__PURE__ */ new Set([
              buildDir,
              srcDir,
              rootDir,
              workspaceDir,
              ...Array.isArray(val) ? val : []
            ])];
          }
        }
      }
    },
    cacheDir: {
      $resolve: async (val, get) => typeof val === "string" ? val : resolve(await get("rootDir"), "node_modules/.cache/vite")
    }
  }
});

const webpack = defineResolvers({
  webpack: {
    /**
     * Nuxt uses `webpack-bundle-analyzer` to visualize your bundles and how to optimize them.
     *
     * Set to `true` to enable bundle analysis, or pass an object with options: [for webpack](https://github.com/webpack-contrib/webpack-bundle-analyzer#options-for-plugin) or [for vite](https://github.com/btd/rollup-plugin-visualizer#options).
     * @example
     * ```js
     * analyze: {
     *   analyzerMode: 'static'
     * }
     * ```
     * @type {boolean | { enabled?: boolean } & typeof import('webpack-bundle-analyzer').BundleAnalyzerPlugin.Options}
     */
    analyze: {
      $resolve: async (val, get) => {
        const value = typeof val === "boolean" ? { enabled: val } : val && typeof val === "object" ? val : {};
        return defu(value, await get("build.analyze"));
      }
    },
    /**
     * Enable the profiler in webpackbar.
     *
     * It is normally enabled by CLI argument `--profile`.
     * @see [webpackbar](https://github.com/unjs/webpackbar#profile).
     */
    profile: process.argv.includes("--profile"),
    /**
     * Enables Common CSS Extraction.
     *
     * Using [mini-css-extract-plugin](https://github.com/webpack-contrib/mini-css-extract-plugin) under the hood, your CSS will be extracted
     * into separate files, usually one per component. This allows caching your CSS and
     * JavaScript separately.
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
     * @type {boolean | typeof import('mini-css-extract-plugin').PluginOptions}
     */
    extractCSS: true,
    /**
     * Enables CSS source map support (defaults to `true` in development).
     */
    cssSourceMap: {
      $resolve: async (val, get) => typeof val === "boolean" ? val : await get("dev")
    },
    /**
     * The polyfill library to load to provide URL and URLSearchParams.
     *
     * Defaults to `'url'` ([see package](https://www.npmjs.com/package/url)).
     */
    serverURLPolyfill: "url",
    /**
     * Customize bundle filenames.
     *
     * To understand a bit more about the use of manifests, take a look at [webpack documentation](https://webpack.js.org/guides/code-splitting/).
     * @note Be careful when using non-hashed based filenames in production
     * as most browsers will cache the asset and not detect the changes on first load.
     *
     * This example changes fancy chunk names to numerical ids:
     * @example
     * ```js
     * filenames: {
     *   chunk: ({ isDev }) => (isDev ? '[name].js' : '[id].[contenthash].js')
     * }
     * ```
     * @type {
     *  Record<
     *    string,
     *    string |
     *    ((
     *      ctx: {
     *        nuxt: import('../src/types/nuxt').Nuxt,
     *        options: import('../src/types/nuxt').Nuxt['options'],
     *        name: string,
     *        isDev: boolean,
     *        isServer: boolean,
     *        isClient: boolean,
     *        alias: { [index: string]: string | false | string[] },
     *        transpile: RegExp[]
     *      }) => string)
     *  >
     * }
     */
    filenames: {
      app: ({ isDev }) => isDev ? "[name].js" : "[contenthash:7].js",
      chunk: ({ isDev }) => isDev ? "[name].js" : "[contenthash:7].js",
      css: ({ isDev }) => isDev ? "[name].css" : "css/[contenthash:7].css",
      img: ({ isDev }) => isDev ? "[path][name].[ext]" : "img/[name].[contenthash:7].[ext]",
      font: ({ isDev }) => isDev ? "[path][name].[ext]" : "fonts/[name].[contenthash:7].[ext]",
      video: ({ isDev }) => isDev ? "[path][name].[ext]" : "videos/[name].[contenthash:7].[ext]"
    },
    /**
     * Customize the options of Nuxt's integrated webpack loaders.
     */
    loaders: {
      $resolve: async (val, get) => {
        const loaders = val && typeof val === "object" ? val : {};
        const styleLoaders = [
          "css",
          "cssModules",
          "less",
          "sass",
          "scss",
          "stylus",
          "vueStyle"
        ];
        for (const name of styleLoaders) {
          const loader = loaders[name];
          if (loader && loader.sourceMap === void 0) {
            loader.sourceMap = Boolean(
              // @ts-expect-error TODO: remove legacay configuration
              await get("build.cssSourceMap")
            );
          }
        }
        return loaders;
      },
      /**
       * @see [esbuild loader](https://github.com/esbuild-kit/esbuild-loader)
       * @type {Omit<typeof import('esbuild-loader')['LoaderOptions'], 'loader'>}
       */
      esbuild: {
        $resolve: async (val, get) => {
          return defu(val && typeof val === "object" ? val : {}, await get("esbuild.options"));
        }
      },
      /**
       * @see [`file-loader` Options](https://github.com/webpack-contrib/file-loader#options)
       * @default
       * ```ts
       * { esModule: false }
       * ```
       */
      file: { esModule: false, limit: 1e3 },
      /**
       * @see [`file-loader` Options](https://github.com/webpack-contrib/file-loader#options)
       * @default
       * ```ts
       * { esModule: false }
       * ```
       */
      fontUrl: { esModule: false, limit: 1e3 },
      /**
       * @see [`file-loader` Options](https://github.com/webpack-contrib/file-loader#options)
       * @default
       * ```ts
       * { esModule: false }
       * ```
       */
      imgUrl: { esModule: false, limit: 1e3 },
      /**
       * @see [`pug` options](https://pugjs.org/api/reference.html#options)
       * @type {typeof import('pug')['Options']}
       */
      pugPlain: {},
      /**
       * See [vue-loader](https://github.com/vuejs/vue-loader) for available options.
       * @type {Partial<typeof import('vue-loader')['VueLoaderOptions']>}
       */
      vue: {
        transformAssetUrls: {
          $resolve: async (val, get) => val ?? await get("vue.transformAssetUrls")
        },
        compilerOptions: {
          $resolve: async (val, get) => val ?? await get("vue.compilerOptions")
        },
        propsDestructure: {
          $resolve: async (val, get) => Boolean(val ?? await get("vue.propsDestructure"))
        }
      },
      /**
       * See [css-loader](https://github.com/webpack-contrib/css-loader) for available options.
       */
      css: {
        importLoaders: 0,
        /**
         * @type {boolean | { filter: (url: string, resourcePath: string) => boolean }}
         */
        url: {
          filter: (url, _resourcePath) => url[0] !== "/"
        },
        esModule: false
      },
      /**
       * See [css-loader](https://github.com/webpack-contrib/css-loader) for available options.
       */
      cssModules: {
        importLoaders: 0,
        /**
         * @type {boolean | { filter: (url: string, resourcePath: string) => boolean }}
         */
        url: {
          filter: (url, _resourcePath) => url[0] !== "/"
        },
        esModule: false,
        modules: {
          localIdentName: "[local]_[hash:base64:5]"
        }
      },
      /**
       * @see [`less-loader` Options](https://github.com/webpack-contrib/less-loader#options)
       */
      less: {},
      /**
       * @see [`sass-loader` Options](https://github.com/webpack-contrib/sass-loader#options)
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
          indentedSyntax: true
        }
      },
      /**
       * @see [`sass-loader` Options](https://github.com/webpack-contrib/sass-loader#options)
       */
      scss: {},
      /**
       * @see [`stylus-loader` Options](https://github.com/webpack-contrib/stylus-loader#options)
       */
      stylus: {},
      vueStyle: {}
    },
    /**
     * Add webpack plugins.
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
    plugins: [],
    /**
     * Hard-replaces `typeof process`, `typeof window` and `typeof document` to tree-shake bundle.
     */
    aggressiveCodeRemoval: false,
    /**
     * OptimizeCSSAssets plugin options.
     *
     * Defaults to true when `extractCSS` is enabled.
     * @see [css-minimizer-webpack-plugin documentation](https://github.com/webpack-contrib/css-minimizer-webpack-plugin).
     * @type {false | typeof import('css-minimizer-webpack-plugin').BasePluginOptions & typeof import('css-minimizer-webpack-plugin').DefinedDefaultMinimizerAndOptions<{}>}
     */
    optimizeCSS: {
      $resolve: async (val, get) => {
        if (val === false || val && typeof val === "object") {
          return val;
        }
        const extractCSS = await get("build.extractCSS");
        return extractCSS ? {} : false;
      }
    },
    /**
     * Configure [webpack optimization](https://webpack.js.org/configuration/optimization/).
     * @type {false | typeof import('webpack').Configuration['optimization']}
     */
    optimization: {
      runtimeChunk: "single",
      /** Set minimize to `false` to disable all minimizers. (It is disabled in development by default). */
      minimize: {
        $resolve: async (val, get) => typeof val === "boolean" ? val : !await get("dev")
      },
      /** You can set minimizer to a customized array of plugins. */
      minimizer: void 0,
      splitChunks: {
        chunks: "all",
        automaticNameDelimiter: "/",
        cacheGroups: {}
      }
    },
    /**
     * Customize PostCSS Loader.
     * same options as [`postcss-loader` options](https://github.com/webpack-contrib/postcss-loader#options)
     * @type {{ execute?: boolean, postcssOptions: typeof import('postcss').ProcessOptions & { plugins: Record<string, unknown> & { autoprefixer?: typeof import('autoprefixer').Options; cssnano?: typeof import('cssnano').Options } }, sourceMap?: boolean, implementation?: any }}
     */
    postcss: {
      postcssOptions: {
        plugins: {
          $resolve: async (val, get) => val && typeof val === "object" ? val : await get("postcss.plugins")
        }
      }
    },
    /**
     * See [webpack-dev-middleware](https://github.com/webpack/webpack-dev-middleware) for available options.
     * @type {typeof import('webpack-dev-middleware').Options<typeof import('http').IncomingMessage, typeof import('http').ServerResponse>}
     */
    devMiddleware: {
      stats: "none"
    },
    /**
     * See [webpack-hot-middleware](https://github.com/webpack-contrib/webpack-hot-middleware) for available options.
     * @type {typeof import('webpack-hot-middleware').MiddlewareOptions & { client?: typeof import('webpack-hot-middleware').ClientOptions }}
     */
    hotMiddleware: {},
    /**
     * Set to `false` to disable the overlay provided by [FriendlyErrorsWebpackPlugin](https://github.com/nuxt/friendly-errors-webpack-plugin).
     */
    friendlyErrors: true,
    /**
     * Filters to hide build warnings.
     * @type {Array<(warn: typeof import('webpack').WebpackError) => boolean>}
     */
    warningIgnoreFilters: [],
    /**
     * Configure [webpack experiments](https://webpack.js.org/configuration/experiments/)
     * @type {false | typeof import('webpack').Configuration['experiments']}
     */
    experiments: {}
  }
});

const index = {
  ...adhoc,
  ...app,
  ...build,
  ...common,
  ...dev,
  ...experimental,
  ...generate,
  ...internal,
  ...nitro,
  ...postcss,
  ...router,
  ...typescript,
  ...esbuild,
  ...vite,
  ...webpack
};

export { index as NuxtConfigSchema };
