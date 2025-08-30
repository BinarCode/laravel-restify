'use strict';

const pathe = require('pathe');
const kit = require('@nuxt/kit');
const pkgTypes = require('pkg-types');
const node_fs = require('node:fs');
const defu = require('defu');
const consola = require('consola');
const unctx = require('unctx');
const utils = require('consola/utils');
const h3 = require('h3');
const ufo = require('ufo');
const c12 = require('c12');
const merger = require('./merger.cjs');
const utils$1 = require('ohash/utils');
require('klona');

var _documentCurrentScript = typeof document !== 'undefined' ? document.currentScript : null;
const name = "@nuxtjs/tailwindcss";
const version = "6.14.0";
const configKey = "tailwindcss";
const compatibility = {
	nuxt: "^2.9.0 || >=3.0.0-rc.1"
};

async function resolveCSSPath(cssPath, nuxt = kit.useNuxt()) {
  if (typeof cssPath === "string") {
    const _cssPath = await kit.resolvePath(cssPath, { extensions: [".css", ".sass", ".scss", ".less", ".styl"] });
    return node_fs.existsSync(_cssPath) ? [_cssPath, `Using Tailwind CSS from ~/${pathe.relative(nuxt.options.srcDir, _cssPath)}`] : await kit.tryResolveModule("tailwindcss/package.json", (typeof document === 'undefined' ? require('u' + 'rl').pathToFileURL(__filename).href : (_documentCurrentScript && _documentCurrentScript.tagName.toUpperCase() === 'SCRIPT' && _documentCurrentScript.src || new URL('module.cjs', document.baseURI).href))).then((twLocation) => twLocation ? [pathe.join(twLocation, "../tailwind.css"), "Using default Tailwind CSS file"] : Promise.reject("Unable to resolve tailwindcss. Is it installed?"));
  } else {
    return [
      false,
      "No Tailwind CSS file found. Skipping..."
    ];
  }
}
const resolveBoolObj = (config, fb) => defu.defu(typeof config === "object" ? config : {}, fb);
const resolveViewerConfig = (config) => resolveBoolObj(config, { endpoint: "/_tailwind", exportViewer: false });
const resolveExposeConfig = (config) => resolveBoolObj(config, { alias: "#tailwind-config", level: 2 });
const resolveEditorSupportConfig = (config) => resolveBoolObj(config, { autocompleteUtil: true, generateConfig: false });
async function resolveInjectPosition(css, position = "first") {
  if (typeof position === "number") {
    return ~~Math.min(position, css.length + 1);
  }
  if (typeof position === "string") {
    switch (position) {
      case "first":
        return 0;
      case "last":
        return css.length;
    }
  }
  if (typeof position === "object") {
    const minIndex = "after" in position ? css.indexOf(await kit.resolvePath(position.after)) + 1 : 0;
    const maxIndex = "before" in position ? css.indexOf(await kit.resolvePath(position.before)) : css.length;
    if ([minIndex, maxIndex].includes(-1) || "after" in position && minIndex === 0) {
      throw new Error(`\`injectPosition\` specifies a file which does not exists on CSS stack: ` + JSON.stringify(position));
    }
    if (minIndex > maxIndex) {
      throw new Error(`\`injectPosition\` specifies a relative location \`${minIndex}\` that cannot be resolved (i.e., \`after\` orders \`before\` may be reversed): ` + JSON.stringify(position));
    }
    return "after" in position ? minIndex : maxIndex;
  }
  throw new Error("invalid `injectPosition`: " + JSON.stringify(position));
}

const logger = kit.useLogger("nuxt:tailwindcss");

const twCtx = unctx.getContext("twcss");
const { set } = twCtx;
twCtx.set = (instance, replace = true) => {
  set(defu.defu(instance, twCtx.tryUse()), replace);
};

