// src/index.ts
export * from "magic-string-ast";
export * from "ast-kit";

// src/ast.ts
import { walkIdentifiers } from "@vue/compiler-sfc";
import { isFunctionType, isLiteralType, resolveObjectKey } from "ast-kit";
function checkInvalidScopeReference(node, method, setupBindings) {
  if (!node) return;
  walkIdentifiers(node, (id) => {
    if (setupBindings.includes(id.name))
      throw new SyntaxError(
        `\`${method}()\` in <script setup> cannot reference locally declared variables (${id.name}) because it will be hoisted outside of the setup() function.`
      );
  });
}
function isStaticExpression(node, options = {}) {
  const { magicComment, fn, object, objectMethod, array, unary, regex } = options;
  if (magicComment && node.leadingComments?.some(
    (comment) => comment.value.trim() === magicComment
  ))
    return true;
  else if (fn && isFunctionType(node)) return true;
  switch (node.type) {
    case "UnaryExpression":
      return !!unary && isStaticExpression(node.argument, options);
    case "LogicalExpression":
    // 1 > 2
    case "BinaryExpression":
      return isStaticExpression(node.left, options) && isStaticExpression(node.right, options);
    case "ConditionalExpression":
      return isStaticExpression(node.test, options) && isStaticExpression(node.consequent, options) && isStaticExpression(node.alternate, options);
    case "SequenceExpression":
    // (1, 2)
    case "TemplateLiteral":
      return node.expressions.every((expr) => isStaticExpression(expr, options));
    case "ArrayExpression":
      return !!array && node.elements.every(
        (element) => element && isStaticExpression(element, options)
      );
    case "ObjectExpression":
      return !!object && node.properties.every((prop) => {
        if (prop.type === "SpreadElement") {
          return prop.argument.type === "ObjectExpression" && isStaticExpression(prop.argument, options);
        } else if (!isLiteralType(prop.key) && prop.computed) {
          return false;
        } else if (prop.type === "ObjectProperty" && !isStaticExpression(prop.value, options)) {
          return false;
        }
        if (prop.type === "ObjectMethod" && !objectMethod) {
          return false;
        }
        return true;
      });
    case "ParenthesizedExpression":
    // (1)
    case "TSNonNullExpression":
    // 1!
    case "TSAsExpression":
    // 1 as number
    case "TSTypeAssertion":
    // (<number>2)
    case "TSSatisfiesExpression":
      return isStaticExpression(node.expression, options);
    case "RegExpLiteral":
      return !!regex;
  }
  if (isLiteralType(node)) return true;
  return false;
}
function isStaticObjectKey(node) {
  return node.properties.every((prop) => {
    if (prop.type === "SpreadElement") {
      return prop.argument.type === "ObjectExpression" && isStaticObjectKey(prop.argument);
    }
    return !prop.computed || isLiteralType(prop.key);
  });
}
function resolveObjectExpression(node) {
  const maps = /* @__PURE__ */ Object.create(null);
  for (const property of node.properties) {
    if (property.type === "SpreadElement") {
      if (property.argument.type !== "ObjectExpression")
        return void 0;
      Object.assign(maps, resolveObjectExpression(property.argument));
    } else {
      const key = resolveObjectKey(property);
      maps[key] = property;
    }
  }
  return maps;
}
var importedMap = /* @__PURE__ */ new WeakMap();
var HELPER_PREFIX = "__MACROS_";
function importHelperFn(s, offset, local, from = "vue", isDefault = false) {
  const imported = isDefault ? "default" : local;
  const cacheKey = `${from}@${imported}`;
  if (!importedMap.get(s)?.has(cacheKey)) {
    s.appendLeft(
      offset,
      `
import ${isDefault ? HELPER_PREFIX + local : `{ ${imported} as ${HELPER_PREFIX + local} }`} from ${JSON.stringify(from)};`
    );
    if (!importedMap.has(s)) {
      importedMap.set(s, /* @__PURE__ */ new Set([cacheKey]));
    } else {
      importedMap.get(s).add(cacheKey);
    }
  }
  return `${HELPER_PREFIX}${local}`;
}

