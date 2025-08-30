import fs, { promises, existsSync, readdirSync, writeFileSync, statSync, readFileSync, mkdirSync } from 'node:fs';
import { mkdir, readFile, readdir, writeFile, rm, stat, unlink, open } from 'node:fs/promises';
import { randomUUID } from 'node:crypto';
import { AsyncLocalStorage } from 'node:async_hooks';
import { dirname, resolve, normalize, basename, extname, relative, isAbsolute, join } from 'pathe';
import { createHooks, createDebugger } from 'hookable';
import ignore from 'ignore';
import { tryUseNuxt, directoryToURL, useLogger, useNuxt, resolveFiles, resolvePath, defineNuxtModule, findPath, addPlugin, addTemplate, addTypeTemplate, addComponent, useNitro, addBuildPlugin, isIgnored, resolveAlias, addPluginTemplate, addVitePlugin, createIsIgnored, updateTemplates, tryResolveModule, normalizeModuleTranspilePath, resolveNuxtModule, resolveIgnorePatterns, logger as logger$1, createResolver, importModule, tryImportModule, runWithNuxtContext, nuxtCtx, loadNuxtConfig, addWebpackPlugin, addServerPlugin, installModule, addServerTemplate, addServerHandler, addRouteMiddleware, normalizeTemplate, compileTemplate, normalizePlugin, templateUtils } from '@nuxt/kit';
import { resolvePackageJSON, readPackageJSON } from 'pkg-types';
import { hash, serialize } from 'ohash';
import consola, { consola as consola$1 } from 'consola';
import onChange from 'on-change';
import { colors } from 'consola/utils';
import { resolveCompatibilityDatesFromEnv, formatDate } from 'compatx';
import escapeRE from 'escape-string-regexp';
import { withTrailingSlash, parseURL, parseQuery, joinURL, withLeadingSlash, encodePath, withoutLeadingSlash } from 'ufo';
import { ImpoundPlugin } from 'impound';
import defu$1, { defu } from 'defu';
import { satisfies, gt } from 'semver';
import { isWindows, hasTTY, isCI } from 'std-env';
import { genArrayFromRaw, genSafeVariableName, genImport, genDynamicImport, genObjectFromRawEntries, genString, genExport } from 'knitwork';
import { resolveModulePath } from 'exsolve';
import { createRoutesContext } from 'unplugin-vue-router';
import { resolveOptions } from 'unplugin-vue-router/options';
import { toRouteMatcher, createRouter, exportMatcher } from 'radix3';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { runInNewContext } from 'node:vm';
import { filename } from 'pathe/utils';
import { klona } from 'klona';
import { walk as walk$1 } from 'estree-walker';
import { transform as transform$1 } from 'esbuild';
import { parseSync } from 'oxc-parser';
import { splitByCase, kebabCase, pascalCase, camelCase } from 'scule';
import { createUnplugin } from 'unplugin';
import { findStaticImports, findExports, parseStaticImport, parseNodeModulePath, lookupNodeModuleSubpath } from 'mlly';
import MagicString from 'magic-string';
import { stripLiteral } from 'strip-literal';
import { unheadVueComposablesImports } from '@unhead/vue';
import { glob } from 'tinyglobby';
import { parse, walk as walk$2, ELEMENT_NODE } from 'ultrahtml';
import { createUnimport, defineUnimportPreset, toExports, scanDirExports } from 'unimport';
import { parseQuery as parseQuery$1 } from 'vue-router';
import { createTransformer } from 'unctx/transform';
import { cpus } from 'node:os';
import { createNitro, scanHandlers, writeTypes, copyPublicAssets, prepare, build as build$1, prerender, createDevServer } from 'nitropack';
import { dynamicEventHandler } from 'h3';
import { watch as watch$1 } from 'chokidar';
import { debounce } from 'perfect-debounce';
import { resolveSchema, generateTypes } from 'untyped';
import untypedPlugin from 'untyped/babel-plugin';
import { createJiti } from 'jiti';
import { resolve as resolve$1 } from 'node:path';
import { parseTar, createTar } from 'nanotar';

let _distDir = dirname(fileURLToPath(import.meta.url));
if (_distDir.match(/(chunks|shared)$/)) {
  _distDir = dirname(_distDir);
}
const distDir = _distDir;
const pkgDir = resolve(distDir, "..");

async function resolveTypePath(path, subpath, searchPaths = tryUseNuxt()?.options.modulesDir) {
  try {
    const r = resolveModulePath(path, {
      from: searchPaths?.map((d) => directoryToURL(d)),
      conditions: ["types", "import", "require"],
      extensions: [".js", ".mjs", ".cjs", ".ts", ".mts", ".cts"]
    });
    if (subpath) {
      return r.replace(/(?:\.d)?\.[mc]?[jt]s$/, "");
    }
    const rootPath = await resolvePackageJSON(r);
    return dirname(rootPath);
  } catch {
    return null;
  }
}

function toArray(value) {
  return Array.isArray(value) ? value : [value];
}
async function isDirectory$1(path) {
  return (await promises.lstat(path)).isDirectory();
}
const logger = useLogger("nuxt");

async function transform(input, options) {
  return await transform$1(input, { ...tryUseNuxt()?.options.esbuild.options, ...options });
}
function walk(ast, callback) {
  return walk$1(ast, {
    enter(node, parent, key, index) {
      callback.scopeTracker?.processNodeEnter(node);
      callback.enter?.call(this, node, parent, { key, index, ast });
    },
    leave(node, parent, key, index) {
      callback.scopeTracker?.processNodeLeave(node);
      callback.leave?.call(this, node, parent, { key, index, ast });
    }
  });
}
function parseAndWalk(code, sourceFilename, callback) {
  const lang = sourceFilename.match(/\.[cm]?([jt]sx?)$/)?.[1] || "ts";
  const ast = parseSync(sourceFilename, code, { sourceType: "module", lang });
  walk(ast.program, typeof callback === "function" ? { enter: callback } : callback);
  return ast.program;
}
function withLocations(node) {
  return node;
}
function isChildScope(a, b) {
  return a.startsWith(b) && a.length > b.length;
}
class BaseNode {
  scope;
  node;
  constructor(node, scope) {
    this.node = node;
    this.scope = scope;
  }
  /**
   * Check if the node is defined under a specific scope.
   * @param scope
   */
  isUnderScope(scope) {
    return isChildScope(this.scope, scope);
  }
}
class IdentifierNode extends BaseNode {
  type = "Identifier";
  get start() {
    return this.node.start;
  }
  get end() {
    return this.node.end;
  }
}
class FunctionParamNode extends BaseNode {
  type = "FunctionParam";
  fnNode;
  constructor(node, scope, fnNode) {
    super(node, scope);
    this.fnNode = fnNode;
  }
  get start() {
    return this.fnNode.start;
  }
  get end() {
    return this.fnNode.end;
  }
}
class FunctionNode extends BaseNode {
  type = "Function";
  get start() {
    return this.node.start;
  }
  get end() {
    return this.node.end;
  }
}
class VariableNode extends BaseNode {
  type = "Variable";
  variableNode;
  constructor(node, scope, variableNode) {
    super(node, scope);
    this.variableNode = variableNode;
  }
  get start() {
    return this.variableNode.start;
  }
  get end() {
    return this.variableNode.end;
  }
}
class ImportNode extends BaseNode {
  type = "Import";
  importNode;
  constructor(node, scope, importNode) {
    super(node, scope);
    this.importNode = importNode;
  }
  get start() {
    return this.importNode.start;
  }
  get end() {
    return this.importNode.end;
  }
}
class CatchParamNode extends BaseNode {
  type = "CatchParam";
  catchNode;
  constructor(node, scope, catchNode) {
    super(node, scope);
    this.catchNode = catchNode;
  }
  get start() {
    return this.catchNode.start;
  }
  get end() {
    return this.catchNode.end;
  }
}
class ScopeTracker {
  scopeIndexStack = [];
  scopeIndexKey = "";
  scopes = /* @__PURE__ */ new Map();
  options;
  isFrozen = false;
  constructor(options = {}) {
    this.options = options;
  }
  updateScopeIndexKey() {
    this.scopeIndexKey = this.scopeIndexStack.slice(0, -1).join("-");
  }
  pushScope() {
    this.scopeIndexStack.push(0);
    this.updateScopeIndexKey();
  }
  popScope() {
    this.scopeIndexStack.pop();
    if (this.scopeIndexStack[this.scopeIndexStack.length - 1] !== void 0) {
      this.scopeIndexStack[this.scopeIndexStack.length - 1]++;
    }
    if (!this.options.keepExitedScopes) {
      this.scopes.delete(this.scopeIndexKey);
    }
    this.updateScopeIndexKey();
  }
  declareIdentifier(name, data) {
    if (this.isFrozen) {
      return;
    }
    let scope = this.scopes.get(this.scopeIndexKey);
    if (!scope) {
      scope = /* @__PURE__ */ new Map();
      this.scopes.set(this.scopeIndexKey, scope);
    }
    scope.set(name, data);
  }
  declareFunctionParameter(param, fn) {
    if (this.isFrozen) {
      return;
    }
    const identifiers = getPatternIdentifiers(param);
    for (const identifier of identifiers) {
      this.declareIdentifier(identifier.name, new FunctionParamNode(identifier, this.scopeIndexKey, fn));
    }
  }
  declarePattern(pattern, parent) {
    if (this.isFrozen) {
      return;
    }
    const identifiers = getPatternIdentifiers(pattern);
    for (const identifier of identifiers) {
      this.declareIdentifier(
        identifier.name,
        parent.type === "VariableDeclaration" ? new VariableNode(identifier, this.scopeIndexKey, parent) : parent.type === "CatchClause" ? new CatchParamNode(identifier, this.scopeIndexKey, parent) : new FunctionParamNode(identifier, this.scopeIndexKey, parent)
      );
    }
  }
  processNodeEnter(node) {
    switch (node.type) {
      case "Program":
      case "BlockStatement":
      case "StaticBlock":
        this.pushScope();
        break;
      case "FunctionDeclaration":
        if (node.id?.name) {
          this.declareIdentifier(node.id.name, new FunctionNode(node, this.scopeIndexKey));
        }
        this.pushScope();
        for (const param of node.params) {
          this.declareFunctionParameter(withLocations(param), node);
        }
        break;
      case "FunctionExpression":
        this.pushScope();
        if (node.id?.name) {
          this.declareIdentifier(node.id.name, new FunctionNode(node, this.scopeIndexKey));
        }
        this.pushScope();
        for (const param of node.params) {
          this.declareFunctionParameter(withLocations(param), node);
        }
        break;
      case "ArrowFunctionExpression":
        this.pushScope();
        for (const param of node.params) {
          this.declareFunctionParameter(withLocations(param), node);
        }
        break;
      case "VariableDeclaration":
        for (const decl of node.declarations) {
          this.declarePattern(withLocations(decl.id), node);
        }
        break;
      case "ClassDeclaration":
        if (node.id?.name) {
          this.declareIdentifier(node.id.name, new IdentifierNode(withLocations(node.id), this.scopeIndexKey));
        }
        break;
      case "ClassExpression":
        this.pushScope();
        if (node.id?.name) {
          this.declareIdentifier(node.id.name, new IdentifierNode(withLocations(node.id), this.scopeIndexKey));
        }
        break;
      case "ImportDeclaration":
        for (const specifier of node.specifiers) {
          this.declareIdentifier(specifier.local.name, new ImportNode(withLocations(specifier), this.scopeIndexKey, node));
        }
        break;
      case "CatchClause":
        this.pushScope();
        if (node.param) {
          this.declarePattern(withLocations(node.param), node);
        }
        break;
      case "ForStatement":
      case "ForOfStatement":
      case "ForInStatement":
        this.pushScope();
        if (node.type === "ForStatement" && node.init?.type === "VariableDeclaration") {
          for (const decl of node.init.declarations) {
            this.declarePattern(withLocations(decl.id), withLocations(node.init));
          }
        } else if ((node.type === "ForOfStatement" || node.type === "ForInStatement") && node.left.type === "VariableDeclaration") {
          for (const decl of node.left.declarations) {
            this.declarePattern(withLocations(decl.id), withLocations(node.left));
          }
        }
        break;
    }
  }
  processNodeLeave(node) {
    switch (node.type) {
      case "Program":
      case "BlockStatement":
      case "CatchClause":
      case "FunctionDeclaration":
      case "ArrowFunctionExpression":
      case "StaticBlock":
      case "ClassExpression":
      case "ForStatement":
      case "ForOfStatement":
      case "ForInStatement":
        this.popScope();
        break;
      case "FunctionExpression":
        this.popScope();
        this.popScope();
        break;
    }
  }
  isDeclared(name) {
    if (!this.scopeIndexKey) {
      return this.scopes.get("")?.has(name) || false;
    }
    const indices = this.scopeIndexKey.split("-").map(Number);
    for (let i = indices.length; i >= 0; i--) {
      if (this.scopes.get(indices.slice(0, i).join("-"))?.has(name)) {
        return true;
      }
    }
    return false;
  }
  getDeclaration(name) {
    if (!this.scopeIndexKey) {
      return this.scopes.get("")?.get(name) ?? null;
    }
    const indices = this.scopeIndexKey.split("-").map(Number);
    for (let i = indices.length; i >= 0; i--) {
      const node = this.scopes.get(indices.slice(0, i).join("-"))?.get(name);
      if (node) {
        return node;
      }
    }
    return null;
  }
  getCurrentScope() {
    return this.scopeIndexKey;
  }
  /**
   * Check if the current scope is a child of a specific scope.
   * @example
   * ```ts
   * // current scope is 0-1
   * isCurrentScopeUnder('0') // true
   * isCurrentScopeUnder('0-1') // false
   * ```
   *
   * @param scope the parent scope
   * @returns `true` if the current scope is a child of the specified scope, `false` otherwise (also when they are the same)
   */
  isCurrentScopeUnder(scope) {
    return isChildScope(this.scopeIndexKey, scope);
  }
  /**
   * Freezes the scope tracker, preventing further declarations.
   * It also resets the scope index stack to its initial state, so that the scope tracker can be reused.
   *
   * This is useful for second passes through the AST.
   */
  freeze() {
    this.isFrozen = true;
    this.scopeIndexStack = [];
    this.updateScopeIndexKey();
  }
}
function getPatternIdentifiers(pattern) {
  const identifiers = [];
  function collectIdentifiers(pattern2) {
    switch (pattern2.type) {
      case "Identifier":
        identifiers.push(pattern2);
        break;
      case "AssignmentPattern":
        collectIdentifiers(withLocations(pattern2.left));
        break;
      case "RestElement":
        collectIdentifiers(withLocations(pattern2.argument));
        break;
      case "ArrayPattern":
        for (const element of pattern2.elements) {
          if (element) {
            collectIdentifiers(withLocations(element.type === "RestElement" ? element.argument : element));
          }
        }
        break;
      case "ObjectPattern":
        for (const property of pattern2.properties) {
          collectIdentifiers(withLocations(property.type === "RestElement" ? property.argument : property.value));
        }
        break;
    }
  }
  collectIdentifiers(pattern);
  return identifiers;
}
function isNotReferencePosition(node, parent) {
  if (!parent || node.type !== "Identifier") {
    return false;
  }
  switch (parent.type) {
    case "FunctionDeclaration":
    case "FunctionExpression":
    case "ArrowFunctionExpression":
      if (parent.type !== "ArrowFunctionExpression" && parent.id === node) {
        return true;
      }
      if (parent.params.length) {
        for (const param of parent.params) {
          const identifiers = getPatternIdentifiers(withLocations(param));
          if (identifiers.includes(node)) {
            return true;
          }
        }
      }
      return false;
    case "ClassDeclaration":
    case "ClassExpression":
      return parent.id === node;
    case "MethodDefinition":
      return parent.key === node;
    case "PropertyDefinition":
      return parent.key === node;
    case "VariableDeclarator":
      return getPatternIdentifiers(withLocations(parent.id)).includes(node);
    case "CatchClause":
      if (!parent.param) {
        return false;
      }
      return getPatternIdentifiers(withLocations(parent.param)).includes(node);
    case "Property":
      return parent.key === node && parent.value !== node;
    case "MemberExpression":
      return parent.property === node;
  }
  return false;
}
function getUndeclaredIdentifiersInFunction(node) {
  const scopeTracker = new ScopeTracker({
    keepExitedScopes: true
  });
  const undeclaredIdentifiers = /* @__PURE__ */ new Set();
  function isIdentifierUndeclared(node2, parent) {
    return !isNotReferencePosition(node2, parent) && !scopeTracker.isDeclared(node2.name);
  }
  walk(node, {
    scopeTracker
  });
  scopeTracker.freeze();
  walk(node, {
    scopeTracker,
    enter(node2, parent) {
      if (node2.type === "Identifier" && isIdentifierUndeclared(node2, parent)) {
        undeclaredIdentifiers.add(node2.name);
      }
    }
  });
  return Array.from(undeclaredIdentifiers);
}

function getNameFromPath(path, relativeTo) {
  const relativePath = relativeTo ? normalize(path).replace(withTrailingSlash(normalize(relativeTo)), "") : basename(path);
  const prefixParts = splitByCase(dirname(relativePath));
  const fileName = basename(relativePath, extname(relativePath));
  const segments = resolveComponentNameSegments(fileName.toLowerCase() === "index" ? "" : fileName, prefixParts).filter(Boolean);
  return kebabCase(segments).replace(QUOTE_RE, "");
}
function hasSuffix(path, suffix) {
  return basename(path, extname(path)).endsWith(suffix);
}
function resolveComponentNameSegments(fileName, prefixParts) {
  const fileNameParts = splitByCase(fileName);
  const fileNamePartsContent = fileNameParts.join("/").toLowerCase();
  const componentNameParts = prefixParts.flatMap((p) => splitByCase(p));
  let index = prefixParts.length - 1;
  const matchedSuffix = [];
  while (index >= 0) {
    const prefixPart = prefixParts[index];
    matchedSuffix.unshift(...splitByCase(prefixPart).map((p) => p.toLowerCase()));
    const matchedSuffixContent = matchedSuffix.join("/");
    if (fileNamePartsContent === matchedSuffixContent || fileNamePartsContent.startsWith(matchedSuffixContent + "/") || // e.g Item/Item/Item.vue -> Item
    prefixPart.toLowerCase() === fileNamePartsContent && prefixParts[index + 1] && prefixParts[index] === prefixParts[index + 1]) {
      componentNameParts.length = index;
    }
    index--;
  }
  return [...componentNameParts, ...fileNameParts];
}

function isVue(id, opts = {}) {
  const { search } = parseURL(decodeURIComponent(pathToFileURL(id).href));
  if (id.endsWith(".vue") && !search) {
    return true;
  }
  if (!search) {
    return false;
  }
  const query = parseQuery(search);
  if (query.nuxt_component) {
    return false;
  }
  if (query.macro && (search === "?macro=true" || !opts.type || opts.type.includes("script"))) {
    return true;
  }
  const type = "setup" in query ? "script" : query.type;
  if (!("vue" in query) || opts.type && !opts.type.includes(type)) {
    return false;
  }
  return true;
}
const JS_RE$1 = /\.(?:[cm]?j|t)sx?$/;
function isJS(id) {
  const { pathname } = parseURL(decodeURIComponent(pathToFileURL(id).href));
  return JS_RE$1.test(pathname);
}
function getLoader(id) {
  const { pathname } = parseURL(decodeURIComponent(pathToFileURL(id).href));
  const ext = extname(pathname);
  if (ext === ".vue") {
    return "vue";
  }
  if (!JS_RE$1.test(ext)) {
    return null;
  }
  return ext.endsWith("x") ? "tsx" : "ts";
}
function matchWithStringOrRegex(value, matcher) {
  if (typeof matcher === "string") {
    return value === matcher;
  } else if (matcher instanceof RegExp) {
    return matcher.test(value);
  }
  return false;
}

