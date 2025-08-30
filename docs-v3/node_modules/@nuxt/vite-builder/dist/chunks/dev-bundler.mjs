import { pathToFileURL } from 'node:url';
import { existsSync } from 'node:fs';
import { writeFile } from 'node:fs/promises';
import { builtinModules } from 'node:module';
import { resolve, normalize, isAbsolute } from 'pathe';
import { genObjectFromRawEntries, genDynamicImport } from 'knitwork';
import { debounce } from 'perfect-debounce';
import { isIgnored, logger } from '@nuxt/kit';
import { t as toArray, i as isCSS, w as writeManifest, h as hashId, u as uniq } from '../shared/vite-builder.Bsm2RRMi.mjs';
import { ExternalsDefaults, isExternal } from 'externality';
import escapeStringRegexp from 'escape-string-regexp';
import { withTrailingSlash } from 'ufo';
import 'vite';
import '@rollup/plugin-replace';
import 'mlly';
import 'pathe/utils';
import 'exsolve';
import 'pkg-types';
import '@vitejs/plugin-vue';
import '@vitejs/plugin-vue-jsx';
import 'get-port-please';
import 'defu';
import 'unenv';
import 'h3';
import 'ohash';
import 'magic-string';
import 'vite-node/server';
import 'vue-bundle-renderer';
import 'consola/utils';
import 'std-env';
import 'unplugin';
import 'jiti';

function createIsExternal(viteServer, nuxt) {
  const externalOpts = {
    inline: [
      /virtual:/,
      /\.ts$/,
      ...ExternalsDefaults.inline || [],
      ...viteServer.config.ssr.noExternal && viteServer.config.ssr.noExternal !== true ? toArray(viteServer.config.ssr.noExternal) : []
    ],
    external: [
      "#shared",
      new RegExp("^" + escapeStringRegexp(withTrailingSlash(resolve(nuxt.options.rootDir, nuxt.options.dir.shared)))),
      ...viteServer.config.ssr.external || [],
      /node_modules/
    ],
    resolve: {
      modules: nuxt.options.modulesDir,
      type: "module",
      extensions: [".ts", ".js", ".json", ".vue", ".mjs", ".jsx", ".tsx", ".wasm"]
    }
  };
  return (id) => isExternal(id, nuxt.options.rootDir, externalOpts);
}