const NON_ALPHANUMERIC_RE = /^[0-9a-z]+$/i;
const isJSObject = (value) => typeof value === "object" && !Array.isArray(value);
const createExposeTemplates = (config, nuxt = kit.useNuxt()) => {
  const templates = [];
  const getTWConfig = (objPath = [], twConfig = twCtx.use().config) => objPath.reduce((prev, curr) => prev?.[curr], twConfig);
  const populateMap = (obj = twCtx.use().config, path = [], level = 1) => {
    Object.entries(obj).forEach(([key, value = {}]) => {
      const subpathComponents = path.concat(key);
      const subpath = subpathComponents.join("/");
      if (level >= config.level || !isJSObject(value) || Object.keys(value).find((k) => !k.match(NON_ALPHANUMERIC_RE))) {
        templates.push(kit.addTemplate({
          filename: `tailwind/expose/${subpath}.mjs`,
          getContents: () => {
            const _value = getTWConfig(subpathComponents);
            if (isJSObject(_value)) {
              const [validKeys, invalidKeys] = [[], []];
              Object.keys(_value).forEach((i) => (NON_ALPHANUMERIC_RE.test(i) ? validKeys : invalidKeys).push(i));
              return [
                `${validKeys.map((i) => `const _${i} = ${JSON.stringify(_value[i])}`).join("\n")}`,
                `const config = { ${validKeys.map((i) => `"${i}": _${i}, `).join("")}${invalidKeys.map((i) => `"${i}": ${JSON.stringify(_value[i])}, `).join("")} }`,
                `export { config as default${validKeys.length > 0 ? ", _" : ""}${validKeys.join(", _")} }`
              ].join("\n");
            }
            return `export default ${JSON.stringify(_value, null, 2)}`;
          },
          write: config.write
        }));
      } else {
        populateMap(value, path.concat(key), level + 1);
        templates.push(kit.addTemplate({
          filename: `tailwind/expose/${subpath}.mjs`,
          getContents: () => {
            const _value = getTWConfig(subpathComponents);
            const values = Object.keys(_value);
            return [
              `${values.map((v) => `import _${v} from "./${key}/${v}.mjs"`).join("\n")}`,
              `const config = { ${values.map((k) => `"${k}": _${k}`).join(", ")} }`,
              `export { config as default${values.length > 0 ? ", _" : ""}${values.join(", _")} }`
            ].join("\n");
          },
          write: config.write
        }));
      }
    });
  };
  populateMap();
  const entryTemplate = kit.addTemplate({
    filename: "tailwind/expose/index.mjs",
    getContents: () => {
      const _tailwindConfig = getTWConfig();
      const configOptions = Object.keys(_tailwindConfig);
      return [
        `${configOptions.map((v) => `import ${v} from "#build/tailwind/expose/${v}.mjs"`).join("\n")}`,
        `const config = { ${configOptions.join(", ")} }`,
        `export { config as default, ${configOptions.join(", ")} }`
      ].join("\n");
    },
    write: true
  });
  templates.push(kit.addTypeTemplate({
    filename: "types/tailwind.config.d.ts",
    getContents: () => {
      const _tailwindConfig = getTWConfig();
      const declareModule = (obj, path = [], level = 1) => Object.entries(obj).map(([key, value = {}]) => {
        const subpath = path.concat(key).join("/");
        if (level >= config.level || !isJSObject(value) || Object.keys(value).find((k) => !k.match(NON_ALPHANUMERIC_RE))) {
          if (isJSObject(value)) {
            const [validKeys, invalidKeys] = [[], []];
            Object.keys(value).forEach((i) => (NON_ALPHANUMERIC_RE.test(i) ? validKeys : invalidKeys).push(i));
            return `declare module "${config.alias}/${subpath}" { ${validKeys.map((i) => `export const _${i}: ${JSON.stringify(value[i])};`).join("")} const defaultExport: { ${validKeys.map((i) => `"${i}": typeof _${i}, `).join("")}${invalidKeys.map((i) => `"${i}": ${JSON.stringify(value[i])}, `).join("")} }; export default defaultExport; }
`;
          }
          return `declare module "${config.alias}/${subpath}" { const defaultExport: ${JSON.stringify(value)}; export default defaultExport; }
`;
        }
        const values = Object.keys(value);
        return declareModule(value, path.concat(key), level + 1).join("") + `declare module "${config.alias}/${subpath}" {${Object.keys(value).map((v) => ` export const _${v}: typeof import("${config.alias}/${pathe.join(`${key}/${subpath}`, `../${v}`)}")["default"];`).join("")} const defaultExport: { ${values.map((k) => `"${k}": typeof _${k}`).join(", ")} }; export default defaultExport; }
`;
      });
      const configOptions = Object.keys(_tailwindConfig);
      return declareModule(_tailwindConfig).join("") + `declare module "${config.alias}" {${configOptions.map((v) => ` export const ${v}: typeof import("${pathe.join(config.alias, v)}")["default"];`).join("")} const defaultExport: { ${configOptions.map((v) => `"${v}": typeof ${v}`)} }; export default defaultExport; }`;
    }
  }));
  templates.push(entryTemplate);
  nuxt.options.alias[config.alias] = pathe.dirname(entryTemplate.dst);
  return templates.map((t) => t.dst);
};