function uniqueBy(arr, key) {
  if (arr.length < 2) {
    return arr;
  }
  const res = [];
  const seen = /* @__PURE__ */ new Set();
  for (const item of arr) {
    if (seen.has(item[key])) {
      continue;
    }
    seen.add(item[key]);
    res.push(item);
  }
  return res;
}
const QUOTE_RE = /["']/g;
const EXTENSION_RE = /\b\.\w+$/g;
const SX_RE = /\.[tj]sx$/;

const enUSComparator = new Intl.Collator("en-US");
async function resolvePagesRoutes(pattern, nuxt = useNuxt()) {
  const pagesDirs = nuxt.options._layers.map(
    (layer) => resolve(layer.config.srcDir, (layer.config.rootDir === nuxt.options.rootDir ? nuxt.options.dir : layer.config.dir)?.pages || "pages")
  );
  const scannedFiles = [];
  for (const dir of pagesDirs) {
    const files = await resolveFiles(dir, pattern);
    scannedFiles.push(...files.map((file) => ({ relativePath: relative(dir, file), absolutePath: file })));
  }
  scannedFiles.sort((a, b) => enUSComparator.compare(a.relativePath, b.relativePath));
  const allRoutes = generateRoutesFromFiles(uniqueBy(scannedFiles, "relativePath"), {
    shouldUseServerComponents: !!nuxt.options.experimental.componentIslands
  });
  const pages = uniqueBy(allRoutes, "path");
  const shouldAugment = nuxt.options.experimental.scanPageMeta || nuxt.options.experimental.typedPages;
  if (shouldAugment === false) {
    await nuxt.callHook("pages:extend", pages);
    return pages;
  }
  const augmentCtx = {
    extraExtractionKeys: /* @__PURE__ */ new Set(["middleware", ...nuxt.options.experimental.extraPageMetaExtractionKeys]),
    fullyResolvedPaths: new Set(scannedFiles.map((file) => file.absolutePath))
  };
  if (shouldAugment === "after-resolve") {
    await nuxt.callHook("pages:extend", pages);
    await augmentPages(pages, nuxt.vfs, augmentCtx);
  } else {
    const augmentedPages = await augmentPages(pages, nuxt.vfs, augmentCtx);
    await nuxt.callHook("pages:extend", pages);
    await augmentPages(pages, nuxt.vfs, { pagesToSkip: augmentedPages, ...augmentCtx });
    augmentedPages?.clear();
  }
  await nuxt.callHook("pages:resolved", pages);
  return pages;
}
const INDEX_PAGE_RE = /\/index$/;
function generateRoutesFromFiles(files, options = {}) {
  if (!files.length) {
    return [];
  }
  const routes = [];
  const sortedFiles = [...files].sort((a, b) => a.relativePath.length - b.relativePath.length);
  for (const file of sortedFiles) {
    const segments = file.relativePath.replace(new RegExp(`${escapeRE(extname(file.relativePath))}$`), "").split("/");
    const route = {
      name: "",
      path: "",
      file: file.absolutePath,
      children: []
    };
    let parent = routes;
    const lastSegment = segments[segments.length - 1];
    if (lastSegment.endsWith(".server")) {
      segments[segments.length - 1] = lastSegment.replace(".server", "");
      if (options.shouldUseServerComponents) {
        route.mode = "server";
      }
    } else if (lastSegment.endsWith(".client")) {
      segments[segments.length - 1] = lastSegment.replace(".client", "");
      route.mode = "client";
    }
    for (let i = 0; i < segments.length; i++) {
      const segment = segments[i];
      const tokens = parseSegment(segment, file.absolutePath);
      if (tokens.every((token) => token.type === 4 /* group */)) {
        continue;
      }
      const segmentName = tokens.map(({ value, type }) => type === 4 /* group */ ? "" : value).join("");
      route.name += (route.name && "/") + segmentName;
      const routePath = getRoutePath(tokens, segments[i + 1] !== void 0 && segments[i + 1] !== "index");
      const path = withLeadingSlash(joinURL(route.path, routePath.replace(INDEX_PAGE_RE, "/")));
      const child = parent.find((parentRoute) => parentRoute.name === route.name && parentRoute.path === path);
      if (child && child.children) {
        parent = child.children;
        route.path = "";
      } else if (segmentName === "index" && !route.path) {
        route.path += "/";
      } else if (segmentName !== "index") {
        route.path += routePath;
      }
    }
    parent.push(route);
  }
  return prepareRoutes(routes);
}
async function augmentPages(routes, vfs, ctx = {}) {
  ctx.augmentedPages ??= /* @__PURE__ */ new Set();
  for (const route of routes) {
    if (route.file && !ctx.pagesToSkip?.has(route.file)) {
      const fileContent = route.file in vfs ? vfs[route.file] : fs.readFileSync(ctx.fullyResolvedPaths?.has(route.file) ? route.file : await resolvePath(route.file), "utf-8");
      const routeMeta = getRouteMeta(fileContent, route.file, ctx.extraExtractionKeys);
      if (route.meta) {
        routeMeta.meta = { ...routeMeta.meta, ...route.meta };
      }
      Object.assign(route, routeMeta);
      ctx.augmentedPages.add(route.file);
    }
    if (route.children && route.children.length > 0) {
      await augmentPages(route.children, vfs, ctx);
    }
  }
  return ctx.augmentedPages;
}
const SFC_SCRIPT_RE = /<script(?<attrs>[^>]*)>(?<content>[\s\S]*?)<\/script[^>]*>/gi;
function extractScriptContent(sfc) {
  const contents = [];
  for (const match of sfc.matchAll(SFC_SCRIPT_RE)) {
    if (match?.groups?.content) {
      contents.push({
        loader: match.groups.attrs && /[tj]sx/.test(match.groups.attrs) ? "tsx" : "ts",
        code: match.groups.content.trim()
      });
    }
  }
  return contents;
}
const PAGE_META_RE = /definePageMeta\([\s\S]*?\)/;
const defaultExtractionKeys = ["name", "path", "props", "alias", "redirect", "middleware"];
const DYNAMIC_META_KEY = "__nuxt_dynamic_meta_key";
const pageContentsCache = {};
const metaCache$1 = {};
function getRouteMeta(contents, absolutePath, extraExtractionKeys = /* @__PURE__ */ new Set()) {
  if (!(absolutePath in pageContentsCache) || pageContentsCache[absolutePath] !== contents) {
    pageContentsCache[absolutePath] = contents;
    delete metaCache$1[absolutePath];
  }
  if (absolutePath in metaCache$1 && metaCache$1[absolutePath]) {
    return klona(metaCache$1[absolutePath]);
  }
  const loader = getLoader(absolutePath);
  const scriptBlocks = !loader ? null : loader === "vue" ? extractScriptContent(contents) : [{ code: contents, loader }];
  if (!scriptBlocks) {
    metaCache$1[absolutePath] = {};
    return {};
  }
  const extractedMeta = {};
  const extractionKeys = /* @__PURE__ */ new Set([...defaultExtractionKeys, ...extraExtractionKeys]);
  for (const script of scriptBlocks) {
    if (!PAGE_META_RE.test(script.code)) {
      continue;
    }
    const dynamicProperties = /* @__PURE__ */ new Set();
    let foundMeta = false;
    parseAndWalk(script.code, absolutePath.replace(/\.\w+$/, "." + script.loader), (node) => {
      if (foundMeta) {
        return;
      }
      if (node.type !== "ExpressionStatement" || node.expression.type !== "CallExpression" || node.expression.callee.type !== "Identifier" || node.expression.callee.name !== "definePageMeta") {
        return;
      }
      foundMeta = true;
      const pageMetaArgument = node.expression.arguments[0];
      if (pageMetaArgument?.type !== "ObjectExpression") {
        logger.warn(`\`definePageMeta\` must be called with an object literal (reading \`${absolutePath}\`).`);
        return;
      }
      for (const key of extractionKeys) {
        const property = pageMetaArgument.properties.find((property2) => property2.type === "Property" && property2.key.type === "Identifier" && property2.key.name === key);
        if (!property) {
          continue;
        }
        const propertyValue = withLocations(property.value);
        const { value, serializable } = isSerializable(script.code, propertyValue);
        if (!serializable) {
          logger.debug(`Skipping extraction of \`${key}\` metadata as it is not JSON-serializable (reading \`${absolutePath}\`).`);
          dynamicProperties.add(extraExtractionKeys.has(key) ? "meta" : key);
          continue;
        }
        if (extraExtractionKeys.has(key)) {
          extractedMeta.meta ??= {};
          extractedMeta.meta[key] = value;
        } else {
          extractedMeta[key] = value;
        }
      }
      for (const property of pageMetaArgument.properties) {
        if (property.type !== "Property") {
          continue;
        }
        const isIdentifierOrLiteral = property.key.type === "Literal" || property.key.type === "Identifier";
        if (!isIdentifierOrLiteral) {
          continue;
        }
        const name = property.key.type === "Identifier" ? property.key.name : String(property.value);
        if (!extraExtractionKeys.has(name)) {
          dynamicProperties.add("meta");
          break;
        }
      }
      if (dynamicProperties.size) {
        extractedMeta.meta ??= {};
        extractedMeta.meta[DYNAMIC_META_KEY] = dynamicProperties;
      }
    });
  }
  metaCache$1[absolutePath] = extractedMeta;
  return klona(extractedMeta);
}
const COLON_RE = /:/g;
function getRoutePath(tokens, hasSucceedingSegment = false) {
  return tokens.reduce((path, token) => {
    return path + (token.type === 2 /* optional */ ? `:${token.value}?` : token.type === 1 /* dynamic */ ? `:${token.value}()` : token.type === 3 /* catchall */ ? hasSucceedingSegment ? `:${token.value}([^/]*)*` : `:${token.value}(.*)*` : token.type === 4 /* group */ ? "" : encodePath(token.value).replace(COLON_RE, "\\:"));
  }, "/");
}
const PARAM_CHAR_RE = /[\w.]/;
function parseSegment(segment, absolutePath) {
  let state = 0 /* initial */;
  let i = 0;
  let buffer = "";
  const tokens = [];
  function consumeBuffer() {
    if (!buffer) {
      return;
    }
    if (state === 0 /* initial */) {
      throw new Error("wrong state");
    }
    tokens.push({
      type: state === 1 /* static */ ? 0 /* static */ : state === 2 /* dynamic */ ? 1 /* dynamic */ : state === 3 /* optional */ ? 2 /* optional */ : state === 4 /* catchall */ ? 3 /* catchall */ : 4 /* group */,
      value: buffer
    });
    buffer = "";
  }
  while (i < segment.length) {
    const c = segment[i];
    switch (state) {
      case 0 /* initial */:
        buffer = "";
        if (c === "[") {
          state = 2 /* dynamic */;
        } else if (c === "(") {
          state = 5 /* group */;
        } else {
          i--;
          state = 1 /* static */;
        }
        break;
      case 1 /* static */:
        if (c === "[") {
          consumeBuffer();
          state = 2 /* dynamic */;
        } else if (c === "(") {
          consumeBuffer();
          state = 5 /* group */;
        } else {
          buffer += c;
        }
        break;
      case 4 /* catchall */:
      case 2 /* dynamic */:
      case 3 /* optional */:
      case 5 /* group */:
        if (buffer === "...") {
          buffer = "";
          state = 4 /* catchall */;
        }
        if (c === "[" && state === 2 /* dynamic */) {
          state = 3 /* optional */;
        }
        if (c === "]" && (state !== 3 /* optional */ || segment[i - 1] === "]")) {
          if (!buffer) {
            throw new Error("Empty param");
          } else {
            consumeBuffer();
          }
          state = 0 /* initial */;
        } else if (c === ")" && state === 5 /* group */) {
          if (!buffer) {
            throw new Error("Empty group");
          } else {
            consumeBuffer();
          }
          state = 0 /* initial */;
        } else if (c && PARAM_CHAR_RE.test(c)) {
          buffer += c;
        } else if (state === 2 /* dynamic */ || state === 3 /* optional */) {
          if (c !== "[" && c !== "]") {
            logger.warn(`'\`${c}\`' is not allowed in a dynamic route parameter and has been ignored. Consider renaming \`${absolutePath}\`.`);
          }
        }
        break;
    }
    i++;
  }
  if (state === 2 /* dynamic */) {
    throw new Error(`Unfinished param "${buffer}"`);
  }
  consumeBuffer();
  return tokens;
}
function findRouteByName(name, routes) {
  for (const route of routes) {
    if (route.name === name) {
      return route;
    }
  }
  return findRouteByName(name, routes);
}
const NESTED_PAGE_RE = /\//g;
function prepareRoutes(routes, parent, names = /* @__PURE__ */ new Set()) {
  for (const route of routes) {
    if (route.name) {
      route.name = route.name.replace(INDEX_PAGE_RE, "").replace(NESTED_PAGE_RE, "-");
      if (names.has(route.name)) {
        const existingRoute = findRouteByName(route.name, routes);
        const extra = existingRoute?.name ? `is the same as \`${existingRoute.file}\`` : "is a duplicate";
        logger.warn(`Route name generated for \`${route.file}\` ${extra}. You may wish to set a custom name using \`definePageMeta\` within the page file.`);
      }
    }
    if (parent && route.path[0] === "/") {
      route.path = route.path.slice(1);
    }
    if (route.children?.length) {
      route.children = prepareRoutes(route.children, route, names);
    }
    if (route.children?.find((childRoute) => childRoute.path === "")) {
      delete route.name;
    }
    if (route.name) {
      names.add(route.name);
    }
  }
  return routes;
}
function serializeRouteValue(value, skipSerialisation = false) {
  if (skipSerialisation || value === void 0) {
    return void 0;
  }
  return JSON.stringify(value);
}
function normalizeRoutes(routes, metaImports = /* @__PURE__ */ new Set(), options) {
  return {
    imports: metaImports,
    routes: genArrayFromRaw(routes.map((page) => {
      const markedDynamic = page.meta?.[DYNAMIC_META_KEY] ?? /* @__PURE__ */ new Set();
      const metaFiltered = {};
      let skipMeta = true;
      for (const key in page.meta || {}) {
        if (key !== DYNAMIC_META_KEY && page.meta[key] !== void 0) {
          skipMeta = false;
          metaFiltered[key] = page.meta[key];
        }
      }
      const skipAlias = toArray(page.alias).every((val) => !val);
      const route = {
        path: serializeRouteValue(page.path),
        props: serializeRouteValue(page.props),
        name: serializeRouteValue(page.name),
        meta: serializeRouteValue(metaFiltered, skipMeta),
        alias: serializeRouteValue(toArray(page.alias), skipAlias),
        redirect: serializeRouteValue(page.redirect)
      };
      for (const key of [...defaultExtractionKeys, "meta"]) {
        if (route[key] === void 0) {
          delete route[key];
        }
      }
      if (page.children?.length) {
        route.children = normalizeRoutes(page.children, metaImports, options).routes;
      }
      if (!page.file) {
        return route;
      }
      const file = normalize(page.file);
      const pageImportName = genSafeVariableName(filename(file) + hash(file));
      const metaImportName = pageImportName + "Meta";
      metaImports.add(genImport(`${file}?macro=true`, [{ name: "default", as: metaImportName }]));
      if (page._sync) {
        metaImports.add(genImport(file, [{ name: "default", as: pageImportName }]));
      }
      const pageImport = page._sync && page.mode !== "client" ? pageImportName : genDynamicImport(file);
      const metaRoute = {
        name: `${metaImportName}?.name ?? ${route.name}`,
        path: `${metaImportName}?.path ?? ${route.path}`,
        props: `${metaImportName}?.props ?? ${route.props ?? false}`,
        meta: `${metaImportName} || {}`,
        alias: `${metaImportName}?.alias || []`,
        redirect: `${metaImportName}?.redirect`,
        component: page.mode === "server" ? `() => createIslandPage(${route.name})` : page.mode === "client" ? `() => createClientPage(${pageImport})` : pageImport
      };
      if (page.mode === "server") {
        metaImports.add(`
let _createIslandPage
async function createIslandPage (name) {
  _createIslandPage ||= await import(${JSON.stringify(options?.serverComponentRuntime)}).then(r => r.createIslandPage)
  return _createIslandPage(name)
};`);
      } else if (page.mode === "client") {
        metaImports.add(`
let _createClientPage
async function createClientPage(loader) {
  _createClientPage ||= await import(${JSON.stringify(options?.clientComponentRuntime)}).then(r => r.createClientPage)
  return _createClientPage(loader);
}`);
      }
      if (route.children) {
        metaRoute.children = route.children;
      }
      if (route.meta) {
        metaRoute.meta = `{ ...(${metaImportName} || {}), ...${route.meta} }`;
      }
      if (options?.overrideMeta) {
        for (const key of ["name", "path"]) {
          if (markedDynamic.has(key)) {
            continue;
          }
          metaRoute[key] = route[key] ?? `${metaImportName}?.${key}`;
        }
        for (const key of ["meta", "alias", "redirect", "props"]) {
          if (markedDynamic.has(key)) {
            continue;
          }
          if (route[key] == null) {
            delete metaRoute[key];
            continue;
          }
          metaRoute[key] = route[key];
        }
      } else {
        if (route.alias != null) {
          metaRoute.alias = `${route.alias}.concat(${metaImportName}?.alias || [])`;
        }
        if (route.redirect != null) {
          metaRoute.redirect = route.redirect;
        }
      }
      return metaRoute;
    }))
  };
}
const PATH_TO_NITRO_GLOB_RE = /\/[^:/]*:\w.*$/;
function pathToNitroGlob(path) {
  if (!path) {
    return null;
  }
  if (path.indexOf(":") !== path.lastIndexOf(":")) {
    return null;
  }
  return path.replace(PATH_TO_NITRO_GLOB_RE, "/**");
}
function resolveRoutePaths(page, parent = "/") {
  return [
    joinURL(parent, page.path),
    ...page.children?.flatMap((child) => resolveRoutePaths(child, joinURL(parent, page.path))) || []
  ];
}
function isSerializable(code, node) {
  const propertyValue = withLocations(node);
  if (propertyValue.type === "ObjectExpression") {
    const valueString = code.slice(propertyValue.start, propertyValue.end);
    try {
      return {
        value: JSON.parse(runInNewContext(`JSON.stringify(${valueString})`, {})),
        serializable: true
      };
    } catch {
      return {
        serializable: false
      };
    }
  }
  if (propertyValue.type === "ArrayExpression") {
    const values = [];
    for (const element of propertyValue.elements) {
      if (!element) {
        continue;
      }
      const { serializable, value } = isSerializable(code, element);
      if (!serializable) {
        return {
          serializable: false
        };
      }
      values.push(value);
    }
    return {
      value: values,
      serializable: true
    };
  }
  if (propertyValue.type === "Literal" && (typeof propertyValue.value === "string" || typeof propertyValue.value === "boolean" || typeof propertyValue.value === "number" || propertyValue.value === null)) {
    return {
      value: propertyValue.value,
      serializable: true
    };
  }
  return {
    serializable: false
  };
}

const ROUTE_RULE_RE = /\bdefineRouteRules\(/;
const ruleCache = {};
function extractRouteRules(code, path) {
  if (!ROUTE_RULE_RE.test(code)) {
    return null;
  }
  if (code in ruleCache) {
    return ruleCache[code] || null;
  }
  const loader = getLoader(path);
  if (!loader) {
    return null;
  }
  let rule = null;
  const contents = loader === "vue" ? extractScriptContent(code) : [{ code, loader }];
  for (const script of contents) {
    if (rule) {
      break;
    }
    code = script?.code || code;
    parseAndWalk(code, "file." + (script?.loader || "ts"), (node) => {
      if (node.type !== "CallExpression" || node.callee.type !== "Identifier") {
        return;
      }
      if (node.callee.name === "defineRouteRules") {
        const rulesString = code.slice(node.start, node.end);
        try {
          rule = JSON.parse(runInNewContext(rulesString.replace("defineRouteRules", "JSON.stringify"), {}));
        } catch {
          throw new Error("[nuxt] Error parsing route rules. They should be JSON-serializable.");
        }
      }
    });
  }
  ruleCache[code] = rule;
  return rule;
}
function getMappedPages(pages, paths = {}, prefix = "") {
  for (const page of pages) {
    if (page.file) {
      const filename = normalize(page.file);
      paths[filename] = pathToNitroGlob(prefix + page.path);
    }
    if (page.children?.length) {
      getMappedPages(page.children, paths, page.path + "/");
    }
  }
  return paths;
}

const HAS_MACRO_RE = /\bdefinePageMeta\s*\(\s*/;
const CODE_EMPTY = `
const __nuxt_page_meta = null
export default __nuxt_page_meta
`;
const CODE_DEV_EMPTY = `
const __nuxt_page_meta = {}
export default __nuxt_page_meta
`;
const CODE_HMR = `
// Vite
if (import.meta.hot) {
  import.meta.hot.accept(mod => {
    Object.assign(__nuxt_page_meta, mod)
  })
}
// webpack
if (import.meta.webpackHot) {
  import.meta.webpackHot.accept((err) => {
    if (err) { window.location = window.location.href }
  })
}`;
const PageMetaPlugin = (options = {}) => createUnplugin(() => {
  return {
    name: "nuxt:pages-macros-transform",
    enforce: "post",
    transformInclude(id) {
      return !!parseMacroQuery(id).macro;
    },
    transform(code, id) {
      const query = parseMacroQuery(id);
      if (query.type && query.type !== "script") {
        return;
      }
      const s = new MagicString(code);
      function result() {
        if (s.hasChanged()) {
          return {
            code: s.toString(),
            map: options.sourcemap ? s.generateMap({ hires: true }) : void 0
          };
        }
      }
      const hasMacro = HAS_MACRO_RE.test(code);
      const imports = findStaticImports(code);
      const scriptImport = imports.find((i) => parseMacroQuery(i.specifier).type === "script");
      if (scriptImport) {
        const reorderedQuery = rewriteQuery(scriptImport.specifier);
        const quotedSpecifier = getQuotedSpecifier(scriptImport.code)?.replace(scriptImport.specifier, reorderedQuery) ?? JSON.stringify(reorderedQuery);
        s.overwrite(0, code.length, `export { default } from ${quotedSpecifier}`);
        return result();
      }
      const currentExports = findExports(code);
      for (const match of currentExports) {
        if (match.type !== "default" || !match.specifier) {
          continue;
        }
        const reorderedQuery = rewriteQuery(match.specifier);
        const quotedSpecifier = getQuotedSpecifier(match.code)?.replace(match.specifier, reorderedQuery) ?? JSON.stringify(reorderedQuery);
        s.overwrite(0, code.length, `export { default } from ${quotedSpecifier}`);
        return result();
      }
      if (!hasMacro && !code.includes("export { default }") && !code.includes("__nuxt_page_meta")) {
        if (!code) {
          s.append(options.dev ? CODE_DEV_EMPTY + CODE_HMR : CODE_EMPTY);
          const { pathname } = parseURL(decodeURIComponent(pathToFileURL(id).href));
          logger.error(`The file \`${pathname}\` is not a valid page as it has no content.`);
        } else {
          s.overwrite(0, code.length, options.dev ? CODE_DEV_EMPTY + CODE_HMR : CODE_EMPTY);
        }
        return result();
      }
      const importMap = /* @__PURE__ */ new Map();
      const addedImports = /* @__PURE__ */ new Set();
      for (const i of imports) {
        const parsed = parseStaticImport(i);
        for (const name of [
          parsed.defaultImport,
          ...Object.values(parsed.namedImports || {}),
          parsed.namespacedImport
        ].filter(Boolean)) {
          importMap.set(name, i);
        }
      }
      function isStaticIdentifier(name) {
        return !!(name && importMap.has(name));
      }
      function addImport(name) {
        if (!isStaticIdentifier(name)) {
          return;
        }
        const importValue = importMap.get(name).code.trim();
        if (!addedImports.has(importValue)) {
          addedImports.add(importValue);
        }
      }
      const declarationNodes = [];
      const addedDeclarations = /* @__PURE__ */ new Set();
      function addDeclaration(node) {
        const codeSectionKey = `${node.start}-${node.end}`;
        if (addedDeclarations.has(codeSectionKey)) {
          return;
        }
        addedDeclarations.add(codeSectionKey);
        declarationNodes.push(node);
      }
      function addImportOrDeclaration(name, node) {
        if (isStaticIdentifier(name)) {
          addImport(name);
        } else {
          const declaration = scopeTracker.getDeclaration(name);
          if (declaration && declaration !== node) {
            processDeclaration(declaration);
          }
        }
      }
      const scopeTracker = new ScopeTracker({
        keepExitedScopes: true
      });
      function processDeclaration(scopeTrackerNode) {
        if (scopeTrackerNode?.type === "Variable") {
          addDeclaration(scopeTrackerNode);
          for (const decl of scopeTrackerNode.variableNode.declarations) {
            if (!decl.init) {
              continue;
            }
            walk(decl.init, {
              enter: (node, parent) => {
                if (node.type === "AwaitExpression") {
                  logger.error(`Await expressions are not supported in definePageMeta. File: '${id}'`);
                  throw new Error("await in definePageMeta");
                }
                if (isNotReferencePosition(node, parent) || node.type !== "Identifier") {
                  return;
                }
                addImportOrDeclaration(node.name, scopeTrackerNode);
              }
            });
          }
        } else if (scopeTrackerNode?.type === "Function") {
          if (scopeTrackerNode.node.type === "ArrowFunctionExpression") {
            return;
          }
          const name = scopeTrackerNode.node.id?.name;
          if (!name) {
            return;
          }
          addDeclaration(scopeTrackerNode);
          const undeclaredIdentifiers = getUndeclaredIdentifiersInFunction(scopeTrackerNode.node);
          for (const name2 of undeclaredIdentifiers) {
            addImportOrDeclaration(name2);
          }
        }
      }
      const ast = parseAndWalk(code, id + (query.lang ? "." + query.lang : ".ts"), {
        scopeTracker
      });
      scopeTracker.freeze();
      let instances = 0;
      walk(ast, {
        scopeTracker,
        enter: (node) => {
          if (node.type !== "CallExpression" || node.callee.type !== "Identifier") {
            return;
          }
          if (!("name" in node.callee) || node.callee.name !== "definePageMeta") {
            return;
          }
          instances++;
          const meta = withLocations(node.arguments[0]);
          if (!meta) {
            return;
          }
          const metaCode = code.slice(meta.start, meta.end);
          const m = new MagicString(metaCode);
          if (meta.type === "ObjectExpression") {
            for (let i = 0; i < meta.properties.length; i++) {
              const prop = withLocations(meta.properties[i]);
              if (prop.type === "Property" && prop.key.type === "Identifier" && options.extractedKeys?.includes(prop.key.name)) {
                const { serializable } = isSerializable(metaCode, prop.value);
                if (!serializable) {
                  continue;
                }
                const nextProperty = withLocations(meta.properties[i + 1]);
                if (nextProperty) {
                  m.overwrite(prop.start - meta.start, nextProperty.start - meta.start, "");
                } else if (code[prop.end] === ",") {
                  m.overwrite(prop.start - meta.start, prop.end - meta.start + 1, "");
                } else {
                  m.overwrite(prop.start - meta.start, prop.end - meta.start, "");
                }
              }
            }
          }
          const definePageMetaScope = scopeTracker.getCurrentScope();
          walk(meta, {
            scopeTracker,
            enter(node2, parent) {
              if (isNotReferencePosition(node2, parent) || node2.type !== "Identifier") {
                return;
              }
              const declaration = scopeTracker.getDeclaration(node2.name);
              if (declaration) {
                if (declaration.isUnderScope(definePageMetaScope) && (scopeTracker.isCurrentScopeUnder(declaration.scope) || declaration.start < node2.start)) {
                  return;
                }
              }
              if (isStaticIdentifier(node2.name)) {
                addImport(node2.name);
              } else if (declaration) {
                processDeclaration(declaration);
              }
            }
          });
          const importStatements = Array.from(addedImports).join("\n");
          const declarations = declarationNodes.sort((a, b) => a.start - b.start).map((node2) => code.slice(node2.start, node2.end)).join("\n");
          const extracted = [
            importStatements,
            declarations,
            `const __nuxt_page_meta = ${m.toString() || "null"}
export default __nuxt_page_meta` + (options.dev ? CODE_HMR : "")
          ].join("\n");
          s.overwrite(0, code.length, extracted.trim());
        }
      });
      if (instances > 1) {
        throw new Error("Multiple `definePageMeta` calls are not supported. File: " + id.replace(/\?.+$/, ""));
      }
      if (!s.hasChanged() && !code.includes("__nuxt_page_meta")) {
        s.overwrite(0, code.length, options.dev ? CODE_DEV_EMPTY + CODE_HMR : CODE_EMPTY);
      }
      return result();
    },
    vite: {
      handleHotUpdate: {
        order: "post",
        handler: ({ file, modules, server }) => {
          if (options.routesPath && options.isPage?.(file)) {
            const macroModule = server.moduleGraph.getModuleById(file + "?macro=true");
            const routesModule = server.moduleGraph.getModuleById("virtual:nuxt:" + encodeURIComponent(options.routesPath));
            return [
              ...modules,
              ...macroModule ? [macroModule] : [],
              ...routesModule ? [routesModule] : []
            ];
          }
        }
      }
    }
  };
});
const QUERY_START_RE = /^\?/;
const MACRO_RE = /&macro=true/;
function rewriteQuery(id) {
  return id.replace(/\?.+$/, (r) => "?macro=true&" + r.replace(QUERY_START_RE, "").replace(MACRO_RE, ""));
}
function parseMacroQuery(id) {
  const { search } = parseURL(decodeURIComponent(isAbsolute(id) ? pathToFileURL(id).href : id).replace(/\?macro=true$/, ""));
  const query = parseQuery(search);
  if (id.includes("?macro=true")) {
    return { macro: "true", ...query };
  }
  return query;
}
const QUOTED_SPECIFIER_RE = /(["']).*\1/;
function getQuotedSpecifier(id) {
  return id.match(QUOTED_SPECIFIER_RE)?.[0];
}

const INJECTION_RE_TEMPLATE = /\b_ctx\.\$route\b/g;
const INJECTION_RE_SCRIPT = /\bthis\.\$route\b/g;
const INJECTION_SINGLE_RE = /\bthis\.\$route\b|\b_ctx\.\$route\b/;
const RouteInjectionPlugin = (nuxt) => createUnplugin(() => {
  return {
    name: "nuxt:route-injection-plugin",
    enforce: "post",
    transformInclude(id) {
      return isVue(id, { type: ["template", "script"] });
    },
    transform: {
      filter: {
        code: { include: INJECTION_SINGLE_RE }
      },
      handler(code) {
        if (code.includes("_ctx._.provides[__nuxt_route_symbol") || code.includes("this._.provides[__nuxt_route_symbol")) {
          return;
        }
        let replaced = false;
        const s = new MagicString(code);
        const strippedCode = stripLiteral(code);
        const replaceMatches = (regExp, replacement) => {
          for (const match of strippedCode.matchAll(regExp)) {
            const start = match.index;
            const end = start + match[0].length;
            s.overwrite(start, end, replacement);
            replaced ||= true;
          }
        };
        replaceMatches(INJECTION_RE_TEMPLATE, "(_ctx._.provides[__nuxt_route_symbol] || _ctx.$route)");
        replaceMatches(INJECTION_RE_SCRIPT, "(this._.provides[__nuxt_route_symbol] || this.$route)");
        if (replaced) {
          s.prepend("import { PageRouteSymbol as __nuxt_route_symbol } from '#app/components/injections';\n");
        }
        if (s.hasChanged()) {
          return {
            code: s.toString(),
            map: nuxt.options.sourcemap.client || nuxt.options.sourcemap.server ? s.generateMap({ hires: true }) : void 0
          };
        }
      }
    }
  };
});

const OPTIONAL_PARAM_RE = /^\/?:.*(?:\?|\(\.\*\)\*)$/;
const runtimeDir = resolve(distDir, "pages/runtime");
async function resolveRouterOptions(nuxt, builtInRouterOptions) {
  const context = {
    files: []
  };
  for (const layer of nuxt.options._layers) {
    const path = await findPath(resolve(layer.config.srcDir, layer.config.dir?.app || "app", "router.options"));
    if (path) {
      context.files.unshift({ path });
    }
  }
  context.files.unshift({ path: builtInRouterOptions, optional: true });
  await nuxt.callHook("pages:routerOptions", context);
  return context.files;
}
const pagesModule = defineNuxtModule({
  meta: {
    name: "nuxt:pages",
    configKey: "pages"
  },
  defaults: (nuxt) => ({
    enabled: typeof nuxt.options.pages === "boolean" ? nuxt.options.pages : void 0,
    pattern: `**/*{${nuxt.options.extensions.join(",")}}`
  }),
  async setup(_options, nuxt) {
    const options = typeof _options === "boolean" ? { enabled: _options ?? nuxt.options.pages, pattern: `**/*{${nuxt.options.extensions.join(",")}}` } : { ..._options };
    options.pattern = Array.isArray(options.pattern) ? [...new Set(options.pattern)] : options.pattern;
    const useExperimentalTypedPages = nuxt.options.experimental.typedPages;
    const builtInRouterOptions = await findPath(resolve(runtimeDir, "router.options")) || resolve(runtimeDir, "router.options");
    const pagesDirs = nuxt.options._layers.map(
      (layer) => resolve(layer.config.srcDir, (layer.config.rootDir === nuxt.options.rootDir ? nuxt.options : layer.config).dir?.pages || "pages")
    );
    nuxt.options.alias["#vue-router"] = "vue-router";
    const routerPath = await resolveTypePath("vue-router", "", nuxt.options.modulesDir) || "vue-router";
    nuxt.hook("prepare:types", ({ tsConfig }) => {
      tsConfig.compilerOptions ||= {};
      tsConfig.compilerOptions.paths ||= {};
      tsConfig.compilerOptions.paths["#vue-router"] = [routerPath];
      delete tsConfig.compilerOptions.paths["#vue-router/*"];
    });
    const isNonEmptyDir = (dir) => existsSync(dir) && readdirSync(dir).length;
    const userPreference = options.enabled;
    const isPagesEnabled = async () => {
      if (typeof userPreference === "boolean") {
        return userPreference;
      }
      const routerOptionsFiles = await resolveRouterOptions(nuxt, builtInRouterOptions);
      if (routerOptionsFiles.filter((p) => !p.optional).length > 0) {
        return true;
      }
      if (pagesDirs.some((dir) => isNonEmptyDir(dir))) {
        return true;
      }
      const pages = await resolvePagesRoutes(options.pattern, nuxt);
      if (pages.length) {
        if (nuxt.apps.default) {
          nuxt.apps.default.pages = pages;
        }
        return true;
      }
      return false;
    };
    options.enabled = await isPagesEnabled();
    nuxt.options.pages = options;
    Object.defineProperty(nuxt.options.pages, "toString", {
      enumerable: false,
      get: () => () => options.enabled
    });
    if (nuxt.options.dev && options.enabled) {
      addPlugin(resolve(runtimeDir, "plugins/check-if-page-unused"));
    }
    nuxt.hook("app:templates", (app) => {
      if (!nuxt.options.ssr && app.pages?.some((p) => p.mode === "server")) {
        logger.warn("Using server pages with `ssr: false` is not supported with auto-detected component islands. Set `experimental.componentIslands` to `true`.");
      }
    });
    const restartPaths = nuxt.options._layers.flatMap((layer) => {
      const pagesDir = (layer.config.rootDir === nuxt.options.rootDir ? nuxt.options.dir : layer.config.dir)?.pages || "pages";
      return [
        resolve(layer.config.srcDir || layer.cwd, layer.config.dir?.app || "app", "router.options.ts"),
        resolve(layer.config.srcDir || layer.cwd, pagesDir)
      ];
    });
    nuxt.hooks.hook("builder:watch", async (event, relativePath) => {
      const path = resolve(nuxt.options.srcDir, relativePath);
      if (restartPaths.some((p) => p === path || path.startsWith(p + "/"))) {
        const newSetting = await isPagesEnabled();
        if (options.enabled !== newSetting) {
          logger.info("Pages", newSetting ? "enabled" : "disabled");
          return nuxt.callHook("restart");
        }
      }
    });
    if (!options.enabled) {
      addPlugin(resolve(distDir, "app/plugins/router"));
      addTemplate({
        filename: "pages.mjs",
        getContents: () => [
          "export { useRoute } from '#app/composables/router'",
          "export const START_LOCATION = Symbol('router:start-location')"
        ].join("\n")
      });
      addTemplate({
        filename: "router.options.mjs",
        getContents: () => {
          return [
            "export const hashMode = false",
            "export default {}"
          ].join("\n");
        }
      });
      addTypeTemplate({
        filename: "types/middleware.d.ts",
        getContents: () => [
          "declare module 'nitropack' {",
          "  interface NitroRouteConfig {",
          "    appMiddleware?: string | string[] | Record<string, boolean>",
          "  }",
          "}",
          "export {}"
        ].join("\n")
      }, { nuxt: true, nitro: true });
      addComponent({
        name: "NuxtPage",
        priority: 10,
        // built-in that we do not expect the user to override
        filePath: resolve(distDir, "pages/runtime/page-placeholder")
      });
      nuxt.hook("nitro:init", (nitro) => {
        if (nuxt.options.dev || !nuxt.options.ssr || !nitro.options.static || !nitro.options.prerender.crawlLinks) {
          return;
        }
        nitro.options.prerender.routes.push("/");
      });
      return;
    }
    if (useExperimentalTypedPages) {
      const declarationFile = "./types/typed-router.d.ts";
      const typedRouterOptions = {
        routesFolder: [],
        dts: resolve(nuxt.options.buildDir, declarationFile),
        logs: nuxt.options.debug && nuxt.options.debug.router,
        async beforeWriteFiles(rootPage) {
          rootPage.children.forEach((child) => child.delete());
          const pages = nuxt.apps.default?.pages || await resolvePagesRoutes(options.pattern, nuxt);
          if (nuxt.apps.default) {
            nuxt.apps.default.pages = pages;
          }
          const addedPagePaths = /* @__PURE__ */ new Set();
          function addPage(parent, page, basePath = "") {
            const absolutePagePath = joinURL(basePath, page.path);
            const route = addedPagePaths.has(absolutePagePath) ? parent : page.path[0] === "/" ? rootPage.insert(page.path, page.file) : parent.insert(page.path, page.file);
            addedPagePaths.add(absolutePagePath);
            if (page.meta) {
              route.addToMeta(page.meta);
            }
            if (page.alias) {
              route.addAlias(page.alias);
            }
            if (page.name) {
              route.name = page.name;
            }
            if (page.children) {
              page.children.forEach((child) => addPage(route, child, absolutePagePath));
            }
          }
          for (const page of pages) {
            addPage(rootPage, page);
          }
        }
      };
      nuxt.hook("prepare:types", ({ references }) => {
        references.push({ path: declarationFile });
        references.push({ types: "unplugin-vue-router/client" });
      });
      const context = createRoutesContext(resolveOptions(typedRouterOptions));
      const dtsFile = resolve(nuxt.options.buildDir, declarationFile);
      await mkdir(dirname(dtsFile), { recursive: true });
      await context.scanPages(false);
      if (nuxt.options._prepare || !nuxt.options.dev) {
        const dts = await readFile(dtsFile, "utf-8");
        addTemplate({
          filename: "types/typed-router.d.ts",
          getContents: () => dts
        });
      }
      nuxt.hook("app:templatesGenerated", async (_app, _templates, options2) => {
        if (!options2?.filter || options2.filter({ filename: "routes.mjs" })) {
          await context.scanPages();
        }
      });
    }
    nuxt.hook("prepare:types", ({ references }) => {
      references.push({ types: useExperimentalTypedPages ? "vue-router/auto-routes" : "vue-router" });
    });
    nuxt.hook("imports:sources", (sources) => {
      const routerImports = sources.find((s) => s.from === "#app/composables/router" && s.imports.includes("onBeforeRouteLeave"));
      if (routerImports) {
        routerImports.from = "vue-router";
      }
    });
    const updateTemplatePaths = nuxt.options._layers.flatMap((l) => {
      const dir = l.config.rootDir === nuxt.options.rootDir ? nuxt.options.dir : l.config.dir;
      return [
        resolve(l.config.srcDir || l.cwd, dir?.pages || "pages") + "/",
        resolve(l.config.srcDir || l.cwd, dir?.layouts || "layouts") + "/",
        resolve(l.config.srcDir || l.cwd, dir?.middleware || "middleware") + "/"
      ];
    });
    function isPage(file, pages = nuxt.apps.default?.pages) {
      if (!pages) {
        return false;
      }
      return pages.some((page) => page.file === file) || pages.some((page) => page.children && isPage(file, page.children));
    }
    nuxt.hooks.hookOnce("app:templates", async (app) => {
      app.pages ||= await resolvePagesRoutes(options.pattern, nuxt);
    });
    nuxt.hook("builder:watch", async (event, relativePath) => {
      const path = resolve(nuxt.options.srcDir, relativePath);
      const shouldAlwaysRegenerate = nuxt.options.experimental.scanPageMeta && isPage(path);
      if (event === "change" && !shouldAlwaysRegenerate) {
        return;
      }
      if (shouldAlwaysRegenerate || updateTemplatePaths.some((dir) => path.startsWith(dir))) {
        nuxt.apps.default.pages = await resolvePagesRoutes(options.pattern, nuxt);
      }
    });
    nuxt.hook("app:resolve", (app) => {
      if (app.mainComponent === resolve(nuxt.options.appDir, "components/welcome.vue")) {
        app.mainComponent = resolve(runtimeDir, "app.vue");
      }
      app.middleware.unshift({
        name: "validate",
        path: resolve(runtimeDir, "validate"),
        global: true
      });
    });
    nuxt.hook("app:resolve", (app) => {
      const nitro = useNitro();
      if (nitro.options.prerender.crawlLinks || Object.values(nitro.options.routeRules).some((rule) => rule.prerender)) {
        app.plugins.push({
          src: resolve(runtimeDir, "plugins/prerender.server"),
          mode: "server"
        });
      }
    });
    const prerenderRoutes = /* @__PURE__ */ new Set();
    function processPages(pages, currentPath = "/") {
      for (const page of pages) {
        if (OPTIONAL_PARAM_RE.test(page.path) && !page.children?.length) {
          prerenderRoutes.add(currentPath);
        }
        if (page.path.includes(":")) {
          continue;
        }
        const route = joinURL(currentPath, page.path);
        prerenderRoutes.add(route);
        if (page.children) {
          processPages(page.children, route);
        }
      }
    }
    nuxt.hook("pages:extend", (pages) => {
      if (nuxt.options.dev) {
        return;
      }
      prerenderRoutes.clear();
      processPages(pages);
    });
    nuxt.hook("nitro:build:before", (nitro) => {
      if (nuxt.options.dev || nuxt.options.router.options.hashMode) {
        return;
      }
      if (!nitro.options.static && !nitro.options.prerender.crawlLinks) {
        const routeRulesMatcher = toRouteMatcher(createRouter({ routes: nitro.options.routeRules }));
        for (const route of prerenderRoutes) {
          const rules = defu({}, ...routeRulesMatcher.matchAll(route).reverse());
          if (rules.prerender) {
            nitro.options.prerender.routes.push(route);
          }
        }
      }
      if (!nitro.options.static || !nitro.options.prerender.crawlLinks) {
        return;
      }
      if (nuxt.options.ssr) {
        const [firstPage] = [...prerenderRoutes].sort();
        nitro.options.prerender.routes.push(firstPage || "/");
        return;
      }
      for (const route of nitro.options.prerender.routes || []) {
        prerenderRoutes.add(route);
      }
      nitro.options.prerender.routes = Array.from(prerenderRoutes);
    });
    nuxt.hook("imports:extend", (imports) => {
      imports.push(
        { name: "definePageMeta", as: "definePageMeta", from: resolve(runtimeDir, "composables") },
        { name: "useLink", as: "useLink", from: "vue-router" }
      );
      if (nuxt.options.experimental.inlineRouteRules) {
        imports.push({ name: "defineRouteRules", as: "defineRouteRules", from: resolve(runtimeDir, "composables") });
      }
    });
    if (nuxt.options.experimental.inlineRouteRules) {
      let pageToGlobMap = {};
      nuxt.hook("pages:extend", (pages) => {
        pageToGlobMap = getMappedPages(pages);
      });
      const inlineRules = {};
      let updateRouteConfig;
      nuxt.hook("nitro:init", (nitro) => {
        updateRouteConfig = () => nitro.updateConfig({ routeRules: defu(inlineRules, nitro.options._config.routeRules) });
      });
      const updatePage = async function updatePage2(path) {
        const glob = pageToGlobMap[path];
        const code = path in nuxt.vfs ? nuxt.vfs[path] : await readFile(path, "utf-8");
        try {
          const extractedRule = await extractRouteRules(code, path);
          if (extractedRule) {
            if (!glob) {
              const relativePath = relative(nuxt.options.srcDir, path);
              logger.error(`Could not set inline route rules in \`~/${relativePath}\` as it could not be mapped to a Nitro route.`);
              return;
            }
            inlineRules[glob] = extractedRule;
          } else if (glob) {
            delete inlineRules[glob];
          }
        } catch (e) {
          if (e.toString().includes("Error parsing route rules")) {
            const relativePath = relative(nuxt.options.srcDir, path);
            logger.error(`Error parsing route rules within \`~/${relativePath}\`. They should be JSON-serializable.`);
          } else {
            logger.error(e);
          }
        }
      };
      nuxt.hook("builder:watch", async (event, relativePath) => {
        const path = resolve(nuxt.options.srcDir, relativePath);
        if (!(path in pageToGlobMap)) {
          return;
        }
        if (event === "unlink") {
          delete inlineRules[path];
          delete pageToGlobMap[path];
        } else {
          await updatePage(path);
        }
        await updateRouteConfig?.();
      });
      nuxt.hooks.hookOnce("pages:extend", async () => {
        for (const page in pageToGlobMap) {
          await updatePage(page);
        }
        await updateRouteConfig?.();
      });
    }
    const componentStubPath = await resolvePath(resolve(runtimeDir, "component-stub"));
    if (nuxt.options.test && nuxt.options.dev) {
      nuxt.hook("pages:extend", (routes) => {
        routes.push({
          _sync: true,
          path: "/__nuxt_component_test__/:pathMatch(.*)",
          file: componentStubPath
        });
      });
    }
    if (nuxt.options.experimental.appManifest) {
      nuxt.hook("pages:extend", (routes) => {
        const nitro = useNitro();
        let resolvedRoutes;
        for (const [path, rule] of Object.entries(nitro.options.routeRules)) {
          if (!rule.redirect) {
            continue;
          }
          resolvedRoutes ||= routes.flatMap((route) => resolveRoutePaths(route));
          if (resolvedRoutes.includes(path)) {
            continue;
          }
          routes.push({
            _sync: true,
            path: path.replace(/\/[^/]*\*\*/, "/:pathMatch(.*)"),
            file: componentStubPath
          });
        }
      });
    }
    const extractedKeys = nuxt.options.future.compatibilityVersion === 4 ? [...defaultExtractionKeys, "middleware", ...nuxt.options.experimental.extraPageMetaExtractionKeys] : ["middleware", ...nuxt.options.experimental.extraPageMetaExtractionKeys];
    nuxt.hook("modules:done", () => {
      addBuildPlugin(PageMetaPlugin({
        dev: nuxt.options.dev,
        sourcemap: !!nuxt.options.sourcemap.server || !!nuxt.options.sourcemap.client,
        isPage,
        routesPath: resolve(nuxt.options.buildDir, "routes.mjs"),
        extractedKeys: nuxt.options.experimental.scanPageMeta ? extractedKeys : []
      }));
    });
    addPlugin(resolve(runtimeDir, "plugins/prefetch.client"));
    if (nuxt.options.experimental.templateRouteInjection) {
      addBuildPlugin(RouteInjectionPlugin(nuxt), { server: false });
    }
    addPlugin(resolve(runtimeDir, "plugins/router"));
    const getSources = (pages) => pages.filter((p) => Boolean(p.file)).flatMap(
      (p) => [relative(nuxt.options.srcDir, p.file), ...p.children?.length ? getSources(p.children) : []]
    );
    nuxt.hook("build:manifest", (manifest) => {
      if (nuxt.options.dev) {
        return;
      }
      const sourceFiles = nuxt.apps.default?.pages?.length ? getSources(nuxt.apps.default.pages) : [];
      for (const [key, chunk] of Object.entries(manifest)) {
        if (chunk.src && Object.values(nuxt.apps).some((app) => app.pages?.some((page) => page.mode === "server" && page.file === join(nuxt.options.srcDir, chunk.src)))) {
          delete manifest[key];
          continue;
        }
        if (chunk.isEntry) {
          chunk.dynamicImports = chunk.dynamicImports?.filter((i) => !sourceFiles.includes(i));
        }
      }
    });
    const serverComponentRuntime = await findPath(join(distDir, "components/runtime/server-component")) ?? join(distDir, "components/runtime/server-component");
    const clientComponentRuntime = await findPath(join(distDir, "components/runtime/client-component")) ?? join(distDir, "components/runtime/client-component");
    addTemplate({
      filename: "routes.mjs",
      getContents({ app }) {
        if (!app.pages) {
          return ROUTES_HMR_CODE + "export default []";
        }
        const { routes, imports } = normalizeRoutes(app.pages, /* @__PURE__ */ new Set(), {
          serverComponentRuntime,
          clientComponentRuntime,
          overrideMeta: !!nuxt.options.experimental.scanPageMeta
        });
        return ROUTES_HMR_CODE + [...imports, `export default ${routes}`].join("\n");
      }
    });
    addTemplate({
      filename: "pages.mjs",
      getContents: () => "export { START_LOCATION, useRoute } from 'vue-router'"
    });
    nuxt.options.vite.resolve ||= {};
    nuxt.options.vite.resolve.dedupe ||= [];
    nuxt.options.vite.resolve.dedupe.push("vue-router");
    addTemplate({
      filename: "router.options.mjs",
      getContents: async ({ nuxt: nuxt2 }) => {
        const routerOptionsFiles = await resolveRouterOptions(nuxt2, builtInRouterOptions);
        const configRouterOptions = genObjectFromRawEntries(Object.entries(nuxt2.options.router.options).map(([key, value]) => [key, genString(value)]));
        return [
          ...routerOptionsFiles.map((file, index) => genImport(file.path, `routerOptions${index}`)),
          `const configRouterOptions = ${configRouterOptions}`,
          `export const hashMode = ${[...routerOptionsFiles.filter((o) => o.path !== builtInRouterOptions).map((_, index) => `routerOptions${index}.hashMode`).reverse(), nuxt2.options.router.options.hashMode].join(" ?? ")}`,
          "export default {",
          "...configRouterOptions,",
          ...routerOptionsFiles.map((_, index) => `...routerOptions${index},`),
          "}"
        ].join("\n");
      }
    });
    addTypeTemplate({
      filename: "types/middleware.d.ts",
      getContents: ({ app }) => {
        const namedMiddleware = app.middleware.filter((mw) => !mw.global);
        return [
          "import type { NavigationGuard } from 'vue-router'",
          `export type MiddlewareKey = ${namedMiddleware.map((mw) => genString(mw.name)).join(" | ") || "never"}`,
          "declare module 'nuxt/app' {",
          "  interface PageMeta {",
          "    middleware?: MiddlewareKey | NavigationGuard | Array<MiddlewareKey | NavigationGuard>",
          "  }",
          "}"
        ].join("\n");
      }
    });
    addTypeTemplate({
      filename: "types/nitro-middleware.d.ts",
      getContents: ({ app }) => {
        const namedMiddleware = app.middleware.filter((mw) => !mw.global);
        return [
          `export type MiddlewareKey = ${namedMiddleware.map((mw) => genString(mw.name)).join(" | ") || "never"}`,
          "declare module 'nitropack' {",
          "  interface NitroRouteConfig {",
          "    appMiddleware?: MiddlewareKey | MiddlewareKey[] | Record<MiddlewareKey, boolean>",
          "  }",
          "}"
        ].join("\n");
      }
    }, { nuxt: true, nitro: true });
    addTypeTemplate({
      filename: "types/layouts.d.ts",
      getContents: ({ app }) => {
        return [
          "import type { ComputedRef, MaybeRef } from 'vue'",
          `export type LayoutKey = ${Object.keys(app.layouts).map((name) => genString(name)).join(" | ") || "string"}`,
          "declare module 'nuxt/app' {",
          "  interface PageMeta {",
          "    layout?: MaybeRef<LayoutKey | false> | ComputedRef<LayoutKey | false>",
          "  }",
          "}"
        ].join("\n");
      }
    });
    if (nuxt.options.experimental.viewTransition) {
      addTypeTemplate({
        filename: "types/view-transitions.d.ts",
        getContents: () => {
          return [
            "declare module 'nuxt/app' {",
            "  interface PageMeta {",
            "    viewTransition?: boolean | 'always'",
            "  }",
            "}",
            "export {}"
          ].join("\n");
        }
      });
    }
    addComponent({
      name: "NuxtPage",
      priority: 10,
      // built-in that we do not expect the user to override
      filePath: resolve(distDir, "pages/runtime/page")
    });
  }
});
const ROUTES_HMR_CODE = (
  /* js */
  `
if (import.meta.hot) {
  import.meta.hot.accept((mod) => {
    const router = import.meta.hot.data.router
    const generateRoutes = import.meta.hot.data.generateRoutes
    if (!router || !generateRoutes) {
      import.meta.hot.invalidate('[nuxt] Cannot replace routes because there is no active router. Reloading.')
      return
    }
    router.clearRoutes()
    const routes = generateRoutes(mod.default || mod)
    function addRoutes (routes) {
      for (const route of routes) {
        router.addRoute(route)
      }
      router.replace(router.currentRoute.value.fullPath)
    }
    if (routes && 'then' in routes) {
      routes.then(addRoutes)
    } else {
      addRoutes(routes)
    }
  })
}

export function handleHotUpdate(_router, _generateRoutes) {
  if (import.meta.hot) {
    import.meta.hot.data ||= {}
    import.meta.hot.data.router = _router
    import.meta.hot.data.generateRoutes = _generateRoutes
  }
}
`
);

const UNHEAD_LIB_RE = /node_modules[/\\](?:@unhead[/\\][^/\\]+|unhead)[/\\]/;
function toImports(specifiers) {
  return specifiers.map((specifier) => {
    const imported = specifier.imported;
    const isNamedImport = imported && imported.name !== specifier.local.name;
    return isNamedImport ? `${imported.name} as ${specifier.local.name}` : specifier.local.name;
  });
}
const UnheadVue = "@unhead/vue";
const UnheadVueRE = /@unhead\/vue/;
const UnheadImportsPlugin = (options) => createUnplugin(() => {
  return {
    name: "nuxt:head:unhead-imports",
    enforce: "post",
    transformInclude(id) {
      id = normalize(id);
      return (isJS(id) || isVue(id, { type: ["script"] })) && !id.startsWith("virtual:") && !id.startsWith(normalize(distDir)) && !UNHEAD_LIB_RE.test(id);
    },
    transform: {
      filter: {
        code: { include: UnheadVueRE }
      },
      handler(code, id) {
        const s = new MagicString(code);
        const importsToAdd = [];
        parseAndWalk(code, id, function(node) {
          if (node.type === "ImportDeclaration" && [UnheadVue, "#app/composables/head"].includes(String(node.source.value))) {
            importsToAdd.push(...node.specifiers);
            const { start, end } = withLocations(node);
            s.remove(start, end);
          }
        });
        const importsFromUnhead = importsToAdd.filter((specifier) => unheadVueComposablesImports[UnheadVue].includes(specifier.imported?.name));
        const importsFromHead = importsToAdd.filter((specifier) => !unheadVueComposablesImports[UnheadVue].includes(specifier.imported?.name));
        if (importsFromUnhead.length) {
          if (!normalize(id).includes("node_modules")) {
            logger.warn(`You are importing from \`${UnheadVue}\` in \`./${relative(normalize(options.rootDir), normalize(id))}\`. Please import from \`#imports\` instead for full type safety.`);
          }
          s.prepend(`${genImport("#app/composables/head", toImports(importsFromUnhead))}
`);
        }
        if (importsFromHead.length) {
          s.prepend(`${genImport(UnheadVue, toImports(importsFromHead))}
`);
        }
        if (s.hasChanged()) {
          return {
            code: s.toString(),
            map: options.sourcemap ? s.generateMap({ hires: true }) : void 0
          };
        }
      }
    }
  };
});

const components = ["NoScript", "Link", "Base", "Title", "Meta", "Style", "Head", "Html", "Body"];
const metaModule = defineNuxtModule({
  meta: {
    name: "nuxt:meta",
    configKey: "unhead"
  },
  setup(options, nuxt) {
    const runtimeDir = resolve(distDir, "head/runtime");
    nuxt.options.build.transpile.push("@unhead/vue");
    const isNuxtV4 = nuxt.options._majorVersion === 4 || nuxt.options.future?.compatibilityVersion === 4;
    const componentsPath = resolve(runtimeDir, "components");
    for (const componentName of components) {
      addComponent({
        name: componentName,
        filePath: componentsPath,
        export: componentName,
        // built-in that we do not expect the user to override
        priority: 10,
        // kebab case version of these tags is not valid
        kebabName: componentName
      });
    }
    if (!nuxt.options.dev) {
      nuxt.options.optimization.treeShake.composables.client["@unhead/vue"] = [
        "useServerHead",
        "useServerSeoMeta",
        "useServerHeadSafe"
      ];
    }
    nuxt.options.alias["#unhead/composables"] = resolve(runtimeDir, "composables", isNuxtV4 ? "v4" : "v3");
    addBuildPlugin(UnheadImportsPlugin({
      sourcemap: !!nuxt.options.sourcemap.server,
      rootDir: nuxt.options.rootDir
    }));
    const importPaths = nuxt.options.modulesDir.map((d) => directoryToURL(d));
    const unheadPlugins = resolveModulePath("@unhead/vue/plugins", { try: true, from: importPaths }) || "@unhead/vue/plugins";
    if (nuxt.options.experimental.polyfillVueUseHead) {
      nuxt.options.alias["@vueuse/head"] = resolveModulePath("@unhead/vue", { try: true, from: importPaths }) || "@unhead/vue";
      addPlugin({ src: resolve(runtimeDir, "plugins/vueuse-head-polyfill") });
    }
    addTemplate({
      filename: "unhead-options.mjs",
      getContents() {
        if (isNuxtV4 && !options.legacy) {
          return `
export default {
  disableDefaults: true,
}`;
        }
        const disableCapoSorting = !nuxt.options.experimental.headNext;
        return `import { DeprecationsPlugin, PromisesPlugin, TemplateParamsPlugin, AliasSortingPlugin } from ${JSON.stringify(unheadPlugins)};
export default {
  disableDefaults: true,
  disableCapoSorting: ${Boolean(disableCapoSorting)},
  plugins: [DeprecationsPlugin, PromisesPlugin, TemplateParamsPlugin, AliasSortingPlugin],
}`;
      }
    });
    addTemplate({
      filename: "unhead.config.mjs",
      getContents() {
        return [
          `export const renderSSRHeadOptions = ${JSON.stringify(options.renderSSRHeadOptions || {})}`
        ].join("\n");
      }
    });
    nuxt.hooks.hook("nitro:config", (config) => {
      config.virtual["#internal/unhead-options.mjs"] = () => nuxt.vfs["#build/unhead-options.mjs"];
      config.virtual["#internal/unhead.config.mjs"] = () => nuxt.vfs["#build/unhead.config.mjs"];
    });
    addPlugin({ src: resolve(runtimeDir, "plugins/unhead") });
  }
});

const createImportMagicComments = (options) => {
  const { chunkName, prefetch, preload } = options;
  return [
    `webpackChunkName: "${chunkName}"`,
    prefetch === true || typeof prefetch === "number" ? `webpackPrefetch: ${prefetch}` : false,
    preload === true || typeof preload === "number" ? `webpackPreload: ${preload}` : false
  ].filter(Boolean).join(", ");
};
const emptyComponentsPlugin = `
import { defineNuxtPlugin } from '#app/nuxt'
export default defineNuxtPlugin({
  name: 'nuxt:global-components',
})
`;
const componentsPluginTemplate = {
  filename: "components.plugin.mjs",
  getContents({ app }) {
    const lazyGlobalComponents = /* @__PURE__ */ new Set();
    const syncGlobalComponents = /* @__PURE__ */ new Set();
    for (const component of app.components) {
      if (component.global === "sync") {
        syncGlobalComponents.add(component.pascalName);
      } else if (component.global) {
        lazyGlobalComponents.add(component.pascalName);
      }
    }
    if (!lazyGlobalComponents.size && !syncGlobalComponents.size) {
      return emptyComponentsPlugin;
    }
    const lazyComponents = [...lazyGlobalComponents];
    const syncComponents = [...syncGlobalComponents];
    return `import { defineNuxtPlugin } from '#app/nuxt'
import { ${[...lazyComponents.map((c) => "Lazy" + c), ...syncComponents].join(", ")} } from '#components'
const lazyGlobalComponents = [
  ${lazyComponents.map((c) => `["${c}", Lazy${c}]`).join(",\n")},
  ${syncComponents.map((c) => `["${c}", ${c}]`).join(",\n")}
]

export default defineNuxtPlugin({
  name: 'nuxt:global-components',
  setup (nuxtApp) {
    for (const [name, component] of lazyGlobalComponents) {
      nuxtApp.vueApp.component(name, component)
      nuxtApp.vueApp.component('Lazy' + name, component)
    }
  }
})
`;
  }
};
const componentNamesTemplate = {
  filename: "component-names.mjs",
  getContents({ app }) {
    return `export const componentNames = ${JSON.stringify(app.components.filter((c) => !c.island).map((c) => c.pascalName))}`;
  }
};
const componentsIslandsTemplate = {
  // components.islands.mjs'
  getContents({ app, nuxt }) {
    if (!nuxt.options.experimental.componentIslands) {
      return "export const islandComponents = {}";
    }
    const components = app.components;
    const pages = app.pages;
    const islands = components.filter(
      (component) => component.island || // .server components without a corresponding .client component will need to be rendered as an island
      component.mode === "server" && !components.some((c) => c.pascalName === component.pascalName && c.mode === "client")
    );
    const pageExports = pages?.filter((p) => p.mode === "server" && p.file && p.name).map((p) => {
      return `"page_${p.name}": defineAsyncComponent(${genDynamicImport(p.file)}.then(c => c.default || c))`;
    }) || [];
    return [
      "import { defineAsyncComponent } from 'vue'",
      "export const islandComponents = import.meta.client ? {} : {",
      islands.map(
        (c) => {
          const exp = c.export === "default" ? "c.default || c" : `c['${c.export}']`;
          const comment = createImportMagicComments(c);
          return `  "${c.pascalName}": defineAsyncComponent(${genDynamicImport(c.filePath, { comment })}.then(c => ${exp}))`;
        }
      ).concat(pageExports).join(",\n"),
      "}"
    ].join("\n");
  }
};
const NON_VUE_RE = /\b\.(?!vue)\w+$/g;
const componentsTypeTemplate = {
  filename: "components.d.ts",
  getContents: ({ app, nuxt }) => {
    const buildDir = nuxt.options.buildDir;
    const componentTypes = app.components.filter((c) => !c.island).map((c) => {
      const type = `typeof ${genDynamicImport(isAbsolute(c.filePath) ? relative(buildDir, c.filePath).replace(NON_VUE_RE, "") : c.filePath.replace(NON_VUE_RE, ""), { wrapper: false })}['${c.export}']`;
      return [
        c.pascalName,
        c.island || c.mode === "server" ? `IslandComponent<${type}>` : type
      ];
    });
    const islandType = "type IslandComponent<T extends DefineComponent> = T & DefineComponent<{}, {refresh: () => Promise<void>}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, SlotsType<{ fallback: { error: unknown } }>>";
    return `
import type { DefineComponent, SlotsType } from 'vue'
${nuxt.options.experimental.componentIslands ? islandType : ""}
type HydrationStrategies = {
  hydrateOnVisible?: IntersectionObserverInit | true
  hydrateOnIdle?: number | true
  hydrateOnInteraction?: keyof HTMLElementEventMap | Array<keyof HTMLElementEventMap> | true
  hydrateOnMediaQuery?: string
  hydrateAfter?: number
  hydrateWhen?: boolean
  hydrateNever?: true
}
type LazyComponent<T> = (T & DefineComponent<HydrationStrategies, {}, {}, {}, {}, {}, {}, { hydrated: () => void }>)
interface _GlobalComponents {
  ${componentTypes.map(([pascalName, type]) => `    '${pascalName}': ${type}`).join("\n")}
  ${componentTypes.map(([pascalName, type]) => `    'Lazy${pascalName}': LazyComponent<${type}>`).join("\n")}
}

declare module 'vue' {
  export interface GlobalComponents extends _GlobalComponents { }
}

${componentTypes.map(([pascalName, type]) => `export const ${pascalName}: ${type}`).join("\n")}
${componentTypes.map(([pascalName, type]) => `export const Lazy${pascalName}: LazyComponent<${type}>`).join("\n")}

export const componentNames: string[]
`;
  }
};
const componentsMetadataTemplate = {
  filename: "components.json",
  write: true,
  getContents: ({ app }) => JSON.stringify(app.components, null, 2)
};

const ISLAND_RE = /\.island(?:\.global)?$/;
const GLOBAL_RE = /\.global(?:\.island)?$/;
const COMPONENT_MODE_RE = /(?<=\.)(client|server)(\.global|\.island)*$/;
const MODE_REPLACEMENT_RE = /(\.(client|server))?(\.global|\.island)*$/;
async function scanComponents(dirs, srcDir) {
  const components = [];
  const filePaths = /* @__PURE__ */ new Set();
  const scannedPaths = [];
  for (const dir of dirs) {
    if (dir.enabled === false) {
      continue;
    }
    const resolvedNames = /* @__PURE__ */ new Map();
    const files = (await glob(dir.pattern, { cwd: dir.path, ignore: dir.ignore })).sort();
    if (files.length) {
      const siblings = new Set(await readdir(dirname(dir.path)).catch(() => []));
      const directory = basename(dir.path);
      if (!siblings.has(directory)) {
        const directoryLowerCase = directory.toLowerCase();
        for (const sibling of siblings) {
          if (sibling.toLowerCase() === directoryLowerCase) {
            const nuxt = useNuxt();
            const original = relative(nuxt.options.srcDir, dir.path);
            const corrected = relative(nuxt.options.srcDir, join(dirname(dir.path), sibling));
            logger.warn(`Components not scanned from \`~/${corrected}\`. Did you mean to name the directory \`~/${original}\` instead?`);
            break;
          }
        }
      }
    }
    for (const _file of files) {
      const filePath = join(dir.path, _file);
      if (scannedPaths.find((d) => filePath.startsWith(withTrailingSlash(d))) || isIgnored(filePath)) {
        continue;
      }
      if (filePaths.has(filePath)) {
        continue;
      }
      filePaths.add(filePath);
      const prefixParts = [].concat(
        dir.prefix ? splitByCase(dir.prefix) : [],
        dir.pathPrefix !== false ? splitByCase(relative(dir.path, dirname(filePath))) : []
      );
      let fileName = basename(filePath, extname(filePath));
      const island = ISLAND_RE.test(fileName) || dir.island;
      const global = GLOBAL_RE.test(fileName) || dir.global;
      const mode = island ? "server" : fileName.match(COMPONENT_MODE_RE)?.[1] || "all";
      fileName = fileName.replace(MODE_REPLACEMENT_RE, "");
      if (fileName.toLowerCase() === "index") {
        fileName = dir.pathPrefix === false ? basename(dirname(filePath)) : "";
      }
      const suffix = mode !== "all" ? `-${mode}` : "";
      const componentNameSegments = resolveComponentNameSegments(fileName.replace(QUOTE_RE, ""), prefixParts);
      const pascalName = pascalCase(componentNameSegments);
      if (LAZY_COMPONENT_NAME_REGEX.test(pascalName)) {
        logger.warn(`The component \`${pascalName}\` (in \`${filePath}\`) is using the reserved "Lazy" prefix used for dynamic imports, which may cause it to break at runtime.`);
      }
      if (resolvedNames.has(pascalName + suffix) || resolvedNames.has(pascalName)) {
        warnAboutDuplicateComponent(pascalName, filePath, resolvedNames.get(pascalName) || resolvedNames.get(pascalName + suffix));
        continue;
      }
      resolvedNames.set(pascalName + suffix, filePath);
      const kebabName = kebabCase(componentNameSegments);
      const shortPath = relative(srcDir, filePath);
      const chunkName = "components/" + kebabName + suffix;
      let component = {
        // inheritable from directory configuration
        mode,
        global,
        island,
        prefetch: Boolean(dir.prefetch),
        preload: Boolean(dir.preload),
        // specific to the file
        filePath,
        pascalName,
        kebabName,
        chunkName,
        shortPath,
        export: "default",
        // by default, give priority to scanned components
        priority: dir.priority ?? 1,
        // @ts-expect-error untyped property
        _scanned: true
      };
      if (typeof dir.extendComponent === "function") {
        component = await dir.extendComponent(component) || component;
      }
      if (!pascalName) {
        logger.warn(`Component did not resolve to a file name in \`~/${relative(srcDir, filePath)}\`.`);
        continue;
      }
      const validModes = /* @__PURE__ */ new Set(["all", component.mode]);
      const existingComponent = components.find((c) => c.pascalName === component.pascalName && validModes.has(c.mode));
      if (existingComponent) {
        const existingPriority = existingComponent.priority ?? 0;
        const newPriority = component.priority ?? 0;
        if (newPriority > existingPriority) {
          components.splice(components.indexOf(existingComponent), 1, component);
        }
        if (newPriority > 0 && newPriority === existingPriority) {
          warnAboutDuplicateComponent(pascalName, filePath, existingComponent.filePath);
        }
        continue;
      }
      components.push(component);
    }
    scannedPaths.push(dir.path);
  }
  return components;
}
function warnAboutDuplicateComponent(componentName, filePath, duplicatePath) {
  logger.warn(
    `Two component files resolving to the same name \`${componentName}\`:

 - ${filePath}
 - ${duplicatePath}`
  );
}
const LAZY_COMPONENT_NAME_REGEX = /^Lazy(?=[A-Z])/;

const REPLACE_COMPONENT_TO_DIRECT_IMPORT_RE = /(?<=[ (])_?resolveComponent\(\s*(?<quote>["'`])(?<lazy>lazy-|Lazy(?=[A-Z]))?(?<modifier>Idle|Visible|idle-|visible-|Interaction|interaction-|MediaQuery|media-query-|If|if-|Never|never-|Time|time-)?(?<name>[^'"`]*)\k<quote>[^)]*\)/g;
const LoaderPlugin = (options) => createUnplugin(() => {
  const exclude = options.transform?.exclude || [];
  const include = options.transform?.include || [];
  const nuxt = tryUseNuxt();
  return {
    name: "nuxt:components-loader",
    enforce: "post",
    transformInclude(id) {
      if (exclude.some((pattern) => pattern.test(id))) {
        return false;
      }
      if (include.some((pattern) => pattern.test(id))) {
        return true;
      }
      return isVue(id, { type: ["template", "script"] }) || !!id.match(SX_RE);
    },
    transform(code, id) {
      const components = options.getComponents();
      let num = 0;
      const imports = /* @__PURE__ */ new Set();
      const map = /* @__PURE__ */ new Map();
      const s = new MagicString(code);
      s.replace(REPLACE_COMPONENT_TO_DIRECT_IMPORT_RE, (full, ...args) => {
        const { lazy, modifier, name } = args.pop();
        const normalComponent = findComponent(components, name, options.mode);
        const modifierComponent = !normalComponent && modifier ? findComponent(components, modifier + name, options.mode) : null;
        const component = normalComponent || modifierComponent;
        if (component) {
          const internalInstall = component._internal_install;
          if (internalInstall && nuxt?.options.test === false) {
            if (!nuxt.options.dev) {
              const relativePath = relative(nuxt.options.rootDir, id);
              throw new Error(`[nuxt] \`~/${relativePath}\` is using \`${component.pascalName}\` which requires \`${internalInstall}\``);
            }
            import('../chunks/features.mjs').then(({ installNuxtModule }) => installNuxtModule(internalInstall));
          }
          let identifier = map.get(component) || `__nuxt_component_${num++}`;
          map.set(component, identifier);
          const isServerOnly = !component._raw && component.mode === "server" && !components.some((c) => c.pascalName === component.pascalName && c.mode === "client");
          if (isServerOnly) {
            imports.add(genImport(options.serverComponentRuntime, [{ name: "createServerComponent" }]));
            imports.add(`const ${identifier} = createServerComponent(${JSON.stringify(component.pascalName)})`);
            if (!options.experimentalComponentIslands) {
              logger.warn(`Standalone server components (\`${name}\`) are not yet supported without enabling \`experimental.componentIslands\`.`);
            }
            return identifier;
          }
          const isClientOnly = !component._raw && component.mode === "client";
          if (isClientOnly) {
            imports.add(genImport("#app/components/client-only", [{ name: "createClientOnly" }]));
            identifier += "_client";
          }
          if (lazy) {
            const dynamicImport = `${genDynamicImport(component.filePath, { interopDefault: false })}.then(c => c.${component.export ?? "default"} || c)`;
            if (modifier && normalComponent) {
              const relativePath = relative(options.srcDir, component.filePath);
              switch (modifier) {
                case "Visible":
                case "visible-":
                  imports.add(genImport(options.clientDelayedComponentRuntime, [{ name: "createLazyVisibleComponent" }]));
                  identifier += "_lazy_visible";
                  imports.add(`const ${identifier} = createLazyVisibleComponent(${JSON.stringify(relativePath)}, ${dynamicImport})`);
                  break;
                case "Interaction":
                case "interaction-":
                  imports.add(genImport(options.clientDelayedComponentRuntime, [{ name: "createLazyInteractionComponent" }]));
                  identifier += "_lazy_event";
                  imports.add(`const ${identifier} = createLazyInteractionComponent(${JSON.stringify(relativePath)}, ${dynamicImport})`);
                  break;
                case "Idle":
                case "idle-":
                  imports.add(genImport(options.clientDelayedComponentRuntime, [{ name: "createLazyIdleComponent" }]));
                  identifier += "_lazy_idle";
                  imports.add(`const ${identifier} = createLazyIdleComponent(${JSON.stringify(relativePath)}, ${dynamicImport})`);
                  break;
                case "MediaQuery":
                case "media-query-":
                  imports.add(genImport(options.clientDelayedComponentRuntime, [{ name: "createLazyMediaQueryComponent" }]));
                  identifier += "_lazy_media";
                  imports.add(`const ${identifier} = createLazyMediaQueryComponent(${JSON.stringify(relativePath)}, ${dynamicImport})`);
                  break;
                case "If":
                case "if-":
                  imports.add(genImport(options.clientDelayedComponentRuntime, [{ name: "createLazyIfComponent" }]));
                  identifier += "_lazy_if";
                  imports.add(`const ${identifier} = createLazyIfComponent(${JSON.stringify(relativePath)}, ${dynamicImport})`);
                  break;
                case "Never":
                case "never-":
                  imports.add(genImport(options.clientDelayedComponentRuntime, [{ name: "createLazyNeverComponent" }]));
                  identifier += "_lazy_never";
                  imports.add(`const ${identifier} = createLazyNeverComponent(${JSON.stringify(relativePath)}, ${dynamicImport})`);
                  break;
                case "Time":
                case "time-":
                  imports.add(genImport(options.clientDelayedComponentRuntime, [{ name: "createLazyTimeComponent" }]));
                  identifier += "_lazy_time";
                  imports.add(`const ${identifier} = createLazyTimeComponent(${JSON.stringify(relativePath)}, ${dynamicImport})`);
                  break;
              }
            } else {
              imports.add(genImport("vue", [{ name: "defineAsyncComponent", as: "__defineAsyncComponent" }]));
              identifier += "_lazy";
              imports.add(`const ${identifier} = __defineAsyncComponent(${dynamicImport}${isClientOnly ? ".then(c => createClientOnly(c))" : ""})`);
            }
          } else {
            imports.add(genImport(component.filePath, [{ name: component._raw ? "default" : component.export, as: identifier }]));
            if (isClientOnly) {
              imports.add(`const ${identifier}_wrapped = createClientOnly(${identifier})`);
              identifier += "_wrapped";
            }
          }
          return identifier;
        }
        return full;
      });
      if (imports.size) {
        s.prepend([...imports, ""].join("\n"));
      }
      if (s.hasChanged()) {
        return {
          code: s.toString(),
          map: options.sourcemap ? s.generateMap({ hires: true }) : void 0
        };
      }
    }
  };
});
function findComponent(components, name, mode) {
  const id = pascalCase(name).replace(QUOTE_RE, "");
  const validModes = /* @__PURE__ */ new Set(["all", mode, void 0]);
  const component = components.find((component2) => id === component2.pascalName && validModes.has(component2.mode));
  if (component) {
    return component;
  }
  const otherModeComponent = components.find((component2) => id === component2.pascalName);
  if (mode === "server" && otherModeComponent) {
    return components.find((c) => c.pascalName === "ServerPlaceholder");
  }
  return otherModeComponent;
}