async function transformRequest(opts, id) {
  if (id && id.startsWith("/@id/__x00__")) {
    id = "\0" + id.slice("/@id/__x00__".length);
  }
  if (id && id.startsWith("/@id/")) {
    id = id.slice("/@id/".length);
  }
  if (id && !id.startsWith("/@fs/") && id.startsWith("/")) {
    const resolvedPath = resolve(opts.viteServer.config.root, "." + id);
    if (existsSync(resolvedPath)) {
      id = resolvedPath;
    }
  }
  id = id.replace(/^\/?(?=\w:)/, "/@fs/");
  const externalId = id.replace(/\?v=\w+$|^\/@fs/, "");
  if (await opts.isExternal(externalId)) {
    const path = builtinModules.includes(externalId.split("node:").pop()) ? externalId : isAbsolute(externalId) ? pathToFileURL(externalId).href : externalId;
    return {
      code: `(global, module, _, exports, importMeta, ssrImport, ssrDynamicImport, ssrExportAll) =>
${genDynamicImport(path, { wrapper: false })}
  .then(r => {
    if (r.default && r.default.__esModule)
      r = r.default
    exports.default = r.default
    ssrExportAll(r)
  })
  .catch(e => {
    console.error(e)
    throw new Error(${JSON.stringify(`[vite dev] Error loading external "${id}".`)})
  })`,
      deps: [],
      dynamicDeps: []
    };
  }
  const res = await opts.viteServer.transformRequest(id, { ssr: true }).catch((err) => {
    logger.warn(`[SSR] Error transforming ${id}:`, err);
  }) || { code: "", deps: [], dynamicDeps: [] };
  const code = `async function (global, module, exports, __vite_ssr_exports__, __vite_ssr_import_meta__, __vite_ssr_import__, __vite_ssr_dynamic_import__, __vite_ssr_exportAll__) {
${res.code || "/* empty */"};
}`;
  return { code, deps: res.deps || [], dynamicDeps: res.dynamicDeps || [] };
}
async function transformRequestRecursive(opts, id, parent = "<entry>", chunks = {}) {
  if (chunks[id]) {
    chunks[id].parents.push(parent);
    return;
  }
  const res = await transformRequest(opts, id);
  const deps = uniq([...res.deps, ...res.dynamicDeps]);
  chunks[id] = {
    id,
    code: res.code,
    deps,
    parents: [parent]
  };
  for (const dep of deps) {
    await transformRequestRecursive(opts, dep, id, chunks);
  }
  return Object.values(chunks);
}
async function bundleRequest(opts, entryURL) {
  const chunks = await transformRequestRecursive(opts, entryURL);
  const listIds = (ids) => ids.map((id) => `// - ${id} (${hashId(id)})`).join("\n");
  const chunksCode = chunks.map((chunk) => `
// --------------------
// Request: ${chunk.id}
// Parents: 
${listIds(chunk.parents)}
// Dependencies: 
${listIds(chunk.deps)}
// --------------------
const ${hashId(chunk.id + "-" + chunk.code)} = ${chunk.code}
`).join("\n");
  const manifestCode = `const __modules__ = ${genObjectFromRawEntries(chunks.map((chunk) => [chunk.id, hashId(chunk.id + "-" + chunk.code)]))}`;
  const ssrModuleLoader = `
const __pendingModules__ = new Map()
const __pendingImports__ = new Map()
const __ssrContext__ = { global: globalThis }

function __ssrLoadModule__(url, urlStack = []) {
  const pendingModule = __pendingModules__.get(url)
  if (pendingModule) { return pendingModule }
  const modulePromise = __instantiateModule__(url, urlStack)
  __pendingModules__.set(url, modulePromise)
  modulePromise.catch(() => { __pendingModules__.delete(url) })
         .finally(() => { __pendingModules__.delete(url) })
  return modulePromise
}

async function __instantiateModule__(url, urlStack) {
  const mod = __modules__[url]
  if (mod.stubModule) { return mod.stubModule }
  const stubModule = { [Symbol.toStringTag]: 'Module' }
  Object.defineProperty(stubModule, '__esModule', { value: true })
  mod.stubModule = stubModule
  // https://vitejs.dev/guide/api-hmr.html
  const importMeta = { url, hot: { accept() {}, prune() {}, dispose() {}, invalidate() {}, decline() {}, on() {} } }
  urlStack = urlStack.concat(url)
  const isCircular = url => urlStack.includes(url)
  const pendingDeps = []
  const ssrImport = async (dep) => {
    // TODO: Handle externals if dep[0] !== '.' | '/'
    if (!isCircular(dep) && !__pendingImports__.get(dep)?.some(isCircular)) {
      pendingDeps.push(dep)
      if (pendingDeps.length === 1) {
        __pendingImports__.set(url, pendingDeps)
      }
      await __ssrLoadModule__(dep, urlStack)
      if (pendingDeps.length === 1) {
        __pendingImports__.delete(url)
      } else {
        pendingDeps.splice(pendingDeps.indexOf(dep), 1)
      }
    }
    return __modules__[dep].stubModule
  }
  function ssrDynamicImport (dep) {
    // TODO: Handle dynamic import starting with . relative to url
    return ssrImport(dep)
  }

  function ssrExportAll(sourceModule) {
    for (const key in sourceModule) {
      if (key !== 'default') {
        try {
          Object.defineProperty(stubModule, key, {
            enumerable: true,
            configurable: true,
            get() { return sourceModule[key] }
          })
        } catch (_err) { }
      }
    }
  }

  const cjsModule = {
    get exports () {
      return stubModule.default
    },
    set exports (v) {
      stubModule.default = v
    },
  }

  await mod(
    __ssrContext__.global,
    cjsModule,
    stubModule.default,
    stubModule,
    importMeta,
    ssrImport,
    ssrDynamicImport,
    ssrExportAll
  )

  return stubModule
}
`;
  const code = [
    chunksCode,
    manifestCode,
    ssrModuleLoader,
    `export default await __ssrLoadModule__(${JSON.stringify(entryURL)})`
  ].join("\n\n");
  return {
    code,
    ids: chunks.map((i) => i.id)
  };
}
async function initViteDevBundler(ctx, onBuild) {
  const viteServer = ctx.ssrServer;
  const options = {
    viteServer,
    isExternal: createIsExternal(viteServer, ctx.nuxt)
  };
  const _doBuild = async () => {
    const start = Date.now();
    const { code, ids } = await bundleRequest(options, ctx.entry);
    await writeFile(resolve(ctx.nuxt.options.buildDir, "dist/server/server.mjs"), code, "utf-8");
    const manifestIds = [];
    for (const i of ids) {
      if (isCSS(i)) {
        manifestIds.push(i.slice(1));
      }
    }
    await writeManifest(ctx, manifestIds);
    const time = Date.now() - start;
    logger.success(`Vite server built in ${time}ms`);
    await onBuild();
  };
  const doBuild = debounce(_doBuild);
  await _doBuild();
  viteServer.watcher.on("all", (_event, file) => {
    file = normalize(file);
    if (file.indexOf(ctx.nuxt.options.buildDir) === 0 || isIgnored(file)) {
      return;
    }
    doBuild();
  });
  ctx.nuxt.hook("app:templatesGenerated", () => doBuild());
}

export { initViteDevBundler };