// src/constants.ts
var DEFINE_PROPS = "defineProps";
var DEFINE_PROPS_DOLLAR = "$defineProps";
var DEFINE_PROPS_REFS = "definePropsRefs";
var DEFINE_EMITS = "defineEmits";
var WITH_DEFAULTS = "withDefaults";
var DEFINE_OPTIONS = "defineOptions";
var DEFINE_MODELS = "defineModels";
var DEFINE_MODELS_DOLLAR = "$defineModels";
var DEFINE_SETUP_COMPONENT = "defineSetupComponent";
var DEFINE_RENDER = "defineRender";
var DEFINE_SLOTS = "defineSlots";
var DEFINE_STYLEX = "defineStyleX";
var DEFINE_PROP = "defineProp";
var DEFINE_PROP_DOLLAR = "$defineProp";
var DEFINE_EMIT = "defineEmit";
var REPO_ISSUE_URL = `${"https://github.com/vue-macros/vue-macros"}/issues`;
var REGEX_SRC_FILE = /\.[cm]?[jt]sx?$/;
var REGEX_SETUP_SFC = /\.setup\.[cm]?[jt]sx?$/;
var REGEX_SETUP_SFC_SUB = /\.setup\.[cm]?[jt]sx?((?!(?<!definePage&)vue&).)*$/;
var REGEX_VUE_SFC = /\.vue$/;
var REGEX_VUE_SUB = /\.vue\?vue&type=script/;
var REGEX_VUE_SUB_SETUP = /\.vue\?vue&type=script\b.+\bsetup=true/;
var REGEX_NODE_MODULES = /node_modules/;
var REGEX_SUPPORTED_EXT = /\.([cm]?[jt]sx?|vue)$/;
var VIRTUAL_ID_PREFIX = "/vue-macros";

// src/dep.ts
var _require;
if (false) {
  _require = __require;
} else if (typeof process !== "undefined" && process.version) {
  if (process.getBuiltinModule) {
    const module = process.getBuiltinModule("module");
    _require = module.createRequire(import.meta.url);
  } else {
    import("node:module").then(
      ({ default: { createRequire } }) => {
        _require = createRequire(import.meta.url);
      },
      () => {
      }
    );
  }
}
function detectVueVersion(root, defaultVersion = 3.5) {
  if (!_require) {
    console.warn(`Cannot detect Vue version. Default to Vue ${defaultVersion}`);
    return defaultVersion;
  }
  const { resolve: resolve2 } = _require("node:path");
  const { statSync } = _require("node:fs");
  const { getPackageInfoSync } = _require(
    "local-pkg"
  );
  root ||= process.cwd();
  let isFile = false;
  try {
    isFile = statSync(root).isFile();
  } catch {
  }
  const paths = [root];
  if (!isFile) paths.unshift(resolve2(root, "_index.js"));
  const vuePkg = getPackageInfoSync("vue", { paths });
  if (vuePkg && vuePkg.version) {
    const version = Number.parseFloat(vuePkg.version);
    return version >= 2 && version < 3 ? Math.trunc(version) : version;
  } else {
    return defaultVersion;
  }
}

// src/filter.ts
import { isAbsolute, posix, resolve } from "pathe";
import pm from "picomatch";

// src/general.ts
var isArray = Array.isArray;
function toArray(thing) {
  if (isArray(thing)) return thing;
  if (thing == null) return [];
  return [thing];
}

// src/path.ts
function normalizePath(filename) {
  return filename.replaceAll("\\", "/");
}

