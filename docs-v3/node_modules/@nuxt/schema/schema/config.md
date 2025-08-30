 

# `components`

# `imports`

## `global`
- **Type**: `boolean`
- **Default**: `false`


## `scan`
- **Type**: `boolean`
- **Default**: `true`

> Whether to scan your `composables/` and `utils/` directories for composables to auto-import. Auto-imports registered by Nuxt or other modules, such as imports from `vue` or `nuxt`, will still be enabled.


## `dirs`
- **Type**: `array`
- **Default**: `[]`

> An array of custom directories that will be auto-imported. Note that this option will not override the default directories (~/composables, ~/utils).


# `pages`
- **Type**: `boolean | { enabled?: boolean, pattern?: string | string[] }`
- **Default**: `{}`

> Whether to use the vue-router integration in Nuxt 3. If you do not provide a value it will be enabled if you have a `pages/` directory in your source folder.


Additionally, you can provide a glob pattern or an array of patterns to scan only certain files for pages.


# `telemetry`
- **Type**: `boolean | Record<string, any>`
- **Default**: `{}`

> Manually disable nuxt telemetry.


# `devtools`
- **Type**: ` { enabled: boolean, [key: string]: any } `
- **Default**: `{}`

> Enable Nuxt DevTools for development.


Breaking changes for devtools might not reflect on the version of Nuxt.


# `vue`

## `transformAssetUrls`

### `video`
- **Type**: `array`
- **Default**: `["src","poster"]`


### `source`
- **Type**: `array`
- **Default**: `["src"]`


### `img`
- **Type**: `array`
- **Default**: `["src"]`


### `image`
- **Type**: `array`
- **Default**: `["xlink:href","href"]`


### `use`
- **Type**: `array`
- **Default**: `["xlink:href","href"]`


## `compilerOptions`
- **Type**: `@vueCompilerCoreCompilerOptions`
- **Default**: `{}`

> Options for the Vue compiler that will be passed at build time.


## `runtimeCompiler`
- **Type**: `boolean`
- **Default**: `false`

> Include Vue compiler in runtime bundle.


## `propsDestructure`
- **Type**: `boolean`
- **Default**: `true`

> Enable reactive destructure for `defineProps`


## `config`
- **Type**: `any`
- **Default**: `{}`

> It is possible to pass configure the Vue app globally. Only serializable options may be set in your `nuxt.config`. All other options should be set at runtime in a Nuxt plugin..


# `app`

## `baseURL`
- **Type**: `string`
- **Default**: `"/"`

> The base path of your Nuxt application.


For example:


## `buildAssetsDir`
- **Type**: `string`
- **Default**: `"/_nuxt/"`

> The folder name for the built site assets, relative to `baseURL` (or `cdnURL` if set). This is set at build time and should not be customized at runtime.


## `cdnURL`
- **Type**: `string`
- **Default**: `""`

> An absolute URL to serve the public folder from (production-only).


For example:


## `head`

## `layoutTransition`
- **Type**: `SrcTypesConfigNuxtAppConfig['layoutTransition']`
- **Default**: `false`

> Default values for layout transitions.


This can be overridden with `definePageMeta` on an individual page. Only JSON-serializable values are allowed.


## `pageTransition`
- **Type**: `SrcTypesConfigNuxtAppConfig['pageTransition']`
- **Default**: `false`

> Default values for page transitions.


This can be overridden with `definePageMeta` on an individual page. Only JSON-serializable values are allowed.


## `viewTransition`
- **Type**: `SrcTypesConfigNuxtAppConfig['viewTransition']`
- **Default**: `false`

> Default values for view transitions.