const SCRIPT_RE$1 = /<script[^>]*>/gi;
const HAS_SLOT_OR_CLIENT_RE = /<slot[^>]*>|nuxt-client/;
const TEMPLATE_RE$1 = /<template>([\s\S]*)<\/template>/;
const NUXTCLIENT_ATTR_RE = /\s:?nuxt-client(="[^"]*")?/g;
const IMPORT_CODE = "\nimport { mergeProps as __mergeProps } from 'vue'\nimport { vforToArray as __vforToArray } from '#app/components/utils'\nimport NuxtTeleportIslandComponent from '#app/components/nuxt-teleport-island-component'\nimport NuxtTeleportSsrSlot from '#app/components/nuxt-teleport-island-slot'";
const EXTRACTED_ATTRS_RE = /v-(?:if|else-if|else)(="[^"]*")?/g;
const KEY_RE = /:?key="[^"]"/g;
function wrapWithVForDiv(code, vfor) {
  return `<div v-for="${vfor}" style="display: contents;">${code}</div>`;
}
const IslandsTransformPlugin = (options) => createUnplugin((_options, meta) => {
  const isVite = meta.framework === "vite";
  return {
    name: "nuxt:server-only-component-transform",
    enforce: "pre",
    transformInclude(id) {
      if (!isVue(id)) {
        return false;
      }
      if (isVite && options.selectiveClient === "deep") {
        return true;
      }
      const components = options.getComponents();
      const islands = components.filter(
        (component) => component.island || component.mode === "server" && !components.some((c) => c.pascalName === component.pascalName && c.mode === "client")
      );
      const { pathname } = parseURL(decodeURIComponent(pathToFileURL(id).href));
      return islands.some((c) => c.filePath === pathname);
    },
    transform: {
      filter: {
        code: {
          include: [HAS_SLOT_OR_CLIENT_RE]
        }
      },
      async handler(code, id) {
        const template = code.match(TEMPLATE_RE$1);
        if (!template) {
          return;
        }
        const startingIndex = template.index || 0;
        const s = new MagicString(code);
        if (!code.match(SCRIPT_RE$1)) {
          s.prepend("<script setup>" + IMPORT_CODE + "<\/script>");
        } else {
          s.replace(SCRIPT_RE$1, (full) => {
            return full + IMPORT_CODE;
          });
        }
        let hasNuxtClient = false;
        const ast = parse(template[0]);
        await walk$2(ast, (node) => {
          if (node.type !== ELEMENT_NODE) {
            return;
          }
          if (node.name === "slot") {
            const { attributes: attributes2, children, loc: loc2 } = node;
            const slotName = attributes2.name ?? "default";
            if (attributes2.name) {
              delete attributes2.name;
            }
            if (attributes2["v-bind"]) {
              attributes2._bind = extractAttributes(attributes2, ["v-bind"])["v-bind"];
            }
            const teleportAttributes = extractAttributes(attributes2, ["v-if", "v-else-if", "v-else"]);
            const bindings = getPropsToString(attributes2);
            s.appendLeft(startingIndex + loc2[0].start, `<NuxtTeleportSsrSlot${attributeToString(teleportAttributes)} name="${slotName}" :props="${bindings}">`);
            if (children.length) {
              const attrString = attributeToString(attributes2);
              const slice = code.slice(startingIndex + loc2[0].end, startingIndex + loc2[1].start).replaceAll(KEY_RE, "");
              s.overwrite(startingIndex + loc2[0].start, startingIndex + loc2[1].end, `<slot${attrString.replaceAll(EXTRACTED_ATTRS_RE, "")}/><template #fallback>${attributes2["v-for"] ? wrapWithVForDiv(slice, attributes2["v-for"]) : slice}</template>`);
            } else {
              s.overwrite(startingIndex + loc2[0].start, startingIndex + loc2[0].end, code.slice(startingIndex + loc2[0].start, startingIndex + loc2[0].end).replaceAll(EXTRACTED_ATTRS_RE, ""));
            }
            s.appendRight(startingIndex + loc2[1].end, "</NuxtTeleportSsrSlot>");
            return;
          }
          if (!("nuxt-client" in node.attributes) && !(":nuxt-client" in node.attributes)) {
            return;
          }
          hasNuxtClient = true;
          if (!isVite || !options.selectiveClient) {
            return;
          }
          const { loc, attributes } = node;
          const attributeValue = attributes[":nuxt-client"] || attributes["nuxt-client"] || "true";
          const wrapperAttributes = extractAttributes(attributes, ["v-if", "v-else-if", "v-else"]);
          let startTag = code.slice(startingIndex + loc[0].start, startingIndex + loc[0].end).replace(NUXTCLIENT_ATTR_RE, "");
          if (wrapperAttributes) {
            startTag = startTag.replaceAll(EXTRACTED_ATTRS_RE, "");
          }
          s.appendLeft(startingIndex + loc[0].start, `<NuxtTeleportIslandComponent${attributeToString(wrapperAttributes)} :nuxt-client="${attributeValue}">`);
          s.overwrite(startingIndex + loc[0].start, startingIndex + loc[0].end, startTag);
          s.appendRight(startingIndex + loc[1].end, "</NuxtTeleportIslandComponent>");
        });
        if (hasNuxtClient) {
          if (!options.selectiveClient) {
            console.warn(`The \`nuxt-client\` attribute and client components within islands are only supported when \`experimental.componentIslands.selectiveClient\` is enabled. file: ${id}`);
          } else if (!isVite) {
            console.warn(`The \`nuxt-client\` attribute and client components within islands are only supported with Vite. file: ${id}`);
          }
        }
        if (s.hasChanged()) {
          return {
            code: s.toString(),
            map: s.generateMap({ source: id, includeContent: true })
          };
        }
      }
    }
  };
});
function extractAttributes(attributes, names) {
  const extracted = {};
  for (const name of names) {
    if (name in attributes) {
      extracted[name] = attributes[name];
      delete attributes[name];
    }
  }
  return extracted;
}
function attributeToString(attributes) {
  return Object.entries(attributes).map(([name, value]) => value ? ` ${name}="${value}"` : ` ${name}`).join("");
}
function isBinding(attr) {
  return attr.startsWith(":");
}
function getPropsToString(bindings) {
  const vfor = bindings["v-for"]?.split(" in ").map((v) => v.trim());
  if (Object.keys(bindings).length === 0) {
    return "undefined";
  }
  const content = Object.entries(bindings).filter((b) => b[0] && (b[0] !== "_bind" && b[0] !== "v-for")).map(([name, value]) => isBinding(name) ? `[\`${name.slice(1)}\`]: ${value}` : `[\`${name}\`]: \`${value}\``).join(",");
  const data = bindings._bind ? `__mergeProps(${bindings._bind}, { ${content} })` : `{ ${content} }`;
  if (!vfor) {
    return `[${data}]`;
  } else {
    return `__vforToArray(${vfor[1]}).map(${vfor[0]} => (${data}))`;
  }
}
const ComponentsChunkPlugin = (options) => {
  const ids = /* @__PURE__ */ new Map();
  const isDev = useNuxt().options.dev;
  return {
    client: createUnplugin(() => {
      return {
        name: "nuxt:components-chunk:client",
        vite: {
          buildStart() {
            const components = options.getComponents().filter((c) => c.mode === "client" || c.mode === "all");
            for (const component of components) {
              if (component.filePath) {
                if (isDev) {
                  ids.set(component.pascalName, `@fs/${component.filePath}`);
                } else {
                  const id = this.emitFile({
                    type: "chunk",
                    fileName: "_nuxt/" + hash(component.filePath) + ".mjs",
                    id: component.filePath,
                    preserveSignature: "strict"
                  });
                  ids.set(component.pascalName, this.getFileName(id));
                }
              }
            }
          },
          generateBundle(_, bundle) {
            const idSet = new Set(ids.values());
            for (const chunk of Object.values(bundle)) {
              if (chunk.type === "chunk") {
                if (idSet.has(chunk.fileName)) {
                  chunk.isEntry = false;
                }
              }
            }
          }
        }
      };
    }),
    server: createUnplugin(() => {
      return {
        name: "nuxt:components-chunk:server",
        buildStart() {
          writeFileSync(
            join(useNuxt().options.buildDir, "component-chunk.mjs"),
            `export default {${Array.from(ids.entries()).map(([name, id]) => {
              return `${JSON.stringify(name)}: ${JSON.stringify("/" + id)}`;
            }).join(",\n")}}`
          );
        }
      };
    })
  };
};

const COMPONENT_QUERY_RE = /[?&]nuxt_component=/;
function TransformPlugin$1(nuxt, options) {
  const componentUnimport = createUnimport({
    imports: [
      {
        name: "componentNames",
        from: "#build/component-names"
      }
    ],
    virtualImports: ["#components"],
    injectAtEnd: true
  });
  function getComponentsImports() {
    const components = options.getComponents(options.mode);
    const clientOrServerModes = /* @__PURE__ */ new Set(["client", "server"]);
    return components.flatMap((c) => {
      const withMode = (mode2) => mode2 ? `${c.filePath}${c.filePath.includes("?") ? "&" : "?"}nuxt_component=${mode2}&nuxt_component_name=${c.pascalName}&nuxt_component_export=${c.export || "default"}` : c.filePath;
      const mode = !c._raw && c.mode && clientOrServerModes.has(c.mode) ? c.mode : void 0;
      return [
        {
          as: c.pascalName,
          from: withMode(mode),
          name: c.export || "default"
        },
        {
          as: "Lazy" + c.pascalName,
          from: withMode([mode, "async"].filter(Boolean).join(",")),
          name: c.export || "default"
        }
      ];
    });
  }
  return createUnplugin(() => ({
    name: "nuxt:components:imports",
    enforce: "post",
    transformInclude(id) {
      id = normalize(id);
      return id.startsWith("virtual:") || id.startsWith("\0virtual:") || id.startsWith(nuxt.options.buildDir) || !isIgnored(id, void 0, nuxt);
    },
    async transform(code, id) {
      if (COMPONENT_QUERY_RE.test(id)) {
        const { search } = parseURL(id);
        const query = parseQuery$1(search);
        const mode = query.nuxt_component;
        const bare = id.replace(/\?.*/, "");
        const componentExport = query.nuxt_component_export || "default";
        const exportWording = componentExport === "default" ? "export default" : `export const ${componentExport} =`;
        if (mode === "async") {
          return {
            code: [
              'import { defineAsyncComponent } from "vue"',
              `${exportWording} defineAsyncComponent(() => import(${JSON.stringify(bare)}).then(r => r[${JSON.stringify(componentExport)}] || r.default || r))`
            ].join("\n"),
            map: null
          };
        } else if (mode === "client") {
          return {
            code: [
              genImport(bare, [{ name: componentExport, as: "__component" }]),
              'import { createClientOnly } from "#app/components/client-only"',
              `${exportWording} createClientOnly(__component)`
            ].join("\n"),
            map: null
          };
        } else if (mode === "client,async") {
          return {
            code: [
              'import { defineAsyncComponent } from "vue"',
              'import { createClientOnly } from "#app/components/client-only"',
              `${exportWording} defineAsyncComponent(() => import(${JSON.stringify(bare)}).then(r => createClientOnly(r[${JSON.stringify(componentExport)}] || r.default || r)))`
            ].join("\n"),
            map: null
          };
        } else if (mode === "server" || mode === "server,async") {
          const name = query.nuxt_component_name;
          return {
            code: [
              `import { createServerComponent } from ${JSON.stringify(options.serverComponentRuntime)}`,
              `${exportWording} createServerComponent(${JSON.stringify(name)})`
            ].join("\n"),
            map: null
          };
        } else {
          throw new Error(`Unknown component mode: ${mode}, this might be an internal bug of Nuxt.`);
        }
      }
      if (!code.includes("#components")) {
        return;
      }
      componentUnimport.modifyDynamicImports((imports) => {
        imports.length = 0;
        imports.push(...getComponentsImports());
        return imports;
      });
      const result = await componentUnimport.injectImports(code, id, { autoImport: false, transformVirtualImports: true });
      if (!result) {
        return;
      }
      return {
        code: result.code,
        map: nuxt.options.sourcemap.server || nuxt.options.sourcemap.client ? result.s.generateMap({ hires: true }) : void 0
      };
    }
  }));
}