const setupViewer = async (twConfig, config, nuxt = kit.useNuxt()) => {
  const route = ufo.joinURL(nuxt.options.app?.baseURL, config.endpoint);
  const [routeWithSlash, routeWithoutSlash] = [ufo.withTrailingSlash(route), ufo.withoutTrailingSlash(route)];
  const viewerServer = await Promise.all([
    // @ts-expect-error untyped package export
    import('tailwind-config-viewer/server/index.js').then((r) => r.default || r),
    typeof twConfig === "string" ? import('tailwindcss/loadConfig.js').then((r) => r.default || r).then((loadConfig) => () => loadConfig(twConfig)) : () => twConfig
  ]).then(([server, tailwindConfigProvider]) => server({ tailwindConfigProvider }).asMiddleware());
  const viewerDevMiddleware = h3.eventHandler((event) => viewerServer(event.node?.req || event.req, event.node?.res || event.res));
  if (!kit.isNuxtMajorVersion(2, nuxt)) {
    kit.addDevServerHandler({
      handler: h3.eventHandler((event) => {
        if (event.path === routeWithoutSlash) {
          return h3.sendRedirect(event, routeWithSlash, 301);
        }
      })
    });
    kit.addDevServerHandler({ route, handler: viewerDevMiddleware });
  } else {
    nuxt.options.serverMiddleware.push(
      // @ts-expect-error untyped handler parameters
      (req, res, next) => {
        if (req.url === routeWithoutSlash) {
          return h3.sendRedirect(new h3.H3Event(req, res), routeWithSlash, 301);
        }
        next();
      },
      // @ts-expect-error untyped handler parameters
      { route, handler: (req, res) => viewerDevMiddleware(new h3.H3Event(req, res)) }
    );
  }
  nuxt.hook("devtools:customTabs", (tabs) => {
    tabs?.push({
      title: "Tailwind CSS",
      name: "tailwindcss",
      icon: "logos-tailwindcss-icon",
      category: "modules",
      view: { type: "iframe", src: route }
    });
  });
  const shouldLogUrl = "devtools" in nuxt.options ? !nuxt.options.devtools.enabled : true;
  shouldLogUrl && nuxt.hook("listen", (_, listener) => {
    const viewerUrl = ufo.cleanDoubleSlashes(ufo.joinURL(listener.url, config.endpoint));
    logger.info(`Tailwind Viewer: ${utils.colors.underline(utils.colors.yellow(ufo.withTrailingSlash(viewerUrl)))}`);
  });
};
const exportViewer = async (twConfig, config, nuxt = kit.useNuxt()) => {
  if (!config.exportViewer) {
    return;
  }
  const cli = await import('tailwind-config-viewer/cli/export.js').then((r) => r.default || r);
  nuxt.hook("nitro:build:public-assets", (nitro) => {
    const dir = ufo.joinURL(nitro.options.output.publicDir, config.endpoint);
    cli(dir, twConfig);
    logger.success(`Exported viewer to ${utils.colors.yellow(pathe.relative(nuxt.options.srcDir, dir))}`);
  });
};

const checkUnsafeInlineConfig = (inlineConfig) => {
  if (!inlineConfig)
    return;
  if ("plugins" in inlineConfig && Array.isArray(inlineConfig.plugins) && inlineConfig.plugins.find((p) => typeof p === "function" || typeof p?.handler === "function")) {
    return "plugins";
  }
  if (inlineConfig.content) {
    const invalidProperty = ["extract", "transform"].find((i) => i in inlineConfig.content && typeof inlineConfig.content[i] === "function");
    if (invalidProperty) {
      return `content.${invalidProperty}`;
    }
  }
  if (inlineConfig.safelist) {
    const invalidIdx = inlineConfig.safelist.findIndex((s) => typeof s === "object" && s.pattern instanceof RegExp);
    if (invalidIdx > -1) {
      return `safelist[${invalidIdx}]`;
    }
  }
};