This only has an effect when **experimental** support for View Transitions is [enabled in your nuxt.config file](/docs/getting-started/transitions#view-transitions-api-experimental).
This can be overridden with `definePageMeta` on an individual page.


## `keepalive`
- **Type**: `SrcTypesConfigNuxtAppConfig['keepalive']`
- **Default**: `false`

> Default values for KeepAlive configuration between pages.


This can be overridden with `definePageMeta` on an individual page. Only JSON-serializable values are allowed.


## `rootId`
- **Type**: `string | false`
- **Default**: `"__nuxt"`

> Customize Nuxt root element id.


## `rootTag`
- **Type**: `string`
- **Default**: `"div"`

> Customize Nuxt root element tag.


## `rootAttrs`

## `teleportTag`
- **Type**: `string`
- **Default**: `"div"`

> Customize Nuxt Teleport element tag.


## `teleportId`
- **Type**: `string | false`
- **Default**: `"teleports"`

> Customize Nuxt Teleport element id.


## `teleportAttrs`

## `spaLoaderTag`
- **Type**: `string`
- **Default**: `"div"`

> Customize Nuxt SpaLoader element tag.


## `spaLoaderAttrs`

### `id`
- **Type**: `string`
- **Default**: `"__nuxt-loader"`


# `spaLoadingTemplate`
- **Type**: `string | boolean | undefined | null`
- **Default**: `null`

> Boolean or a path to an HTML file with the contents of which will be inserted into any HTML page rendered with `ssr: false`.


- If it is unset, it will use `~/app/spa-loading-template.html` file in one of your layers, if it exists. - If it is false, no SPA loading indicator will be loaded. - If true, Nuxt will look for `~/app/spa-loading-template.html` file in one of your layers, or a
  default Nuxt image will be used.
Some good sources for spinners are [SpinKit](https://github.com/tobiasahlin/SpinKit) or [SVG Spinners](https://icones.js.org/collection/svg-spinners).


# `plugins`
- **Type**: `(SrcTypesNuxtNuxtPlugin | string)[]`
- **Default**: `[]`

> An array of nuxt app plugins.


Each plugin can be a string (which can be an absolute or relative path to a file). If it ends with `.client` or `.server` then it will be automatically loaded only in the appropriate context.
It can also be an object with `src` and `mode` keys.


# `css`
- **Type**: `string[]`
- **Default**: `[]`

> You can define the CSS files/modules/libraries you want to set globally (included in every page).


Nuxt will automatically guess the file type by its extension and use the appropriate pre-processor. You will still need to install the required loader if you need to use them.


# `unhead`

## `legacy`
- **Type**: `boolean`
- **Default**: `false`

> Enable the legacy compatibility mode for `unhead` module. This applies the following changes: - Disables Capo.js sorting - Adds the `DeprecationsPlugin`: supports `hid`, `vmid`, `children`, `body` - Adds the `PromisesPlugin`: supports promises as input


## `renderSSRHeadOptions`

# `builder`
- **Type**: `'vite' | 'webpack' | 'rspack' | { bundle: (nuxt: SrcTypesNuxtNuxt) => Promise<void> }`
- **Default**: `"@nuxt/vite-builder"`

> The builder to use for bundling the Vue part of your application.


# `sourcemap`

# `logLevel`
- **Type**: `'silent' | 'info' | 'verbose'`
- **Default**: `"info"`

> Log level when building logs.


Defaults to 'silent' when running in CI or when a TTY is not available. This option is then used as 'silent' in Vite and 'none' in Webpack


# `build`

## `transpile`
- **Type**: `Array<string | RegExp | ((ctx: { isClient?: boolean; isServer?: boolean; isDev: boolean }) => string | RegExp | false)>`
- **Default**: `[]`

> If you want to transpile specific dependencies with Babel, you can add them here. Each item in transpile can be a package name, a function, a string or regex object matching the dependency's file name.


You can also use a function to conditionally transpile. The function will receive an object ({ isDev, isServer, isClient, isModern, isLegacy }).


## `templates`
- **Type**: `SrcTypesNuxtNuxtTemplate<any>[]`
- **Default**: `[]`

> It is recommended to use `addTemplate` from `@nuxt/kit` instead of this option.


## `analyze`

# `optimization`

## `keyedComposables`
- **Type**: `Array<{ name: string, source?: string | RegExp, argumentLength: number }>`
- **Default**: `[{"name":"callOnce","argumentLength":3},{"name":"defineNuxtComponent","argumentLength":2},{"name":"useState","argumentLength":2},{"name":"useFetch","argumentLength":3},{"name":"useAsyncData","argumentLength":3},{"name":"useLazyAsyncData","argumentLength":3},{"name":"useLazyFetch","argumentLength":3}]`

> Functions to inject a key for.


As long as the number of arguments passed to the function is less than `argumentLength`, an additional magic string will be injected that can be used to deduplicate requests between server and client. You will need to take steps to handle this additional key.
The key will be unique based on the location of the function being invoked within the file.


## `treeShake`

### `composables`

#### `server`

#### `client`

## `asyncTransforms`

### `asyncFunctions`
- **Type**: `array`
- **Default**: `["defineNuxtPlugin","defineNuxtRouteMiddleware"]`


### `objectDefinitions`

#### `defineNuxtComponent`
- **Type**: `array`
- **Default**: `["asyncData","setup"]`


#### `defineNuxtPlugin`
- **Type**: `array`
- **Default**: `["setup"]`


#### `definePageMeta`
- **Type**: `array`
- **Default**: `["middleware","validate"]`


# `extends`
- **Type**: `string | [string, C12SourceOptions?] | (string | [string, C12SourceOptions?])[]`
- **Default**: `{}`

> Extend project from multiple local or remote sources.


Value should be either a string or array of strings pointing to source directories or config path relative to current config.
You can use `github:`, `gh:` `gitlab:` or `bitbucket:`


# `compatibilityDate`
- **Type**: `CompatxCompatibilityDateSpec`
- **Default**: `{}`

> Specify a compatibility date for your app.


This is used to control the behavior of presets in Nitro, Nuxt Image and other modules that may change behavior without a major version bump.
We plan to improve the tooling around this feature in the future.


# `theme`
- **Type**: `string`
- **Default**: `undefined`

> Extend project from a local or remote source.


Value should be a string pointing to source directory or config path relative to current config.
You can use `github:`, `gitlab:`, `bitbucket:` or `https://` to extend from a remote git repository.


# `rootDir`
- **Type**: `string`
- **Default**: `"/<rootDir>"`

> Define the root directory of your application.


This property can be overwritten (for example, running `nuxt ./my-app/` will set the `rootDir` to the absolute path of `./my-app/` from the current/working directory.
It is normally not needed to configure this option.


# `workspaceDir`
- **Type**: `string`
- **Default**: `"/<workspaceDir>"`

> Define the workspace directory of your application.


Often this is used when in a monorepo setup. Nuxt will attempt to detect your workspace directory automatically, but you can override it here.
It is normally not needed to configure this option.


# `srcDir`
- **Type**: `string`
- **Default**: `"/<srcDir>"`

> Define the source directory of your Nuxt application.


If a relative path is specified, it will be relative to the `rootDir`.


# `serverDir`
- **Type**: `string`
- **Default**: `"/<srcDir>/server"`

> Define the server directory of your Nuxt application, where Nitro routes, middleware and plugins are kept.


If a relative path is specified, it will be relative to your `rootDir`.


# `buildDir`
- **Type**: `string`
- **Default**: `"/<rootDir>/.nuxt"`

> Define the directory where your built Nuxt files will be placed.


Many tools assume that `.nuxt` is a hidden directory (because it starts with a `.`). If that is a problem, you can use this option to prevent that.


# `appId`
- **Type**: `string`
- **Default**: `"nuxt-app"`

> For multi-app projects, the unique id of the Nuxt application.


Defaults to `nuxt-app`.


# `buildId`
- **Type**: `string`
- **Default**: `"f2edd0e7-2595-424c-bd97-c91196e3f2d2"`

> A unique identifier matching the build. This may contain the hash of the current state of the project.


# `modulesDir`
- **Type**: `array`
- **Default**: `["/<rootDir>/node_modules"]`

> Used to set the modules directories for path resolving (for example, webpack's `resolveLoading`, `nodeExternals` and `postcss`).


The configuration path is relative to `options.rootDir` (default is current working directory).
Setting this field may be necessary if your project is organized as a yarn workspace-styled mono-repository.


# `analyzeDir`
- **Type**: `string`
- **Default**: `"/<rootDir>/.nuxt/analyze"`

> The directory where Nuxt will store the generated files when running `nuxt analyze`.


If a relative path is specified, it will be relative to your `rootDir`.


# `dev`
- **Type**: `boolean`
- **Default**: `false`

> Whether Nuxt is running in development mode.


Normally, you should not need to set this.


# `test`
- **Type**: `boolean`
- **Default**: `false`

> Whether your app is being unit tested.


# `debug`
- **Type**: `boolean | (SrcTypesDebugNuxtDebugOptions) | undefined`
- **Default**: `false`

> Set to `true` to enable debug mode.


At the moment, it prints out hook names and timings on the server, and logs hook arguments as well in the browser.
You can also set this to an object to enable specific debug options.


# `ssr`
- **Type**: `boolean`
- **Default**: `true`

> Whether to enable rendering of HTML - either dynamically (in server mode) or at generate time. If set to `false` generated pages will have no content.


# `modules`
- **Type**: `(SrcTypesModuleNuxtModule<any> | string | [SrcTypesModuleNuxtModule | string, Record<string, any>] | undefined | null | false)[]`
- **Default**: `[]`

> Modules are Nuxt extensions which can extend its core functionality and add endless integrations.


Each module is either a string (which can refer to a package, or be a path to a file), a tuple with the module as first string and the options as a second object, or an inline module function.
Nuxt tries to resolve each item in the modules array using node require path (in `node_modules`) and then will be resolved from project `srcDir` if `~` alias is used.


# `dir`

## `app`
- **Type**: `string`
- **Default**: `"app"`


## `assets`
- **Type**: `string`
- **Default**: `"assets"`

> The assets directory (aliased as `~assets` in your build).


## `layouts`
- **Type**: `string`
- **Default**: `"layouts"`

> The layouts directory, each file of which will be auto-registered as a Nuxt layout.


## `middleware`
- **Type**: `string`
- **Default**: `"middleware"`

> The middleware directory, each file of which will be auto-registered as a Nuxt middleware.


## `modules`
- **Type**: `string`
- **Default**: `"modules"`

> The modules directory, each file in which will be auto-registered as a Nuxt module.


## `pages`
- **Type**: `string`
- **Default**: `"pages"`

> The directory which will be processed to auto-generate your application page routes.


## `plugins`
- **Type**: `string`
- **Default**: `"plugins"`

> The plugins directory, each file of which will be auto-registered as a Nuxt plugin.


## `shared`
- **Type**: `string`
- **Default**: `"shared"`

> The shared directory. This directory is shared between the app and the server.


## `public`
- **Type**: `string`
- **Default**: `"public"`

> The directory containing your static files, which will be directly accessible via the Nuxt server and copied across into your `dist` folder when your app is generated.


## `static`
- **Type**: `string`
- **Default**: `"public"`


# `extensions`
- **Type**: `array`
- **Default**: `[".js",".jsx",".mjs",".ts",".tsx",".vue"]`

> The extensions that should be resolved by the Nuxt resolver.


# `alias`

# `ignoreOptions`
- **Type**: `IgnoreOptions`
- **Default**: `{}`

> Pass options directly to `node-ignore` (which is used by Nuxt to ignore files).


# `ignorePrefix`
- **Type**: `string`
- **Default**: `"-"`

> Any file in `pages/`, `layouts/`, `middleware/`, and `public/` directories will be ignored during the build process if its filename starts with the prefix specified by `ignorePrefix`. This is intended to prevent certain files from being processed or served in the built application. By default, the `ignorePrefix` is set to '-', ignoring any files starting with '-'.


# `ignore`
- **Type**: `array`
- **Default**: `["**/*.stories.{js,cts,mts,ts,jsx,tsx}","**/*.{spec,test}.{js,cts,mts,ts,jsx,tsx}","**/*.d.{cts,mts,ts}","**/.{pnpm-store,vercel,netlify,output,git,cache,data}","**/*.sock",".nuxt/analyze",".nuxt","**/-*.*"]`

> More customizable than `ignorePrefix`: all files matching glob patterns specified inside the `ignore` array will be ignored in building.


# `watch`
- **Type**: `Array<string | RegExp>`
- **Default**: `[]`

> The watch property lets you define patterns that will restart the Nuxt dev server when changed.


It is an array of strings or regular expressions. Strings should be either absolute paths or relative to the `srcDir` (and the `srcDir` of any layers). Regular expressions will be matched against the path relative to the project `srcDir` (and the `srcDir` of any layers).


# `watchers`

## `rewatchOnRawEvents`
- **Type**: `any`
- **Default**: `{}`

> An array of event types, which, when received, will cause the watcher to restart.


## `webpack`

### `aggregateTimeout`
- **Type**: `number`
- **Default**: `1000`


## `chokidar`

### `ignoreInitial`
- **Type**: `boolean`
- **Default**: `true`


### `ignorePermissionErrors`
- **Type**: `boolean`
- **Default**: `true`


# `hooks`
- **Type**: `SrcTypesHooksNuxtHooks`
- **Default**: `{}`

> Hooks are listeners to Nuxt events that are typically used in modules, but are also available in `nuxt.config`.


Internally, hooks follow a naming pattern using colons (e.g., build:done).
For ease of configuration, you can also structure them as an hierarchical object in `nuxt.config` (as below).


# `runtimeConfig`

# `appConfig`

## `nuxt`
- **Type**: `any`
- **Default**: `{}`


# `devServer`

## `https`
- **Type**: `boolean | { key: string; cert: string } | { pfx: string; passphrase: string }`
- **Default**: `false`

> Whether to enable HTTPS.


## `port`
- **Type**: `number`
- **Default**: `3000`

> Dev server listening port


## `host`
- **Type**: `string | undefined`
- **Default**: `{}`

> Dev server listening host


## `url`
- **Type**: `string`
- **Default**: `"http://localhost:3000"`

> Listening dev server URL.


This should not be set directly as it will always be overridden by the dev server with the full URL (for module and internal use).


## `loadingTemplate`
- **Type**: `(data: { loading?: string }) => string`
- **Default**: `undefined`

> Template to show a loading screen

```ts
() => any
```


## `cors`

### `origin`
- **Type**: `array`
- **Default**: `[{}]`


# `future`

## `compatibilityVersion`
- **Type**: `3 | 4`
- **Default**: `3`

> Enable early access to Nuxt v4 features or flags.


Setting `compatibilityVersion` to `4` changes defaults throughout your Nuxt configuration, but you can granularly re-enable Nuxt v3 behaviour when testing (see example). Please file issues if so, so that we can address in Nuxt or in the ecosystem.


## `multiApp`
- **Type**: `boolean`
- **Default**: `false`

> This enables early access to the experimental multi-app support.


## `typescriptBundlerResolution`
- **Type**: `boolean`
- **Default**: `true`

> This enables 'Bundler' module resolution mode for TypeScript, which is the recommended setting for frameworks like Nuxt and Vite.


It improves type support when using modern libraries with `exports`.
You can set it to false to use the legacy 'Node' mode, which is the default for TypeScript.


# `features`

## `inlineStyles`
- **Type**: `boolean | ((id?: string) => boolean)`
- **Default**: `true`

> Inline styles when rendering HTML (currently vite only).


You can also pass a function that receives the path of a Vue component and returns a boolean indicating whether to inline the styles for that component.


## `devLogs`
- **Type**: `boolean | 'silent'`
- **Default**: `false`

> Stream server logs to the client as you are developing. These logs can be handled in the `dev:ssr-logs` hook.


If set to `silent`, the logs will not be printed to the browser console.


## `noScripts`
- **Type**: `'production' | 'all' | boolean`
- **Default**: `false`

> Turn off rendering of Nuxt scripts and JS resource hints. You can also disable scripts more granularly within `routeRules`.


If set to 'production' or `true`, JS will be disabled in production mode only.


# `experimental`

## `decorators`
- **Type**: `boolean`
- **Default**: `false`

> Enable to use experimental decorators in Nuxt and Nitro.


## `asyncEntry`
- **Type**: `boolean`
- **Default**: `false`

> Set to true to generate an async entry point for the Vue bundle (for module federation support).


## `externalVue`
- **Type**: `boolean`
- **Default**: `true`

> Externalize `vue`, `@vue/*` and `vue-router` when building.


## `treeshakeClientOnly`
- **Type**: `boolean`
- **Default**: `true`

> Tree shakes contents of client-only components from server bundle.


## `emitRouteChunkError`
- **Type**: `false | 'manual' | 'automatic' | 'automatic-immediate'`
- **Default**: `"automatic"`

> Emit `app:chunkError` hook when there is an error loading vite/webpack chunks.


By default, Nuxt will also perform a reload of the new route when a chunk fails to load when navigating to a new route (`automatic`).
Setting `automatic-immediate` will lead Nuxt to perform a reload of the current route right when a chunk fails to load (instead of waiting for navigation).
You can disable automatic handling by setting this to `false`, or handle chunk errors manually by setting it to `manual`.


## `templateRouteInjection`
- **Type**: `boolean`
- **Default**: `true`

> By default the route object returned by the auto-imported `useRoute()` composable is kept in sync with the current page in view in `<NuxtPage>`. This is not true for `vue-router`'s exported `useRoute` or for the default `$route` object available in your Vue templates.


By enabling this option a mixin will be injected to keep the `$route` template object in sync with Nuxt's managed `useRoute()`.


## `restoreState`
- **Type**: `boolean`
- **Default**: `false`

> Whether to restore Nuxt app state from `sessionStorage` when reloading the page after a chunk error or manual `reloadNuxtApp()` call.


To avoid hydration errors, it will be applied only after the Vue app has been mounted, meaning there may be a flicker on initial load.
Consider carefully before enabling this as it can cause unexpected behavior, and consider providing explicit keys to `useState` as auto-generated keys may not match across builds.


## `renderJsonPayloads`
- **Type**: `boolean`
- **Default**: `true`

> Render JSON payloads with support for revivifying complex types.


## `noVueServer`
- **Type**: `boolean`
- **Default**: `false`

> Disable vue server renderer endpoint within nitro.


## `payloadExtraction`
- **Type**: `boolean | undefined`
- **Default**: `true`

> When this option is enabled (by default) payload of pages that are prerendered are extracted


## `clientFallback`
- **Type**: `boolean`
- **Default**: `false`

> Whether to enable the experimental `<NuxtClientFallback>` component for rendering content on the client if there's an error in SSR.


## `crossOriginPrefetch`
- **Type**: `boolean`
- **Default**: `false`

> Enable cross-origin prefetch using the Speculation Rules API.


## `viewTransition`
- **Type**: `boolean | 'always'`
- **Default**: `false`

> Enable View Transition API integration with client-side router.


## `writeEarlyHints`
- **Type**: `boolean`
- **Default**: `false`

> Write early hints when using node server.


## `componentIslands`
- **Type**: `true | 'auto' | 'local' | 'local+remote' | Partial<{ remoteIsland: boolean, selectiveClient: boolean | 'deep' }> | false`
- **Default**: `"auto"`

> Experimental component islands support with `<NuxtIsland>` and `.island.vue` files.


By default it is set to 'auto', which means it will be enabled only when there are islands, server components or server pages in your app.


## `configSchema`
- **Type**: `boolean`
- **Default**: `true`

> Config schema support


## `polyfillVueUseHead`
- **Type**: `boolean`
- **Default**: `false`

> Whether or not to add a compatibility layer for modules, plugins or user code relying on the old `@vueuse/head` API.


This is disabled to reduce the client-side bundle by ~0.5kb.


## `respectNoSSRHeader`
- **Type**: `boolean`
- **Default**: `false`

> Allow disabling Nuxt SSR responses by setting the `x-nuxt-no-ssr` header.


## `localLayerAliases`
- **Type**: `boolean`
- **Default**: `true`

> Resolve `~`, `~~`, `@` and `@@` aliases located within layers with respect to their layer source and root directories.


## `typedPages`
- **Type**: `boolean`
- **Default**: `false`

> Enable the new experimental typed router using [unplugin-vue-router](https://github.com/posva/unplugin-vue-router).


## `appManifest`
- **Type**: `boolean`
- **Default**: `true`

> Use app manifests to respect route rules on client-side.


## `checkOutdatedBuildInterval`
- **Type**: `number | false`
- **Default**: `3600000`

> Set the time interval (in ms) to check for new builds. Disabled when `experimental.appManifest` is `false`.


Set to `false` to disable.


## `watcher`
- **Type**: `'chokidar' | 'parcel' | 'chokidar-granular'`
- **Default**: `"chokidar"`

> Set an alternative watcher that will be used as the watching service for Nuxt.


Nuxt uses 'chokidar-granular' if your source directory is the same as your root directory . This will ignore top-level directories (like `node_modules` and `.git`) that are excluded from watching.
You can set this instead to `parcel` to use `@parcel/watcher`, which may improve performance in large projects or on Windows platforms.
You can also set this to `chokidar` to watch all files in your source directory.


## `asyncContext`
- **Type**: `boolean`
- **Default**: `false`

> Enable native async context to be accessible for nested composables


## `headNext`
- **Type**: `boolean`
- **Default**: `true`

> Use new experimental head optimisations:


- Add the capo.js head plugin in order to render tags in of the head in a more performant way. - Uses the hash hydration plugin to reduce initial hydration


## `inlineRouteRules`
- **Type**: `boolean`
- **Default**: `false`

> Allow defining `routeRules` directly within your `~/pages` directory using `defineRouteRules`.


Rules are converted (based on the path) and applied for server requests. For example, a rule defined in `~/pages/foo/bar.vue` will be applied to `/foo/bar` requests. A rule in `~/pages/foo/[id].vue` will be applied to `/foo/**` requests.
For more control, such as if you are using a custom `path` or `alias` set in the page's `definePageMeta`, you should set `routeRules` directly within your `nuxt.config`.


## `scanPageMeta`
- **Type**: `boolean | 'after-resolve'`
- **Default**: `true`

> Allow exposing some route metadata defined in `definePageMeta` at build-time to modules (alias, name, path, redirect, props, middleware).


This only works with static or strings/arrays rather than variables or conditional assignment.


## `extraPageMetaExtractionKeys`
- **Type**: `string[]`
- **Default**: `[]`

> Configure additional keys to extract from the page metadata when using `scanPageMeta`.


This allows modules to access additional metadata from the page metadata. It's recommended to augment the NuxtPage types with your keys.


## `sharedPrerenderData`
- **Type**: `boolean`
- **Default**: `false`

> Automatically share payload _data_ between pages that are prerendered. This can result in a significant performance improvement when prerendering sites that use `useAsyncData` or `useFetch` and fetch the same data in different pages.


It is particularly important when enabling this feature to make sure that any unique key of your data is always resolvable to the same data. For example, if you are using `useAsyncData` to fetch data related to a particular page, you should provide a key that uniquely matches that data. (`useFetch` should do this automatically for you.)


## `cookieStore`
- **Type**: `boolean`
- **Default**: `true`

> Enables CookieStore support to listen for cookie updates (if supported by the browser) and refresh `useCookie` ref values.


## `defaults`

### `nuxtLink`

#### `componentName`
- **Type**: `string`
- **Default**: `"NuxtLink"`


#### `prefetch`
- **Type**: `boolean`
- **Default**: `true`


#### `prefetchOn`

##### `visibility`
- **Type**: `boolean`
- **Default**: `true`


### `useAsyncData`

#### `value`
- **Type**: `'undefined' | 'null'`
- **Default**: `"null"`


#### `errorValue`
- **Type**: `'undefined' | 'null'`
- **Default**: `"null"`


#### `deep`
- **Type**: `boolean`
- **Default**: `true`


### `useFetch`
- **Type**: `Pick<OfetchFetchOptions, 'timeout' | 'retry' | 'retryDelay' | 'retryStatusCodes'>`
- **Default**: `{}`


## `clientNodeCompat`
- **Type**: `boolean`
- **Default**: `false`

> Automatically polyfill Node.js imports in the client build using `unenv`.


## `compileTemplate`
- **Type**: `boolean`
- **Default**: `true`

> Whether to use `lodash.template` to compile Nuxt templates.


This flag will be removed with the release of v4 and exists only for advance testing within Nuxt v3.12+ or in [the nightly release channel](/docs/guide/going-further/nightly-release-channel).


## `templateUtils`
- **Type**: `boolean`
- **Default**: `true`

> Whether to provide a legacy `templateUtils` object (with `serialize`, `importName` and `importSources`) when compiling Nuxt templates.


This flag will be removed with the release of v4 and exists only for advance testing within Nuxt v3.12+ or in [the nightly release channel](/docs/guide/going-further/nightly-release-channel).


## `relativeWatchPaths`
- **Type**: `boolean`
- **Default**: `true`

> Whether to provide relative paths in the `builder:watch` hook.


This flag will be removed with the release of v4 and exists only for advance testing within Nuxt v3.12+ or in [the nightly release channel](/docs/guide/going-further/nightly-release-channel).


## `resetAsyncDataToUndefined`
- **Type**: `boolean`
- **Default**: `true`

> Whether `clear` and `clearNuxtData` should reset async data to its _default_ value or update it to `null`/`undefined`.


## `navigationRepaint`
- **Type**: `boolean`
- **Default**: `true`

> Wait for a single animation frame before navigation, which gives an opportunity for the browser to repaint, acknowledging user interaction.


It can reduce INP when navigating on prerendered routes.


## `buildCache`
- **Type**: `boolean`
- **Default**: `false`

> Cache Nuxt/Nitro build artifacts based on a hash of the configuration and source files.


This only works for source files within `srcDir` and `serverDir` for the Vue/Nitro parts of your app.


## `normalizeComponentNames`
- **Type**: `boolean`
- **Default**: `false`

> Ensure that auto-generated Vue component names match the full component name you would use to auto-import the component.


## `spaLoadingTemplateLocation`
- **Type**: `'body' | 'within'`
- **Default**: `"within"`

> Keep showing the spa-loading-template until suspense:resolve


## `browserDevtoolsTiming`
- **Type**: `boolean`
- **Default**: `false`

> Enable timings for Nuxt application hooks in the performance panel of Chromium-based browsers.


This feature adds performance markers for Nuxt hooks, allowing you to track their execution time in the browser's Performance tab. This is particularly useful for debugging performance issues.


## `debugModuleMutation`
- **Type**: `boolean`
- **Default**: `false`

> Record mutations to `nuxt.options` in module context, helping to debug configuration changes made by modules during the Nuxt initialization phase.


When enabled, Nuxt will track which modules modify configuration options, making it easier to trace unexpected configuration changes.


## `lazyHydration`
- **Type**: `boolean`
- **Default**: `true`

> Enable automatic configuration of hydration strategies for `<Lazy>` components.


This feature intelligently determines when to hydrate lazy components based on visibility, idle time, or other triggers, improving performance by deferring hydration of components until they're needed.


## `templateImportResolution`
- **Type**: `boolean`
- **Default**: `true`

> Disable resolving imports into Nuxt templates from the path of the module that added the template.


By default, Nuxt attempts to resolve imports in templates relative to the module that added them. Setting this to `false` disables this behavior, which may be useful if you're experiencing resolution conflicts in certain environments.


## `purgeCachedData`
- **Type**: `boolean`
- **Default**: `true`

> Whether to clean up Nuxt static and asyncData caches on route navigation.


Nuxt will automatically purge cached data from `useAsyncData` and `nuxtApp.static.data`. This helps prevent memory leaks and ensures fresh data is loaded when needed, but it is possible to disable it.


## `granularCachedData`
- **Type**: `boolean`
- **Default**: `false`

> Whether to call and use the result from `getCachedData` on manual refresh for `useAsyncData` and `useFetch`.


## `alwaysRunFetchOnKeyChange`
- **Type**: `boolean`
- **Default**: `true`

> Whether to run `useFetch` when the key changes, even if it is set to `immediate: false` and it has not been triggered yet.


`useFetch` and `useAsyncData` will always run when the key changes if `immediate: true` or if it has been already triggered.


## `parseErrorData`
- **Type**: `boolean`
- **Default**: `false`

> Whether to parse `error.data` when rendering a server error page.


## `enforceModuleCompatibility`
- **Type**: `boolean`
- **Default**: `false`

> Whether Nuxt should stop if a Nuxt module is incompatible.


## `pendingWhenIdle`
- **Type**: `boolean`
- **Default**: `true`

> For `useAsyncData` and `useFetch`, whether `pending` should be `true` when data has not yet started to be fetched.


# `generate`

## `routes`
- **Type**: `string | string[]`
- **Default**: `[]`

> The routes to generate.


If you are using the crawler, this will be only the starting point for route generation. This is often necessary when using dynamic routes.
It is preferred to use `nitro.prerender.routes`.


## `exclude`
- **Type**: `array`
- **Default**: `[]`

> This option is no longer used. Instead, use `nitro.prerender.ignore`.


# `_majorVersion`
- **Type**: `number`
- **Default**: `3`


# `_legacyGenerate`
- **Type**: `boolean`
- **Default**: `false`


# `_start`
- **Type**: `boolean`
- **Default**: `false`


# `_build`
- **Type**: `boolean`
- **Default**: `false`


# `_generate`
- **Type**: `boolean`
- **Default**: `false`


# `_prepare`
- **Type**: `boolean`
- **Default**: `false`


# `_cli`
- **Type**: `boolean`
- **Default**: `false`


# `_requiredModules`
- **Type**: `any`
- **Default**: `{}`


# `_loadOptions`
- **Type**: `{ dotenv?: boolean | C12DotenvOptions }`
- **Default**: `{}`


# `_nuxtConfigFile`
- **Type**: `any`
- **Default**: `{}`


# `_nuxtConfigFiles`
- **Type**: `array`
- **Default**: `[]`


# `appDir`
- **Type**: `string`
- **Default**: `""`


# `_installedModules`
- **Type**: `Array<{ meta: SrcTypesModuleModuleMeta; module: SrcTypesModuleNuxtModule, timings?: Record<string, number | undefined>; entryPath?: string }>`
- **Default**: `[]`


# `_modules`
- **Type**: `array`
- **Default**: `[]`


# `nitro`

## `runtimeConfig`

## `routeRules`

# `routeRules`
- **Type**: `NitropackNitroConfig['routeRules']`
- **Default**: `{}`

> Global route options applied to matching server routes.


# `serverHandlers`
- **Type**: `NitropackNitroEventHandler[]`
- **Default**: `[]`

> Nitro server handlers.


Each handler accepts the following options:
- handler: The path to the file defining the handler. - route: The route under which the handler is available. This follows the conventions of [rou3](https://github.com/unjs/rou3). - method: The HTTP method of requests that should be handled. - middleware: Specifies whether it is a middleware handler. - lazy: Specifies whether to use lazy loading to import the handler.


# `devServerHandlers`
- **Type**: `NitropackNitroDevEventHandler[]`
- **Default**: `[]`

> Nitro development-only server handlers.


# `postcss`

## `order`
- **Type**: `'cssnanoLast' | 'autoprefixerLast' | 'autoprefixerAndCssnanoLast' | string[] | ((names: string[]) => string[])`
- **Default**: `undefined`

> A strategy for ordering PostCSS plugins.

```ts
() => any
```


## `plugins`

### `autoprefixer`
- **Type**: `any`
- **Default**: `{}`

> Plugin to parse CSS and add vendor prefixes to CSS rules.


### `cssnano`

# `router`

## `options`

### `hashMode`
- **Type**: `SrcTypesRouterRouterConfigSerializable['hashMode']`
- **Default**: `false`

> You can enable hash history in SPA mode. In this mode, router uses a hash character (#) before the actual URL that is internally passed. When enabled, the **URL is never sent to the server** and **SSR is not supported**.


### `scrollBehaviorType`
- **Type**: `SrcTypesRouterRouterConfigSerializable['scrollBehaviorType']`
- **Default**: `"auto"`

> Customize the scroll behavior for hash links.


# `typescript`

## `strict`
- **Type**: `boolean`
- **Default**: `true`

> TypeScript comes with certain checks to give you more safety and analysis of your program. Once you’ve converted your codebase to TypeScript, you can start enabling these checks for greater safety. [Read More](https://www.typescriptlang.org/docs/handbook/migrating-from-javascript.html#getting-stricter-checks)


## `builder`
- **Type**: `'vite' | 'webpack' | 'rspack' | 'shared' | false | undefined | null`
- **Default**: `null`

> Which builder types to include for your project.


By default Nuxt infers this based on your `builder` option (defaulting to 'vite') but you can either turn off builder environment types (with `false`) to handle this fully yourself, or opt for a 'shared' option.
The 'shared' option is advised for module authors, who will want to support multiple possible builders.


## `hoist`
- **Type**: `array`
- **Default**: `["nitropack/types","nitropack/runtime","nitropack","defu","h3","consola","ofetch","@unhead/vue","@nuxt/devtools","vue","@vue/runtime-core","@vue/compiler-sfc","vue-router","vue-router/auto-routes","unplugin-vue-router/client","@nuxt/schema","nuxt"]`

> Modules to generate deep aliases for within `compilerOptions.paths`. This does not yet support subpaths. It may be necessary when using Nuxt within a pnpm monorepo with `shamefully-hoist=false`.


## `includeWorkspace`
- **Type**: `boolean`
- **Default**: `false`

> Include parent workspace in the Nuxt project. Mostly useful for themes and module authors.


## `typeCheck`
- **Type**: `boolean | 'build'`
- **Default**: `false`

> Enable build-time type checking.


If set to true, this will type check in development. You can restrict this to build-time type checking by setting it to `build`. Requires to install `typescript` and `vue-tsc` as dev dependencies.


## `tsConfig`
- **Type**: `0 extends 1 & RawVueCompilerOptions ? PkgTypesTSConfig : PkgTypesTSConfig & { vueCompilerOptions?: @vueLanguageCoreRawVueCompilerOptions }`
- **Default**: `{}`

> You can extend generated `.nuxt/tsconfig.json` using this option.


## `shim`
- **Type**: `boolean`
- **Default**: `false`

> Generate a `*.vue` shim.


We recommend instead letting the [official Vue extension](https://marketplace.visualstudio.com/items?itemName=Vue.volar) generate accurate types for your components.
Note that you may wish to set this to `true` if you are using other libraries, such as ESLint, that are unable to understand the type of `.vue` files.


# `esbuild`

## `options`

### `target`
- **Type**: `string`
- **Default**: `"esnext"`


### `jsxFactory`
- **Type**: `string`
- **Default**: `"h"`


### `jsxFragment`
- **Type**: `string`
- **Default**: `"Fragment"`


### `tsconfigRaw`

# `vite`

## `root`
- **Type**: `string`
- **Default**: `"/<srcDir>"`


## `mode`
- **Type**: `string`
- **Default**: `"production"`


## `define`

## `resolve`

### `extensions`
- **Type**: `array`
- **Default**: `[".mjs",".js",".ts",".jsx",".tsx",".json",".vue"]`


## `publicDir`
- **Type**: `any`
- **Default**: `{}`


## `vue`

### `isProduction`
- **Type**: `boolean`
- **Default**: `true`


### `template`

#### `compilerOptions`

#### `transformAssetUrls`

### `script`

#### `hoistStatic`
- **Type**: `any`
- **Default**: `{}`


### `features`

#### `propsDestructure`
- **Type**: `boolean`
- **Default**: `true`


## `vueJsx`

## `optimizeDeps`

### `esbuildOptions`

### `exclude`
- **Type**: `array`
- **Default**: `["vue-demi"]`


## `esbuild`

## `clearScreen`
- **Type**: `boolean`
- **Default**: `true`


## `build`

### `assetsDir`
- **Type**: `string`
- **Default**: `"_nuxt/"`


### `emptyOutDir`
- **Type**: `boolean`
- **Default**: `false`


## `server`

### `fs`

#### `allow`
- **Type**: `array`
- **Default**: `["/<rootDir>/.nuxt","/<srcDir>","/<rootDir>","/<workspaceDir>"]`


## `cacheDir`
- **Type**: `string`
- **Default**: `"/<rootDir>/node_modules/.cache/vite"`


# `webpack`

## `analyze`

## `profile`
- **Type**: `boolean`
- **Default**: `false`

> Enable the profiler in webpackbar.


It is normally enabled by CLI argument `--profile`.


## `extractCSS`
- **Type**: `boolean | MiniCssExtractPluginPluginOptions`
- **Default**: `true`

> Enables Common CSS Extraction.


Using [mini-css-extract-plugin](https://github.com/webpack-contrib/mini-css-extract-plugin) under the hood, your CSS will be extracted into separate files, usually one per component. This allows caching your CSS and JavaScript separately.


## `cssSourceMap`
- **Type**: `boolean`
- **Default**: `false`

> Enables CSS source map support (defaults to `true` in development).


## `serverURLPolyfill`
- **Type**: `string`
- **Default**: `"url"`

> The polyfill library to load to provide URL and URLSearchParams.


Defaults to `'url'` ([see package](https://www.npmjs.com/package/url)).


## `filenames`

### `app`
- **Type**: `function`
- **Default**: `undefined`

```ts
() => any
```


### `chunk`
- **Type**: `function`
- **Default**: `undefined`

```ts
() => any
```


### `css`
- **Type**: `function`
- **Default**: `undefined`

```ts
() => any
```


### `img`
- **Type**: `function`
- **Default**: `undefined`

```ts
() => any
```


### `font`
- **Type**: `function`
- **Default**: `undefined`

```ts
() => any
```


### `video`
- **Type**: `function`
- **Default**: `undefined`

```ts
() => any
```


## `loaders`

### `esbuild`

### `file`

#### `esModule`
- **Type**: `boolean`
- **Default**: `false`


#### `limit`
- **Type**: `number`
- **Default**: `1000`


### `fontUrl`

#### `esModule`
- **Type**: `boolean`
- **Default**: `false`


#### `limit`
- **Type**: `number`
- **Default**: `1000`


### `imgUrl`

#### `esModule`
- **Type**: `boolean`
- **Default**: `false`


#### `limit`
- **Type**: `number`
- **Default**: `1000`


### `pugPlain`
- **Type**: `PugOptions`
- **Default**: `{}`


### `vue`

#### `transformAssetUrls`

#### `compilerOptions`

#### `propsDestructure`
- **Type**: `boolean`
- **Default**: `true`


### `css`

#### `importLoaders`
- **Type**: `number`
- **Default**: `0`


#### `url`

##### `filter`
- **Type**: `function`
- **Default**: `undefined`

```ts
() => any
```


#### `esModule`
- **Type**: `boolean`
- **Default**: `false`


### `cssModules`

#### `importLoaders`
- **Type**: `number`
- **Default**: `0`


#### `url`

##### `filter`
- **Type**: `function`
- **Default**: `undefined`

```ts
() => any
```


#### `esModule`
- **Type**: `boolean`
- **Default**: `false`


#### `modules`

##### `localIdentName`
- **Type**: `string`
- **Default**: `"[local]_[hash:base64:5]"`


### `less`
- **Type**: `any`
- **Default**: `{"sourceMap":false}`


### `sass`

#### `sassOptions`

##### `indentedSyntax`
- **Type**: `boolean`
- **Default**: `true`


### `scss`
- **Type**: `any`
- **Default**: `{"sourceMap":false}`


### `stylus`
- **Type**: `any`
- **Default**: `{"sourceMap":false}`


### `vueStyle`
- **Type**: `any`
- **Default**: `{"sourceMap":false}`


## `plugins`
- **Type**: `array`
- **Default**: `[]`

> Add webpack plugins.


## `aggressiveCodeRemoval`
- **Type**: `boolean`
- **Default**: `false`

> Hard-replaces `typeof process`, `typeof window` and `typeof document` to tree-shake bundle.


## `optimizeCSS`
- **Type**: `false | CssMinimizerWebpackPluginBasePluginOptions & CssMinimizerWebpackPluginDefinedDefaultMinimizerAndOptions<{}>`
- **Default**: `false`

> OptimizeCSSAssets plugin options.


Defaults to true when `extractCSS` is enabled.


## `optimization`

### `runtimeChunk`
- **Type**: `string`
- **Default**: `"single"`


### `minimize`
- **Type**: `boolean`
- **Default**: `true`

> Set minimize to `false` to disable all minimizers. (It is disabled in development by default).


### `minimizer`
- **Type**: `any`
- **Default**: `{}`

> You can set minimizer to a customized array of plugins.


### `splitChunks`

#### `chunks`
- **Type**: `string`
- **Default**: `"all"`


#### `automaticNameDelimiter`
- **Type**: `string`
- **Default**: `"/"`


#### `cacheGroups`
- **Type**: `any`
- **Default**: `{}`


## `postcss`

### `postcssOptions`

#### `plugins`

## `devMiddleware`

### `stats`
- **Type**: `string`
- **Default**: `"none"`


## `hotMiddleware`
- **Type**: `WebpackHotMiddlewareMiddlewareOptions & { client?: WebpackHotMiddlewareClientOptions }`
- **Default**: `{}`

> See [webpack-hot-middleware](https://github.com/webpack-contrib/webpack-hot-middleware) for available options.


## `friendlyErrors`
- **Type**: `boolean`
- **Default**: `true`

> Set to `false` to disable the overlay provided by [FriendlyErrorsWebpackPlugin](https://github.com/nuxt/friendly-errors-webpack-plugin).


## `warningIgnoreFilters`
- **Type**: `Array<(warn: WebpackWebpackError) => boolean>`
- **Default**: `[]`

> Filters to hide build warnings.


## `experiments`
- **Type**: `false | WebpackConfiguration['experiments']`
- **Default**: `{}`

> Configure [webpack experiments](https://webpack.js.org/configuration/experiments/)