const SSR_RENDER_RE = /ssrRenderComponent/;
const PLACEHOLDER_EXACT_RE = /^(?:fallback|placeholder)$/;
const CLIENT_ONLY_NAME_RE = /^(?:_unref\()?(?:_component_)?(?:Lazy|lazy_)?(?:client_only|ClientOnly\)?)$/;
const TreeShakeTemplatePlugin = (options) => createUnplugin(() => {
  const regexpMap = /* @__PURE__ */ new WeakMap();
  return {
    name: "nuxt:tree-shake-template",
    enforce: "post",
    transformInclude(id) {
      const { pathname } = parseURL(decodeURIComponent(pathToFileURL(id).href));
      return pathname.endsWith(".vue");
    },
    transform(code, id) {
      const components = options.getComponents();
      if (!regexpMap.has(components)) {
        const serverPlaceholderPath = resolve(distDir, "app/components/server-placeholder");
        const clientOnlyComponents = components.filter((c) => c.mode === "client" && !components.some((other) => other.mode !== "client" && other.pascalName === c.pascalName && !other.filePath.startsWith(serverPlaceholderPath))).flatMap((c) => [c.pascalName, c.kebabName.replaceAll("-", "_")]).concat(["ClientOnly", "client_only"]);
        regexpMap.set(components, [new RegExp(`(${clientOnlyComponents.join("|")})`), new RegExp(`^(${clientOnlyComponents.map((c) => `(?:(?:_unref\\()?(?:_component_)?(?:Lazy|lazy_)?${c}\\)?)`).join("|")})$`), clientOnlyComponents]);
      }
      const s = new MagicString(code);
      const [COMPONENTS_RE, COMPONENTS_IDENTIFIERS_RE] = regexpMap.get(components);
      if (!COMPONENTS_RE.test(code)) {
        return;
      }
      const componentsToRemoveSet = /* @__PURE__ */ new Set();
      const ast = parseAndWalk(code, id, (node) => {
        if (!isSsrRender(node)) {
          return;
        }
        const [componentCall, _, children] = node.arguments;
        if (!componentCall) {
          return;
        }
        if (componentCall.type === "Identifier" || componentCall.type === "MemberExpression" || componentCall.type === "CallExpression") {
          const componentName = getComponentName(node);
          if (!componentName || !COMPONENTS_IDENTIFIERS_RE.test(componentName) || children?.type !== "ObjectExpression") {
            return;
          }
          const isClientOnlyComponent = CLIENT_ONLY_NAME_RE.test(componentName);
          const slotsToRemove = isClientOnlyComponent ? children.properties.filter((prop) => prop.type === "Property" && prop.key.type === "Identifier" && !PLACEHOLDER_EXACT_RE.test(prop.key.name)) : children.properties;
          for (const _slot of slotsToRemove) {
            const slot = withLocations(_slot);
            s.remove(slot.start, slot.end + 1);
            const removedCode = `({${code.slice(slot.start, slot.end + 1)}})`;
            const currentState = s.toString();
            parseAndWalk(removedCode, id, (node2) => {
              if (!isSsrRender(node2)) {
                return;
              }
              const name = getComponentName(node2);
              if (!name) {
                return;
              }
              const nameToRemove = isComponentNotCalledInSetup(currentState, id, name);
              if (nameToRemove) {
                componentsToRemoveSet.add(nameToRemove);
              }
            });
          }
        }
      });
      const componentsToRemove = [...componentsToRemoveSet];
      const removedNodes = /* @__PURE__ */ new WeakSet();
      for (const componentName of componentsToRemove) {
        removeImportDeclaration(ast, componentName, s);
        removeVariableDeclarator(ast, componentName, s, removedNodes);
        removeFromSetupReturn(ast, componentName, s);
      }
      if (s.hasChanged()) {
        return {
          code: s.toString(),
          map: options.sourcemap ? s.generateMap({ hires: true }) : void 0
        };
      }
    }
  };
});
function removeFromSetupReturn(codeAst, name, magicString) {
  let walkedInSetup = false;
  walk(codeAst, {
    enter(node) {
      if (walkedInSetup) {
        this.skip();
      } else if (node.type === "Property" && node.key.type === "Identifier" && node.key.name === "setup" && (node.value.type === "FunctionExpression" || node.value.type === "ArrowFunctionExpression")) {
        walkedInSetup = true;
        if (node.value.body?.type === "BlockStatement") {
          const returnStatement = node.value.body.body.find((statement) => statement.type === "ReturnStatement");
          if (returnStatement && returnStatement.argument?.type === "ObjectExpression") {
            removePropertyFromObject(returnStatement.argument, name, magicString);
          }
          const variableList = node.value.body.body.filter((statement) => statement.type === "VariableDeclaration");
          const returnedVariableDeclaration = variableList.find((declaration) => declaration.declarations[0]?.id.type === "Identifier" && declaration.declarations[0]?.id.name === "__returned__" && declaration.declarations[0]?.init?.type === "ObjectExpression");
          if (returnedVariableDeclaration) {
            const init = returnedVariableDeclaration.declarations[0]?.init;
            if (init) {
              removePropertyFromObject(init, name, magicString);
            }
          }
        }
      }
    }
  });
}
function removePropertyFromObject(node, name, magicString) {
  for (const property of node.properties) {
    if (property.type === "Property" && property.key.type === "Identifier" && property.key.name === name) {
      const _property = withLocations(property);
      magicString.remove(_property.start, _property.end + 1);
      return true;
    }
  }
  return false;
}
function isSsrRender(node) {
  return node.type === "CallExpression" && node.callee.type === "Identifier" && SSR_RENDER_RE.test(node.callee.name);
}
function removeImportDeclaration(ast, importName, magicString) {
  for (const node of ast.body) {
    if (node.type !== "ImportDeclaration" || !node.specifiers) {
      continue;
    }
    const specifierIndex = node.specifiers.findIndex((s) => s.local.name === importName);
    if (specifierIndex > -1) {
      if (node.specifiers.length > 1) {
        const specifier = withLocations(node.specifiers[specifierIndex]);
        magicString.remove(specifier.start, specifier.end + 1);
        node.specifiers.splice(specifierIndex, 1);
      } else {
        const specifier = withLocations(node);
        magicString.remove(specifier.start, specifier.end);
      }
      return true;
    }
  }
  return false;
}
function isComponentNotCalledInSetup(code, id, name) {
  if (!name) {
    return;
  }
  let found = false;
  parseAndWalk(code, id, function(node) {
    if (node.type === "Property" && node.key.type === "Identifier" && node.value.type === "FunctionExpression" && node.key.name === "setup" || node.type === "FunctionDeclaration" && (node.id?.name === "_sfc_ssrRender" || node.id?.name === "ssrRender")) {
      walk(node, {
        enter(node2) {
          if (found || node2.type === "VariableDeclaration") {
            this.skip();
          } else if (node2.type === "Identifier" && node2.name === name) {
            found = true;
          } else if (node2.type === "MemberExpression") {
            found = node2.property.type === "Literal" && node2.property.value === name || node2.property.type === "Identifier" && node2.property.name === name;
          }
        }
      });
    }
  });
  if (!found) {
    return name;
  }
}
function getComponentName(ssrRenderNode) {
  const componentCall = ssrRenderNode.arguments[0];
  if (!componentCall) {
    return;
  }
  if (componentCall.type === "Identifier") {
    return componentCall.name;
  } else if (componentCall.type === "MemberExpression") {
    if (componentCall.property.type === "Literal") {
      return componentCall.property.value;
    }
  } else if (componentCall.type === "CallExpression") {
    return getComponentName(componentCall);
  }
}
function removeVariableDeclarator(codeAst, name, magicString, removedNodes) {
  walk(codeAst, {
    enter(node) {
      if (node.type !== "VariableDeclaration") {
        return;
      }
      for (const declarator of node.declarations) {
        const toRemove = withLocations(findMatchingPatternToRemove(declarator.id, node, name, removedNodes));
        if (toRemove) {
          magicString.remove(toRemove.start, toRemove.end + 1);
          removedNodes.add(toRemove);
        }
      }
    }
  });
}
function findMatchingPatternToRemove(node, toRemoveIfMatched, name, removedNodeSet) {
  if (node.type === "Identifier") {
    if (node.name === name) {
      return toRemoveIfMatched;
    }
  } else if (node.type === "ArrayPattern") {
    const elements = node.elements.filter((e) => e !== null && !removedNodeSet.has(e));
    for (const element of elements) {
      const matched = findMatchingPatternToRemove(element, elements.length > 1 ? element : toRemoveIfMatched, name, removedNodeSet);
      if (matched) {
        return matched;
      }
    }
  } else if (node.type === "ObjectPattern") {
    const properties = node.properties.filter((e) => e.type === "Property" && !removedNodeSet.has(e));
    for (const [index, property] of properties.entries()) {
      let nodeToRemove = property;
      if (properties.length < 2) {
        nodeToRemove = toRemoveIfMatched;
      }
      const matched = findMatchingPatternToRemove(property.value, nodeToRemove, name, removedNodeSet);
      if (matched) {
        if (matched === property) {
          properties.splice(index, 1);
        }
        return matched;
      }
    }
  } else if (node.type === "AssignmentPattern") {
    const matched = findMatchingPatternToRemove(node.left, toRemoveIfMatched, name, removedNodeSet);
    if (matched) {
      return matched;
    }
  }
}

const FILENAME_RE = /([^/\\]+)\.\w+$/;
const ComponentNamePlugin = (options) => createUnplugin(() => {
  return {
    name: "nuxt:component-name-plugin",
    enforce: "post",
    transformInclude(id) {
      return isVue(id) || !!id.match(SX_RE);
    },
    transform: {
      filter: {
        id: { include: FILENAME_RE }
      },
      handler(code, id) {
        const filename = id.match(FILENAME_RE)?.[1];
        if (!filename) {
          return;
        }
        const component = options.getComponents().find((c) => c.filePath === id);
        if (!component) {
          return;
        }
        const NAME_RE = new RegExp(`__name:\\s*['"]${filename}['"]`);
        const s = new MagicString(code);
        s.replace(NAME_RE, `__name: ${JSON.stringify(component.pascalName)}`);
        if (!s.hasChanged()) {
          parseAndWalk(code, id, function(node) {
            if (node.type !== "ExportDefaultDeclaration") {
              return;
            }
            const { start, end } = withLocations(node.declaration);
            s.overwrite(start, end, `Object.assign(${code.slice(start, end)}, { __name: ${JSON.stringify(component.pascalName)} })`);
            this.skip();
          });
        }
        if (s.hasChanged()) {
          return {
            code: s.toString(),
            map: options.sourcemap ? s.generateMap({ hires: true }) : void 0
          };
        }
      }
    }
  };
});

const TEMPLATE_RE = /<template>([\s\S]*)<\/template>/;
const hydrationStrategyMap = {
  hydrateOnIdle: "Idle",
  hydrateOnVisible: "Visible",
  hydrateOnInteraction: "Interaction",
  hydrateOnMediaQuery: "MediaQuery",
  hydrateAfter: "Time",
  hydrateWhen: "If",
  hydrateNever: "Never"
};
const LAZY_HYDRATION_PROPS_RE = /\bhydrate-?on-?idle|hydrate-?on-?visible|hydrate-?on-?interaction|hydrate-?on-?media-?query|hydrate-?after|hydrate-?when|hydrate-?never\b/;
const LazyHydrationTransformPlugin = (options) => createUnplugin(() => {
  const exclude = options.transform?.exclude || [];
  const include = options.transform?.include || [];
  return {
    name: "nuxt:components-loader-pre",
    enforce: "pre",
    transformInclude(id) {
      if (exclude.some((pattern) => pattern.test(id))) {
        return false;
      }
      if (include.some((pattern) => pattern.test(id))) {
        return true;
      }
      return isVue(id);
    },
    transform: {
      filter: {
        code: { include: TEMPLATE_RE }
      },
      async handler(code) {
        const { 0: template, index: offset = 0 } = code.match(TEMPLATE_RE) || {};
        if (!template || !LAZY_HYDRATION_PROPS_RE.test(template)) {
          return;
        }
        const s = new MagicString(code);
        try {
          const ast = parse(template);
          const components = options.getComponents();
          await walk$2(ast, (node) => {
            if (node.type !== 1) {
              return;
            }
            if (!/^(?:Lazy|lazy-)/.test(node.name)) {
              return;
            }
            const pascalName = pascalCase(node.name.slice(4));
            if (!components.some((c) => c.pascalName === pascalName)) {
              return;
            }
            let strategy;
            for (const attr in node.attributes) {
              const isDynamic = attr.startsWith(":");
              const prop = camelCase(isDynamic ? attr.slice(1) : attr);
              if (prop in hydrationStrategyMap) {
                if (strategy) {
                  logger.warn(`Multiple hydration strategies are not supported in the same component`);
                } else {
                  strategy = hydrationStrategyMap[prop];
                }
              }
            }
            if (strategy) {
              const newName = "Lazy" + strategy + pascalName;
              const chunk = template.slice(node.loc[0].start, node.loc.at(-1).end);
              const chunkOffset = node.loc[0].start + offset;
              const { 0: startingChunk, index: startingPoint = 0 } = chunk.match(new RegExp(`<${node.name}[^>]*>`)) || {};
              s.overwrite(startingPoint + chunkOffset, startingPoint + chunkOffset + startingChunk.length, startingChunk.replace(node.name, newName));
              const { 0: endingChunk, index: endingPoint } = chunk.match(new RegExp(`<\\/${node.name}[^>]*>$`)) || {};
              if (endingChunk && endingPoint) {
                s.overwrite(endingPoint + chunkOffset, endingPoint + chunkOffset + endingChunk.length, endingChunk.replace(node.name, newName));
              }
            }
          });
        } catch {
        }
        if (s.hasChanged()) {
          return {
            code: s.toString(),
            map: options.sourcemap ? s.generateMap({ hires: true }) : void 0
          };
        }
      }
    }
  };
});

const isPureObjectOrString = (val) => !Array.isArray(val) && typeof val === "object" || typeof val === "string";
const isDirectory = (p) => {
  try {
    return statSync(p).isDirectory();
  } catch {
    return false;
  }
};
const SLASH_SEPARATOR_RE = /[\\/]/;
function compareDirByPathLength({ path: pathA }, { path: pathB }) {
  return pathB.split(SLASH_SEPARATOR_RE).filter(Boolean).length - pathA.split(SLASH_SEPARATOR_RE).filter(Boolean).length;
}
const DEFAULT_COMPONENTS_DIRS_RE = /\/components(?:\/(?:global|islands))?$/;
const STARTER_DOT_RE = /^\./g;
const componentsModule = defineNuxtModule({
  meta: {
    name: "nuxt:components",
    configKey: "components"
  },
  defaults: {
    dirs: []
  },
  async setup(componentOptions, nuxt) {
    let componentDirs = [];
    const context = {
      components: []
    };
    const getComponents = (mode) => {
      return mode && mode !== "all" ? context.components.filter((c) => c.mode === mode || c.mode === "all" || c.mode === "server" && !context.components.some((otherComponent) => otherComponent.mode !== "server" && otherComponent.pascalName === c.pascalName)) : context.components;
    };
    if (nuxt.options.experimental.normalizeComponentNames) {
      addBuildPlugin(ComponentNamePlugin({ sourcemap: !!nuxt.options.sourcemap.client, getComponents }), { server: false });
      addBuildPlugin(ComponentNamePlugin({ sourcemap: !!nuxt.options.sourcemap.server, getComponents }), { client: false });
    }
    const normalizeDirs = (dir, cwd, options) => {
      if (Array.isArray(dir)) {
        return dir.map((dir2) => normalizeDirs(dir2, cwd, options)).flat().sort(compareDirByPathLength);
      }
      if (dir === true || dir === void 0) {
        return [
          { priority: options?.priority || 0, path: resolve(cwd, "components/islands"), island: true },
          { priority: options?.priority || 0, path: resolve(cwd, "components/global"), global: true },
          { priority: options?.priority || 0, path: resolve(cwd, "components") }
        ];
      }
      if (typeof dir === "string") {
        return [
          { priority: options?.priority || 0, path: resolve(cwd, resolveAlias(dir)) }
        ];
      }
      if (!dir) {
        return [];
      }
      const dirs = (dir.dirs || [dir]).map((dir2) => typeof dir2 === "string" ? { path: dir2 } : dir2).filter((_dir) => _dir.path);
      return dirs.map((_dir) => ({
        priority: options?.priority || 0,
        ..._dir,
        path: resolve(cwd, resolveAlias(_dir.path))
      }));
    };
    nuxt.hook("app:resolve", async () => {
      const allDirs = nuxt.options._layers.map((layer) => normalizeDirs(layer.config.components, layer.config.srcDir, { priority: layer.config.srcDir === nuxt.options.srcDir ? 1 : 0 })).flat();
      await nuxt.callHook("components:dirs", allDirs);
      componentDirs = allDirs.filter(isPureObjectOrString).map((dir) => {
        const dirOptions = typeof dir === "object" ? dir : { path: dir };
        const dirPath = resolveAlias(dirOptions.path);
        const transpile = typeof dirOptions.transpile === "boolean" ? dirOptions.transpile : "auto";
        const extensions = (dirOptions.extensions || nuxt.options.extensions).map((e) => e.replace(STARTER_DOT_RE, ""));
        const present = isDirectory(dirPath);
        if (!present && !DEFAULT_COMPONENTS_DIRS_RE.test(dirOptions.path)) {
          logger.warn("Components directory not found: `" + dirPath + "`");
        }
        return {
          global: componentOptions.global,
          ...dirOptions,
          // TODO: https://github.com/nuxt/framework/pull/251
          enabled: true,
          path: dirPath,
          extensions,
          pattern: dirOptions.pattern || `**/*.{${extensions.join(",")},}`,
          ignore: [
            "**/*{M,.m,-m}ixin.{js,ts,jsx,tsx}",
            // ignore mixins
            "**/*.d.{cts,mts,ts}",
            // .d.ts files
            ...dirOptions.ignore || []
          ],
          transpile: transpile === "auto" ? dirPath.includes("node_modules") : transpile
        };
      }).filter((d) => d.enabled);
      componentDirs = [
        ...componentDirs.filter((dir) => !dir.path.includes("node_modules")),
        ...componentDirs.filter((dir) => dir.path.includes("node_modules"))
      ];
      nuxt.options.build.transpile.push(...componentDirs.filter((dir) => dir.transpile).map((dir) => dir.path));
    });
    addTypeTemplate(componentsTypeTemplate);
    addPluginTemplate(componentsPluginTemplate);
    addTemplate(componentNamesTemplate);
    addTemplate({ ...componentsIslandsTemplate, filename: "components.islands.mjs" });
    if (componentOptions.generateMetadata) {
      addTemplate(componentsMetadataTemplate);
    }
    const serverComponentRuntime = await findPath(join(distDir, "components/runtime/server-component")) ?? join(distDir, "components/runtime/server-component");
    addBuildPlugin(TransformPlugin$1(nuxt, { getComponents, serverComponentRuntime, mode: "server" }), { server: true, client: false });
    addBuildPlugin(TransformPlugin$1(nuxt, { getComponents, serverComponentRuntime, mode: "client" }), { server: false, client: true });
    nuxt.hook("build:manifest", (manifest) => {
      const sourceFiles = /* @__PURE__ */ new Set();
      for (const c of getComponents()) {
        if (c.global) {
          sourceFiles.add(relative(nuxt.options.srcDir, c.filePath));
        }
      }
      for (const chunk of Object.values(manifest)) {
        if (chunk.isEntry) {
          chunk.dynamicImports = chunk.dynamicImports?.filter((i) => !sourceFiles.has(i));
        }
      }
    });
    const restartEvents = /* @__PURE__ */ new Set(["addDir", "unlinkDir"]);
    nuxt.hook("builder:watch", (event, relativePath) => {
      if (!restartEvents.has(event)) {
        return;
      }
      const path = resolve(nuxt.options.srcDir, relativePath);
      if (componentDirs.some((dir) => dir.path === path)) {
        logger.info(`Directory \`${relativePath}/\` ${event === "addDir" ? "created" : "removed"}`);
        return nuxt.callHook("restart");
      }
    });
    const serverPlaceholderPath = await findPath(join(distDir, "app/components/server-placeholder")) ?? join(distDir, "app/components/server-placeholder");
    nuxt.hook("app:templates", async (app) => {
      const newComponents = await scanComponents(componentDirs, nuxt.options.srcDir);
      await nuxt.callHook("components:extend", newComponents);
      for (const component of newComponents) {
        if (!component._scanned && !(component.filePath in nuxt.vfs) && isAbsolute(component.filePath) && !existsSync(component.filePath)) {
          component.filePath = resolveModulePath(resolveAlias(component.filePath), { try: true, extensions: nuxt.options.extensions }) ?? component.filePath;
        }
        if (component.mode === "client" && !newComponents.some((c) => c.pascalName === component.pascalName && c.mode === "server")) {
          newComponents.push({
            ...component,
            _raw: true,
            mode: "server",
            filePath: serverPlaceholderPath,
            chunkName: "components/" + component.kebabName
          });
        }
        if (component.mode === "server" && !nuxt.options.ssr && !newComponents.some((other) => other.pascalName === component.pascalName && other.mode === "client")) {
          logger.warn(`Using server components with \`ssr: false\` is not supported with auto-detected component islands. If you need to use server component \`${component.pascalName}\`, set \`experimental.componentIslands\` to \`true\`.`);
        }
      }
      context.components = newComponents;
      app.components = newComponents;
    });
    nuxt.hook("prepare:types", ({ tsConfig }) => {
      tsConfig.compilerOptions.paths["#components"] = [resolve(nuxt.options.buildDir, "components")];
    });
    if (nuxt.options.experimental.treeshakeClientOnly) {
      addBuildPlugin(TreeShakeTemplatePlugin({ sourcemap: !!nuxt.options.sourcemap.server, getComponents }), { client: false });
    }
    const clientDelayedComponentRuntime = await findPath(join(distDir, "components/runtime/lazy-hydrated-component")) ?? join(distDir, "components/runtime/lazy-hydrated-component");
    const sharedLoaderOptions = {
      getComponents,
      clientDelayedComponentRuntime,
      serverComponentRuntime,
      srcDir: nuxt.options.srcDir,
      transform: typeof nuxt.options.components === "object" && !Array.isArray(nuxt.options.components) ? nuxt.options.components.transform : void 0,
      experimentalComponentIslands: !!nuxt.options.experimental.componentIslands
    };
    addBuildPlugin(LoaderPlugin({ ...sharedLoaderOptions, sourcemap: !!nuxt.options.sourcemap.client, mode: "client" }), { server: false });
    addBuildPlugin(LoaderPlugin({ ...sharedLoaderOptions, sourcemap: !!nuxt.options.sourcemap.server, mode: "server" }), { client: false });
    if (nuxt.options.experimental.lazyHydration) {
      addBuildPlugin(LazyHydrationTransformPlugin({
        ...sharedLoaderOptions,
        sourcemap: !!(nuxt.options.sourcemap.server || nuxt.options.sourcemap.client)
      }), { prepend: true });
    }
    if (nuxt.options.experimental.componentIslands) {
      const selectiveClient = typeof nuxt.options.experimental.componentIslands === "object" && nuxt.options.experimental.componentIslands.selectiveClient;
      addVitePlugin({
        name: "nuxt-server-component-hmr",
        handleHotUpdate(ctx) {
          const components = getComponents();
          const filePath = normalize(ctx.file);
          const comp = components.find((c) => c.filePath === filePath);
          if (comp?.mode === "server") {
            ctx.server.ws.send({
              event: `nuxt-server-component:${comp.pascalName}`,
              type: "custom"
            });
          }
        }
      }, { server: false });
      addBuildPlugin(IslandsTransformPlugin({ getComponents, selectiveClient }), { client: false });
      const chunk = ComponentsChunkPlugin({ getComponents });
      nuxt.hook("vite:extendConfig", (config, { isClient }) => {
        config.plugins ||= [];
        if (selectiveClient && isClient) {
          config.plugins.push(chunk.client.vite());
        }
      });
      addBuildPlugin(chunk.server, { client: false, prepend: true });
    }
  }
});