// src/filter.ts
function getMatcherString(id, resolutionBase) {
  if (resolutionBase === false || isAbsolute(id) || id.startsWith("**")) {
    return normalizePath(id);
  }
  const basePath = normalizePath(resolve(resolutionBase || "")).replaceAll(/[-^$*+?.()|[\]{}]/g, String.raw`\$&`);
  return posix.join(basePath, normalizePath(id));
}
function createRollupFilter(include, exclude, options) {
  const resolutionBase = options && options.resolve;
  const getMatcher = (id) => id instanceof RegExp ? id : {
    test: (what) => {
      const pattern = getMatcherString(id, resolutionBase);
      const fn = pm(pattern, { dot: true });
      const result = fn(what);
      return result;
    }
  };
  const includeMatchers = toArray(include).map(getMatcher);
  const excludeMatchers = toArray(exclude).map(getMatcher);
  if (!includeMatchers.length && !excludeMatchers.length)
    return (id) => typeof id === "string" && !id.includes("\0");
  return function result(id) {
    if (typeof id !== "string") return false;
    if (id.includes("\0")) return false;
    const pathId = normalizePath(id);
    for (const matcher of excludeMatchers) {
      if (matcher instanceof RegExp) {
        matcher.lastIndex = 0;
      }
      if (matcher.test(pathId)) return false;
    }
    for (const matcher of includeMatchers) {
      if (matcher instanceof RegExp) {
        matcher.lastIndex = 0;
      }
      if (matcher.test(pathId)) return true;
    }
    return !includeMatchers.length;
  };
}

// src/unplugin.ts
import { generateTransform } from "magic-string-ast";
var getTransformResult = generateTransform;
function createFilter(options) {
  return createRollupFilter(options.include, options.exclude);
}
var VUE3_PLUGINS = ["vite:vue", "unplugin-vue"];
var VUE2_PLUGINS = ["vite:vue2", "unplugin-vue2"];
function getVuePluginApi(plugins) {
  const vuePlugin = (plugins || []).find(
    (p) => [...VUE3_PLUGINS, ...VUE2_PLUGINS].includes(p.name)
  );
  if (!vuePlugin)
    throw new Error(
      "Cannot find Vue plugin (@vitejs/plugin-vue or unplugin-vue). Please make sure to add it before using Vue Macros."
    );
  if (VUE2_PLUGINS.includes(vuePlugin.name)) {
    return null;
  }
  const api = vuePlugin.api;
  if (!api?.version) {
    throw new Error(
      "The Vue plugin is not supported (@vitejs/plugin-vue or unplugin-vue). Please make sure version > 4.3.4."
    );
  }
  return api;
}
var FilterFileType = /* @__PURE__ */ ((FilterFileType2) => {
  FilterFileType2[FilterFileType2["VUE_SFC"] = 0] = "VUE_SFC";
  FilterFileType2[FilterFileType2["VUE_SFC_WITH_SETUP"] = 1] = "VUE_SFC_WITH_SETUP";
  FilterFileType2[FilterFileType2["SETUP_SFC"] = 2] = "SETUP_SFC";
  FilterFileType2[FilterFileType2["SRC_FILE"] = 3] = "SRC_FILE";
  return FilterFileType2;
})(FilterFileType || {});
function getFilterPattern(types, framework) {
  const filter = [];
  const isWebpackLike = framework === "webpack" || framework === "rspack";
  if (types.includes(0 /* VUE_SFC */)) {
    filter.push(isWebpackLike ? REGEX_VUE_SUB : REGEX_VUE_SFC);
  }
  if (types.includes(1 /* VUE_SFC_WITH_SETUP */)) {
    filter.push(isWebpackLike ? REGEX_VUE_SUB_SETUP : REGEX_VUE_SFC);
  }
  if (types.includes(2 /* SETUP_SFC */)) {
    filter.push(REGEX_SETUP_SFC);
  }
  if (types.includes(3 /* SRC_FILE */)) {
    filter.push(REGEX_SRC_FILE);
  }
  return filter;
}
function hackViteHMR(ctx, filter, callback) {
  if (!filter(ctx.file)) return;
  const { read } = ctx;
  ctx.read = async () => {
    const code = await read();
    const result = await callback(code, ctx.file);
    return result?.code || code;
  };
}