const UNSUPPORTED_VAL_STR = "UNSUPPORTED_VAL_STR";
const JSONStringifyWithUnsupportedVals = (val) => JSON.stringify(val, (_, v) => ["function"].includes(typeof v) ? UNSUPPORTED_VAL_STR : v);
const JSONStringifyWithRegex = (obj) => JSON.stringify(obj, (_, v) => v instanceof RegExp ? `__REGEXP ${v.toString()}` : v);
const createObjProxy = (configUpdatedHook, meta) => {
  return (configPath, oldConfig, newConfig) => utils$1.diff(oldConfig, newConfig).forEach((change) => {
    const path = change.key.split(".").map((k) => `[${JSON.stringify(k)}]`).join("");
    const newValue = change.newValue?.value;
    switch (change.type) {
      case "removed":
        configUpdatedHook[configPath] += `delete cfg${path};`;
        break;
      case "added":
      case "changed": {
        const resultingCode = `cfg${path} = ${JSONStringifyWithRegex(newValue)?.replace(/"__REGEXP (.*)"/g, (_, substr) => substr.replace(/\\"/g, '"')) || `cfg${path}`};`;
        if (JSONStringifyWithUnsupportedVals(change.oldValue?.value) === JSONStringifyWithUnsupportedVals(newValue) || configUpdatedHook[configPath].endsWith(resultingCode)) {
          return;
        }
        if (JSONStringifyWithUnsupportedVals(newValue).includes(`"${UNSUPPORTED_VAL_STR}"`) && !meta?.disableHMR) {
          logger.warn(
            `A hook has injected a non-serializable value in \`config${path}\`, so the Tailwind Config cannot be serialized. Falling back to providing the loaded configuration inlined directly to PostCSS loader..`,
            "Please consider using a configuration file/template instead (specifying in `configPath` of the module options) to enable additional support for IntelliSense and HMR."
          );
          twCtx.set({ meta: { disableHMR: true } });
        }
        if (JSONStringifyWithRegex(newValue).includes("__REGEXP") && !meta?.disableHMR) {
          logger.warn(`A hook is injecting RegExp values in your configuration (check \`config${path}\`) which may be unsafely serialized. Consider moving your safelist to a separate configuration file/template instead (specifying in \`configPath\` of the module options)`);
        }
        configUpdatedHook[configPath] += resultingCode;
      }
    }
  });
};