const NODE_MODULES_RE$1 = /[\\/]node_modules[\\/]/;
const IMPORTS_RE = /(['"])#imports\1/;
const TransformPlugin = ({ ctx, options, sourcemap }) => createUnplugin(() => {
  return {
    name: "nuxt:imports-transform",
    enforce: "post",
    transformInclude(id) {
      if (options.transform?.include?.some((pattern) => pattern.test(id))) {
        return true;
      }
      if (options.transform?.exclude?.some((pattern) => pattern.test(id))) {
        return false;
      }
      if (isVue(id, { type: ["script", "template"] })) {
        return true;
      }
      return isJS(id);
    },
    async transform(code, id) {
      id = normalize(id);
      const isNodeModule = NODE_MODULES_RE$1.test(id) && !options.transform?.include?.some((pattern) => pattern.test(id));
      if (isNodeModule && !IMPORTS_RE.test(code)) {
        return;
      }
      const { s, imports } = await ctx.injectImports(code, id, { autoImport: options.autoImport && !isNodeModule });
      if (imports.some((i) => i.from === "#app/composables/script-stubs") && tryUseNuxt()?.options.test === false) {
        import('../chunks/features.mjs').then(({ installNuxtModule }) => installNuxtModule("@nuxt/scripts"));
      }
      if (s.hasChanged()) {
        return {
          code: s.toString(),
          map: sourcemap ? s.generateMap({ hires: true }) : void 0
        };
      }
    }
  };
});

const commonPresets = [
  // vue-demi (mocked)
  defineUnimportPreset({
    from: "vue-demi",
    imports: [
      "isVue2",
      "isVue3"
    ]
  })
];
const granularAppPresets = [
  {
    from: "#app/components/nuxt-link",
    imports: ["defineNuxtLink"]
  },
  {
    imports: ["useNuxtApp", "tryUseNuxtApp", "defineNuxtPlugin", "definePayloadPlugin", "useRuntimeConfig", "defineAppConfig"],
    from: "#app/nuxt"
  },
  {
    imports: ["useAppConfig", "updateAppConfig"],
    from: "#app/config"
  },
  {
    imports: ["defineNuxtComponent"],
    from: "#app/composables/component"
  },
  {
    imports: ["useAsyncData", "useLazyAsyncData", "useNuxtData", "refreshNuxtData", "clearNuxtData"],
    from: "#app/composables/asyncData"
  },
  {
    imports: ["useHydration"],
    from: "#app/composables/hydrate"
  },
  {
    imports: ["callOnce"],
    from: "#app/composables/once"
  },
  {
    imports: ["useState", "clearNuxtState"],
    from: "#app/composables/state"
  },
  {
    imports: ["clearError", "createError", "isNuxtError", "showError", "useError"],
    from: "#app/composables/error"
  },
  {
    imports: ["useFetch", "useLazyFetch"],
    from: "#app/composables/fetch"
  },
  {
    imports: ["useCookie", "refreshCookie"],
    from: "#app/composables/cookie"
  },
  {
    imports: ["onPrehydrate", "prerenderRoutes", "useRequestHeader", "useRequestHeaders", "useResponseHeader", "useRequestEvent", "useRequestFetch", "setResponseStatus"],
    from: "#app/composables/ssr"
  },
  {
    imports: ["onNuxtReady"],
    from: "#app/composables/ready"
  },
  {
    imports: ["preloadComponents", "prefetchComponents", "preloadRouteComponents"],
    from: "#app/composables/preload"
  },
  {
    imports: ["abortNavigation", "addRouteMiddleware", "defineNuxtRouteMiddleware", "setPageLayout", "navigateTo", "useRoute", "useRouter"],
    from: "#app/composables/router"
  },
  {
    imports: ["isPrerendered", "loadPayload", "preloadPayload", "definePayloadReducer", "definePayloadReviver"],
    from: "#app/composables/payload"
  },
  {
    imports: ["useLoadingIndicator"],
    from: "#app/composables/loading-indicator"
  },
  {
    imports: ["getAppManifest", "getRouteRules"],
    from: "#app/composables/manifest"
  },
  {
    imports: ["reloadNuxtApp"],
    from: "#app/composables/chunk"
  },
  {
    imports: ["useRequestURL"],
    from: "#app/composables/url"
  },
  {
    imports: ["usePreviewMode"],
    from: "#app/composables/preview"
  },
  {
    imports: ["useRouteAnnouncer"],
    from: "#app/composables/route-announcer"
  },
  {
    imports: ["useRuntimeHook"],
    from: "#app/composables/runtime-hook"
  },
  {
    imports: ["useHead", "useHeadSafe", "useServerHeadSafe", "useServerHead", "useSeoMeta", "useServerSeoMeta", "injectHead"],
    from: "#app/composables/head"
  }
];
const scriptsStubsPreset = {
  imports: [
    "useScriptTriggerConsent",
    "useScriptEventPage",
    "useScriptTriggerElement",
    "useScript",
    "useScriptGoogleAnalytics",
    "useScriptPlausibleAnalytics",
    "useScriptCrisp",
    "useScriptClarity",
    "useScriptCloudflareWebAnalytics",
    "useScriptFathomAnalytics",
    "useScriptMatomoAnalytics",
    "useScriptGoogleTagManager",
    "useScriptGoogleAdsense",
    "useScriptSegment",
    "useScriptMetaPixel",
    "useScriptXPixel",
    "useScriptIntercom",
    "useScriptHotjar",
    "useScriptStripe",
    "useScriptLemonSqueezy",
    "useScriptVimeoPlayer",
    "useScriptYouTubePlayer",
    "useScriptGoogleMaps",
    "useScriptNpm",
    "useScriptUmamiAnalytics",
    "useScriptSnapchatPixel",
    "useScriptRybbitAnalytics"
  ],
  priority: -1,
  from: "#app/composables/script-stubs"
};
const routerPreset = defineUnimportPreset({
  imports: ["onBeforeRouteLeave", "onBeforeRouteUpdate"],
  from: "#app/composables/router"
});
const vuePreset = defineUnimportPreset({
  from: "vue",
  imports: [
    // <script setup>
    "withCtx",
    "withDirectives",
    "withKeys",
    "withMemo",
    "withModifiers",
    "withScopeId",
    // Lifecycle
    "onActivated",
    "onBeforeMount",
    "onBeforeUnmount",
    "onBeforeUpdate",
    "onDeactivated",
    "onErrorCaptured",
    "onMounted",
    "onRenderTracked",
    "onRenderTriggered",
    "onServerPrefetch",
    "onUnmounted",
    "onUpdated",
    // Reactivity
    "computed",
    "customRef",
    "isProxy",
    "isReactive",
    "isReadonly",
    "isRef",
    "markRaw",
    "proxyRefs",
    "reactive",
    "readonly",
    "ref",
    "shallowReactive",
    "shallowReadonly",
    "shallowRef",
    "toRaw",
    "toRef",
    "toRefs",
    "triggerRef",
    "unref",
    "watch",
    "watchEffect",
    "watchPostEffect",
    "watchSyncEffect",
    "isShallow",
    // effect
    "effect",
    "effectScope",
    "getCurrentScope",
    "onScopeDispose",
    // Component
    "defineComponent",
    "defineAsyncComponent",
    "resolveComponent",
    "getCurrentInstance",
    "h",
    "inject",
    "hasInjectionContext",
    "nextTick",
    "provide",
    "mergeModels",
    "toValue",
    "useModel",
    "useAttrs",
    "useCssModule",
    "useCssVars",
    "useSlots",
    "useTransitionState",
    "useId",
    "useTemplateRef",
    "useShadowRoot",
    "useCssVars"
  ]
});
const vueTypesPreset = defineUnimportPreset({
  from: "vue",
  type: true,
  imports: [
    "Component",
    "ComponentPublicInstance",
    "ComputedRef",
    "DirectiveBinding",
    "ExtractDefaultPropTypes",
    "ExtractPropTypes",
    "ExtractPublicPropTypes",
    "InjectionKey",
    "PropType",
    "Ref",
    "MaybeRef",
    "MaybeRefOrGetter",
    "VNode",
    "WritableComputedRef"
  ]
});
const appCompatPresets = [
  {
    imports: ["requestIdleCallback", "cancelIdleCallback"],
    from: "#app/compat/idle-callback"
  },
  {
    imports: ["setInterval"],
    from: "#app/compat/interval"
  }
];
const defaultPresets = [
  ...commonPresets,
  ...granularAppPresets,
  routerPreset,
  vuePreset,
  vueTypesPreset
];

const importsModule = defineNuxtModule({
  meta: {
    name: "nuxt:imports",
    configKey: "imports"
  },
  defaults: (nuxt) => ({
    autoImport: true,
    scan: true,
    presets: defaultPresets,
    global: false,
    imports: [],
    dirs: [],
    transform: {
      include: [
        new RegExp("^" + escapeRE(nuxt.options.buildDir))
      ],
      exclude: void 0
    },
    virtualImports: ["#imports"],
    polyfills: true
  }),
  async setup(options, nuxt) {
    const presets = JSON.parse(JSON.stringify(options.presets));
    if (options.polyfills) {
      presets.push(...appCompatPresets);
    }
    await nuxt.callHook("imports:sources", presets);
    const { addons: inlineAddons, ...rest } = options;
    const [addons, addonsOptions] = Array.isArray(inlineAddons) ? [inlineAddons] : [[], inlineAddons];
    const ctx = createUnimport({
      injectAtEnd: true,
      ...rest,
      addons: {
        addons,
        vueTemplate: options.autoImport,
        vueDirectives: options.autoImport === false ? void 0 : true,
        ...addonsOptions
      },
      presets
    });
    await nuxt.callHook("imports:context", ctx);
    const isNuxtV4 = nuxt.options.future?.compatibilityVersion === 4;
    let composablesDirs = [];
    if (options.scan) {
      for (const layer of nuxt.options._layers) {
        if (layer.config?.imports?.scan === false) {
          continue;
        }
        composablesDirs.push(resolve(layer.config.srcDir, "composables"));
        composablesDirs.push(resolve(layer.config.srcDir, "utils"));
        if (isNuxtV4) {
          composablesDirs.push(resolve(layer.config.rootDir, layer.config.dir?.shared ?? "shared", "utils"));
          composablesDirs.push(resolve(layer.config.rootDir, layer.config.dir?.shared ?? "shared", "types"));
        }
        for (const dir of layer.config.imports?.dirs ?? []) {
          if (!dir) {
            continue;
          }
          composablesDirs.push(resolve(layer.config.srcDir, dir));
        }
      }
      await nuxt.callHook("imports:dirs", composablesDirs);
      composablesDirs = composablesDirs.map((dir) => normalize(dir));
      nuxt.hook("builder:watch", (event, relativePath) => {
        if (!["addDir", "unlinkDir"].includes(event)) {
          return;
        }
        const path = resolve(nuxt.options.srcDir, relativePath);
        if (composablesDirs.includes(path)) {
          logger.info(`Directory \`${relativePath}/\` ${event === "addDir" ? "created" : "removed"}`);
          return nuxt.callHook("restart");
        }
      });
    }
    addTemplate({
      filename: "imports.mjs",
      getContents: async () => toExports(await ctx.getImports()) + '\nif (import.meta.dev) { console.warn("[nuxt] `#imports` should be transformed with real imports. There seems to be something wrong with the imports plugin.") }'
    });
    nuxt.options.alias["#imports"] = join(nuxt.options.buildDir, "imports");
    addBuildPlugin(TransformPlugin({ ctx, options, sourcemap: !!nuxt.options.sourcemap.server || !!nuxt.options.sourcemap.client }));
    const priorities = nuxt.options._layers.map((layer, i) => [layer.config.srcDir, -i]).sort(([a], [b]) => b.length - a.length);
    const IMPORTS_TEMPLATE_RE = /\/imports\.(?:d\.ts|mjs)$/;
    function isImportsTemplate(template) {
      return IMPORTS_TEMPLATE_RE.test(template.filename);
    }
    const isIgnored = createIsIgnored(nuxt);
    const defaultImportSources = new Set(defaultPresets.flatMap((i) => i.from));
    const defaultImports = new Set(presets.flatMap((p) => defaultImportSources.has(p.from) ? p.imports : []));
    const regenerateImports = async () => {
      await ctx.modifyDynamicImports(async (imports) => {
        imports.length = 0;
        if (options.scan) {
          const scannedImports = await scanDirExports(composablesDirs, {
            fileFilter: (file) => !isIgnored(file)
          });
          for (const i of scannedImports) {
            i.priority ||= priorities.find(([dir]) => i.from.startsWith(dir))?.[1];
          }
          imports.push(...scannedImports);
        }
        await nuxt.callHook("imports:extend", imports);
        for (const i of imports) {
          if (!defaultImportSources.has(i.from)) {
            const value = i.as || i.name;
            if (defaultImports.has(value) && (!i.priority || i.priority >= 0)) {
              const relativePath = isAbsolute(i.from) ? `~/${relative(nuxt.options.srcDir, i.from)}` : i.from;
              logger.error(`\`${value}\` is an auto-imported function that is in use by Nuxt. Overriding it will likely cause issues. Please consider renaming \`${value}\` in \`${relativePath}\`.`);
            }
          }
        }
        return imports;
      });
      await updateTemplates({
        filter: isImportsTemplate
      });
    };
    await regenerateImports();
    addDeclarationTemplates(ctx, options);
    nuxt.hook("builder:watch", async (_, relativePath) => {
      const path = resolve(nuxt.options.srcDir, relativePath);
      if (options.scan && composablesDirs.some((dir) => dir === path || path.startsWith(dir + "/"))) {
        await regenerateImports();
      }
    });
    nuxt.hook("app:templatesGenerated", async (_app, templates) => {
      if (templates.some((t) => !isImportsTemplate(t))) {
        await regenerateImports();
      }
    });
  }
});
function addDeclarationTemplates(ctx, options) {
  const nuxt = useNuxt();
  const resolvedImportPathMap = /* @__PURE__ */ new Map();
  const r = ({ from }) => resolvedImportPathMap.get(from);
  const SUPPORTED_EXTENSION_RE = new RegExp(`\\.(${nuxt.options.extensions.map((i) => i.replace(".", "")).join("|")})$`);
  const importPaths = nuxt.options.modulesDir.map((dir) => directoryToURL(dir));
  async function cacheImportPaths(imports) {
    const importSource = Array.from(new Set(imports.map((i) => i.from)));
    await Promise.all(importSource.map(async (from) => {
      if (resolvedImportPathMap.has(from) || nuxt._dependencies?.has(from)) {
        return;
      }
      let path = resolveAlias(from);
      if (!isAbsolute(path)) {
        path = await tryResolveModule(from, importPaths).then(async (r2) => {
          if (!r2) {
            return r2;
          }
          const { dir, name } = parseNodeModulePath(r2);
          if (name && nuxt._dependencies?.has(name)) {
            return from;
          }
          if (!dir || !name) {
            return r2;
          }
          const subpath = await lookupNodeModuleSubpath(r2);
          return join(dir, name, subpath || "");
        }) ?? path;
      }
      if (existsSync(path) && !await isDirectory$1(path)) {
        path = path.replace(SUPPORTED_EXTENSION_RE, "");
      }
      if (isAbsolute(path)) {
        path = relative(join(nuxt.options.buildDir, "types"), path);
      }
      resolvedImportPathMap.set(from, path);
    }));
  }
  addTypeTemplate({
    filename: "imports.d.ts",
    getContents: async ({ nuxt: nuxt2 }) => toExports(await ctx.getImports(), nuxt2.options.buildDir, true)
  });
  addTypeTemplate({
    filename: "types/imports.d.ts",
    getContents: async () => {
      const imports = await ctx.getImports();
      await cacheImportPaths(imports);
      return "// Generated by auto imports\n" + (options.autoImport ? await ctx.generateTypeDeclarations({ resolvePath: r }) : "// Implicit auto importing is disabled, you can use explicitly import from `#imports` instead.");
    }
  });
}

const version = "3.17.5";

const createImportProtectionPatterns = (nuxt, options) => {
  const patterns = [];
  const context = contextFlags[options.context];
  patterns.push([
    /^(nuxt|nuxt3|nuxt-nightly)$/,
    `\`nuxt\`, or \`nuxt-nightly\` cannot be imported directly in ${context}.` + (options.context === "nuxt-app" ? " Instead, import runtime Nuxt composables from `#app` or `#imports`." : "")
  ]);
  patterns.push([
    /^((~|~~|@|@@)?\/)?nuxt\.config(\.|$)/,
    "Importing directly from a `nuxt.config` file is not allowed. Instead, use runtime config or a module."
  ]);
  patterns.push([/(^|node_modules\/)@vue\/composition-api/]);
  for (const mod of nuxt.options.modules.filter((m) => typeof m === "string")) {
    patterns.push([
      new RegExp(`^${escapeRE(mod)}$`),
      "Importing directly from module entry-points is not allowed."
    ]);
  }
  for (const i of [/(^|node_modules\/)@nuxt\/(cli|kit|test-utils)/, /(^|node_modules\/)nuxi/, /(^|node_modules\/)nitro(?:pack)?(?:-nightly)?(?:$|\/)(?!(?:dist\/)?(?:node_modules|presets|runtime|types))/, /(^|node_modules\/)nuxt\/(config|kit|schema)/]) {
    patterns.push([i, `This module cannot be imported in ${context}.`]);
  }
  if (options.context === "nitro-app" || options.context === "shared") {
    for (const i of ["#app", /^#build(\/|$)/]) {
      patterns.push([i, `Vue app aliases are not allowed in ${context}.`]);
    }
  }
  if (options.context === "nuxt-app" || options.context === "shared") {
    patterns.push([
      new RegExp(escapeRE(relative(nuxt.options.srcDir, resolve(nuxt.options.srcDir, nuxt.options.serverDir || "server"))) + "\\/(api|routes|middleware|plugins)\\/"),
      `Importing from server is not allowed in ${context}.`
    ]);
  }
  return patterns;
};
const contextFlags = {
  "nitro-app": "server runtime",
  "nuxt-app": "the Vue part of your app",
  "shared": "the #shared directory"
};

const TRANSFORM_MARKER = "/* _processed_nuxt_unctx_transform */\n";
const TRANSFORM_MARKER_RE = /^\/\* _processed_nuxt_unctx_transform \*\/\n/;
const UnctxTransformPlugin = (options) => createUnplugin(() => {
  const transformer = createTransformer(options.transformerOptions);
  return {
    name: "unctx:transform",
    enforce: "post",
    transformInclude(id) {
      return isVue(id, { type: ["template", "script"] }) || isJS(id);
    },
    transform: {
      filter: {
        code: { exclude: TRANSFORM_MARKER_RE }
      },
      handler(code) {
        if (!transformer.shouldTransform(code)) {
          return;
        }
        const result = transformer.transform(code);
        if (result) {
          return {
            code: TRANSFORM_MARKER + result.code,
            map: options.sourcemap ? result.magicString.generateMap({ hires: true }) : void 0
          };
        }
      }
    }
  };
});

const TreeShakeComposablesPlugin = (options) => createUnplugin(() => {
  const composableNames = Object.values(options.composables).flat();
  const regexp = `(^\\s*)(${composableNames.join("|")})(?=\\((?!\\) \\{))`;
  const COMPOSABLE_RE = new RegExp(regexp, "m");
  const COMPOSABLE_RE_GLOBAL = new RegExp(regexp, "gm");
  return {
    name: "nuxt:tree-shake-composables:transform",
    enforce: "post",
    transformInclude(id) {
      return isVue(id, { type: ["script"] }) || isJS(id);
    },
    transform: {
      filter: {
        code: { include: COMPOSABLE_RE }
      },
      handler(code) {
        const s = new MagicString(code);
        const strippedCode = stripLiteral(code);
        for (const match of strippedCode.matchAll(COMPOSABLE_RE_GLOBAL)) {
          s.overwrite(match.index, match.index + match[0].length, `${match[1]} false && /*@__PURE__*/ ${match[2]}`);
        }
        if (s.hasChanged()) {
          return {
            code: s.toString(),
            map: options.sourcemap ? s.generateMap({ hires: true }) : void 0
          };
        }
      }
    }
  };
});

const DEVONLY_COMP_SINGLE_RE = /<(?:dev-only|DevOnly|lazy-dev-only|LazyDevOnly)>[\s\S]*?<\/(?:dev-only|DevOnly|lazy-dev-only|LazyDevOnly)>/;
const DEVONLY_COMP_RE = /<(?:dev-only|DevOnly|lazy-dev-only|LazyDevOnly)>[\s\S]*?<\/(?:dev-only|DevOnly|lazy-dev-only|LazyDevOnly)>/g;
const DevOnlyPlugin = (options) => createUnplugin(() => {
  return {
    name: "nuxt:server-devonly:transform",
    enforce: "pre",
    transformInclude(id) {
      return isVue(id, { type: ["template"] });
    },
    transform: {
      filter: {
        code: { include: DEVONLY_COMP_SINGLE_RE }
      },
      handler(code) {
        const s = new MagicString(code);
        for (const match of code.matchAll(DEVONLY_COMP_RE)) {
          const ast = parse(match[0]).children[0];
          const fallback = ast.children?.find((n) => {
            if (n.name !== "template") {
              return false;
            }
            return "fallback" in n.attributes || "#fallback" in n.attributes;
          });
          const replacement = fallback ? match[0].slice(fallback.loc[0].end, fallback.loc[fallback.loc.length - 1].start) : "";
          s.overwrite(match.index, match.index + match[0].length, replacement);
        }
        if (s.hasChanged()) {
          return {
            code: s.toString(),
            map: options.sourcemap ? s.generateMap({ hires: true }) : void 0
          };
        }
      }
    }
  };
});

const ALIAS_RE = /(?<=['"])[~@]{1,2}(?=\/)/g;
const ALIAS_RE_SINGLE = /(?<=['"])[~@]{1,2}(?=\/)/;
const LayerAliasingPlugin = (options) => createUnplugin((_options, meta) => {
  const aliases = {};
  for (const layer of options.layers) {
    const srcDir = layer.config.srcDir || layer.cwd;
    const rootDir = layer.config.rootDir || layer.cwd;
    aliases[srcDir] = {
      "~": layer.config?.alias?.["~"] || srcDir,
      "@": layer.config?.alias?.["@"] || srcDir,
      "~~": layer.config?.alias?.["~~"] || rootDir,
      "@@": layer.config?.alias?.["@@"] || rootDir
    };
  }
  const layers = Object.keys(aliases).sort((a, b) => b.length - a.length);
  return {
    name: "nuxt:layer-aliasing",
    enforce: "pre",
    vite: {
      resolveId: {
        order: "pre",
        async handler(id, importer) {
          if (!importer) {
            return;
          }
          const layer = layers.find((l) => importer.startsWith(l));
          if (!layer) {
            return;
          }
          const resolvedId = resolveAlias(id, aliases[layer]);
          if (resolvedId !== id) {
            return await this.resolve(resolvedId, importer, { skipSelf: true });
          }
        }
      }
    },
    // webpack-only transform
    transformInclude: (id) => {
      if (meta.framework === "vite") {
        return false;
      }
      const _id = normalize(id);
      return layers.some((dir) => _id.startsWith(dir));
    },
    transform: {
      filter: {
        code: { include: ALIAS_RE_SINGLE }
      },
      handler(code, id) {
        if (meta.framework === "vite") {
          return;
        }
        const _id = normalize(id);
        const layer = layers.find((l) => _id.startsWith(l));
        if (!layer) {
          return;
        }
        const s = new MagicString(code);
        s.replace(ALIAS_RE, (r) => aliases[layer]?.[r] || r);
        if (s.hasChanged()) {
          return {
            code: s.toString(),
            map: options.sourcemap ? s.generateMap({ hires: true }) : void 0
          };
        }
      }
    }
  };
});

const addModuleTranspiles = (opts = {}) => {
  const nuxt = useNuxt();
  const modules = [
    ...opts.additionalModules || [],
    ...nuxt.options.modules,
    ...nuxt.options._modules
  ].map((m) => typeof m === "string" ? m : Array.isArray(m) ? m[0] : m.src).filter((m) => typeof m === "string").map(normalizeModuleTranspilePath);
  nuxt.options.build.transpile = nuxt.options.build.transpile.map((m) => typeof m === "string" ? m.split("node_modules/").pop() : m).filter((x) => !!x);
  function isTranspilePresent(mod) {
    return nuxt.options.build.transpile.some((t) => !(t instanceof Function) && (t instanceof RegExp ? t.test(mod) : new RegExp(t).test(mod)));
  }
  for (const module of modules) {
    if (!isTranspilePresent(module)) {
      nuxt.options.build.transpile.push(module);
    }
  }
};

const template = () => {
  return '<svg xmlns="http://www.w3.org/2000/svg" width="80" fill="none" class="nuxt-spa-loading" viewBox="0 0 37 25"><path d="M24.236 22.006h10.742L25.563 5.822l-8.979 14.31a4 4 0 0 1-3.388 1.874H2.978l11.631-20 5.897 10.567"/></svg><style>.nuxt-spa-loading{left:50%;position:fixed;top:50%;transform:translate(-50%,-50%)}.nuxt-spa-loading>path{fill:none;stroke:#00dc82;stroke-width:4px;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:128;stroke-dashoffset:128;animation:nuxt-spa-loading-move 3s linear infinite}@keyframes nuxt-spa-loading-move{to{stroke-dashoffset:-128}}</style>';
};

const logLevelMapReverse = {
  silent: 0,
  info: 3,
  verbose: 3
};
const NODE_MODULES_RE = /(?<=\/)node_modules\/(.+)$/;
const PNPM_NODE_MODULES_RE = /\.pnpm\/.+\/node_modules\/(.+)$/;
async function initNitro(nuxt) {
  const excludePaths = nuxt.options._layers.flatMap((l) => [
    l.cwd.match(NODE_MODULES_RE)?.[1],
    l.cwd.match(PNPM_NODE_MODULES_RE)?.[1]
  ]).filter((dir) => Boolean(dir)).map((dir) => escapeRE(dir));
  const excludePattern = excludePaths.length ? [new RegExp(`node_modules\\/(?!${excludePaths.join("|")})`)] : [/node_modules/];
  const rootDirWithSlash = withTrailingSlash(nuxt.options.rootDir);
  const modules = await resolveNuxtModule(
    rootDirWithSlash,
    nuxt.options._installedModules.filter((m) => m.entryPath).map((m) => m.entryPath)
  );
  const sharedDirs = /* @__PURE__ */ new Set();
  const isNuxtV4 = nuxt.options.future?.compatibilityVersion === 4;
  if (isNuxtV4 && (nuxt.options.nitro.imports !== false && nuxt.options.imports.scan !== false)) {
    for (const layer of nuxt.options._layers) {
      if (layer.config?.imports?.scan === false) {
        continue;
      }
      sharedDirs.add(resolve(layer.config.rootDir, layer.config.dir?.shared ?? "shared", "utils"));
      sharedDirs.add(resolve(layer.config.rootDir, layer.config.dir?.shared ?? "shared", "types"));
    }
  }
  const mockProxy = resolveModulePath("mocked-exports/proxy", { from: import.meta.url });
  const nitroConfig = defu(nuxt.options.nitro, {
    debug: nuxt.options.debug ? nuxt.options.debug.nitro : false,
    rootDir: nuxt.options.rootDir,
    workspaceDir: nuxt.options.workspaceDir,
    srcDir: nuxt.options.serverDir,
    dev: nuxt.options.dev,
    buildDir: nuxt.options.buildDir,
    experimental: {
      asyncContext: nuxt.options.experimental.asyncContext,
      typescriptBundlerResolution: nuxt.options.future.typescriptBundlerResolution || nuxt.options.typescript?.tsConfig?.compilerOptions?.moduleResolution?.toLowerCase() === "bundler" || nuxt.options.nitro.typescript?.tsConfig?.compilerOptions?.moduleResolution?.toLowerCase() === "bundler"
    },
    framework: {
      name: "nuxt",
      version: version
    },
    imports: {
      autoImport: nuxt.options.imports.autoImport,
      dirs: [...sharedDirs],
      imports: [
        {
          as: "__buildAssetsURL",
          name: "buildAssetsURL",
          from: resolve(distDir, "core/runtime/nitro/utils/paths")
        },
        {
          as: "__publicAssetsURL",
          name: "publicAssetsURL",
          from: resolve(distDir, "core/runtime/nitro/utils/paths")
        },
        {
          // TODO: Remove after https://github.com/nitrojs/nitro/issues/1049
          as: "defineAppConfig",
          name: "defineAppConfig",
          from: resolve(distDir, "core/runtime/nitro/utils/config"),
          priority: -1
        }
      ],
      exclude: [...excludePattern, /[\\/]\.git[\\/]/]
    },
    esbuild: {
      options: { exclude: excludePattern }
    },
    analyze: !nuxt.options.test && nuxt.options.build.analyze && (nuxt.options.build.analyze === true || nuxt.options.build.analyze.enabled) ? {
      template: "treemap",
      projectRoot: nuxt.options.rootDir,
      filename: join(nuxt.options.analyzeDir, "{name}.html")
    } : false,
    scanDirs: nuxt.options._layers.map((layer) => (layer.config.serverDir || layer.config.srcDir) && resolve(layer.cwd, layer.config.serverDir || resolve(layer.config.srcDir, "server"))).filter(Boolean),
    renderer: resolve(distDir, "core/runtime/nitro/handlers/renderer"),
    errorHandler: resolve(distDir, "core/runtime/nitro/handlers/error"),
    nodeModulesDirs: nuxt.options.modulesDir,
    handlers: nuxt.options.serverHandlers,
    devHandlers: [],
    baseURL: nuxt.options.app.baseURL,
    virtual: {
      "#internal/nuxt.config.mjs": () => nuxt.vfs["#build/nuxt.config.mjs"],
      "#spa-template": async () => `export const template = ${JSON.stringify(await spaLoadingTemplate(nuxt))}`
    },
    routeRules: {
      "/__nuxt_error": { cache: false }
    },
    appConfig: nuxt.options.appConfig,
    appConfigFiles: nuxt.options._layers.map(
      (layer) => resolve(layer.config.srcDir, "app.config")
    ),
    typescript: {
      strict: true,
      generateTsConfig: true,
      tsconfigPath: "tsconfig.server.json",
      tsConfig: {
        compilerOptions: {
          lib: ["esnext", "webworker", "dom.iterable"]
        },
        include: [
          join(nuxt.options.buildDir, "types/nitro-nuxt.d.ts"),
          ...modules.map((m) => join(relativeWithDot(nuxt.options.buildDir, m), "runtime/server"))
        ],
        exclude: [
          ...nuxt.options.modulesDir.map((m) => relativeWithDot(nuxt.options.buildDir, m)),
          // nitro generate output: https://github.com/nuxt/nuxt/blob/main/packages/nuxt/src/core/nitro.ts#L186
          relativeWithDot(nuxt.options.buildDir, resolve(nuxt.options.rootDir, "dist"))
        ]
      }
    },
    publicAssets: [
      nuxt.options.dev ? { dir: resolve(nuxt.options.buildDir, "dist/client") } : {
        dir: join(nuxt.options.buildDir, "dist/client", nuxt.options.app.buildAssetsDir),
        maxAge: 31536e3,
        baseURL: nuxt.options.app.buildAssetsDir
      },
      ...nuxt.options._layers.map((layer) => resolve(layer.config.srcDir, (layer.config.rootDir === nuxt.options.rootDir ? nuxt.options.dir : layer.config.dir)?.public || "public")).filter((dir) => existsSync(dir)).map((dir) => ({ dir }))
    ],
    prerender: {
      ignoreUnprefixedPublicAssets: true,
      failOnError: true,
      concurrency: cpus().length * 4 || 4,
      routes: [].concat(nuxt.options.generate.routes)
    },
    sourceMap: nuxt.options.sourcemap.server,
    externals: {
      inline: [
        ...nuxt.options.dev ? [] : [
          ...nuxt.options.experimental.externalVue ? [] : ["vue", "@vue/"],
          "@nuxt/",
          nuxt.options.buildDir
        ],
        ...nuxt.options.build.transpile.filter((i) => typeof i === "string"),
        "nuxt/dist",
        "nuxt3/dist",
        "nuxt-nightly/dist",
        distDir,
        // Ensure app config files have auto-imports injected even if they are pure .js files
        ...nuxt.options._layers.map((layer) => resolve(layer.config.srcDir, "app.config"))
      ],
      traceInclude: [
        // force include files used in generated code from the runtime-compiler
        ...nuxt.options.vue.runtimeCompiler && !nuxt.options.experimental.externalVue ? [
          ...nuxt.options.modulesDir.reduce((targets, path) => {
            const serverRendererPath = resolve(path, "vue/server-renderer/index.js");
            if (existsSync(serverRendererPath)) {
              targets.push(serverRendererPath);
            }
            return targets;
          }, [])
        ] : []
      ]
    },
    alias: {
      // Vue 3 mocks
      ...nuxt.options.vue.runtimeCompiler || nuxt.options.experimental.externalVue ? {} : {
        "estree-walker": mockProxy,
        "@babel/parser": mockProxy,
        "@vue/compiler-core": mockProxy,
        "@vue/compiler-dom": mockProxy,
        "@vue/compiler-ssr": mockProxy
      },
      "@vue/devtools-api": "vue-devtools-stub",
      // Nuxt aliases
      ...nuxt.options.alias,
      // Paths
      "#internal/nuxt/paths": resolve(distDir, "core/runtime/nitro/utils/paths")
    },
    replace: {
      "process.env.NUXT_NO_SSR": nuxt.options.ssr === false,
      "process.env.NUXT_EARLY_HINTS": nuxt.options.experimental.writeEarlyHints !== false,
      "process.env.NUXT_NO_SCRIPTS": String(nuxt.options.features.noScripts === "all" || !!nuxt.options.features.noScripts && !nuxt.options.dev),
      "process.env.NUXT_INLINE_STYLES": !!nuxt.options.features.inlineStyles,
      "process.env.PARSE_ERROR_DATA": String(!!nuxt.options.experimental.parseErrorData),
      "process.env.NUXT_JSON_PAYLOADS": !!nuxt.options.experimental.renderJsonPayloads,
      "process.env.NUXT_ASYNC_CONTEXT": !!nuxt.options.experimental.asyncContext,
      "process.env.NUXT_SHARED_DATA": !!nuxt.options.experimental.sharedPrerenderData,
      "process.dev": nuxt.options.dev,
      "__VUE_PROD_DEVTOOLS__": false
    },
    rollupConfig: {
      output: {},
      plugins: []
    },
    logLevel: logLevelMapReverse[nuxt.options.logLevel]
  });
  nitroConfig.srcDir = resolve(nuxt.options.rootDir, nuxt.options.srcDir, nitroConfig.srcDir);
  nitroConfig.ignore ||= [];
  nitroConfig.ignore.push(
    ...resolveIgnorePatterns(nitroConfig.srcDir),
    `!${join(nuxt.options.buildDir, "dist/client", nuxt.options.app.buildAssetsDir, "**/*")}`
  );
  nitroConfig.plugins = nitroConfig.plugins?.map((plugin) => plugin ? resolveAlias(plugin, nuxt.options.alias) : plugin);
  if (nuxt.options.experimental.appManifest) {
    const buildId = nuxt.options.runtimeConfig.app.buildId ||= nuxt.options.buildId;
    const buildTimestamp = Date.now();
    const manifestPrefix = joinURL(nuxt.options.app.buildAssetsDir, "builds");
    const tempDir = join(nuxt.options.buildDir, "manifest");
    nitroConfig.prerender ||= {};
    nitroConfig.prerender.ignore ||= [];
    nitroConfig.prerender.ignore.push(joinURL(nuxt.options.app.baseURL, manifestPrefix));
    nitroConfig.publicAssets.unshift(
      // build manifest
      {
        dir: join(tempDir, "meta"),
        maxAge: 31536e3,
        baseURL: joinURL(manifestPrefix, "meta")
      },
      // latest build
      {
        dir: tempDir,
        maxAge: 1,
        baseURL: manifestPrefix
      }
    );
    nuxt.options.alias["#app-manifest"] = join(tempDir, `meta/${buildId}.json`);
    if (!nuxt.options.dev) {
      nuxt.hook("build:before", async () => {
        await promises.mkdir(join(tempDir, "meta"), { recursive: true });
        await promises.writeFile(join(tempDir, `meta/${buildId}.json`), JSON.stringify({}));
      });
    }
    if (nuxt.options.future.compatibilityVersion !== 4) {
      nuxt.hook("nitro:config", (config) => {
        for (const value of Object.values(config.routeRules || {})) {
          if ("experimentalNoScripts" in value) {
            value.noScripts = value.experimentalNoScripts;
            delete value.experimentalNoScripts;
          }
        }
      });
    }
    nuxt.hook("nitro:config", (config) => {
      config.alias ||= {};
      config.alias["#app-manifest"] = join(tempDir, `meta/${buildId}.json`);
      const rules = config.routeRules;
      for (const rule in rules) {
        if (!rules[rule].appMiddleware) {
          continue;
        }
        const value = rules[rule].appMiddleware;
        if (typeof value === "string") {
          rules[rule].appMiddleware = { [value]: true };
        } else if (Array.isArray(value)) {
          const normalizedRules = {};
          for (const middleware of value) {
            normalizedRules[middleware] = true;
          }
          rules[rule].appMiddleware = normalizedRules;
        }
      }
    });
    nuxt.hook("nitro:init", (nitro2) => {
      nitro2.hooks.hook("rollup:before", async (nitro3) => {
        const routeRules = {};
        const _routeRules = nitro3.options.routeRules;
        const validManifestKeys = /* @__PURE__ */ new Set(["prerender", "redirect", "appMiddleware"]);
        for (const key in _routeRules) {
          if (key === "/__nuxt_error") {
            continue;
          }
          let hasRules = false;
          const filteredRules = {};
          for (const routeKey in _routeRules[key]) {
            const value = _routeRules[key][routeKey];
            if (value && validManifestKeys.has(routeKey)) {
              if (routeKey === "redirect") {
                filteredRules[routeKey] = typeof value === "string" ? value : value.to;
              } else {
                filteredRules[routeKey] = value;
              }
              hasRules = true;
            }
          }
          if (hasRules) {
            routeRules[key] = filteredRules;
          }
        }
        const prerenderedRoutes = /* @__PURE__ */ new Set();
        const routeRulesMatcher = toRouteMatcher(
          createRouter({ routes: routeRules })
        );
        if (nitro3._prerenderedRoutes?.length) {
          const payloadSuffix = nuxt.options.experimental.renderJsonPayloads ? "/_payload.json" : "/_payload.js";
          for (const route of nitro3._prerenderedRoutes) {
            if (!route.error && route.route.endsWith(payloadSuffix)) {
              const url = route.route.slice(0, -payloadSuffix.length) || "/";
              const rules = defu({}, ...routeRulesMatcher.matchAll(url).reverse());
              if (!rules.prerender) {
                prerenderedRoutes.add(url);
              }
            }
          }
        }
        const manifest = {
          id: buildId,
          timestamp: buildTimestamp,
          matcher: exportMatcher(routeRulesMatcher),
          prerendered: nuxt.options.dev ? [] : [...prerenderedRoutes]
        };
        await promises.mkdir(join(tempDir, "meta"), { recursive: true });
        await promises.writeFile(join(tempDir, "latest.json"), JSON.stringify({
          id: buildId,
          timestamp: buildTimestamp
        }));
        await promises.writeFile(join(tempDir, `meta/${buildId}.json`), JSON.stringify(manifest));
      });
    });
  }
  if (!nuxt.options.experimental.appManifest) {
    nuxt.options.alias["#app-manifest"] = mockProxy;
  }
  const FORWARD_SLASH_RE = /\//g;
  if (!nuxt.options.ssr) {
    nitroConfig.virtual["#build/dist/server/server.mjs"] = "export default () => {}";
    if (process.platform === "win32") {
      nitroConfig.virtual["#build/dist/server/server.mjs".replace(FORWARD_SLASH_RE, "\\")] = "export default () => {}";
    }
  }
  if (nuxt.options.dev || nuxt.options.builder === "@nuxt/webpack-builder" || nuxt.options.builder === "@nuxt/rspack-builder") {
    nitroConfig.virtual["#build/dist/server/styles.mjs"] = "export default {}";
    if (process.platform === "win32") {
      nitroConfig.virtual["#build/dist/server/styles.mjs".replace(FORWARD_SLASH_RE, "\\")] = "export default {}";
    }
  }
  if (nuxt.options.experimental.respectNoSSRHeader) {
    nitroConfig.handlers ||= [];
    nitroConfig.handlers.push({
      handler: resolve(distDir, "core/runtime/nitro/middleware/no-ssr"),
      middleware: true
    });
  }
  nitroConfig.rollupConfig.plugins = await nitroConfig.rollupConfig.plugins || [];
  nitroConfig.rollupConfig.plugins = toArray(nitroConfig.rollupConfig.plugins);
  const sharedDir = withTrailingSlash(resolve(nuxt.options.rootDir, nuxt.options.dir.shared));
  const relativeSharedDir = withTrailingSlash(relative(nuxt.options.rootDir, resolve(nuxt.options.rootDir, nuxt.options.dir.shared)));
  const sharedPatterns = [/^#shared\//, new RegExp("^" + escapeRE(sharedDir)), new RegExp("^" + escapeRE(relativeSharedDir))];
  nitroConfig.rollupConfig.plugins.push(
    ImpoundPlugin.rollup({
      cwd: nuxt.options.rootDir,
      include: sharedPatterns,
      patterns: createImportProtectionPatterns(nuxt, { context: "shared" })
    }),
    ImpoundPlugin.rollup({
      cwd: nuxt.options.rootDir,
      patterns: createImportProtectionPatterns(nuxt, { context: "nitro-app" }),
      exclude: [/node_modules[\\/]nitro(?:pack)?(?:-nightly)?[\\/]|core[\\/]runtime[\\/]nitro[\\/](?:handlers|utils)/, ...sharedPatterns]
    })
  );
  const isIgnored = createIsIgnored(nuxt);
  nitroConfig.devStorage ??= {};
  nitroConfig.devStorage.root ??= {
    driver: "fs",
    readOnly: true,
    base: nitroConfig.rootDir,
    watchOptions: {
      ignored: [isIgnored]
    }
  };
  nitroConfig.devStorage.src ??= {
    driver: "fs",
    readOnly: true,
    base: nitroConfig.srcDir,
    watchOptions: {
      ignored: [isIgnored]
    }
  };
  await nuxt.callHook("nitro:config", nitroConfig);
  const excludedAlias = [/^@vue\/.*$/, "vue", /vue-router/, "vite/client", "#imports", "vue-demi", /^#app/, "~", "@", "~~", "@@"];
  const basePath = nitroConfig.typescript.tsConfig.compilerOptions?.baseUrl ? resolve(nuxt.options.buildDir, nitroConfig.typescript.tsConfig.compilerOptions?.baseUrl) : nuxt.options.buildDir;
  const aliases = nitroConfig.alias;
  const tsConfig = nitroConfig.typescript.tsConfig;
  tsConfig.compilerOptions ||= {};
  tsConfig.compilerOptions.paths ||= {};
  for (const _alias in aliases) {
    const alias = _alias;
    if (excludedAlias.some((pattern) => typeof pattern === "string" ? alias === pattern : pattern.test(alias))) {
      continue;
    }
    if (alias in tsConfig.compilerOptions.paths) {
      continue;
    }
    const absolutePath = resolve(basePath, aliases[alias]);
    const stats = await promises.stat(absolutePath).catch(
      () => null
      /* file does not exist */
    );
    tsConfig.compilerOptions.paths[alias] = [absolutePath];
    if (stats?.isDirectory()) {
      tsConfig.compilerOptions.paths[`${alias}/*`] = [`${absolutePath}/*`];
    }
  }
  const nitro = await createNitro(nitroConfig, {
    compatibilityDate: nuxt.options.compatibilityDate,
    dotenv: nuxt.options._loadOptions?.dotenv
  });
  const spaLoadingTemplateFilePath = await spaLoadingTemplatePath(nuxt);
  nuxt.hook("builder:watch", async (_event, relativePath) => {
    const path = resolve(nuxt.options.srcDir, relativePath);
    if (path === spaLoadingTemplateFilePath) {
      await nitro.hooks.callHook("rollup:reload");
    }
  });
  const cacheDir = resolve(nuxt.options.buildDir, "cache/nitro/prerender");
  const cacheDriverPath = join(distDir, "core/runtime/nitro/utils/cache-driver.js");
  await promises.rm(cacheDir, { recursive: true, force: true }).catch(() => {
  });
  nitro.options._config.storage = defu(nitro.options._config.storage, {
    "internal:nuxt:prerender": {
      // TODO: resolve upstream where file URLs are not being resolved/inlined correctly
      driver: isWindows ? pathToFileURL(cacheDriverPath).href : cacheDriverPath,
      base: cacheDir
    }
  });
  nuxt._nitro = nitro;
  await nuxt.callHook("nitro:init", nitro);
  nitro.vfs = nuxt.vfs = nitro.vfs || nuxt.vfs || {};
  nuxt.hook("close", () => nitro.hooks.callHook("close"));
  nitro.hooks.hook("prerender:routes", (routes) => {
    return nuxt.callHook("prerender:routes", { routes });
  });
  if (nuxt.options.vue.runtimeCompiler) {
    nuxt.hook("vite:extendConfig", (config, { isClient }) => {
      if (isClient) {
        if (Array.isArray(config.resolve.alias)) {
          config.resolve.alias.push({
            find: "vue",
            replacement: "vue/dist/vue.esm-bundler"
          });
        } else {
          config.resolve.alias = {
            ...config.resolve.alias,
            vue: "vue/dist/vue.esm-bundler"
          };
        }
      }
    });
    for (const hook of ["webpack:config", "rspack:config"]) {
      nuxt.hook(hook, (configuration) => {
        const clientConfig = configuration.find((config) => config.name === "client");
        if (!clientConfig.resolve) {
          clientConfig.resolve.alias = {};
        }
        if (Array.isArray(clientConfig.resolve.alias)) {
          clientConfig.resolve.alias.push({
            name: "vue",
            alias: "vue/dist/vue.esm-bundler"
          });
        } else {
          clientConfig.resolve.alias.vue = "vue/dist/vue.esm-bundler";
        }
      });
    }
  }
  const devMiddlewareHandler = dynamicEventHandler();
  nitro.options.devHandlers.unshift({ handler: devMiddlewareHandler });
  nitro.options.devHandlers.push(...nuxt.options.devServerHandlers);
  nitro.options.handlers.unshift({
    route: "/__nuxt_error",
    lazy: true,
    handler: resolve(distDir, "core/runtime/nitro/handlers/renderer")
  });
  if (!nuxt.options.dev && nuxt.options.experimental.noVueServer) {
    nitro.hooks.hook("rollup:before", (nitro2) => {
      if (nitro2.options.preset === "nitro-prerender") {
        return;
      }
      const nuxtErrorHandler = nitro2.options.handlers.findIndex((h) => h.route === "/__nuxt_error");
      if (nuxtErrorHandler >= 0) {
        nitro2.options.handlers.splice(nuxtErrorHandler, 1);
      }
      nitro2.options.renderer = void 0;
      nitro2.options.errorHandler = "#internal/nitro/error";
    });
  }
  nuxt.hook("prepare:types", async (opts) => {
    if (!nuxt.options.dev) {
      await scanHandlers(nitro);
      await writeTypes(nitro);
    }
    opts.tsConfig.exclude ||= [];
    opts.tsConfig.exclude.push(relative(nuxt.options.buildDir, resolve(nuxt.options.rootDir, nitro.options.output.dir)));
    opts.references.push({ path: resolve(nuxt.options.buildDir, "types/nitro.d.ts") });
  });
  if (nitro.options.static) {
    nitro.hooks.hook("prerender:routes", (routes) => {
      for (const route of ["/200.html", "/404.html"]) {
        routes.add(route);
      }
      if (!nuxt.options.ssr) {
        routes.add("/index.html");
      }
    });
  }
  if (!nuxt.options.dev) {
    nitro.hooks.hook("rollup:before", async (nitro2) => {
      await copyPublicAssets(nitro2);
      await nuxt.callHook("nitro:build:public-assets", nitro2);
    });
  }
  async function symlinkDist() {
    if (nitro.options.static) {
      const distDir2 = resolve(nuxt.options.rootDir, "dist");
      if (!existsSync(distDir2)) {
        await promises.symlink(nitro.options.output.publicDir, distDir2, "junction").catch(() => {
        });
      }
    }
  }
  nuxt.hook("build:done", async () => {
    await nuxt.callHook("nitro:build:before", nitro);
    await prepare(nitro);
    if (nuxt.options.dev) {
      return build$1(nitro);
    }
    await prerender(nitro);
    logger$1.restoreAll();
    await build$1(nitro);
    logger$1.wrapAll();
    await symlinkDist();
  });
  if (nuxt.options.dev) {
    for (const builder of ["webpack", "rspack"]) {
      nuxt.hook(`${builder}:compile`, ({ name, compiler }) => {
        if (name === "server") {
          const memfs = compiler.outputFileSystem;
          nitro.options.virtual["#build/dist/server/server.mjs"] = () => memfs.readFileSync(join(nuxt.options.buildDir, "dist/server/server.mjs"), "utf-8");
        }
      });
      nuxt.hook(`${builder}:compiled`, () => {
        nuxt.server.reload();
      });
    }
    nuxt.hook("vite:compiled", () => {
      nuxt.server.reload();
    });
    nuxt.hook("server:devHandler", (h) => {
      devMiddlewareHandler.set(h);
    });
    nuxt.server = createDevServer(nitro);
    const waitUntilCompile = new Promise((resolve2) => nitro.hooks.hook("compiled", () => resolve2()));
    nuxt.hook("build:done", () => waitUntilCompile);
  }
}
const RELATIVE_RE = /^([^.])/;
function relativeWithDot(from, to) {
  return relative(from, to).replace(RELATIVE_RE, "./$1") || ".";
}
async function spaLoadingTemplatePath(nuxt) {
  if (typeof nuxt.options.spaLoadingTemplate === "string") {
    return resolve(nuxt.options.srcDir, nuxt.options.spaLoadingTemplate);
  }
  const possiblePaths = nuxt.options._layers.map((layer) => resolve(layer.config.srcDir, layer.config.dir?.app || "app", "spa-loading-template.html"));
  return await findPath(possiblePaths) ?? resolve(nuxt.options.srcDir, nuxt.options.dir?.app || "app", "spa-loading-template.html");
}
async function spaLoadingTemplate(nuxt) {
  if (nuxt.options.spaLoadingTemplate === false) {
    return "";
  }
  const spaLoadingTemplate2 = await spaLoadingTemplatePath(nuxt);
  try {
    if (existsSync(spaLoadingTemplate2)) {
      return readFileSync(spaLoadingTemplate2, "utf-8").trim();
    }
  } catch {
  }
  if (nuxt.options.spaLoadingTemplate === true) {
    return template();
  }
  if (nuxt.options.spaLoadingTemplate) {
    logger$1.warn(`Could not load custom \`spaLoadingTemplate\` path as it does not exist: \`${nuxt.options.spaLoadingTemplate}\`.`);
  }
  return "";
}

const schemaModule = defineNuxtModule({
  meta: {
    name: "nuxt:nuxt-config-schema"
  },
  async setup(_, nuxt) {
    if (!nuxt.options.experimental.configSchema) {
      return;
    }
    const resolver = createResolver(import.meta.url);
    const _resolveSchema = createJiti(fileURLToPath(import.meta.url), {
      cache: false,
      transformOptions: {
        babel: {
          plugins: [untypedPlugin]
        }
      }
    });
    nuxt.hook("prepare:types", async (ctx) => {
      ctx.references.push({ path: "schema/nuxt.schema.d.ts" });
      if (nuxt.options._prepare) {
        await writeSchema(schema);
      }
    });
    let schema;
    nuxt.hook("modules:done", async () => {
      schema = await resolveSchema$1();
    });
    nuxt.hooks.hook("build:done", () => writeSchema(schema));
    if (nuxt.options.dev) {
      const onChange = debounce(async () => {
        schema = await resolveSchema$1();
        await writeSchema(schema);
      });
      if (nuxt.options.experimental.watcher === "parcel") {
        try {
          const { subscribe } = await importModule("@parcel/watcher", {
            url: [nuxt.options.rootDir, ...nuxt.options.modulesDir].map((dir) => directoryToURL(dir))
          });
          for (const layer of nuxt.options._layers) {
            const subscription = await subscribe(layer.config.rootDir, onChange, {
              ignore: ["!nuxt.schema.*"]
            });
            nuxt.hook("close", () => subscription.unsubscribe());
          }
          return;
        } catch {
          logger.warn("Falling back to `chokidar` as `@parcel/watcher` cannot be resolved in your project.");
        }
      }
      const isIgnored = createIsIgnored(nuxt);
      const dirsToWatch = nuxt.options._layers.map((layer) => resolver.resolve(layer.config.rootDir));
      const SCHEMA_RE = /(?:^|\/)nuxt.schema.\w+$/;
      const watcher = watch$1(dirsToWatch, {
        ...nuxt.options.watchers.chokidar,
        depth: 1,
        ignored: [
          (path, stats) => stats && !stats.isFile() || !SCHEMA_RE.test(path),
          isIgnored,
          /[\\/]node_modules[\\/]/
        ],
        ignoreInitial: true
      });
      watcher.on("all", onChange);
      nuxt.hook("close", () => watcher.close());
    }
    async function resolveSchema$1() {
      globalThis.defineNuxtSchema = (val) => val;
      const schemaDefs = [nuxt.options.$schema];
      for (const layer of nuxt.options._layers) {
        const filePath = await resolver.resolvePath(resolve(layer.config.rootDir, "nuxt.schema"));
        if (filePath && existsSync(filePath)) {
          let loadedConfig;
          try {
            loadedConfig = await _resolveSchema.import(filePath, { default: true });
          } catch (err) {
            logger.warn(
              "Unable to load schema from",
              filePath,
              err
            );
            continue;
          }
          schemaDefs.push(loadedConfig);
        }
      }
      await nuxt.hooks.callHook("schema:extend", schemaDefs);
      const schemas = await Promise.all(
        schemaDefs.map((schemaDef) => resolveSchema(schemaDef))
      );
      const schema2 = defu(...schemas);
      await nuxt.hooks.callHook("schema:resolved", schema2);
      return schema2;
    }
    async function writeSchema(schema2) {
      await nuxt.hooks.callHook("schema:beforeWrite", schema2);
      await mkdir(resolve(nuxt.options.buildDir, "schema"), { recursive: true });
      await writeFile(
        resolve(nuxt.options.buildDir, "schema/nuxt.schema.json"),
        JSON.stringify(schema2, null, 2),
        "utf8"
      );
      const _types = generateTypes(schema2, {
        addExport: true,
        interfaceName: "NuxtCustomSchema",
        partial: true,
        allowExtraKeys: false
      });
      const types = _types + `
export type CustomAppConfig = Exclude<NuxtCustomSchema['appConfig'], undefined>
type _CustomAppConfig = CustomAppConfig

declare module '@nuxt/schema' {
  interface NuxtConfig extends Omit<NuxtCustomSchema, 'appConfig'> {}
  interface NuxtOptions extends Omit<NuxtCustomSchema, 'appConfig'> {}
  interface CustomAppConfig extends _CustomAppConfig {}
}

declare module 'nuxt/schema' {
  interface NuxtConfig extends Omit<NuxtCustomSchema, 'appConfig'> {}
  interface NuxtOptions extends Omit<NuxtCustomSchema, 'appConfig'> {}
  interface CustomAppConfig extends _CustomAppConfig {}
}
`;
      const typesPath = resolve(
        nuxt.options.buildDir,
        "schema/nuxt.schema.d.ts"
      );
      await writeFile(typesPath, types, "utf8");
      await nuxt.hooks.callHook("schema:written");
    }
  }
});

const internalOrderMap = {
  // -40: custom payload revivers (user)
  "user-revivers": -40,
  // -20: pre (user) <-- pre mapped to this
  "user-pre": -20,
  // 0: default (user) <-- default behavior
  "user-default": 0,
  // +20: post (user) <-- post mapped to this
  "user-post": 20};
const orderMap = {
  pre: internalOrderMap["user-pre"],
  default: internalOrderMap["user-default"],
  post: internalOrderMap["user-post"]
};
const metaCache = {};
function extractMetadata(code, loader = "ts") {
  let meta = {};
  if (metaCache[code]) {
    return metaCache[code];
  }
  if (code.match(/defineNuxtPlugin\s*\([\w(]/)) {
    return {};
  }
  parseAndWalk(code, `file.${loader}`, (node) => {
    if (node.type !== "CallExpression" || node.callee.type !== "Identifier") {
      return;
    }
    const name = "name" in node.callee && node.callee.name;
    if (name !== "defineNuxtPlugin" && name !== "definePayloadPlugin") {
      return;
    }
    if (name === "definePayloadPlugin") {
      meta.order = internalOrderMap["user-revivers"];
    }
    const metaArg = node.arguments[1];
    if (metaArg) {
      if (metaArg.type !== "ObjectExpression") {
        throw new Error("Invalid plugin metadata");
      }
      meta = extractMetaFromObject(metaArg.properties);
    }
    const plugin = node.arguments[0];
    if (plugin?.type === "ObjectExpression") {
      meta = defu(extractMetaFromObject(plugin.properties), meta);
    }
    meta.order ||= orderMap[meta.enforce || "default"] || orderMap.default;
    delete meta.enforce;
  });
  metaCache[code] = meta;
  return meta;
}
const keys = {
  name: "name",
  order: "order",
  enforce: "enforce",
  dependsOn: "dependsOn"
};
function isMetadataKey(key) {
  return key in keys;
}
function extractMetaFromObject(properties) {
  const meta = {};
  for (const property of properties) {
    if (property.type === "SpreadElement" || !("name" in property.key)) {
      throw new Error("Invalid plugin metadata");
    }
    const propertyKey = property.key.name;
    if (!isMetadataKey(propertyKey)) {
      continue;
    }
    if (property.value.type === "Literal") {
      meta[propertyKey] = property.value.value;
    }
    if (property.value.type === "UnaryExpression" && property.value.argument.type === "Literal") {
      meta[propertyKey] = JSON.parse(property.value.operator + property.value.argument.raw);
    }
    if (propertyKey === "dependsOn" && property.value.type === "ArrayExpression") {
      if (property.value.elements.some((e) => !e || e.type !== "Literal" || typeof e.value !== "string")) {
        throw new Error("dependsOn must take an array of string literals");
      }
      meta[propertyKey] = property.value.elements.map((e) => e.value);
    }
  }
  return meta;
}
const RemovePluginMetadataPlugin = (nuxt) => createUnplugin(() => {
  return {
    name: "nuxt:remove-plugin-metadata",
    transform(code, id) {
      id = normalize(id);
      const plugin = nuxt.apps.default?.plugins.find((p) => p.src === id);
      if (!plugin) {
        return;
      }
      if (!code.trim()) {
        logger.warn(`Plugin \`${plugin.src}\` has no content.`);
        return {
          code: "export default () => {}",
          map: null
        };
      }
      const exports = findExports(code);
      const defaultExport = exports.find((e) => e.type === "default" || e.name === "default");
      if (!defaultExport) {
        logger.warn(`Plugin \`${plugin.src}\` has no default export and will be ignored at build time. Add \`export default defineNuxtPlugin(() => {})\` to your plugin.`);
        return {
          code: "export default () => {}",
          map: null
        };
      }
      const s = new MagicString(code);
      let wrapped = false;
      const wrapperNames = /* @__PURE__ */ new Set(["defineNuxtPlugin", "definePayloadPlugin"]);
      try {
        parseAndWalk(code, id, (node) => {
          if (node.type === "ImportSpecifier" && node.imported.type === "Identifier" && (node.imported.name === "defineNuxtPlugin" || node.imported.name === "definePayloadPlugin")) {
            wrapperNames.add(node.local.name);
          }
          if (node.type === "ExportDefaultDeclaration" && (node.declaration.type === "FunctionDeclaration" || node.declaration.type === "ArrowFunctionExpression")) {
            if ("params" in node.declaration && node.declaration.params.length > 1) {
              logger.warn(`Plugin \`${plugin.src}\` is in legacy Nuxt 2 format (context, inject) which is likely to be broken and will be ignored.`);
              s.overwrite(0, code.length, "export default () => {}");
              wrapped = true;
              return;
            }
          }
          if (node.type !== "CallExpression" || node.callee.type !== "Identifier") {
            return;
          }
          const name = "name" in node.callee && node.callee.name;
          if (!name || !wrapperNames.has(name)) {
            return;
          }
          wrapped = true;
          if (node.arguments[0] && node.arguments[0].type !== "ObjectExpression") {
            if ("params" in node.arguments[0] && node.arguments[0].params.length > 1) {
              logger.warn(`Plugin \`${plugin.src}\` is in legacy Nuxt 2 format (context, inject) which is likely to be broken and will be ignored.`);
              s.overwrite(0, code.length, "export default () => {}");
              return;
            }
          }
          if (!("order" in plugin) && !("name" in plugin)) {
            return;
          }
          for (const [argIndex, arg] of node.arguments.entries()) {
            if (arg.type !== "ObjectExpression") {
              continue;
            }
            for (const [propertyIndex, property] of arg.properties.entries()) {
              if (property.type === "SpreadElement" || !("name" in property.key)) {
                continue;
              }
              const propertyKey = property.key.name;
              if (propertyKey === "order" || propertyKey === "enforce" || propertyKey === "name") {
                const nextNode = arg.properties[propertyIndex + 1] || node.arguments[argIndex + 1];
                const nextIndex = withLocations(nextNode)?.start || withLocations(arg).end - 1;
                s.remove(withLocations(property).start, nextIndex);
              }
            }
          }
        });
      } catch (e) {
        logger.error(e);
        return;
      }
      if (!wrapped) {
        logger.warn(`Plugin \`${plugin.src}\` is not wrapped in \`defineNuxtPlugin\`. It is advised to wrap your plugins as in the future this may enable enhancements.`);
      }
      if (s.hasChanged()) {
        return {
          code: s.toString(),
          map: nuxt.options.sourcemap.client || nuxt.options.sourcemap.server ? s.generateMap({ hires: true }) : null
        };
      }
    }
  };
});

const AsyncContextInjectionPlugin = (nuxt) => createUnplugin(() => {
  return {
    name: "nuxt:vue-async-context",
    transformInclude(id) {
      return isVue(id, { type: ["template", "script"] });
    },
    transform: {
      filter: {
        code: { include: /_withAsyncContext/ }
      },
      handler(code) {
        const s = new MagicString(code);
        s.prepend('import { withAsyncContext as _withAsyncContext } from "#app/composables/asyncContext";\n');
        s.replace(/withAsyncContext as _withAsyncContext,?/, "");
        if (s.hasChanged()) {
          return {
            code: s.toString(),
            map: nuxt.options.sourcemap.client || nuxt.options.sourcemap.server ? s.generateMap({ hires: true }) : void 0
          };
        }
      }
    }
  };
});

const stringTypes = ["Literal", "TemplateLiteral"];
const NUXT_LIB_RE = /node_modules\/(?:nuxt|nuxt3|nuxt-nightly)\//;
const SUPPORTED_EXT_RE = /\.(?:m?[jt]sx?|vue)/;
const SCRIPT_RE = /(?<=<script[^>]*>)[\s\S]*?(?=<\/script>)/i;
const ComposableKeysPlugin = (options) => createUnplugin(() => {
  const composableMeta = {};
  const composableLengths = /* @__PURE__ */ new Set();
  const keyedFunctions = /* @__PURE__ */ new Set();
  for (const { name, ...meta } of options.composables) {
    composableMeta[name] = meta;
    keyedFunctions.add(name);
    composableLengths.add(meta.argumentLength);
  }
  const maxLength = Math.max(...composableLengths);
  const KEYED_FUNCTIONS_RE = new RegExp(`\\b(${[...keyedFunctions].map((f) => escapeRE(f)).join("|")})\\b`);
  return {
    name: "nuxt:composable-keys",
    enforce: "post",
    transformInclude(id) {
      const { pathname, search } = parseURL(decodeURIComponent(pathToFileURL(id).href));
      return !NUXT_LIB_RE.test(pathname) && SUPPORTED_EXT_RE.test(pathname) && parseQuery(search).type !== "style" && !parseQuery(search).macro;
    },
    transform: {
      filter: {
        code: { include: KEYED_FUNCTIONS_RE }
      },
      handler(code, id) {
        const { 0: script = code, index: codeIndex = 0 } = code.match(SCRIPT_RE) || { index: 0, 0: code };
        const s = new MagicString(code);
        let imports;
        let count = 0;
        const relativeID = isAbsolute(id) ? relative(options.rootDir, id) : id;
        const { pathname: relativePathname } = parseURL(relativeID);
        const scopeTracker = new ScopeTracker({
          keepExitedScopes: true
        });
        const ast = parseAndWalk(script, id, {
          scopeTracker
        });
        scopeTracker.freeze();
        walk(ast, {
          scopeTracker,
          enter(node) {
            if (node.type !== "CallExpression" || node.callee.type !== "Identifier") {
              return;
            }
            const name = node.callee.name;
            if (!name || !keyedFunctions.has(name) || node.arguments.length >= maxLength) {
              return;
            }
            imports ||= detectImportNames(script, composableMeta);
            if (imports.has(name)) {
              return;
            }
            const meta = composableMeta[name];
            const declaration = scopeTracker.getDeclaration(name);
            if (declaration && declaration.type !== "Import") {
              let skip = true;
              if (meta.source) {
                skip = !matchWithStringOrRegex(relativePathname, meta.source);
              }
              if (skip) {
                return;
              }
            }
            if (node.arguments.length >= meta.argumentLength) {
              return;
            }
            switch (name) {
              case "useState":
                if (stringTypes.includes(node.arguments[0]?.type)) {
                  return;
                }
                break;
              case "useFetch":
              case "useLazyFetch":
                if (stringTypes.includes(node.arguments[1]?.type)) {
                  return;
                }
                break;
              case "useAsyncData":
              case "useLazyAsyncData":
                if (stringTypes.includes(node.arguments[0]?.type) || stringTypes.includes(node.arguments[node.arguments.length - 1]?.type)) {
                  return;
                }
                break;
            }
            const newCode = code.slice(codeIndex + node.start, codeIndex + node.end - 1).trim();
            const endsWithComma = newCode[newCode.length - 1] === ",";
            s.appendLeft(
              codeIndex + node.end - 1,
              (node.arguments.length && !endsWithComma ? ", " : "") + "'$" + hash(`${relativeID}-${++count}`).slice(0, 10) + "'"
            );
          }
        });
        if (s.hasChanged()) {
          return {
            code: s.toString(),
            map: options.sourcemap ? s.generateMap({ hires: true }) : void 0
          };
        }
      }
    }
  };
});
const NUXT_IMPORT_RE = /nuxt|#app|#imports/;
function detectImportNames(code, composableMeta) {
  const names = /* @__PURE__ */ new Set();
  function addName(name, specifier) {
    const source = composableMeta[name]?.source;
    if (source && matchWithStringOrRegex(specifier, source)) {
      return;
    }
    names.add(name);
  }
  for (const i of findStaticImports(code)) {
    if (NUXT_IMPORT_RE.test(i.specifier)) {
      continue;
    }
    const { namedImports = {}, defaultImport, namespacedImport } = parseStaticImport(i);
    for (const name in namedImports) {
      addName(namedImports[name], i.specifier);
    }
    if (defaultImport) {
      addName(defaultImport, i.specifier);
    }
    if (namespacedImport) {
      addName(namespacedImport, i.specifier);
    }
  }
  return names;
}

const VIRTUAL_RE = /^\0?virtual:(?:nuxt:)?/;
function ResolveDeepImportsPlugin(nuxt) {
  const exclude = ["virtual:", "\0virtual:", "/__skip_vite", "@vitest/"];
  let conditions;
  return {
    name: "nuxt:resolve-bare-imports",
    enforce: "post",
    configResolved(config) {
      const resolvedConditions = /* @__PURE__ */ new Set([nuxt.options.dev ? "development" : "production", ...config.resolve.conditions]);
      if (resolvedConditions.has("browser")) {
        resolvedConditions.add("web");
        resolvedConditions.add("import");
        resolvedConditions.add("module");
        resolvedConditions.add("default");
      }
      if (config.mode === "test") {
        resolvedConditions.add("import");
        resolvedConditions.add("require");
      }
      conditions = [...resolvedConditions];
    },
    async resolveId(id, importer) {
      if (!importer || isAbsolute(id) || !isAbsolute(importer) && !VIRTUAL_RE.test(importer) || exclude.some((e) => id.startsWith(e))) {
        return;
      }
      const normalisedId = resolveAlias(normalize(id), nuxt.options.alias);
      const isNuxtTemplate = importer.startsWith("virtual:nuxt");
      const normalisedImporter = (isNuxtTemplate ? decodeURIComponent(importer) : importer).replace(VIRTUAL_RE, "");
      if (nuxt.options.experimental.templateImportResolution !== false && isNuxtTemplate) {
        const template = nuxt.options.build.templates.find((t) => resolve(nuxt.options.buildDir, t.filename) === normalisedImporter);
        if (template?._path) {
          const res2 = await this.resolve?.(normalisedId, template._path, { skipSelf: true });
          if (res2 !== void 0 && res2 !== null) {
            return res2;
          }
        }
      }
      const dir = parseNodeModulePath(normalisedImporter).dir || pkgDir;
      const res = await this.resolve?.(normalisedId, dir, { skipSelf: true });
      if (res !== void 0 && res !== null) {
        return res;
      }
      const path = resolveModulePath(id, {
        from: [dir, ...nuxt.options.modulesDir].map((d) => directoryToURL(d)),
        suffixes: ["", "index"],
        conditions,
        try: true
      });
      if (!path) {
        logger.debug("Could not resolve id", id, importer);
        return null;
      }
      return normalize(path);
    }
  };
}

const runtimeDependencies = [
  // other deps
  "devalue",
  "klona",
  // unjs ecosystem
  "defu",
  "ufo",
  "h3",
  "destr",
  "consola",
  "hookable",
  "unctx",
  "cookie-es",
  "perfect-debounce",
  "radix3",
  "ohash",
  "pathe",
  "uncrypto"
];

function ResolveExternalsPlugin(nuxt) {
  let external = /* @__PURE__ */ new Set();
  return {
    name: "nuxt:resolve-externals",
    enforce: "pre",
    async configResolved() {
      if (!nuxt.options.dev) {
        const runtimeNitroDependencies = await tryImportModule("nitropack/package.json", {
          url: new URL(import.meta.url)
        })?.then((r) => r?.dependencies ? Object.keys(r.dependencies) : []).catch(() => []) || [];
        external = /* @__PURE__ */ new Set([
          // explicit dependencies we use in our ssr renderer - these can be inlined (if necessary) in the nitro build
          "unhead",
          "@unhead/vue",
          "@nuxt/devalue",
          "rou3",
          "unstorage",
          // ensure we only have one version of vue if nitro is going to inline anyway
          ...nuxt._nitro.options.inlineDynamicImports ? ["vue", "@vue/server-renderer"] : [],
          ...runtimeDependencies,
          // dependencies we might share with nitro - these can be inlined (if necessary) in the nitro build
          ...runtimeNitroDependencies
        ]);
      }
    },
    async resolveId(id, importer) {
      if (!external.has(id)) {
        return;
      }
      const res = await this.resolve?.(id, importer, { skipSelf: true });
      if (res !== void 0 && res !== null) {
        if (res.id === id) {
          res.id = resolveModulePath(res.id, {
            try: true,
            from: importer,
            extensions: nuxt.options.extensions
          }) || res.id;
        }
        return {
          ...res,
          external: "absolute"
        };
      }
    }
  };
}

function PrehydrateTransformPlugin(options = {}) {
  return createUnplugin(() => ({
    name: "nuxt:prehydrate-transform",
    transformInclude(id) {
      return isJS(id) || isVue(id, { type: ["script"] });
    },
    transform: {
      filter: {
        code: { include: /onPrehydrate\(/ }
      },
      async handler(code, id) {
        const s = new MagicString(code);
        const promises = [];
        parseAndWalk(code, id, (node) => {
          if (node.type !== "CallExpression" || node.callee.type !== "Identifier") {
            return;
          }
          if (node.callee.name === "onPrehydrate") {
            const callback = withLocations(node.arguments[0]);
            if (!callback) {
              return;
            }
            if (callback.type !== "ArrowFunctionExpression" && callback.type !== "FunctionExpression") {
              return;
            }
            const needsAttr = callback.params.length > 0;
            const p = transform(`forEach(${code.slice(callback.start, callback.end)})`, { loader: "ts", minify: true });
            promises.push(p.then(({ code: result }) => {
              const cleaned = result.slice("forEach".length).replace(/;\s+$/, "");
              const args = [JSON.stringify(cleaned)];
              if (needsAttr) {
                args.push(JSON.stringify(hash(result).slice(0, 10)));
              }
              s.overwrite(callback.start, callback.end, args.join(", "));
            }));
          }
        });
        await Promise.all(promises).catch((e) => {
          console.error(`[nuxt] Could not transform onPrehydrate in \`${id}\`:`, e);
        });
        if (s.hasChanged()) {
          return {
            code: s.toString(),
            map: options.sourcemap ? s.generateMap({ hires: true }) : void 0
          };
        }
      }
    }
  }));
}

const PREFIX = "virtual:nuxt:";
const PREFIX_RE = /^\/?virtual:nuxt:/;
const RELATIVE_ID_RE = /^\.{1,2}[\\/]/;
const VirtualFSPlugin = (nuxt, options) => createUnplugin((_, meta) => {
  const extensions = ["", ...nuxt.options.extensions];
  const alias = { ...nuxt.options.alias, ...options.alias };
  const resolveWithExt = (id) => {
    for (const suffix of ["", "." + options.mode]) {
      for (const ext of extensions) {
        const rId = id + suffix + ext;
        if (rId in nuxt.vfs) {
          return rId;
        }
      }
    }
  };
  function resolveId(id, importer) {
    id = resolveAlias(id, alias);
    if (PREFIX_RE.test(id)) {
      id = withoutPrefix(decodeURIComponent(id));
    }
    const search = id.match(QUERY_RE)?.[0] || "";
    id = withoutQuery(id);
    if (process.platform === "win32" && isAbsolute(id)) {
      id = resolve(id);
    }
    const resolvedId = resolveWithExt(id);
    if (resolvedId) {
      return PREFIX + encodeURIComponent(resolvedId) + search;
    }
    if (importer && RELATIVE_ID_RE.test(id)) {
      const path = resolve(dirname(withoutPrefix(decodeURIComponent(importer))), id);
      const resolved = resolveWithExt(path);
      if (resolved) {
        return PREFIX + encodeURIComponent(resolved) + search;
      }
    }
  }
  return {
    name: "nuxt:virtual",
    resolveId: meta.framework === "vite" ? void 0 : { order: "pre", handler: resolveId },
    vite: {
      resolveId: {
        order: "pre",
        handler(id, importer) {
          const res = resolveId(id, importer);
          if (res) {
            return res;
          }
          if (importer && PREFIX_RE.test(importer) && RELATIVE_ID_RE.test(id)) {
            return this.resolve?.(id, withoutPrefix(decodeURIComponent(importer)), { skipSelf: true });
          }
        }
      }
    },
    loadInclude(id) {
      return PREFIX_RE.test(id) && withoutQuery(withoutPrefix(decodeURIComponent(id))) in nuxt.vfs;
    },
    load(id) {
      const key = withoutQuery(withoutPrefix(decodeURIComponent(id)));
      return {
        code: nuxt.vfs[key] || "",
        map: null
      };
    }
  };
});
function withoutPrefix(id) {
  return id.replace(PREFIX_RE, "");
}
const QUERY_RE = /\?.*$/;
function withoutQuery(id) {
  return id.replace(QUERY_RE, "");
}

function createNuxt(options) {
  const hooks = createHooks();
  const { callHook, callHookParallel, callHookWith } = hooks;
  hooks.callHook = (...args) => runWithNuxtContext(nuxt, () => callHook(...args));
  hooks.callHookParallel = (...args) => runWithNuxtContext(nuxt, () => callHookParallel(...args));
  hooks.callHookWith = (...args) => runWithNuxtContext(nuxt, () => callHookWith(...args));
  const nuxt = {
    __name: randomUUID(),
    _version: version,
    _asyncLocalStorageModule: options.experimental.debugModuleMutation ? new AsyncLocalStorage() : void 0,
    hooks,
    callHook: hooks.callHook,
    addHooks: hooks.addHooks,
    hook: hooks.hook,
    ready: () => runWithNuxtContext(nuxt, () => initNuxt(nuxt)),
    close: () => hooks.callHook("close", nuxt),
    vfs: {},
    apps: {},
    runWithContext: (fn) => runWithNuxtContext(nuxt, fn),
    options
  };
  if (options.experimental.debugModuleMutation) {
    const proxiedOptions = /* @__PURE__ */ new WeakMap();
    Object.defineProperty(nuxt, "options", {
      get() {
        const currentModule = nuxt._asyncLocalStorageModule.getStore();
        if (!currentModule) {
          return options;
        }
        if (proxiedOptions.has(currentModule)) {
          return proxiedOptions.get(currentModule);
        }
        nuxt._debug ||= {};
        nuxt._debug.moduleMutationRecords ||= [];
        const proxied = onChange(options, (keys, newValue, previousValue, applyData) => {
          if (newValue === previousValue && !applyData) {
            return;
          }
          let value = applyData?.args ?? newValue;
          if (Array.isArray(value)) {
            value = [...value];
          } else if (typeof value === "object") {
            value = { ...value };
          }
          nuxt._debug.moduleMutationRecords.push({
            module: currentModule,
            keys,
            target: "nuxt.options",
            value,
            timestamp: Date.now(),
            method: applyData?.name
          });
        }, {
          ignoreUnderscores: true,
          ignoreSymbols: true,
          pathAsArray: true
        });
        proxiedOptions.set(currentModule, proxied);
        return proxied;
      }
    });
  }
  if (!nuxtCtx.tryUse()) {
    nuxtCtx.set(nuxt);
    nuxt.hook("close", () => {
      nuxtCtx.unset();
    });
  }
  hooks.hookOnce("close", () => {
    hooks.removeAllHooks();
  });
  return nuxt;
}
const fallbackCompatibilityDate = "2024-04-03";
const nightlies = {
  "nitropack": "nitropack-nightly",
  "h3": "h3-nightly",
  "nuxt": "nuxt-nightly",
  "@nuxt/schema": "@nuxt/schema-nightly",
  "@nuxt/kit": "@nuxt/kit-nightly"
};
const keyDependencies = [
  "@nuxt/kit"
];
let warnedAboutCompatDate = false;
async function initNuxt(nuxt) {
  for (const config of nuxt.options._layers.map((layer) => layer.config).reverse()) {
    if (config.hooks) {
      nuxt.hooks.addHooks(config.hooks);
    }
  }
  nuxt.options.compatibilityDate = resolveCompatibilityDatesFromEnv(nuxt.options.compatibilityDate);
  if (!nuxt.options.compatibilityDate.default) {
    nuxt.options.compatibilityDate.default = fallbackCompatibilityDate;
    if (nuxt.options.dev && hasTTY && !isCI && !nuxt.options.test && !warnedAboutCompatDate) {
      warnedAboutCompatDate = true;
      consola.warn(`We recommend adding \`compatibilityDate: '${formatDate("latest")}'\` to your \`nuxt.config\` file.
Using \`${fallbackCompatibilityDate}\` as fallback. More info at: ${colors.underline("https://nitro.build/deploy#compatibility-date")}`);
    }
  }
  const layersDir = withTrailingSlash(resolve(nuxt.options.rootDir, "layers"));
  nuxt.hook("builder:watch", (event, relativePath) => {
    const path = resolve(nuxt.options.srcDir, relativePath);
    if (event === "addDir" || event === "unlinkDir") {
      if (path.startsWith(layersDir)) {
        return nuxt.callHook("restart", { hard: true });
      }
    }
  });
  const coreTypePackages = nuxt.options.typescript.hoist || [];
  if (nuxt.options.typescript.builder !== false) {
    const envMap = {
      // defaults from `builder` based on package name
      "@nuxt/rspack-builder": "@rspack/core/module",
      "@nuxt/vite-builder": "vite/client",
      "@nuxt/webpack-builder": "webpack/module",
      // simpler overrides from `typescript.builder` for better DX
      "rspack": "@rspack/core/module",
      "vite": "vite/client",
      "webpack": "webpack/module",
      // default 'merged' builder environment for module authors
      "shared": "@nuxt/schema/builder-env"
    };
    const overrideEnv = nuxt.options.typescript.builder && envMap[nuxt.options.typescript.builder];
    const defaultEnv = typeof nuxt.options.builder === "string" ? envMap[nuxt.options.builder] : false;
    const environmentTypes = overrideEnv || defaultEnv;
    if (environmentTypes) {
      nuxt.options.typescript.hoist.push(environmentTypes);
      addTypeTemplate({
        filename: "types/builder-env.d.ts",
        getContents: () => genImport(environmentTypes)
      });
    }
  }
  const packageJSON = await readPackageJSON(nuxt.options.rootDir).catch(() => ({}));
  const NESTED_PKG_RE = /^[^@]+\//;
  nuxt._dependencies = /* @__PURE__ */ new Set([...Object.keys(packageJSON.dependencies || {}), ...Object.keys(packageJSON.devDependencies || {})]);
  const paths = Object.fromEntries(await Promise.all(coreTypePackages.map(async (pkg) => {
    const [_pkg = pkg, _subpath] = NESTED_PKG_RE.test(pkg) ? pkg.split("/") : [pkg];
    const subpath = _subpath ? "/" + _subpath : "";
    if (nuxt._dependencies?.has(_pkg) && !(_pkg in nightlies)) {
      return [];
    }
    if (_pkg in nightlies) {
      const nightly = nightlies[_pkg];
      const path2 = await resolveTypePath(nightly + subpath, subpath, nuxt.options.modulesDir);
      if (path2) {
        return [[pkg, [path2]], [nightly + subpath, [path2]]];
      }
    }
    const path = await resolveTypePath(_pkg + subpath, subpath, nuxt.options.modulesDir);
    if (path) {
      return [[pkg, [path]]];
    }
    return [];
  })).then((r) => r.flat()));
  nuxt.options.nitro.typescript = defu$1(nuxt.options.nitro.typescript, {
    tsConfig: { compilerOptions: { paths: { ...paths } } }
  });
  nuxt.hook("prepare:types", (opts) => {
    opts.references.push({ types: "nuxt" });
    opts.references.push({ path: resolve(nuxt.options.buildDir, "types/app-defaults.d.ts") });
    opts.references.push({ path: resolve(nuxt.options.buildDir, "types/plugins.d.ts") });
    if (nuxt.options.typescript.shim) {
      opts.references.push({ path: resolve(nuxt.options.buildDir, "types/vue-shim.d.ts") });
    }
    opts.references.push({ path: resolve(nuxt.options.buildDir, "types/build.d.ts") });
    opts.references.push({ path: resolve(nuxt.options.buildDir, "types/schema.d.ts") });
    opts.references.push({ path: resolve(nuxt.options.buildDir, "types/app.config.d.ts") });
    opts.tsConfig.compilerOptions = defu$1(opts.tsConfig.compilerOptions, { paths: { ...paths } });
    for (const layer of nuxt.options._layers) {
      const declaration = join(layer.cwd, "index.d.ts");
      if (existsSync(declaration)) {
        opts.references.push({ path: declaration });
      }
    }
  });
  if (nuxt.options.scripts) {
    if (!nuxt.options._modules.some((m) => m === "@nuxt/scripts" || m === "@nuxt/scripts-nightly")) {
      await import('../chunks/features.mjs').then(({ installNuxtModule }) => installNuxtModule("@nuxt/scripts"));
    }
  }
  addBuildPlugin(VirtualFSPlugin(nuxt, { mode: "server" }), { client: false });
  addBuildPlugin(VirtualFSPlugin(nuxt, { mode: "client", alias: { "#internal/nitro": join(nuxt.options.buildDir, "nitro.client.mjs") } }), { server: false });
  addBuildPlugin(RemovePluginMetadataPlugin(nuxt));
  addBuildPlugin(ComposableKeysPlugin({
    sourcemap: !!nuxt.options.sourcemap.server || !!nuxt.options.sourcemap.client,
    rootDir: nuxt.options.rootDir,
    composables: nuxt.options.optimization.keyedComposables
  }));
  const sharedDir = withTrailingSlash(resolve(nuxt.options.rootDir, nuxt.options.dir.shared));
  const relativeSharedDir = withTrailingSlash(relative(nuxt.options.rootDir, resolve(nuxt.options.rootDir, nuxt.options.dir.shared)));
  const sharedPatterns = [/^#shared\//, new RegExp("^" + escapeRE(sharedDir)), new RegExp("^" + escapeRE(relativeSharedDir))];
  const sharedProtectionConfig = {
    cwd: nuxt.options.rootDir,
    include: sharedPatterns,
    patterns: createImportProtectionPatterns(nuxt, { context: "shared" })
  };
  addVitePlugin(() => ImpoundPlugin.vite(sharedProtectionConfig), { server: false });
  addWebpackPlugin(() => ImpoundPlugin.webpack(sharedProtectionConfig), { server: false });
  const nuxtProtectionConfig = {
    cwd: nuxt.options.rootDir,
    // Exclude top-level resolutions by plugins
    exclude: [relative(nuxt.options.rootDir, join(nuxt.options.srcDir, "index.html")), ...sharedPatterns],
    patterns: createImportProtectionPatterns(nuxt, { context: "nuxt-app" })
  };
  addVitePlugin(() => Object.assign(ImpoundPlugin.vite({ ...nuxtProtectionConfig, error: false }), { name: "nuxt:import-protection" }), { client: false });
  addVitePlugin(() => Object.assign(ImpoundPlugin.vite({ ...nuxtProtectionConfig, error: true }), { name: "nuxt:import-protection" }), { server: false });
  addWebpackPlugin(() => ImpoundPlugin.webpack(nuxtProtectionConfig));
  addVitePlugin(() => ResolveDeepImportsPlugin(nuxt), { client: false });
  addVitePlugin(() => ResolveDeepImportsPlugin(nuxt), { server: false });
  addVitePlugin(() => ResolveExternalsPlugin(nuxt), { client: false, prepend: true });
  addBuildPlugin(PrehydrateTransformPlugin({ sourcemap: !!nuxt.options.sourcemap.server || !!nuxt.options.sourcemap.client }));
  if (nuxt.options.experimental.localLayerAliases) {
    addBuildPlugin(LayerAliasingPlugin({
      sourcemap: !!nuxt.options.sourcemap.server || !!nuxt.options.sourcemap.client,
      dev: nuxt.options.dev,
      root: nuxt.options.srcDir,
      // skip top-level layer (user's project) as the aliases will already be correctly resolved
      layers: nuxt.options._layers.slice(1)
    }));
  }
  nuxt.hook("modules:done", () => {
    addBuildPlugin(UnctxTransformPlugin({
      sourcemap: !!nuxt.options.sourcemap.server || !!nuxt.options.sourcemap.client,
      transformerOptions: {
        ...nuxt.options.optimization.asyncTransforms,
        helperModule: "unctx"
      }
    }));
    if (Object.keys(nuxt.options.optimization.treeShake.composables.server).length) {
      addBuildPlugin(TreeShakeComposablesPlugin({
        sourcemap: !!nuxt.options.sourcemap.server,
        composables: nuxt.options.optimization.treeShake.composables.server
      }), { client: false });
    }
    if (Object.keys(nuxt.options.optimization.treeShake.composables.client).length) {
      addBuildPlugin(TreeShakeComposablesPlugin({
        sourcemap: !!nuxt.options.sourcemap.client,
        composables: nuxt.options.optimization.treeShake.composables.client
      }), { server: false });
    }
  });
  if (!nuxt.options.dev) {
    addBuildPlugin(DevOnlyPlugin({
      sourcemap: !!nuxt.options.sourcemap.server || !!nuxt.options.sourcemap.client
    }));
  }
  if (nuxt.options.dev) {
    addPlugin(resolve(nuxt.options.appDir, "plugins/check-if-layout-used"));
  }
  if (nuxt.options.dev && nuxt.options.features.devLogs) {
    addPlugin(resolve(nuxt.options.appDir, "plugins/dev-server-logs"));
    addServerPlugin(resolve(distDir, "core/runtime/nitro/plugins/dev-server-logs"));
    nuxt.options.nitro = defu$1(nuxt.options.nitro, {
      externals: {
        inline: [/#internal\/dev-server-logs-options/]
      },
      virtual: {
        "#internal/dev-server-logs-options": () => `export const rootDir = ${JSON.stringify(nuxt.options.rootDir)};`
      }
    });
  }
  if (nuxt.options.experimental.asyncContext) {
    addBuildPlugin(AsyncContextInjectionPlugin(nuxt), { client: false });
  }
  if (nuxt.options.features.noScripts && !nuxt.options.dev) {
    nuxt.hook("build:manifest", async (manifest) => {
      for (const chunk of Object.values(manifest)) {
        if (chunk.resourceType === "script") {
          await rm(resolve(nuxt.options.buildDir, "dist/client", withoutLeadingSlash(nuxt.options.app.buildAssetsDir), chunk.file), { force: true });
          chunk.file = "";
        }
      }
    });
  }
  nuxt.options.build.transpile.push("nuxt/app");
  nuxt.options.build.transpile.push(
    ...nuxt.options._layers.filter((i) => i.cwd.includes("node_modules")).map((i) => i.cwd)
  );
  const locallyScannedLayersDirs = nuxt.options._layers.map((l) => resolve(l.cwd, "layers").replace(/\/?$/, "/"));
  nuxt.options.modulesDir.push(...nuxt.options._layers.filter((l) => l.cwd !== nuxt.options.rootDir && locallyScannedLayersDirs.every((dir) => !l.cwd.startsWith(dir))).map((l) => resolve(l.cwd, "node_modules")));
  await nuxt.callHook("modules:before");
  const modulesToInstall = /* @__PURE__ */ new Map();
  const modulePaths = /* @__PURE__ */ new Set();
  const specifiedModules = /* @__PURE__ */ new Set();
  for (const _mod of nuxt.options.modules) {
    const mod = Array.isArray(_mod) ? _mod[0] : _mod;
    if (typeof mod !== "string") {
      continue;
    }
    const modAlias = resolveAlias(mod);
    const modPath = resolveModulePath(modAlias, {
      try: true,
      from: nuxt.options.modulesDir.map((m) => directoryToURL(m.replace(/\/node_modules\/?$/, "/"))),
      suffixes: ["nuxt", "nuxt/index", "module", "module/index", "", "index"],
      extensions: [".js", ".mjs", ".cjs", ".ts", ".mts", ".cts"]
    });
    specifiedModules.add(modPath || modAlias);
  }
  for (const config of nuxt.options._layers.map((layer) => layer.config).reverse()) {
    const modulesDir = (config.rootDir === nuxt.options.rootDir ? nuxt.options.dir : config.dir)?.modules || "modules";
    const layerModules = await resolveFiles(config.srcDir, [
      `${modulesDir}/*{${nuxt.options.extensions.join(",")}}`,
      `${modulesDir}/*/index{${nuxt.options.extensions.join(",")}}`
    ]);
    for (const mod of layerModules) {
      modulePaths.add(mod);
      if (specifiedModules.has(mod)) {
        continue;
      }
      specifiedModules.add(mod);
      modulesToInstall.set(mod, {});
    }
  }
  nuxt.options.watch.push(...modulePaths);
  for (const key of ["modules", "_modules"]) {
    for (const item of nuxt.options[key]) {
      if (item) {
        const [key2, options = {}] = Array.isArray(item) ? item : [item];
        if (!modulesToInstall.has(key2)) {
          modulesToInstall.set(key2, options);
        }
      }
    }
  }
  const islandsConfig = nuxt.options.experimental.componentIslands;
  if (nuxt.options.dev || !(typeof islandsConfig === "object" && islandsConfig.selectiveClient === "deep")) {
    addComponent({
      name: "NuxtWelcome",
      priority: 10,
      // built-in that we do not expect the user to override
      filePath: resolve(nuxt.options.appDir, "components/welcome")
    });
  }
  addComponent({
    name: "NuxtLayout",
    priority: 10,
    // built-in that we do not expect the user to override
    filePath: resolve(nuxt.options.appDir, "components/nuxt-layout")
  });
  addComponent({
    name: "NuxtErrorBoundary",
    priority: 10,
    // built-in that we do not expect the user to override
    filePath: resolve(nuxt.options.appDir, "components/nuxt-error-boundary")
  });
  addComponent({
    name: "ClientOnly",
    priority: 10,
    // built-in that we do not expect the user to override
    filePath: resolve(nuxt.options.appDir, "components/client-only")
  });
  addComponent({
    name: "DevOnly",
    priority: 10,
    // built-in that we do not expect the user to override
    filePath: resolve(nuxt.options.appDir, "components/dev-only")
  });
  addComponent({
    name: "ServerPlaceholder",
    priority: 10,
    // built-in that we do not expect the user to override
    filePath: resolve(nuxt.options.appDir, "components/server-placeholder")
  });
  addComponent({
    name: "NuxtLink",
    priority: 10,
    // built-in that we do not expect the user to override
    filePath: resolve(nuxt.options.appDir, "components/nuxt-link")
  });
  addComponent({
    name: "NuxtLoadingIndicator",
    priority: 10,
    // built-in that we do not expect the user to override
    filePath: resolve(nuxt.options.appDir, "components/nuxt-loading-indicator")
  });
  addComponent({
    name: "NuxtTime",
    priority: 10,
    // built-in that we do not expect the user to override
    filePath: resolve(nuxt.options.appDir, "components/nuxt-time.vue")
  });
  addComponent({
    name: "NuxtRouteAnnouncer",
    priority: 10,
    // built-in that we do not expect the user to override
    filePath: resolve(nuxt.options.appDir, "components/nuxt-route-announcer"),
    mode: "client"
  });
  if (nuxt.options.experimental.clientFallback) {
    addComponent({
      name: "NuxtClientFallback",
      _raw: true,
      priority: 10,
      // built-in that we do not expect the user to override
      filePath: resolve(nuxt.options.appDir, "components/client-fallback.client"),
      mode: "client"
    });
    addComponent({
      name: "NuxtClientFallback",
      _raw: true,
      priority: 10,
      // built-in that we do not expect the user to override
      filePath: resolve(nuxt.options.appDir, "components/client-fallback.server"),
      mode: "server"
    });
  }
  for (const name of ["NuxtImg", "NuxtPicture"]) {
    addComponent({
      name,
      export: name,
      priority: -1,
      filePath: resolve(nuxt.options.appDir, "components/nuxt-stubs"),
      // @ts-expect-error TODO: refactor to @nuxt/cli
      _internal_install: "@nuxt/image"
    });
  }
  if (nuxt.options.builder === "@nuxt/webpack-builder") {
    addPlugin(resolve(nuxt.options.appDir, "plugins/preload.server"));
  }
  if (nuxt.options.debug && nuxt.options.debug.hooks && (nuxt.options.debug.hooks === true || nuxt.options.debug.hooks.client)) {
    addPlugin(resolve(nuxt.options.appDir, "plugins/debug-hooks"));
  }
  if (nuxt.options.experimental.browserDevtoolsTiming) {
    addPlugin(resolve(nuxt.options.appDir, "plugins/browser-devtools-timing.client"));
  }
  for (const [key, options] of modulesToInstall) {
    await installModule(key, options);
  }
  nuxt._ignore = ignore(nuxt.options.ignoreOptions);
  nuxt._ignore.add(resolveIgnorePatterns());
  await nuxt.callHook("modules:done");
  if (nuxt.options.experimental.componentIslands) {
    addComponent({
      name: "NuxtIsland",
      priority: 10,
      // built-in that we do not expect the user to override
      filePath: resolve(nuxt.options.appDir, "components/nuxt-island")
    });
    addServerTemplate({
      filename: "#internal/nuxt/island-renderer.mjs",
      getContents() {
        if (nuxt.options.dev || nuxt.options.experimental.componentIslands !== "auto" || nuxt.apps.default?.pages?.some((p) => p.mode === "server") || nuxt.apps.default?.components?.some((c) => c.mode === "server" && !nuxt.apps.default?.components.some((other) => other.pascalName === c.pascalName && other.mode === "client"))) {
          return `export { default } from '${resolve(distDir, "core/runtime/nitro/handlers/island")}'`;
        }
        return `import { defineEventHandler } from 'h3'; export default defineEventHandler(() => {});`;
      }
    });
    addServerHandler({
      route: "/__nuxt_island/**",
      handler: "#internal/nuxt/island-renderer.mjs"
    });
    if (!nuxt.options.ssr && nuxt.options.experimental.componentIslands !== "auto") {
      nuxt.options.ssr = true;
      nuxt.options.nitro.routeRules ||= {};
      nuxt.options.nitro.routeRules["/**"] = defu$1(nuxt.options.nitro.routeRules["/**"], { ssr: false });
    }
  }
  if (!nuxt.options.dev && nuxt.options.experimental.payloadExtraction) {
    addPlugin(resolve(nuxt.options.appDir, "plugins/payload.client"));
  }
  if (nuxt.options.experimental.crossOriginPrefetch) {
    addPlugin(resolve(nuxt.options.appDir, "plugins/cross-origin-prefetch.client"));
  }
  if (nuxt.options.experimental.emitRouteChunkError === "automatic") {
    addPlugin(resolve(nuxt.options.appDir, "plugins/chunk-reload.client"));
  }
  if (nuxt.options.experimental.emitRouteChunkError === "automatic-immediate") {
    addPlugin(resolve(nuxt.options.appDir, "plugins/chunk-reload-immediate.client"));
  }
  if (nuxt.options.experimental.restoreState) {
    addPlugin(resolve(nuxt.options.appDir, "plugins/restore-state.client"));
  }
  if (nuxt.options.experimental.viewTransition) {
    addPlugin(resolve(nuxt.options.appDir, "plugins/view-transitions.client"));
  }
  if (nuxt.options.experimental.renderJsonPayloads) {
    addPlugin(resolve(nuxt.options.appDir, "plugins/revive-payload.client"));
    addPlugin(resolve(nuxt.options.appDir, "plugins/revive-payload.server"));
  }
  if (nuxt.options.experimental.appManifest) {
    addRouteMiddleware({
      name: "manifest-route-rule",
      path: resolve(nuxt.options.appDir, "middleware/manifest-route-rule"),
      global: true
    });
    if (nuxt.options.experimental.checkOutdatedBuildInterval !== false) {
      addPlugin(resolve(nuxt.options.appDir, "plugins/check-outdated-build.client"));
    }
  }
  if (nuxt.options.experimental.navigationRepaint) {
    addPlugin({
      src: resolve(nuxt.options.appDir, "plugins/navigation-repaint.client")
    });
  }
  if (nuxt.options.vue.config && Object.values(nuxt.options.vue.config).some((v) => v !== null && v !== void 0)) {
    addPluginTemplate({
      filename: "vue-app-config.mjs",
      getContents: () => `
import { defineNuxtPlugin } from '#app/nuxt'
export default defineNuxtPlugin({
  name: 'nuxt:vue-app-config',
  enforce: 'pre',
  setup (nuxtApp) {
    ${Object.keys(nuxt.options.vue.config).map((k) => `    nuxtApp.vueApp.config[${JSON.stringify(k)}] = ${JSON.stringify(nuxt.options.vue.config[k])}`).join("\n")}
  }
})`
    });
  }
  nuxt.hooks.hook("builder:watch", (event, relativePath) => {
    const path = resolve(nuxt.options.srcDir, relativePath);
    if (modulePaths.has(path)) {
      return nuxt.callHook("restart", { hard: true });
    }
    const layerRelativePaths = new Set(nuxt.options._layers.map((l) => relative(l.config.srcDir || l.cwd, path)));
    for (const pattern of nuxt.options.watch) {
      if (typeof pattern === "string") {
        if (pattern === path || layerRelativePaths.has(pattern)) {
          return nuxt.callHook("restart");
        }
        continue;
      }
      for (const p of layerRelativePaths) {
        if (pattern.test(p)) {
          return nuxt.callHook("restart");
        }
      }
    }
    if (event === "addDir" && path === resolve(nuxt.options.srcDir, "app")) {
      logger.info(`\`${path}/\` ${event === "addDir" ? "created" : "removed"}`);
      return nuxt.callHook("restart", { hard: true });
    }
    const isFileChange = ["add", "unlink"].includes(event);
    if (isFileChange && RESTART_RE.test(path)) {
      logger.info(`\`${path}\` ${event === "add" ? "created" : "removed"}`);
      return nuxt.callHook("restart");
    }
  });
  nuxt.options.build.transpile = nuxt.options.build.transpile.map((t) => typeof t === "string" ? normalize(t) : t);
  addModuleTranspiles();
  await initNitro(nuxt);
  const nitro = useNitro();
  if (nitro.options.static && nuxt.options.experimental.payloadExtraction === void 0) {
    logger.warn("Using experimental payload extraction for full-static output. You can opt-out by setting `experimental.payloadExtraction` to `false`.");
    nuxt.options.experimental.payloadExtraction = true;
  }
  nitro.options.replace["process.env.NUXT_PAYLOAD_EXTRACTION"] = String(!!nuxt.options.experimental.payloadExtraction);
  nitro.options._config.replace["process.env.NUXT_PAYLOAD_EXTRACTION"] = String(!!nuxt.options.experimental.payloadExtraction);
  if (!nuxt.options.dev && nuxt.options.experimental.payloadExtraction) {
    addPlugin(resolve(nuxt.options.appDir, "plugins/payload.client"));
  }
  if (!satisfies(nuxt._version, nuxt.options.future.compatibilityVersion + ".x")) {
    logger.info(`Running with compatibility version \`${nuxt.options.future.compatibilityVersion}\``);
  }
  await nuxt.callHook("ready", nuxt);
}
async function loadNuxt(opts) {
  const options = await loadNuxtConfig(opts);
  options.appDir = options.alias["#app"] = resolve(distDir, "app");
  options._majorVersion = 3;
  for (const key in options.app.head || {}) {
    options.app.head[key] = deduplicateArray(options.app.head[key]);
  }
  const orderedCSS = /* @__PURE__ */ new Set();
  const optionsCSS = new Set(options.css);
  for (const config of options._layers.map((layer) => layer.config).reverse()) {
    for (const style of config.css || []) {
      if (typeof style === "string") {
        orderedCSS.delete(style);
        optionsCSS.delete(style);
        orderedCSS.add(style);
      }
    }
  }
  options.css = [...orderedCSS, ...optionsCSS];
  if (options.builder === "@nuxt/vite-builder") {
    const isDevToolsEnabled = typeof options.devtools === "boolean" ? options.devtools : options.devtools?.enabled !== false;
    if (isDevToolsEnabled) {
      if (!options._modules.some((m) => m === "@nuxt/devtools" || m === "@nuxt/devtools-nightly" || m === "@nuxt/devtools-edge")) {
        options._modules.push("@nuxt/devtools");
      }
    }
  }
  if (!options._modules.some((m) => m === "@nuxt/scripts" || m === "@nuxt/scripts-nightly")) {
    options.imports = defu$1(options.imports, {
      presets: [scriptsStubsPreset]
    });
  }
  if (options.builder === "@nuxt/webpack-builder") {
    if (!await import('../chunks/features.mjs').then((r) => r.ensurePackageInstalled("@nuxt/webpack-builder", {
      rootDir: options.rootDir,
      searchPaths: options.modulesDir
    }))) {
      logger.warn("Failed to install `@nuxt/webpack-builder`, please install it manually, or change the `builder` option to vite in `nuxt.config`");
    }
  }
  options._modules.push(pagesModule, metaModule, componentsModule);
  options._modules.push([importsModule, {
    transform: {
      include: options._layers.filter((i) => i.cwd && i.cwd.includes("node_modules")).map((i) => new RegExp(`(^|\\/)${escapeRE(i.cwd.split("node_modules/").pop())}(\\/|$)(?!node_modules\\/)`))
    }
  }]);
  options._modules.push(schemaModule);
  options.modulesDir.push(resolve(options.workspaceDir, "node_modules"));
  options.modulesDir.push(resolve(pkgDir, "node_modules"));
  options.build.transpile.push(
    "mocked-exports",
    "std-env"
    // we need to statically replace process.env when used in runtime code
  );
  options.alias["vue-demi"] = resolve(options.appDir, "compat/vue-demi");
  options.alias["@vue/composition-api"] = resolve(options.appDir, "compat/capi");
  if (options.telemetry !== false && !process.env.NUXT_TELEMETRY_DISABLED) {
    options._modules.push("@nuxt/telemetry");
  }
  const allowedKeys = /* @__PURE__ */ new Set(["baseURL", "buildAssetsDir", "cdnURL", "buildId"]);
  for (const key in options.runtimeConfig.app) {
    if (!allowedKeys.has(key)) {
      logger.warn(`The \`app\` namespace is reserved for Nuxt and is exposed to the browser. Please move \`runtimeConfig.app.${key}\` to a different namespace.`);
      delete options.runtimeConfig.app[key];
    }
  }
  createPortalProperties(options.nitro.runtimeConfig, options, ["nitro.runtimeConfig", "runtimeConfig"]);
  createPortalProperties(options.nitro.routeRules, options, ["nitro.routeRules", "routeRules"]);
  const nitroOptions = options.nitro;
  Object.defineProperties(options, {
    nitro: {
      configurable: false,
      enumerable: true,
      get: () => nitroOptions,
      set(value) {
        Object.assign(nitroOptions, value);
      }
    }
  });
  const nuxt = createNuxt(options);
  nuxt.runWithContext(() => {
    if (nuxt.options.dev && !nuxt.options.test) {
      nuxt.hooks.hookOnce("build:done", () => {
        for (const dep of keyDependencies) {
          checkDependencyVersion(dep, nuxt._version).catch((e) => logger.warn(`Problem checking \`${dep}\` version.`, e));
        }
      });
    }
    if (opts.overrides?.hooks) {
      nuxt.hooks.addHooks(opts.overrides.hooks);
    }
    if (nuxt.options.debug && nuxt.options.debug.hooks && (nuxt.options.debug.hooks === true || nuxt.options.debug.hooks.server)) {
      createDebugger(nuxt.hooks, { tag: "nuxt" });
    }
  });
  if (opts.ready !== false) {
    await nuxt.ready();
  }
  return nuxt;
}
async function checkDependencyVersion(name, nuxtVersion) {
  const path = resolveModulePath(name, { try: true });
  if (!path) {
    return;
  }
  const { version: version2 } = await readPackageJSON(path);
  if (version2 && gt(nuxtVersion, version2)) {
    console.warn(`[nuxt] Expected \`${name}\` to be at least \`${nuxtVersion}\` but got \`${version2}\`. This might lead to unexpected behavior. Check your package.json or refresh your lockfile.`);
  }
}
const RESTART_RE = /^(?:app|error|app\.config)\.(?:js|ts|mjs|jsx|tsx|vue)$/i;
function deduplicateArray(maybeArray) {
  if (!Array.isArray(maybeArray)) {
    return maybeArray;
  }
  const fresh = [];
  const hashes = /* @__PURE__ */ new Set();
  for (const item of maybeArray) {
    const _hash = hash(item);
    if (!hashes.has(_hash)) {
      hashes.add(_hash);
      fresh.push(item);
    }
  }
  return fresh;
}
function createPortalProperties(sourceValue, options, paths) {
  let sharedValue = sourceValue;
  for (const path of paths) {
    const segments = path.split(".");
    const key = segments.pop();
    let parent = options;
    while (segments.length) {
      const key2 = segments.shift();
      parent = parent[key2] ||= {};
    }
    delete parent[key];
    Object.defineProperties(parent, {
      [key]: {
        configurable: false,
        enumerable: true,
        get: () => sharedValue,
        set(value) {
          sharedValue = value;
        }
      }
    });
  }
}

const vueShim = {
  filename: "types/vue-shim.d.ts",
  getContents: ({ nuxt }) => {
    if (!nuxt.options.typescript.shim) {
      return "";
    }
    return [
      "declare module '*.vue' {",
      "  import { DefineComponent } from 'vue'",
      "  const component: DefineComponent<{}, {}, any>",
      "  export default component",
      "}"
    ].join("\n");
  }
};
const appComponentTemplate = {
  filename: "app-component.mjs",
  getContents: (ctx) => genExport(ctx.app.mainComponent, ["default"])
};
const rootComponentTemplate = {
  filename: "root-component.mjs",
  // TODO: fix upstream in vite - this ensures that vite generates a module graph for islands
  // but should not be necessary (and has a warmup performance cost). See https://github.com/nuxt/nuxt/pull/24584.
  getContents: (ctx) => (ctx.nuxt.options.dev ? "import '#build/components.islands.mjs';\n" : "") + genExport(ctx.app.rootComponent, ["default"])
};
const errorComponentTemplate = {
  filename: "error-component.mjs",
  getContents: (ctx) => genExport(ctx.app.errorComponent, ["default"])
};
const testComponentWrapperTemplate = {
  filename: "test-component-wrapper.mjs",
  getContents: (ctx) => genExport(resolve(ctx.nuxt.options.appDir, "components/test-component-wrapper"), ["default"])
};
const cssTemplate = {
  filename: "css.mjs",
  getContents: (ctx) => ctx.nuxt.options.css.map((i) => genImport(i)).join("\n")
};
const PLUGIN_TEMPLATE_RE = /_(45|46|47)/g;
const clientPluginTemplate = {
  filename: "plugins.client.mjs",
  async getContents(ctx) {
    const clientPlugins = await annotatePlugins(ctx.nuxt, ctx.app.plugins.filter((p) => !p.mode || p.mode !== "server"));
    checkForCircularDependencies(clientPlugins);
    const exports = [];
    const imports = [];
    for (const plugin of clientPlugins) {
      const path = relative(ctx.nuxt.options.rootDir, plugin.src);
      const variable = genSafeVariableName(filename(plugin.src) || path).replace(PLUGIN_TEMPLATE_RE, "_") + "_" + hash(path).replace(/-/g, "_");
      exports.push(variable);
      imports.push(genImport(plugin.src, variable));
    }
    return [
      ...imports,
      `export default ${genArrayFromRaw(exports)}`
    ].join("\n");
  }
};
const serverPluginTemplate = {
  filename: "plugins.server.mjs",
  async getContents(ctx) {
    const serverPlugins = await annotatePlugins(ctx.nuxt, ctx.app.plugins.filter((p) => !p.mode || p.mode !== "client"));
    checkForCircularDependencies(serverPlugins);
    const exports = [];
    const imports = [];
    for (const plugin of serverPlugins) {
      const path = relative(ctx.nuxt.options.rootDir, plugin.src);
      const variable = genSafeVariableName(filename(plugin.src) || path).replace(PLUGIN_TEMPLATE_RE, "_") + "_" + hash(path).replace(/-/g, "_");
      exports.push(variable);
      imports.push(genImport(plugin.src, variable));
    }
    return [
      ...imports,
      `export default ${genArrayFromRaw(exports)}`
    ].join("\n");
  }
};
const appDefaults = {
  filename: "types/app-defaults.d.ts",
  getContents: (ctx) => {
    const isV4 = ctx.nuxt.options.future.compatibilityVersion === 4;
    return `
declare module 'nuxt/app/defaults' {
  type DefaultAsyncDataErrorValue = ${isV4 ? "undefined" : "null"}
  type DefaultAsyncDataValue = ${isV4 ? "undefined" : "null"}
  type DefaultErrorValue = ${isV4 ? "undefined" : "null"}
  type DedupeOption = ${isV4 ? "'cancel' | 'defer'" : "boolean | 'cancel' | 'defer'"}
}`;
  }
};
const TS_RE = /\.[cm]?tsx?$/;
const JS_LETTER_RE = /\.(?<letter>[cm])?jsx?$/;
const JS_RE = /\.[cm]jsx?$/;
const JS_CAPTURE_RE = /\.[cm](jsx?)$/;
const pluginsDeclaration = {
  filename: "types/plugins.d.ts",
  getContents: async ({ nuxt, app }) => {
    const EXTENSION_RE2 = new RegExp(`(?<=\\w)(${nuxt.options.extensions.map((e) => escapeRE(e)).join("|")})$`, "g");
    const typesDir = join(nuxt.options.buildDir, "types");
    const tsImports = [];
    const pluginNames = [];
    function exists(path) {
      return app.templates.some((t) => t.write && path === t.dst) || existsSync(path);
    }
    for (const plugin of await annotatePlugins(nuxt, app.plugins)) {
      if (plugin.name) {
        pluginNames.push(`'${plugin.name}'`);
      }
      const pluginPath = resolve(typesDir, plugin.src);
      const relativePath = relative(typesDir, pluginPath);
      const correspondingDeclaration = pluginPath.replace(JS_LETTER_RE, ".d.$<letter>ts");
      if (correspondingDeclaration !== pluginPath && exists(correspondingDeclaration)) {
        tsImports.push(relativePath);
        continue;
      }
      const incorrectDeclaration = pluginPath.replace(JS_RE, ".d.ts");
      if (incorrectDeclaration !== pluginPath && exists(incorrectDeclaration)) {
        tsImports.push(relativePath.replace(JS_CAPTURE_RE, ".$1"));
        continue;
      }
      if (exists(pluginPath)) {
        if (TS_RE.test(pluginPath)) {
          tsImports.push(relativePath.replace(EXTENSION_RE2, ""));
          continue;
        }
        tsImports.push(relativePath);
      }
    }
    return `// Generated by Nuxt'
import type { Plugin } from '#app'

type Decorate<T extends Record<string, any>> = { [K in keyof T as K extends string ? \`$\${K}\` : never]: T[K] }

type InjectionType<A extends Plugin> = A extends {default: Plugin<infer T>} ? Decorate<T> : unknown

type NuxtAppInjections = 
  ${tsImports.map((p) => `InjectionType<typeof ${genDynamicImport(p, { wrapper: false })}>`).join(" &\n  ")}

declare module '#app' {
  interface NuxtApp extends NuxtAppInjections { }

  interface NuxtAppLiterals {
    pluginName: ${pluginNames.join(" | ")}
  }
}

declare module 'vue' {
  interface ComponentCustomProperties extends NuxtAppInjections { }
}

export { }
`;
  }
};
const IMPORT_NAME_RE = /\.\w+$/;
const GIT_RE = /^git\+/;
const schemaTemplate = {
  filename: "types/schema.d.ts",
  getContents: async ({ nuxt }) => {
    const relativeRoot = relative(resolve(nuxt.options.buildDir, "types"), nuxt.options.rootDir);
    const getImportName = (name) => (name[0] === "." ? "./" + join(relativeRoot, name) : name).replace(IMPORT_NAME_RE, "");
    const modules = [];
    for (const m of nuxt.options._installedModules) {
      if (!m.meta || !m.meta.configKey || !m.meta.name) {
        continue;
      }
      if (m.meta.name.startsWith("nuxt:") || m.meta.name === "nuxt-config-schema") {
        continue;
      }
      modules.push([genString(m.meta.configKey), getImportName(m.entryPath || m.meta.name), m]);
    }
    const privateRuntimeConfig = /* @__PURE__ */ Object.create(null);
    for (const key in nuxt.options.runtimeConfig) {
      if (key !== "public") {
        privateRuntimeConfig[key] = nuxt.options.runtimeConfig[key];
      }
    }
    const moduleOptionsInterface = (options) => [
      ...modules.flatMap(([configKey, importName, mod]) => {
        let link;
        if (!mod.meta?.rawPath) {
          link = `https://www.npmjs.com/package/${importName}`;
        }
        if (typeof mod.meta?.docs === "string") {
          link = mod.meta.docs;
        } else if (mod.meta?.repository) {
          if (typeof mod.meta.repository === "string") {
            link = mod.meta.repository;
          } else if (typeof mod.meta.repository === "object" && "url" in mod.meta.repository && typeof mod.meta.repository.url === "string") {
            link = mod.meta.repository.url;
          }
          if (link) {
            if (link.startsWith("git+")) {
              link = link.replace(GIT_RE, "");
            }
            if (!link.startsWith("http")) {
              link = "https://github.com/" + link;
            }
          }
        }
        return [
          `    /**`,
          `     * Configuration for \`${importName}\``,
          ...options.addJSDocTags && link ? [`     * @see ${link}`] : [],
          `     */`,
          `    [${configKey}]${options.unresolved ? "?" : ""}: typeof ${genDynamicImport(importName, { wrapper: false })}.default extends NuxtModule<infer O> ? ${options.unresolved ? "Partial<O>" : "O"} : Record<string, any>`
        ];
      }),
      modules.length > 0 && options.unresolved ? `    modules?: (undefined | null | false | NuxtModule<any> | string | [NuxtModule | string, Record<string, any>] | ${modules.map(([configKey, importName, mod]) => `[${genString(mod.meta?.rawPath || importName)}, Exclude<NuxtConfig[${configKey}], boolean>]`).join(" | ")})[],` : ""
    ].filter(Boolean);
    return [
      "import { NuxtModule, RuntimeConfig } from '@nuxt/schema'",
      "declare module '@nuxt/schema' {",
      "  interface NuxtOptions {",
      ...moduleOptionsInterface({ addJSDocTags: false, unresolved: false }),
      "  }",
      "  interface NuxtConfig {",
      // TypeScript will duplicate the jsdoc tags if we augment it twice
      // So here we only generate tags for `nuxt/schema`
      ...moduleOptionsInterface({ addJSDocTags: false, unresolved: true }),
      "  }",
      "}",
      "declare module 'nuxt/schema' {",
      "  interface NuxtOptions {",
      ...moduleOptionsInterface({ addJSDocTags: true, unresolved: false }),
      "  }",
      "  interface NuxtConfig {",
      ...moduleOptionsInterface({ addJSDocTags: true, unresolved: true }),
      "  }",
      generateTypes(
        await resolveSchema(privateRuntimeConfig),
        {
          interfaceName: "RuntimeConfig",
          addExport: false,
          addDefaults: false,
          allowExtraKeys: false,
          indentation: 2
        }
      ),
      generateTypes(
        await resolveSchema(nuxt.options.runtimeConfig.public),
        {
          interfaceName: "PublicRuntimeConfig",
          addExport: false,
          addDefaults: false,
          allowExtraKeys: false,
          indentation: 2
        }
      ),
      "}",
      `declare module 'vue' {
        interface ComponentCustomProperties {
          $config: RuntimeConfig
        }
      }`
    ].join("\n");
  }
};
const layoutTemplate = {
  filename: "layouts.mjs",
  getContents({ app }) {
    const layoutsObject = genObjectFromRawEntries(Object.values(app.layouts).map(({ name, file }) => {
      return [name, `defineAsyncComponent(${genDynamicImport(file, { interopDefault: true })})`];
    }));
    return [
      `import { defineAsyncComponent } from 'vue'`,
      `export default ${layoutsObject}`
    ].join("\n");
  }
};
const middlewareTemplate = {
  filename: "middleware.mjs",
  getContents({ app }) {
    const globalMiddleware = app.middleware.filter((mw) => mw.global);
    const namedMiddleware = app.middleware.filter((mw) => !mw.global);
    const namedMiddlewareObject = genObjectFromRawEntries(namedMiddleware.map((mw) => [mw.name, genDynamicImport(mw.path)]));
    return [
      ...globalMiddleware.map((mw) => genImport(mw.path, genSafeVariableName(mw.name))),
      `export const globalMiddleware = ${genArrayFromRaw(globalMiddleware.map((mw) => genSafeVariableName(mw.name)))}`,
      `export const namedMiddleware = ${namedMiddlewareObject}`
    ].join("\n");
  }
};
function renderAttr(key, value) {
  return value ? `${key}="${value}"` : "";
}
function renderAttrs(obj) {
  const attrs = [];
  for (const key in obj) {
    attrs.push(renderAttr(key, obj[key]));
  }
  return attrs.join(" ");
}
const nitroSchemaTemplate = {
  filename: "types/nitro-nuxt.d.ts",
  async getContents({ nuxt }) {
    const references = [];
    const declarations = [];
    await nuxt.callHook("nitro:prepare:types", { references, declarations });
    const sourceDir = join(nuxt.options.buildDir, "types");
    const lines = [
      ...references.map((ref) => {
        if ("path" in ref && isAbsolute(ref.path)) {
          ref.path = relative(sourceDir, ref.path);
        }
        return `/// <reference ${renderAttrs(ref)} />`;
      }),
      ...declarations
    ];
    return (
      /* typescript */
      `
${lines.join("\n")}
/// <reference path="./schema.d.ts" />

import type { RuntimeConfig } from 'nuxt/schema'
import type { H3Event } from 'h3'
import type { LogObject } from 'consola'
import type { NuxtIslandContext, NuxtIslandResponse, NuxtRenderHTMLContext } from 'nuxt/app'

declare module 'nitropack' {
  interface NitroRuntimeConfigApp {
    buildAssetsDir: string
    cdnURL: string
  }
  interface NitroRuntimeConfig extends RuntimeConfig {}
  interface NitroRouteConfig {
    ssr?: boolean
    noScripts?: boolean
    /** @deprecated Use \`noScripts\` instead */
    experimentalNoScripts?: boolean
  }
  interface NitroRouteRules {
    ssr?: boolean
    noScripts?: boolean
    /** @deprecated Use \`noScripts\` instead */
    experimentalNoScripts?: boolean
    appMiddleware?: Record<string, boolean>
  }
  interface NitroRuntimeHooks {
    'dev:ssr-logs': (ctx: { logs: LogObject[], path: string }) => void | Promise<void>
    'render:html': (htmlContext: NuxtRenderHTMLContext, context: { event: H3Event }) => void | Promise<void>
    'render:island': (islandResponse: NuxtIslandResponse, context: { event: H3Event, islandContext: NuxtIslandContext }) => void | Promise<void>
  }
}
`
    );
  }
};
const clientConfigTemplate = {
  filename: "nitro.client.mjs",
  getContents: ({ nuxt }) => {
    const appId = JSON.stringify(nuxt.options.appId);
    return [
      "export const useRuntimeConfig = () => ",
      (!nuxt.options.future.multiApp ? "window?.__NUXT__?.config || {}" : `window?.__NUXT__?.[${appId}]?.config || {}`) || {}
    ].join("\n");
  }
};
const appConfigDeclarationTemplate = {
  filename: "types/app.config.d.ts",
  getContents({ app, nuxt }) {
    const typesDir = join(nuxt.options.buildDir, "types");
    const configPaths = app.configs.map((path) => relative(typesDir, path).replace(EXTENSION_RE, ""));
    return `
import type { CustomAppConfig } from 'nuxt/schema'
import type { Defu } from 'defu'
${configPaths.map((id, index) => `import ${`cfg${index}`} from ${JSON.stringify(id)}`).join("\n")}

declare const inlineConfig = ${JSON.stringify(nuxt.options.appConfig, null, 2)}
type ResolvedAppConfig = Defu<typeof inlineConfig, [${app.configs.map((_id, index) => `typeof cfg${index}`).join(", ")}]>
type IsAny<T> = 0 extends 1 & T ? true : false

type MergedAppConfig<Resolved extends Record<string, unknown>, Custom extends Record<string, unknown>> = {
  [K in keyof (Resolved & Custom)]: K extends keyof Custom
    ? unknown extends Custom[K]
      ? Resolved[K]
      : IsAny<Custom[K]> extends true
        ? Resolved[K]
        : Custom[K] extends Record<string, any>
            ? Resolved[K] extends Record<string, any>
              ? MergedAppConfig<Resolved[K], Custom[K]>
              : Exclude<Custom[K], undefined>
            : Exclude<Custom[K], undefined>
    : Resolved[K]
}

declare module 'nuxt/schema' {
  interface AppConfig extends MergedAppConfig<ResolvedAppConfig, CustomAppConfig> { }
}
declare module '@nuxt/schema' {
  interface AppConfig extends MergedAppConfig<ResolvedAppConfig, CustomAppConfig> { }
}
`;
  }
};
const appConfigTemplate = {
  filename: "app.config.mjs",
  write: true,
  getContents({ app, nuxt }) {
    return `
import { _replaceAppConfig } from '#app/config'
import { defuFn } from 'defu'

const inlineConfig = ${JSON.stringify(nuxt.options.appConfig, null, 2)}

// Vite - webpack is handled directly in #app/config
if (import.meta.hot) {
  import.meta.hot.accept((newModule) => {
    _replaceAppConfig(newModule.default)
  })
}

${app.configs.map((id, index) => `import ${`cfg${index}`} from ${JSON.stringify(id)}`).join("\n")}

export default /*@__PURE__*/ defuFn(${app.configs.map((_id, index) => `cfg${index}`).concat(["inlineConfig"]).join(", ")})
`;
  }
};
const publicPathTemplate = {
  filename: "paths.mjs",
  getContents({ nuxt }) {
    return [
      "import { joinRelativeURL } from 'ufo'",
      !nuxt.options.dev && "import { useRuntimeConfig } from '#internal/nitro'",
      nuxt.options.dev ? `const appConfig = ${JSON.stringify(nuxt.options.app)}` : "const appConfig = useRuntimeConfig().app",
      "export const baseURL = () => appConfig.baseURL",
      "export const buildAssetsDir = () => appConfig.buildAssetsDir",
      "export const buildAssetsURL = (...path) => joinRelativeURL(publicAssetsURL(), buildAssetsDir(), ...path)",
      "export const publicAssetsURL = (...path) => {",
      "  const publicBase = appConfig.cdnURL || appConfig.baseURL",
      "  return path.length ? joinRelativeURL(publicBase, ...path) : publicBase",
      "}",
      // On server these are registered directly in packages/nuxt/src/core/runtime/nitro/handlers/renderer.ts
      "if (import.meta.client) {",
      "  globalThis.__buildAssetsURL = buildAssetsURL",
      "  globalThis.__publicAssetsURL = publicAssetsURL",
      "}"
    ].filter(Boolean).join("\n");
  }
};
const globalPolyfillsTemplate = {
  filename: "global-polyfills.mjs",
  getContents() {
    return `
if (!("global" in globalThis)) {
  globalThis.global = globalThis;
}`;
  }
};
const dollarFetchTemplate = {
  filename: "fetch.mjs",
  getContents() {
    return [
      "import { $fetch } from 'ofetch'",
      "import { baseURL } from '#internal/nuxt/paths'",
      "if (!globalThis.$fetch) {",
      "  globalThis.$fetch = $fetch.create({",
      "    baseURL: baseURL()",
      "  })",
      "}"
    ].join("\n");
  }
};
const nuxtConfigTemplate = {
  filename: "nuxt.config.mjs",
  getContents: (ctx) => {
    const fetchDefaults = {
      ...ctx.nuxt.options.experimental.defaults.useFetch,
      baseURL: void 0,
      headers: void 0
    };
    const shouldEnableComponentIslands = ctx.nuxt.options.experimental.componentIslands && (ctx.nuxt.options.dev || ctx.nuxt.options.experimental.componentIslands !== "auto" || ctx.app.pages?.some((p) => p.mode === "server") || ctx.app.components?.some((c) => c.mode === "server" && !ctx.app.components.some((other) => other.pascalName === c.pascalName && other.mode === "client")));
    return [
      ...Object.entries(ctx.nuxt.options.app).map(([k, v]) => `export const ${camelCase("app-" + k)} = ${JSON.stringify(v)}`),
      `export const renderJsonPayloads = ${!!ctx.nuxt.options.experimental.renderJsonPayloads}`,
      `export const componentIslands = ${shouldEnableComponentIslands}`,
      `export const payloadExtraction = ${!!ctx.nuxt.options.experimental.payloadExtraction}`,
      `export const cookieStore = ${!!ctx.nuxt.options.experimental.cookieStore}`,
      `export const appManifest = ${!!ctx.nuxt.options.experimental.appManifest}`,
      `export const remoteComponentIslands = ${typeof ctx.nuxt.options.experimental.componentIslands === "object" && ctx.nuxt.options.experimental.componentIslands.remoteIsland}`,
      `export const selectiveClient = ${typeof ctx.nuxt.options.experimental.componentIslands === "object" && Boolean(ctx.nuxt.options.experimental.componentIslands.selectiveClient)}`,
      `export const devPagesDir = ${ctx.nuxt.options.dev ? JSON.stringify(ctx.nuxt.options.dir.pages) : "null"}`,
      `export const devRootDir = ${ctx.nuxt.options.dev ? JSON.stringify(ctx.nuxt.options.rootDir) : "null"}`,
      `export const devLogs = ${JSON.stringify(ctx.nuxt.options.features.devLogs)}`,
      `export const nuxtLinkDefaults = ${JSON.stringify(ctx.nuxt.options.experimental.defaults.nuxtLink)}`,
      `export const asyncDataDefaults = ${JSON.stringify({
        ...ctx.nuxt.options.experimental.defaults.useAsyncData,
        value: ctx.nuxt.options.experimental.defaults.useAsyncData.value === "null" ? null : void 0,
        errorValue: ctx.nuxt.options.experimental.defaults.useAsyncData.errorValue === "null" ? null : void 0
      })}`,
      `export const resetAsyncDataToUndefined = ${ctx.nuxt.options.experimental.resetAsyncDataToUndefined}`,
      `export const nuxtDefaultErrorValue = ${ctx.nuxt.options.future.compatibilityVersion === 4 ? "undefined" : "null"}`,
      `export const fetchDefaults = ${JSON.stringify(fetchDefaults)}`,
      `export const vueAppRootContainer = ${ctx.nuxt.options.app.rootAttrs.id ? `'#${ctx.nuxt.options.app.rootAttrs.id}'` : `'body > ${ctx.nuxt.options.app.rootTag}'`}`,
      `export const viewTransition = ${ctx.nuxt.options.experimental.viewTransition}`,
      `export const appId = ${JSON.stringify(ctx.nuxt.options.appId)}`,
      `export const outdatedBuildInterval = ${ctx.nuxt.options.experimental.checkOutdatedBuildInterval}`,
      `export const multiApp = ${!!ctx.nuxt.options.future.multiApp}`,
      `export const chunkErrorEvent = ${ctx.nuxt.options.experimental.emitRouteChunkError ? ctx.nuxt.options.builder === "@nuxt/vite-builder" ? '"vite:preloadError"' : '"nuxt:preloadError"' : "false"}`,
      `export const crawlLinks = ${!!ctx.nuxt._nitro.options.prerender.crawlLinks}`,
      `export const spaLoadingTemplateOutside = ${ctx.nuxt.options.experimental.spaLoadingTemplateLocation === "body"}`,
      `export const purgeCachedData = ${!!ctx.nuxt.options.experimental.purgeCachedData}`,
      `export const granularCachedData = ${!!ctx.nuxt.options.experimental.granularCachedData}`,
      `export const pendingWhenIdle = ${!!ctx.nuxt.options.experimental.pendingWhenIdle}`,
      `export const alwaysRunFetchOnKeyChange = ${!!ctx.nuxt.options.experimental.alwaysRunFetchOnKeyChange}`
    ].join("\n\n");
  }
};
const TYPE_FILENAME_RE = /\.([cm])?[jt]s$/;
const DECLARATION_RE = /\.d\.[cm]?ts$/;
const buildTypeTemplate = {
  filename: "types/build.d.ts",
  getContents({ app }) {
    let declarations = "";
    for (const file of app.templates) {
      if (file.write || !file.filename || DECLARATION_RE.test(file.filename)) {
        continue;
      }
      if (TYPE_FILENAME_RE.test(file.filename)) {
        const typeFilenames = /* @__PURE__ */ new Set([file.filename.replace(TYPE_FILENAME_RE, ".d.$1ts"), file.filename.replace(TYPE_FILENAME_RE, ".d.ts")]);
        if (app.templates.some((f) => f.filename && typeFilenames.has(f.filename))) {
          continue;
        }
      }
      declarations += "declare module " + JSON.stringify(join("#build", file.filename)) + ";\n";
    }
    return declarations;
  }
};

const defaultTemplates = {
  __proto__: null,
  appComponentTemplate: appComponentTemplate,
  appConfigDeclarationTemplate: appConfigDeclarationTemplate,
  appConfigTemplate: appConfigTemplate,
  appDefaults: appDefaults,
  buildTypeTemplate: buildTypeTemplate,
  clientConfigTemplate: clientConfigTemplate,
  clientPluginTemplate: clientPluginTemplate,
  cssTemplate: cssTemplate,
  dollarFetchTemplate: dollarFetchTemplate,
  errorComponentTemplate: errorComponentTemplate,
  globalPolyfillsTemplate: globalPolyfillsTemplate,
  layoutTemplate: layoutTemplate,
  middlewareTemplate: middlewareTemplate,
  nitroSchemaTemplate: nitroSchemaTemplate,
  nuxtConfigTemplate: nuxtConfigTemplate,
  pluginsDeclaration: pluginsDeclaration,
  publicPathTemplate: publicPathTemplate,
  rootComponentTemplate: rootComponentTemplate,
  schemaTemplate: schemaTemplate,
  serverPluginTemplate: serverPluginTemplate,
  testComponentWrapperTemplate: testComponentWrapperTemplate,
  vueShim: vueShim
};

function createApp(nuxt, options = {}) {
  return defu(options, {
    dir: nuxt.options.srcDir,
    extensions: nuxt.options.extensions,
    plugins: [],
    components: [],
    templates: []
  });
}
const postTemplates = /* @__PURE__ */ new Set([
  clientPluginTemplate.filename,
  serverPluginTemplate.filename,
  pluginsDeclaration.filename
]);
async function generateApp(nuxt, app, options = {}) {
  await resolveApp(nuxt, app);
  app.templates = Object.values(defaultTemplates).concat(nuxt.options.build.templates);
  await nuxt.callHook("app:templates", app);
  app.templates = app.templates.map((tmpl) => normalizeTemplate(tmpl, nuxt.options.buildDir));
  const filteredTemplates = {
    pre: [],
    post: []
  };
  for (const template of app.templates) {
    if (options.filter && !options.filter(template)) {
      continue;
    }
    const key = template.filename && postTemplates.has(template.filename) ? "post" : "pre";
    filteredTemplates[key].push(template);
  }
  const templateContext = { utils: templateUtils, nuxt, app };
  const compileTemplate$1 = nuxt.options.experimental.compileTemplate ? compileTemplate : futureCompileTemplate;
  const writes = [];
  const dirs = /* @__PURE__ */ new Set();
  const changedTemplates = [];
  const FORWARD_SLASH_RE = /\//g;
  async function processTemplate(template) {
    const fullPath = template.dst || resolve(nuxt.options.buildDir, template.filename);
    const start = performance.now();
    const oldContents = nuxt.vfs[fullPath];
    const contents = await compileTemplate$1(template, templateContext).catch((e) => {
      logger.error(`Could not compile template \`${template.filename}\`.`);
      logger.error(e);
      throw e;
    });
    template.modified = oldContents !== contents;
    if (template.modified) {
      nuxt.vfs[fullPath] = contents;
      const aliasPath = "#build/" + template.filename;
      nuxt.vfs[aliasPath] = contents;
      if (process.platform === "win32") {
        nuxt.vfs[fullPath.replace(FORWARD_SLASH_RE, "\\")] = contents;
      }
      changedTemplates.push(template);
    }
    const perf = performance.now() - start;
    const setupTime = Math.round(perf * 100) / 100;
    if (nuxt.options.debug && nuxt.options.debug.templates || setupTime > 500) {
      logger.info(`Compiled \`${template.filename}\` in ${setupTime}ms`);
    }
    if (template.modified && template.write) {
      dirs.add(dirname(fullPath));
      writes.push(() => writeFileSync(fullPath, contents, "utf8"));
    }
  }
  await Promise.allSettled(filteredTemplates.pre.map(processTemplate));
  await Promise.allSettled(filteredTemplates.post.map(processTemplate));
  for (const dir of dirs) {
    mkdirSync(dir, { recursive: true });
  }
  for (const write of writes) {
    write();
  }
  if (changedTemplates.length) {
    await nuxt.callHook("app:templatesGenerated", app, changedTemplates, options);
  }
}
async function futureCompileTemplate(template, ctx) {
  delete ctx.utils;
  if (template.src) {
    try {
      return await promises.readFile(template.src, "utf-8");
    } catch (err) {
      logger.error(`[nuxt] Error reading template from \`${template.src}\``);
      throw err;
    }
  }
  if (template.getContents) {
    return template.getContents({ ...ctx, options: template.options });
  }
  throw new Error("[nuxt] Invalid template. Templates must have either `src` or `getContents`: " + JSON.stringify(template));
}
async function resolveApp(nuxt, app) {
  app.mainComponent ||= await findPath(
    nuxt.options._layers.flatMap((layer) => [
      join(layer.config.srcDir, "App"),
      join(layer.config.srcDir, "app")
    ])
  );
  app.mainComponent ||= resolve(nuxt.options.appDir, "components/welcome.vue");
  app.rootComponent ||= await findPath(["~/app.root", resolve(nuxt.options.appDir, "components/nuxt-root.vue")]);
  app.errorComponent ||= await findPath(
    nuxt.options._layers.map((layer) => join(layer.config.srcDir, "error"))
  ) ?? resolve(nuxt.options.appDir, "components/nuxt-error-page.vue");
  const extensionGlob = nuxt.options.extensions.join(",");
  const layerConfigs = nuxt.options._layers.map((layer) => layer.config);
  const reversedConfigs = layerConfigs.slice().reverse();
  app.layouts = {};
  for (const config of layerConfigs) {
    const layoutDir = (config.rootDir === nuxt.options.rootDir ? nuxt.options.dir : config.dir)?.layouts || "layouts";
    const layoutFiles = await resolveFiles(config.srcDir, `${layoutDir}/**/*{${extensionGlob}}`);
    for (const file of layoutFiles) {
      const name = getNameFromPath(file, resolve(config.srcDir, layoutDir));
      if (!name) {
        logger.warn(`No layout name could be resolved for \`~/${relative(nuxt.options.srcDir, file)}\`. Bear in mind that \`index\` is ignored for the purpose of creating a layout name.`);
        continue;
      }
      app.layouts[name] ||= { name, file };
    }
  }
  app.middleware = [];
  for (const config of reversedConfigs) {
    const middlewareDir = (config.rootDir === nuxt.options.rootDir ? nuxt.options.dir : config.dir)?.middleware || "middleware";
    const middlewareFiles = await resolveFiles(config.srcDir, [
      `${middlewareDir}/*{${extensionGlob}}`,
      ...nuxt.options.future.compatibilityVersion === 4 ? [`${middlewareDir}/*/index{${extensionGlob}}`] : []
    ]);
    for (const file of middlewareFiles) {
      const name = getNameFromPath(file);
      if (!name) {
        logger.warn(`No middleware name could be resolved for \`~/${relative(nuxt.options.srcDir, file)}\`. Bear in mind that \`index\` is ignored for the purpose of creating a middleware name.`);
        continue;
      }
      app.middleware.push({ name, path: file, global: hasSuffix(file, ".global") });
    }
  }
  app.plugins = [];
  for (const config of reversedConfigs) {
    const pluginDir = (config.rootDir === nuxt.options.rootDir ? nuxt.options.dir : config.dir)?.plugins || "plugins";
    app.plugins.push(...[
      ...config.plugins || [],
      ...config.srcDir ? await resolveFiles(config.srcDir, [
        `${pluginDir}/*{${extensionGlob}}`,
        `${pluginDir}/*/index{${extensionGlob}}`
      ]) : []
    ].map((plugin) => normalizePlugin(plugin)));
  }
  for (const p of [...nuxt.options.plugins].reverse()) {
    const plugin = normalizePlugin(p);
    if (!app.plugins.some((p2) => p2.src === plugin.src)) {
      app.plugins.unshift(plugin);
    }
  }
  app.middleware = uniqueBy(await resolvePaths(nuxt, [...app.middleware].reverse(), "path"), "name").reverse();
  app.plugins = uniqueBy(await resolvePaths(nuxt, app.plugins, "src"), "src");
  app.configs = [];
  for (const config of layerConfigs) {
    const appConfigPath = await findPath(resolve(config.srcDir, "app.config"));
    if (appConfigPath) {
      app.configs.push(appConfigPath);
    }
  }
  await nuxt.callHook("app:resolve", app);
  app.middleware = uniqueBy(await resolvePaths(nuxt, app.middleware, "path"), "name");
  app.plugins = uniqueBy(await resolvePaths(nuxt, app.plugins, "src"), "src");
  app.configs = [...new Set(app.configs)];
}
function resolvePaths(nuxt, items, key) {
  return Promise.all(items.map(async (item) => {
    if (!item[key]) {
      return item;
    }
    return {
      ...item,
      [key]: await resolvePath(item[key], {
        alias: nuxt.options.alias,
        extensions: nuxt.options.extensions,
        fallbackToOriginal: true,
        virtual: true
      })
    };
  }));
}
const IS_TSX = /\.[jt]sx$/;
async function annotatePlugins(nuxt, plugins) {
  const _plugins = [];
  for (const plugin of plugins) {
    try {
      const code = plugin.src in nuxt.vfs ? nuxt.vfs[plugin.src] : await promises.readFile(plugin.src, "utf-8");
      _plugins.push({
        ...await extractMetadata(code, IS_TSX.test(plugin.src) ? "tsx" : "ts"),
        ...plugin
      });
    } catch (e) {
      const relativePluginSrc = relative(nuxt.options.rootDir, plugin.src);
      if (e.message === "Invalid plugin metadata") {
        logger.warn(`Failed to parse static properties from plugin \`${relativePluginSrc}\`, falling back to non-optimized runtime meta. Learn more: https://nuxt.com/docs/guide/directory-structure/plugins#object-syntax-plugins`);
      } else {
        logger.warn(`Failed to parse static properties from plugin \`${relativePluginSrc}\`.`, e);
      }
      _plugins.push(plugin);
    }
  }
  return _plugins.sort((a, b) => (a.order ?? orderMap.default) - (b.order ?? orderMap.default));
}
function checkForCircularDependencies(_plugins) {
  const deps = /* @__PURE__ */ Object.create(null);
  const pluginNames = new Set(_plugins.map((plugin) => plugin.name));
  for (const plugin of _plugins) {
    if (plugin.dependsOn && plugin.dependsOn.some((name) => !pluginNames.has(name))) {
      console.error(`Plugin \`${plugin.name}\` depends on \`${plugin.dependsOn.filter((name) => !pluginNames.has(name)).join(", ")}\` but they are not registered.`);
    }
    if (plugin.name) {
      deps[plugin.name] = plugin.dependsOn || [];
    }
  }
  const checkDeps = (name, visited = []) => {
    if (visited.includes(name)) {
      console.error(`Circular dependency detected in plugins: ${visited.join(" -> ")} -> ${name}`);
      return [];
    }
    visited.push(name);
    return deps[name]?.length ? deps[name].flatMap((dep) => checkDeps(dep, [...visited])) : [];
  };
  for (const name in deps) {
    checkDeps(name);
  }
}

async function checkForExternalConfigurationFiles() {
  const checkResults = await Promise.all([checkViteConfig(), checkWebpackConfig(), checkNitroConfig(), checkPostCSSConfig()]);
  const warningMessages = checkResults.filter(Boolean);
  if (!warningMessages.length) {
    return;
  }
  const foundOneExternalConfig = warningMessages.length === 1;
  if (foundOneExternalConfig) {
    logger.warn(warningMessages[0]);
  } else {
    const warningsAsList = warningMessages.map((message) => `- ${message}`).join("\n");
    const warning = `Found multiple external configuration files: 

${warningsAsList}`;
    logger.warn(warning);
  }
}
async function checkViteConfig() {
  return await checkAndWarnAboutConfigFileExistence({
    fileName: "vite.config",
    extensions: [".js", ".mjs", ".ts", ".cjs", ".mts", ".cts"],
    createWarningMessage: (foundFile) => `Using \`${foundFile}\` is not supported together with Nuxt. Use \`options.vite\` instead. You can read more in \`https://nuxt.com/docs/api/nuxt-config#vite\`.`
  });
}
async function checkWebpackConfig() {
  return await checkAndWarnAboutConfigFileExistence({
    fileName: "webpack.config",
    extensions: [".js", ".mjs", ".ts", ".cjs", ".mts", ".cts", "coffee"],
    createWarningMessage: (foundFile) => `Using \`${foundFile}\` is not supported together with Nuxt. Use \`options.webpack\` instead. You can read more in \`https://nuxt.com/docs/api/nuxt-config#webpack-1\`.`
  });
}
async function checkNitroConfig() {
  return await checkAndWarnAboutConfigFileExistence({
    fileName: "nitro.config",
    extensions: [".ts", ".mts"],
    createWarningMessage: (foundFile) => `Using \`${foundFile}\` is not supported together with Nuxt. Use \`options.nitro\` instead. You can read more in \`https://nuxt.com/docs/api/nuxt-config#nitro\`.`
  });
}
async function checkPostCSSConfig() {
  return await checkAndWarnAboutConfigFileExistence({
    fileName: "postcss.config",
    extensions: [".js", ".cjs"],
    createWarningMessage: (foundFile) => `Using \`${foundFile}\` is not supported together with Nuxt. Use \`options.postcss\` instead. You can read more in \`https://nuxt.com/docs/api/nuxt-config#postcss\`.`
  });
}
async function checkAndWarnAboutConfigFileExistence(options) {
  const { fileName, extensions, createWarningMessage } = options;
  const configFile = await findPath(fileName, { extensions }).catch(() => null);
  if (configFile) {
    return createWarningMessage(basename(configFile));
  }
}

async function getVueHash(nuxt) {
  const id = "vue";
  const { hash: hash2 } = await getHashes(nuxt, {
    id,
    cwd: (layer) => layer.config.srcDir || layer.cwd,
    patterns: (layer) => {
      const srcDir = layer.config.srcDir || layer.cwd;
      return [
        "**",
        `!${relative(srcDir, layer.config.serverDir || join(layer.cwd, "server"))}/**`,
        `!${relative(srcDir, resolve$1(layer.cwd, layer.config.dir?.public || "public"))}/**`,
        `!${relative(srcDir, resolve$1(layer.cwd, layer.config.dir?.static || "public"))}/**`,
        "!node_modules/**",
        "!nuxt.config.*"
      ];
    },
    configOverrides: {
      buildId: void 0,
      serverDir: void 0,
      nitro: void 0,
      devServer: void 0,
      runtimeConfig: void 0,
      logLevel: void 0,
      devServerHandlers: void 0,
      generate: void 0,
      devtools: void 0
    }
  });
  const cacheFile = join(getCacheDir(nuxt), id, hash2 + ".tar");
  return {
    hash: hash2,
    async collectCache() {
      const start = Date.now();
      await writeCache(nuxt.options.buildDir, nuxt.options.buildDir, cacheFile);
      const elapsed = Date.now() - start;
      consola$1.success(`Cached Vue client and server builds in \`${elapsed}ms\`.`);
    },
    async restoreCache() {
      const start = Date.now();
      const res = await restoreCache(nuxt.options.buildDir, cacheFile);
      const elapsed = Date.now() - start;
      if (res) {
        consola$1.success(`Restored Vue client and server builds from cache in \`${elapsed}ms\`.`);
      }
      return res;
    }
  };
}
async function cleanupCaches(nuxt) {
  const start = Date.now();
  const caches = await glob(["*/*.tar"], {
    cwd: getCacheDir(nuxt),
    absolute: true
  });
  if (caches.length >= 10) {
    const cachesWithMeta = await Promise.all(caches.map(async (cache) => {
      return [cache, await stat(cache).then((r) => r.mtime.getTime()).catch(() => 0)];
    }));
    cachesWithMeta.sort((a, b) => a[1] - b[1]);
    for (const [cache] of cachesWithMeta.slice(0, cachesWithMeta.length - 10)) {
      await unlink(cache);
    }
    const elapsed = Date.now() - start;
    consola$1.success(`Cleaned up old build caches in \`${elapsed}ms\`.`);
  }
}
async function getHashes(nuxt, options) {
  if (nuxt[`_${options.id}BuildHash`]) {
    return nuxt[`_${options.id}BuildHash`];
  }
  const start = Date.now();
  const hashSources = [];
  let layerCtr = 0;
  for (const layer of nuxt.options._layers) {
    if (layer.cwd.includes("node_modules")) {
      continue;
    }
    const layerName = `layer#${layerCtr++}`;
    hashSources.push({
      name: `${layerName}:config`,
      data: serialize({
        ...layer.config,
        ...options.configOverrides || {}
      })
    });
    const normalizeFiles = (files) => files.map((f) => ({
      name: f.name,
      size: f.attrs?.size,
      data: hash(f.data)
    })).sort((a, b) => a.name.localeCompare(b.name));
    const isIgnored = createIsIgnored(nuxt);
    const sourceFiles = await readFilesRecursive(options.cwd(layer), {
      shouldIgnore: isIgnored,
      // TODO: Validate if works with absolute paths
      cwd: nuxt.options.rootDir,
      patterns: options.patterns(layer)
    });
    hashSources.push({
      name: `${layerName}:src`,
      data: normalizeFiles(sourceFiles)
    });
    const rootFiles = await readFilesRecursive(layer.config?.rootDir || layer.cwd, {
      shouldIgnore: isIgnored,
      // TODO: Validate if works with absolute paths
      cwd: nuxt.options.rootDir,
      patterns: [
        ".nuxtrc",
        ".npmrc",
        "package.json",
        "package-lock.json",
        "yarn.lock",
        "pnpm-lock.yaml",
        "tsconfig.json",
        "bun.lockb"
      ]
    });
    hashSources.push({
      name: `${layerName}:root`,
      data: normalizeFiles(rootFiles)
    });
  }
  hashSources.sort((a, b) => a.name.localeCompare(b.name));
  const res = nuxt[`_${options.id}BuildHash`] = {
    hash: hash(hashSources),
    sources: hashSources
  };
  const elapsed = Date.now() - start;
  consola$1.debug(`Computed \`${options.id}\` build hash in \`${elapsed}ms\`.`);
  return res;
}
async function readFilesRecursive(dir, opts) {
  if (Array.isArray(dir)) {
    return (await Promise.all(dir.map((d) => readFilesRecursive(d, opts)))).flat();
  }
  const files = await glob(opts.patterns, { cwd: dir });
  const fileEntries = await Promise.all(files.map(async (fileName) => {
    if (!opts.shouldIgnore?.(fileName)) {
      const file = await readFileWithMeta(dir, fileName);
      if (!file) {
        return;
      }
      return {
        ...file,
        name: relative(opts.cwd, join(dir, file.name))
      };
    }
  }));
  return fileEntries.filter(Boolean);
}
async function readFileWithMeta(dir, fileName, count = 0) {
  let fd = void 0;
  try {
    fd = await open(resolve$1(dir, fileName));
    const stats = await fd.stat();
    if (!stats?.isFile()) {
      return;
    }
    const mtime = stats.mtime.getTime();
    const data = await fd.readFile();
    if ((await fd.stat()).mtime.getTime() !== mtime) {
      if (count < 5) {
        return readFileWithMeta(dir, fileName, count + 1);
      }
      console.warn(`Failed to read file \`${fileName}\` as it changed during read.`);
      return;
    }
    return {
      name: fileName,
      data,
      attrs: {
        mtime,
        size: stats.size
      }
    };
  } catch (err) {
    console.warn(`Failed to read file \`${fileName}\`:`, err);
  } finally {
    await fd?.close();
  }
}
async function restoreCache(cwd, cacheFile) {
  if (!existsSync(cacheFile)) {
    return false;
  }
  const files = parseTar(await readFile(cacheFile));
  for (const file of files) {
    let fd = void 0;
    try {
      const filePath = resolve$1(cwd, file.name);
      await mkdir(dirname(filePath), { recursive: true });
      fd = await open(filePath, "w");
      const stats = await fd.stat().catch(() => null);
      if (stats?.isFile() && stats.size) {
        const lastModified = Number.parseInt(file.attrs?.mtime?.toString().padEnd(13, "0") || "0");
        if (stats.mtime.getTime() >= lastModified) {
          consola$1.debug(`Skipping \`${file.name}\` (up to date or newer than cache)`);
          continue;
        }
      }
      await fd.writeFile(file.data);
    } catch (err) {
      console.error(err);
    } finally {
      await fd?.close();
    }
  }
  return true;
}
async function writeCache(cwd, sources, cacheFile) {
  const fileEntries = await readFilesRecursive(sources, {
    patterns: ["**/*", "!analyze/**"],
    cwd
  });
  const tarData = createTar(fileEntries);
  await mkdir(dirname(cacheFile), { recursive: true });
  await writeFile(cacheFile, tarData);
}
function getCacheDir(nuxt) {
  let cacheDir = join(nuxt.options.workspaceDir, "node_modules");
  if (!existsSync(cacheDir)) {
    for (const dir of [...nuxt.options.modulesDir].sort((a, b) => a.length - b.length)) {
      if (existsSync(dir)) {
        cacheDir = dir;
        break;
      }
    }
  }
  return join(cacheDir, ".cache/nuxt/builds");
}

async function build(nuxt) {
  const app = createApp(nuxt);
  nuxt.apps.default = app;
  const generateApp$1 = debounce(() => generateApp(nuxt, app), void 0, { leading: true });
  await generateApp$1();
  if (nuxt.options.dev) {
    watch(nuxt);
    nuxt.hook("builder:watch", async (event, relativePath) => {
      if (event === "add" || event === "unlink") {
        const path = resolve(nuxt.options.srcDir, relativePath);
        for (const layer of nuxt.options._layers) {
          const relativePath2 = relative(layer.config.srcDir || layer.cwd, path);
          if (relativePath2.match(/^app\./i)) {
            app.mainComponent = void 0;
            break;
          }
          if (relativePath2.match(/^error\./i)) {
            app.errorComponent = void 0;
            break;
          }
        }
      }
      await generateApp$1();
    });
    nuxt.hook("builder:generateApp", (options) => {
      if (options) {
        return generateApp(nuxt, app, options);
      }
      return generateApp$1();
    });
  }
  if (!nuxt.options._prepare && !nuxt.options.dev && nuxt.options.experimental.buildCache) {
    const { restoreCache, collectCache } = await getVueHash(nuxt);
    if (await restoreCache()) {
      await nuxt.callHook("build:done");
      return await nuxt.callHook("close", nuxt);
    }
    nuxt.hooks.hookOnce("nitro:build:before", () => collectCache());
    nuxt.hooks.hookOnce("close", () => cleanupCaches(nuxt));
  }
  await nuxt.callHook("build:before");
  if (nuxt.options._prepare) {
    nuxt.hook("prepare:types", () => nuxt.close());
    return;
  }
  if (nuxt.options.dev && !nuxt.options.test) {
    nuxt.hooks.hookOnce("build:done", () => {
      checkForExternalConfigurationFiles().catch((e) => logger.warn("Problem checking for external configuration files.", e));
    });
  }
  await bundle(nuxt);
  await nuxt.callHook("build:done");
  if (!nuxt.options.dev) {
    await nuxt.callHook("close", nuxt);
  }
}
const watchEvents = {
  create: "add",
  delete: "unlink",
  update: "change"
};
async function watch(nuxt) {
  if (nuxt.options.experimental.watcher === "parcel") {
    const success = await createParcelWatcher();
    if (success) {
      return;
    }
  }
  if (nuxt.options.experimental.watcher === "chokidar") {
    return createWatcher();
  }
  return createGranularWatcher();
}
function createWatcher() {
  const nuxt = useNuxt();
  const isIgnored2 = createIsIgnored(nuxt);
  const watcher = watch$1(nuxt.options._layers.map((i) => i.config.srcDir).filter(Boolean), {
    ...nuxt.options.watchers.chokidar,
    ignoreInitial: true,
    ignored: [isIgnored2, /[\\/]node_modules[\\/]/]
  });
  const restartPaths = /* @__PURE__ */ new Set();
  const srcDir = nuxt.options.srcDir.replace(/\/?$/, "/");
  for (const pattern of nuxt.options.watch) {
    if (typeof pattern !== "string") {
      continue;
    }
    const path = resolve(nuxt.options.srcDir, pattern);
    if (!path.startsWith(srcDir)) {
      restartPaths.add(path);
    }
  }
  watcher.add([...restartPaths]);
  watcher.on("all", (event, path) => {
    if (event === "all" || event === "ready" || event === "error" || event === "raw") {
      return;
    }
    nuxt.callHook("builder:watch", event, nuxt.options.experimental.relativeWatchPaths ? normalize(relative(nuxt.options.srcDir, path)) : normalize(path));
  });
  nuxt.hook("close", () => watcher?.close());
}
function createGranularWatcher() {
  const nuxt = useNuxt();
  const isIgnored2 = createIsIgnored(nuxt);
  if (nuxt.options.debug && nuxt.options.debug.watchers) {
    console.time("[nuxt] builder:chokidar:watch");
  }
  let pending = 0;
  const ignoredDirs = /* @__PURE__ */ new Set([...nuxt.options.modulesDir, nuxt.options.buildDir]);
  const pathsToWatch = resolvePathsToWatch(nuxt);
  for (const dir of pathsToWatch) {
    pending++;
    const watcher = watch$1(dir, { ...nuxt.options.watchers.chokidar, ignoreInitial: false, depth: 0, ignored: [isIgnored2, /[\\/]node_modules[\\/]/] });
    const watchers = {};
    watcher.on("all", (event, path) => {
      if (event === "all" || event === "ready" || event === "error" || event === "raw") {
        return;
      }
      path = normalize(path);
      if (!pending) {
        nuxt.callHook("builder:watch", event, nuxt.options.experimental.relativeWatchPaths ? relative(nuxt.options.srcDir, path) : path);
      }
      if (event === "unlinkDir" && path in watchers) {
        watchers[path]?.close();
        delete watchers[path];
      }
      if (event === "addDir" && path !== dir && !ignoredDirs.has(path) && !pathsToWatch.has(path) && !(path in watchers) && !isIgnored2(path)) {
        const pathWatcher = watchers[path] = watch$1(path, { ...nuxt.options.watchers.chokidar, ignored: [isIgnored2] });
        pathWatcher.on("all", (event2, p) => {
          if (event2 === "all" || event2 === "ready" || event2 === "error" || event2 === "raw") {
            return;
          }
          nuxt.callHook("builder:watch", event2, nuxt.options.experimental.relativeWatchPaths ? normalize(relative(nuxt.options.srcDir, p)) : normalize(p));
        });
        nuxt.hook("close", () => watchers[path]?.close());
      }
    });
    watcher.on("ready", () => {
      pending--;
      if (nuxt.options.debug && nuxt.options.debug.watchers && !pending) {
        console.timeEnd("[nuxt] builder:chokidar:watch");
      }
    });
    nuxt.hook("close", () => watcher?.close());
  }
}
async function createParcelWatcher() {
  const nuxt = useNuxt();
  if (nuxt.options.debug && nuxt.options.debug.watchers) {
    console.time("[nuxt] builder:parcel:watch");
  }
  try {
    const { subscribe } = await importModule("@parcel/watcher", { url: [nuxt.options.rootDir, ...nuxt.options.modulesDir].map((d) => directoryToURL(d)) });
    const pathsToWatch = resolvePathsToWatch(nuxt, { parentDirectories: true });
    for (const dir of pathsToWatch) {
      if (!await isDirectory$1(dir)) {
        continue;
      }
      const watcher = subscribe(dir, (err, events) => {
        if (err) {
          return;
        }
        for (const event of events) {
          if (isIgnored(event.path)) {
            continue;
          }
          nuxt.callHook("builder:watch", watchEvents[event.type], nuxt.options.experimental.relativeWatchPaths ? normalize(relative(nuxt.options.srcDir, event.path)) : normalize(event.path));
        }
      }, {
        ignore: [
          ...nuxt.options.ignore,
          "node_modules"
        ]
      });
      watcher.then((subscription) => {
        if (nuxt.options.debug && nuxt.options.debug.watchers) {
          console.timeEnd("[nuxt] builder:parcel:watch");
        }
        nuxt.hook("close", () => subscription.unsubscribe());
      });
    }
    return true;
  } catch {
    logger.warn("Falling back to `chokidar-granular` as `@parcel/watcher` cannot be resolved in your project.");
    return false;
  }
}
async function bundle(nuxt) {
  try {
    const { bundle: bundle2 } = typeof nuxt.options.builder === "string" ? await loadBuilder(nuxt, nuxt.options.builder) : nuxt.options.builder;
    await bundle2(nuxt);
  } catch (error) {
    await nuxt.callHook("build:error", error);
    if (error.toString().includes("Cannot find module '@nuxt/webpack-builder'")) {
      throw new Error("Could not load `@nuxt/webpack-builder`. You may need to add it to your project dependencies, following the steps in `https://github.com/nuxt/framework/pull/2812`.");
    }
    throw error;
  }
}
async function loadBuilder(nuxt, builder) {
  try {
    return await importModule(builder, { url: [directoryToURL(nuxt.options.rootDir), new URL(import.meta.url)] });
  } catch {
    throw new Error(`Loading \`${builder}\` builder failed. You can read more about the nuxt \`builder\` option at: \`https://nuxt.com/docs/api/nuxt-config#builder\``);
  }
}
function resolvePathsToWatch(nuxt, opts = {}) {
  const pathsToWatch = /* @__PURE__ */ new Set();
  for (const layer of nuxt.options._layers) {
    const dir = layer.config.srcDir || layer.cwd;
    if (!dir || isIgnored(dir)) {
      continue;
    }
    pathsToWatch.add(dir.replace(/[^/]$/, "$&/"));
  }
  for (const pattern of nuxt.options.watch) {
    if (typeof pattern !== "string") {
      continue;
    }
    const path = opts?.parentDirectories ? join(dirname(resolve(nuxt.options.srcDir, pattern)), "") : resolve(nuxt.options.srcDir, pattern);
    let shouldAdd = true;
    for (const w of [...pathsToWatch]) {
      if (w.startsWith(path)) {
        pathsToWatch.delete(w);
      }
      if (path.startsWith(w)) {
        shouldAdd = false;
      }
    }
    if (shouldAdd) {
      pathsToWatch.add(path);
    }
  }
  return pathsToWatch;
}

export { loadNuxt as a, build as b, createNuxt as c, logger as l };