// src/vue.ts
import {
  parse
} from "@vue/compiler-sfc";
import { babelParse, getLang, resolveString } from "ast-kit";
function parseSFC(code, id) {
  const sfc = parse(code, {
    filename: id
  });
  const { descriptor, errors } = sfc;
  const scriptLang = sfc.descriptor.script?.lang;
  const scriptSetupLang = sfc.descriptor.scriptSetup?.lang;
  if (sfc.descriptor.script && sfc.descriptor.scriptSetup && (scriptLang || "js") !== (scriptSetupLang || "js")) {
    throw new Error(
      `[unplugin-vue-macros] <script> and <script setup> must have the same language type.`
    );
  }
  const lang = scriptLang || scriptSetupLang;
  return Object.assign({}, descriptor, {
    sfc,
    lang,
    errors,
    offset: descriptor.scriptSetup?.loc.start.offset ?? 0,
    getSetupAst() {
      if (!descriptor.scriptSetup) return;
      return babelParse(descriptor.scriptSetup.content, lang, {
        plugins: [["importAttributes", { deprecatedAssertSyntax: true }]],
        cache: true
      });
    },
    getScriptAst() {
      if (!descriptor.script) return;
      return babelParse(descriptor.script.content, lang, {
        plugins: [["importAttributes", { deprecatedAssertSyntax: true }]],
        cache: true
      });
    }
  });
}
function getFileCodeAndLang(code, id) {
  if (!REGEX_VUE_SFC.test(id)) {
    return {
      code,
      lang: getLang(id)
    };
  }
  const sfc = parseSFC(code, id);
  const scriptCode = sfc.script?.content ?? "";
  return {
    code: sfc.scriptSetup ? `${scriptCode}
;
${sfc.scriptSetup.content}` : scriptCode,
    lang: sfc.lang ?? "js"
  };
}
function addNormalScript({ script, lang }, s) {
  return {
    start() {
      if (script) return script.loc.end.offset;
      const attrs = lang ? ` lang="${lang}"` : "";
      s.prependLeft(0, `<script${attrs}>`);
      return 0;
    },
    end() {
      if (!script) s.appendRight(0, `
</script>
`);
    }
  };
}
function removeMacroImport(node, s, offset) {
  if (node.type === "ImportDeclaration" && node.attributes?.some(
    (attr) => resolveString(attr.key) === "type" && attr.value.value === "macro"
  )) {
    s.removeNode(node, { offset });
    return true;
  }
}
export {
  DEFINE_EMIT,
  DEFINE_EMITS,
  DEFINE_MODELS,
  DEFINE_MODELS_DOLLAR,
  DEFINE_OPTIONS,
  DEFINE_PROP,
  DEFINE_PROPS,
  DEFINE_PROPS_DOLLAR,
  DEFINE_PROPS_REFS,
  DEFINE_PROP_DOLLAR,
  DEFINE_RENDER,
  DEFINE_SETUP_COMPONENT,
  DEFINE_SLOTS,
  DEFINE_STYLEX,
  FilterFileType,
  HELPER_PREFIX,
  REGEX_NODE_MODULES,
  REGEX_SETUP_SFC,
  REGEX_SETUP_SFC_SUB,
  REGEX_SRC_FILE,
  REGEX_SUPPORTED_EXT,
  REGEX_VUE_SFC,
  REGEX_VUE_SUB,
  REGEX_VUE_SUB_SETUP,
  REPO_ISSUE_URL,
  VIRTUAL_ID_PREFIX,
  WITH_DEFAULTS,
  addNormalScript,
  checkInvalidScopeReference,
  createFilter,
  createRollupFilter,
  detectVueVersion,
  getFileCodeAndLang,
  getFilterPattern,
  getTransformResult,
  getVuePluginApi,
  hackViteHMR,
  importHelperFn,
  isStaticExpression,
  isStaticObjectKey,
  normalizePath,
  parseSFC,
  removeMacroImport,
  resolveObjectExpression,
  toArray
};