const loadConfig = c12.loadConfig;
const pagesContentPath = unctx.getContext("twcss-pages-path");
const componentsContentPath = unctx.getContext("twcss-components-path");
const resolvedConfigsCtx = unctx.getContext("twcss-resolved-configs");
const createInternalContext = async (moduleOptions, nuxt = kit.useNuxt()) => {
  const configUpdatedHook = {};
  const { meta = { disableHMR: moduleOptions.disableHMR } } = twCtx.tryUse() ?? {};
  const trackObjChanges = createObjProxy(configUpdatedHook, meta);
  const resolveConfigs = (configs, nuxt2 = kit.useNuxt()) => (Array.isArray(configs) ? configs : [configs]).filter((c) => Boolean(c) && c !== pathe.join(nuxt2.options.rootDir, "tailwind.config")).map(async (config, idx, arr) => {
    if (typeof config !== "string") {
      const hasUnsafeProperty = checkUnsafeInlineConfig(config);
      if (hasUnsafeProperty && !meta.disableHMR) {
        logger.warn(
          `The provided Tailwind configuration in your \`nuxt.config\` is non-serializable. Check \`${hasUnsafeProperty}\`. Falling back to providing the loaded configuration inlined directly to PostCSS loader..`,
          "Please consider using `tailwind.config` or a separate file (specifying in `configPath` of the module options) to enable it with additional support for IntelliSense and HMR. Suppress this warning with `quiet: true` in the module options."
        );
        meta.disableHMR = true;
        twCtx.set({ meta });
      }
      return { config };
    }
    const configFile = await (config.startsWith(nuxt2.options.buildDir) ? config : kit.findPath(config, { extensions: [".js", ".cjs", ".mjs", ".ts"] }));
    return configFile ? loadConfig({ configFile }).then(async (resolvedConfig) => {
      const { configFile: resolvedConfigFile = configFile } = resolvedConfig;
      const config2 = merger(void 0, resolvedConfig.config);
      configUpdatedHook[resolvedConfigFile] = "";
      if (resolvedConfig.config?.purge && !resolvedConfig.config.content) {
        configUpdatedHook[resolvedConfigFile] += "cfg.content = cfg.purge;";
      }
      await nuxt2.callHook("tailwindcss:loadConfig", config2, resolvedConfigFile, idx, arr);
      trackObjChanges(resolvedConfigFile, resolvedConfig.config, config2);
      return { ...resolvedConfig, config: config2 };
    }).catch((e) => {
      logger.warn(`Failed to load config \`./${pathe.relative(nuxt2.options.rootDir, configFile)}\` due to the error below. Skipping..
`, e);
      return null;
    }) : null;
  });
  const resolveContentConfig = (rootDir, nuxtOptions = kit.useNuxt().options) => {
    const r = (p) => pathe.isAbsolute(p) || p.startsWith(rootDir) ? p : pathe.resolve(rootDir, p);
    const withSrcDir = (p) => r(nuxtOptions.srcDir && !p.startsWith(nuxtOptions.srcDir) ? pathe.resolve(nuxtOptions.srcDir, p) : p);
    const formatExtensions = (s) => s.length > 1 ? `.{${s.join(",")}}` : `.${s.join("") || "vue"}`;
    const defaultExtensions = formatExtensions(["js", "ts", "mjs"]);
    const sfcExtensions = formatExtensions(Array.from(/* @__PURE__ */ new Set([".vue", ...nuxtOptions.extensions || nuxt.options.extensions])).map((e) => e?.replace(/^\.*/, "")).filter((v) => Boolean(v)));
    const importDirs = [...nuxtOptions.imports?.dirs || []].filter((v) => Boolean(v)).map(withSrcDir);
    const [composablesDir, utilsDir] = [withSrcDir("composables"), withSrcDir("utils")];
    if (!importDirs.includes(composablesDir))
      importDirs.push(composablesDir);
    if (!importDirs.includes(utilsDir))
      importDirs.push(utilsDir);
    const isLayer = rootDir !== nuxt.options.rootDir;
    const pagePaths = [];
    const pageFiles = pagesContentPath.tryUse();
    if (moduleOptions.experimental?.strictScanContentPaths && pageFiles && pageFiles.length) {
      if (!isLayer)
        pagePaths.push(...pageFiles.map((p) => p.replaceAll(/\[(\.+)([^.].*)\]/g, "?$1$2?")));
    } else if (nuxtOptions.pages !== false && nuxtOptions.pages?.enabled !== false) {
      pagePaths.push(withSrcDir(`${nuxtOptions.dir?.pages || "pages"}/**/*${sfcExtensions}`));
    }
    const componentPaths = [];
    const componentFiles = componentsContentPath.tryUse();
    if (moduleOptions.experimental?.strictScanContentPaths && componentFiles && componentFiles.length) {
      if (!isLayer)
        componentPaths.push(...componentFiles);
    } else {
      componentPaths.push(
        withSrcDir(`components/**/*${sfcExtensions}`),
        ...(() => {
          if (nuxtOptions.components) {
            return (Array.isArray(nuxtOptions.components) ? nuxtOptions.components : typeof nuxtOptions.components === "boolean" ? ["components"] : nuxtOptions.components.dirs || []).map((d) => {
              const valueToResolve = typeof d === "string" ? d : d?.path;
              return valueToResolve ? `${kit.resolveAlias(valueToResolve)}/**/*${sfcExtensions}` : "";
            }).filter(Boolean);
          }
          return [];
        })()
      );
    }
    return {
      config: {
        content: {
          files: [
            ...componentPaths,
            nuxtOptions.dir?.layouts && withSrcDir(`${nuxtOptions.dir.layouts}/**/*${sfcExtensions}`),
            nuxtOptions.dir?.plugins && withSrcDir(`${nuxtOptions.dir.plugins}/**/*${defaultExtensions}`),
            ...importDirs.map((d) => `${d}/**/*${defaultExtensions}`),
            ...pagePaths,
            withSrcDir(`{A,a}pp${sfcExtensions}`),
            withSrcDir(`{E,e}rror${sfcExtensions}`),
            withSrcDir(`app.config${defaultExtensions}`),
            !nuxtOptions.ssr && nuxtOptions.spaLoadingTemplate !== false && r(typeof nuxtOptions.spaLoadingTemplate === "string" ? nuxtOptions.spaLoadingTemplate : "app/spa-loading-template.html")
          ].filter((p) => Boolean(p))
        }
      }
    };
  };
  const resolvePageFiles = (pages) => {
    const filePaths = [];
    pages.forEach((page) => {
      if (page.file) {
        filePaths.push(page.file);
      }
      if (page.children && page.children.length) {
        filePaths.push(...resolvePageFiles(page.children));
      }
    });
    return filePaths;
  };
  const getModuleConfigs = () => {
    const thenCallHook = async (resolvedConfig) => {
      const { configFile: resolvedConfigFile } = resolvedConfig;
      if (!resolvedConfigFile || !resolvedConfig.config) {
        return { ...resolvedConfig, configFile: resolvedConfigFile === "tailwind.config" ? void 0 : resolvedConfigFile };
      }
      const config = merger(void 0, resolvedConfig.config);
      configUpdatedHook[resolvedConfigFile] = "";
      if (resolvedConfig.config?.purge && !resolvedConfig.config.content) {
        configUpdatedHook[resolvedConfigFile] += "cfg.content = cfg.purge;";
      }
      await nuxt.callHook("tailwindcss:loadConfig", config, resolvedConfigFile, 0, []);
      trackObjChanges(resolvedConfigFile, resolvedConfig.config, config);
      return { ...resolvedConfig, config };
    };
    return Promise.all([
      resolveContentConfig(nuxt.options.rootDir, nuxt.options),
      ...resolveConfigs(moduleOptions.config, nuxt),
      loadConfig({ name: "tailwind", cwd: nuxt.options.rootDir, merger: merger, packageJson: true, extend: false }).then(thenCallHook),
      ...resolveConfigs(moduleOptions.configPath, nuxt),
      ...(nuxt.options._layers || []).slice(1).flatMap((nuxtLayer) => [
        resolveContentConfig(nuxtLayer.config.rootDir || nuxtLayer.cwd, nuxtLayer.config),
        ...resolveConfigs(nuxtLayer.config.tailwindcss?.config, nuxt),
        loadConfig({ name: "tailwind", cwd: nuxtLayer.cwd, merger: merger, packageJson: true, extend: false }).then(thenCallHook),
        ...resolveConfigs(nuxtLayer.config.tailwindcss?.configPath, nuxt)
      ])
    ]);
  };
  const resolveTWConfig = await import('tailwindcss/resolveConfig.js').then((m) => m.default || m).catch(() => (c) => c);
  const loadConfigs = async () => {
    const moduleConfigs = await getModuleConfigs();
    resolvedConfigsCtx.set(moduleConfigs, true);
    const tailwindConfig = moduleConfigs.reduce((acc, curr) => merger(acc, curr?.config ?? {}), {});
    const clonedConfig = merger(void 0, tailwindConfig);
    configUpdatedHook["main-config"] = "";
    await nuxt.callHook("tailwindcss:config", clonedConfig);
    trackObjChanges("main-config", tailwindConfig, clonedConfig);
    const resolvedConfig = resolveTWConfig(clonedConfig);
    await nuxt.callHook("tailwindcss:resolvedConfig", resolvedConfig, twCtx.tryUse()?.config ?? void 0);
    twCtx.set({ config: resolvedConfig });
    return tailwindConfig;
  };
  const generateConfig = () => {
    const ctx = twCtx.tryUse();
    const targetDir = pathe.join(nuxt.options.buildDir, "tailwind");
    const template = !meta.disableHMR || !ctx?.meta?.disableHMR ? kit.addTemplate({
      filename: "tailwind/postcss.mjs",
      write: true,
      getContents: () => {
        const serializeConfig = (config) => JSON.stringify(
          Array.isArray(config.plugins) && config.plugins.length > 0 ? merger({ plugins: (defaultPlugins) => defaultPlugins?.filter((p) => p && typeof p !== "function") }, config) : config,
          (_, v) => typeof v === "function" ? `() => (${JSON.stringify(v())})` : v
        ).replace(/"(\(\) => \(.*\))"/g, (_, substr) => substr.replace(/\\"/g, '"'));
        const layerConfigs = resolvedConfigsCtx.use().map((c, idx) => c?.configFile ? [`import cfg${idx} from ${JSON.stringify(/[/\\]node_modules[/\\]/.test(c.configFile) ? c.configFile : "./" + pathe.relative(targetDir, c.configFile))}`, configUpdatedHook[c.configFile] ? `(() => {const cfg=configMerger(undefined, cfg${idx});${configUpdatedHook[c.configFile]};return cfg;})()` : `cfg${idx}`] : [null, c?.config ? serializeConfig(c.config) : null]);
        return [
          `// generated by the @nuxtjs/tailwindcss <https://github.com/nuxt-modules/tailwindcss> module at ${( new Date()).toLocaleString()}`,
          'import "@nuxtjs/tailwindcss/config-ctx"',
          `import configMerger from "@nuxtjs/tailwindcss/merger";
`,
          layerConfigs.map(([i, _]) => i).filter(Boolean).join(";\n") + ";",
          "const config = [",
          layerConfigs.map(([_, i]) => i).filter(Boolean).join(",\n"),
          `].reduce((acc, curr) => configMerger(acc, curr), {});
`,
          `const resolvedConfig = ${configUpdatedHook["main-config"] ? `(() => {const cfg=config;${configUpdatedHook["main-config"]};return cfg;})()` : "config"};
`,
          "export default resolvedConfig;"
        ].join("\n");
      }
    }) : { dst: "" };
    twCtx.set({ dst: template.dst });
    return template;
  };
  const registerHooks = () => {
    if (twCtx.use().meta?.disableHMR)
      return;
    const reloadConfigTemplate = async () => {
      const { dst } = twCtx.use();
      await loadConfigs();
      setTimeout(async () => {
        await kit.updateTemplates({ filter: (t) => t.dst === dst || dst?.endsWith(t.filename) || false });
        await nuxt.callHook("tailwindcss:internal:regenerateTemplates", { configTemplateUpdated: true });
      }, 100);
    };
    nuxt.hook("app:templatesGenerated", async (_app, templates) => {
      if (Array.isArray(templates) && templates?.some((t) => Object.keys(configUpdatedHook).includes(t.dst))) {
        await reloadConfigTemplate();
      }
    });
    if (moduleOptions.experimental?.strictScanContentPaths) {
      nuxt.hook("pages:extend", async (pages) => {
        const newPageFiles = resolvePageFiles(pages);
        if (newPageFiles.length !== pagesContentPath.tryUse()?.length) {
          pagesContentPath.set(newPageFiles, true);
          await reloadConfigTemplate();
        }
      });
      nuxt.hook("components:extend", async (components) => {
        const newComponentFiles = components.map((c) => c.filePath);
        if (newComponentFiles.length !== componentsContentPath.tryUse()?.length) {
          componentsContentPath.set(newComponentFiles, true);
          await reloadConfigTemplate();
        }
      });
    } else {
      nuxt.hook("pages:extend", () => reloadConfigTemplate());
    }
    nuxt.hook("vite:serverCreated", (server) => {
      nuxt.hook("tailwindcss:internal:regenerateTemplates", (data) => {
        if (!data || !data.configTemplateUpdated)
          return;
        const ctx = twCtx.use();
        const configFile = ctx.dst && server.moduleGraph.getModuleById(ctx.dst);
        configFile && server.moduleGraph.invalidateModule(configFile);
      });
    });
    moduleOptions.exposeConfig && nuxt.hook("builder:watch", async (_, path) => {
      if (Object.keys(configUpdatedHook).includes(pathe.join(nuxt.options.rootDir, path))) {
        const ctx = twCtx.use();
        setTimeout(async () => {
          await import(ctx.dst).then(async (_config) => {
            twCtx.set({ config: resolveTWConfig(_config.default ?? _config) });
            await nuxt.callHook("tailwindcss:internal:regenerateTemplates");
          });
        }, 100);
      }
    });
  };
  return {
    loadConfigs,
    generateConfig,
    registerHooks
  };
};

const defaults = (nuxt = kit.useNuxt()) => ({
  configPath: [],
  cssPath: pathe.join(nuxt.options.dir.assets, "css/tailwind.css"),
  config: {},
  viewer: nuxt.options.dev,
  exposeConfig: false,
  quiet: nuxt.options.logLevel === "silent",
  editorSupport: false
});
const module$1 = kit.defineNuxtModule({
  meta: { name, version, configKey, compatibility },
  defaults,
  async setup(moduleOptions, nuxt) {
    if (moduleOptions.quiet)
      logger.level = consola.LogLevels.silent;
    if (Number.parseFloat(kit.getNuxtVersion()) < 2.16) {
      await kit.installModule("@nuxt/postcss8").catch((e) => {
        logger.error(`Error occurred while loading \`@nuxt/postcss8\` required for Nuxt ${kit.getNuxtVersion()}, is it installed?`);
        throw e;
      });
    }
    const isTailwind4 = await pkgTypes.readPackageJSON("tailwindcss", { parent: (typeof document === 'undefined' ? require('u' + 'rl').pathToFileURL(__filename).href : (_documentCurrentScript && _documentCurrentScript.tagName.toUpperCase() === 'SCRIPT' && _documentCurrentScript.src || new URL('module.cjs', document.baseURI).href)) }).then((m) => Number.parseFloat(m.version) >= 4);
    if (isTailwind4 && !moduleOptions.experimental?.tailwindcss4) {
      logger.warn("Tailwind CSS v4 detected. The current version of `@nuxtjs/tailwindcss` supports Tailwind CSS 3 officially and support for v4 is experimental. To suppress this warning, set `tailwindcss.experimental.tailwindcss4` to  `true` in your `nuxt.config`.");
    }
    const ctx = await createInternalContext(moduleOptions, nuxt);
    if (moduleOptions.editorSupport) {
      const editorSupportConfig = resolveEditorSupportConfig(moduleOptions.editorSupport);
      if (editorSupportConfig.autocompleteUtil && !kit.isNuxtMajorVersion(2, nuxt)) {
        kit.addImports({
          name: "autocompleteUtil",
          from: kit.createResolver((typeof document === 'undefined' ? require('u' + 'rl').pathToFileURL(__filename).href : (_documentCurrentScript && _documentCurrentScript.tagName.toUpperCase() === 'SCRIPT' && _documentCurrentScript.src || new URL('module.cjs', document.baseURI).href))).resolve("./runtime/utils"),
          as: "tw",
          ...typeof editorSupportConfig.autocompleteUtil === "object" ? editorSupportConfig.autocompleteUtil : {}
        });
      }
    }
    const [cssPath, cssPathConfig] = Array.isArray(moduleOptions.cssPath) ? moduleOptions.cssPath : [moduleOptions.cssPath];
    const [resolvedCss, loggerInfo] = await resolveCSSPath(cssPath, nuxt).catch((e) => {
      if (isTailwind4) {
        return [kit.addTemplate({ filename: "tailwind.css", getContents: () => `@import 'tailwindcss';`, write: true }).dst, "Generating default CSS file for Tailwind CSS 4..."];
      }
      throw e;
    });
    logger.info(loggerInfo);
    nuxt.options.css = nuxt.options.css ?? [];
    const resolvedNuxtCss = resolvedCss && await Promise.all(nuxt.options.css.map((p) => kit.resolvePath(p.src ?? p))) || [];
    if (resolvedCss && !resolvedNuxtCss.includes(resolvedCss)) {
      const injectPosition = await resolveInjectPosition(resolvedNuxtCss, cssPathConfig?.injectPosition);
      nuxt.options.css.splice(injectPosition, 0, resolvedCss);
    }
    const shouldInstallTWVitePlugin = isTailwind4 && nuxt.options.builder === "@nuxt/vite-builder";
    if (shouldInstallTWVitePlugin) {
      await import('@tailwindcss/vite').then((r) => kit.addVitePlugin(r.default()));
    }
    let nuxt2ViewerConfig = pathe.join(nuxt.options.buildDir, "tailwind/postcss.mjs");
    nuxt.hook("modules:done", async () => {
      const _config = await ctx.loadConfigs();
      const twConfig = ctx.generateConfig();
      ctx.registerHooks();
      nuxt2ViewerConfig = twConfig.dst || _config;
      if (moduleOptions.exposeConfig) {
        const exposeConfig = resolveExposeConfig(moduleOptions.exposeConfig);
        const exposeTemplates = createExposeTemplates(exposeConfig);
        nuxt.hook("tailwindcss:internal:regenerateTemplates", () => kit.updateTemplates({ filter: (template) => exposeTemplates.includes(template.dst) }));
      }
      if (!shouldInstallTWVitePlugin) {
        const postcssOptions = nuxt.options.postcss || nuxt.options.build.postcss.postcssOptions || nuxt.options.build.postcss;
        const pluginsToAdd = isTailwind4 ? { "@tailwindcss/postcss": {} } : {
          "tailwindcss/nesting": postcssOptions.plugins?.["tailwindcss/nesting"] ?? {},
          "tailwindcss": twConfig.dst || _config
        };
        postcssOptions.plugins = {
          ...postcssOptions.plugins || {},
          ...pluginsToAdd
        };
      }
      if (nuxt.options.dev && !kit.isNuxtMajorVersion(2, nuxt)) {
        if (moduleOptions.viewer) {
          const viewerConfig = resolveViewerConfig(moduleOptions.viewer);
          setupViewer(twConfig.dst || _config, viewerConfig, nuxt);
        }
      } else {
        if (!nuxt.options.dev)
          return;
        if (moduleOptions.viewer) {
          const viewerConfig = resolveViewerConfig(moduleOptions.viewer);
          exportViewer(twConfig.dst || kit.addTemplate({ filename: "tailwind.config/viewer-config.cjs", getContents: () => `module.exports = ${JSON.stringify(_config)}`, write: true }).dst, viewerConfig);
        }
      }
    });
    if (nuxt.options.dev && moduleOptions.viewer && kit.isNuxtMajorVersion(2, nuxt)) {
      const viewerConfig = resolveViewerConfig(moduleOptions.viewer);
      setupViewer(nuxt2ViewerConfig, viewerConfig, nuxt);
    }
  }
});

module.exports = module$1;
