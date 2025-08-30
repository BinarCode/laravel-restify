"use strict";Object.defineProperty(exports, "__esModule", {value: true}); function _nullishCoalesce(lhs, rhsFn) { if (lhs != null) { return lhs; } else { return rhsFn(); } } function _optionalChain(ops) { let lastAccessLHS = undefined; let value = ops[0]; let i = 1; while (i < ops.length) { const op = ops[i]; const fn = ops[i + 1]; i += 2; if ((op === 'optionalAccess' || op === 'optionalCall') && value == null) { return undefined; } if (op === 'access' || op === 'optionalAccess') { lastAccessLHS = value; value = fn(value); } else if (op === 'call' || op === 'optionalCall') { value = fn((...args) => value.call(lastAccessLHS, ...args)); lastAccessLHS = undefined; } } return value; } var _class;// src/options.ts
var _localpkg = require('local-pkg');

// src/core/utils.ts
var _scule = require('scule');
function warn(msg, type = "warn") {
  console[type](`\u26A0\uFE0F  [unplugin-vue-router]: ${msg}`);
}
function logTree(tree, log) {
  log(printTree(tree));
}
var MAX_LEVEL = 1e3;
function printTree(tree, level = 0, parentPre = "", treeStr = "") {
  if (typeof tree !== "object" || level >= MAX_LEVEL) return "";
  if (tree instanceof Map) {
    const total = tree.size;
    let index = 0;
    for (const [_key, child] of tree) {
      const hasNext = index++ < total - 1;
      const { children } = child;
      treeStr += `${`${parentPre}${hasNext ? "\u251C" : "\u2514"}${"\u2500" + (children.size > 0 ? "\u252C" : "")} `}${child}
`;
      if (children) {
        treeStr += printTree(
          children,
          level + 1,
          `${parentPre}${hasNext ? "\u2502" : " "} `
        );
      }
    }
  } else {
    const children = tree.children;
    treeStr = `${tree}
`;
    if (children) {
      treeStr += printTree(children, level + 1);
    }
  }
  return treeStr;
}
var isArray = Array.isArray;
function trimExtension(path, extensions) {
  for (const extension of extensions) {
    const lastDot = path.endsWith(extension) ? -extension.length : 0;
    if (lastDot < 0) {
      return path.slice(0, lastDot);
    }
  }
  return path;
}
function throttle(fn, wait, initialWait) {
  let pendingExecutionTimeout = null;
  let pendingExecution = false;
  let executionTimeout = null;
  return () => {
    if (pendingExecutionTimeout == null) {
      pendingExecutionTimeout = setTimeout(() => {
        pendingExecutionTimeout = null;
        if (pendingExecution) {
          pendingExecution = false;
          fn();
        }
      }, wait);
      executionTimeout = setTimeout(() => {
        executionTimeout = null;
        fn();
      }, initialWait);
    } else if (executionTimeout == null) {
      pendingExecution = true;
    }
  };
}
var LEADING_SLASH_RE = /^\//;
var TRAILING_SLASH_RE = /\/$/;
function joinPath(...paths) {
  let result = "";
  for (const path of paths) {
    result = result.replace(TRAILING_SLASH_RE, "") + // check path to avoid adding a trailing slash when joining an empty string
    (path && "/" + path.replace(LEADING_SLASH_RE, ""));
  }
  return result;
}
function paramToName({ paramName, modifier, isSplat }) {
  return `${isSplat ? "$" : ""}${paramName.charAt(0).toUpperCase() + paramName.slice(1)}${modifier}`;
}
function getPascalCaseRouteName(node) {
  if (_optionalChain([node, 'access', _ => _.parent, 'optionalAccess', _2 => _2.isRoot, 'call', _3 => _3()]) && node.value.pathSegment === "") return "Root";
  let name = node.value.subSegments.map((segment) => {
    if (typeof segment === "string") {
      return _scule.pascalCase.call(void 0, segment);
    }
    return paramToName(segment);
  }).join("");
  if (node.value.components.size && node.children.has("index")) {
    name += "Parent";
  }
  const parent = node.parent;
  return (parent && !parent.isRoot() ? getPascalCaseRouteName(parent).replace(/Parent$/, "") : "") + name;
}
function getFileBasedRouteName(node) {
  if (!node.parent) return "";
  return getFileBasedRouteName(node.parent) + "/" + (node.value.rawSegment === "index" ? "" : node.value.rawSegment);
}
function mergeRouteRecordOverride(a, b) {
  const merged = {};
  const keys = [
    .../* @__PURE__ */ new Set([
      ...Object.keys(a),
      ...Object.keys(b)
    ])
  ];
  for (const key of keys) {
    if (key === "alias") {
      const newAlias = [];
      merged[key] = newAlias.concat(a.alias || [], b.alias || []);
    } else if (key === "meta") {
      merged[key] = mergeDeep(a[key] || {}, b[key] || {});
    } else {
      merged[key] = _nullishCoalesce(b[key], () => ( a[key]));
    }
  }
  return merged;
}
function isObject(obj) {
  return obj && typeof obj === "object";
}
function mergeDeep(...objects) {
  return objects.reduce((prev, obj) => {
    Object.keys(obj).forEach((key) => {
      const pVal = prev[key];
      const oVal = obj[key];
      if (Array.isArray(pVal) && Array.isArray(oVal)) {
        prev[key] = pVal.concat(...oVal);
      } else if (isObject(pVal) && isObject(oVal)) {
        prev[key] = mergeDeep(pVal, oVal);
      } else {
        prev[key] = oVal;
      }
    });
    return prev;
  }, {});
}
function asRoutePath({
  src,
  path = "",
  extensions
}, filePath) {
  return trimExtension(
    typeof path === "string" ? (
      // add the path prefix if any
      path + // remove the absolute path to the pages folder
      filePath.slice(src.length + 1)
    ) : path(filePath),
    extensions
  );
}
function appendExtensionListToPattern(filePatterns, extensions) {
  const extensionPattern = extensions.length === 1 ? extensions[0] : `.{${extensions.map((extension) => extension.replace(".", "")).join(",")}}`;
  return Array.isArray(filePatterns) ? filePatterns.map((filePattern) => `${filePattern}${extensionPattern}`) : `${filePatterns}${extensionPattern}`;
}
var ImportsMap = (_class = class {
  // path -> import as -> import name
  // e.g map['vue-router']['myUseRouter'] = 'useRouter' -> import { useRouter as myUseRouter } from 'vue-router'
  __init() {this.map = /* @__PURE__ */ new Map()}
  constructor() {;_class.prototype.__init.call(this);
  }
  add(path, importEntry) {
    if (!this.map.has(path)) {
      this.map.set(path, /* @__PURE__ */ new Map());
    }
    const imports = this.map.get(path);
    if (typeof importEntry === "string") {
      imports.set(importEntry, importEntry);
    } else {
      imports.set(importEntry.as || importEntry.name, importEntry.name);
    }
    return this;
  }
  addDefault(path, as) {
    return this.add(path, { name: "default", as });
  }
  /**
   * Get the list of imports for the given path.
   *
   * @param path - the path to get the import list for
   * @returns the list of imports for the given path
   */
  getImportList(path) {
    if (!this.map.has(path)) return [];
    return Array.from(this.map.get(path)).map(([as, name]) => ({
      as: as || name,
      name
    }));
  }
  toString() {
    let importStatements = "";
    for (const [path, imports] of this.map) {
      if (!imports.size) continue;
      if (imports.size === 1) {
        const [[importName, maybeDefault]] = [...imports.entries()];
        if (maybeDefault === "default") {
          importStatements += `import ${importName} from '${path}'
`;
          continue;
        }
      }
      importStatements += `import { ${Array.from(imports).map(([as, name]) => as === name ? name : `${name} as ${as}`).join(", ")} } from '${path}'
`;
    }
    return importStatements;
  }
  get size() {
    return this.map.size;
  }
}, _class);

// src/options.ts
var _pathe = require('pathe');
function resolveOverridableOption(defaultValue, value) {
  return typeof value === "function" ? value(defaultValue) : _nullishCoalesce(value, () => ( defaultValue));
}
var DEFAULT_OPTIONS = {
  extensions: [".vue"],
  exclude: [],
  routesFolder: "src/pages",
  filePatterns: ["**/*"],
  routeBlockLang: "json5",
  getRouteName: getFileBasedRouteName,
  importMode: "async",
  root: process.cwd(),
  dts: _localpkg.isPackageExists.call(void 0, "typescript"),
  logs: false,
  _inspect: false,
  pathParser: {
    dotNesting: true
  },
  watch: !process.env.CI,
  experimental: {}
};
function normalizeRoutesFolderOption(routesFolder) {
  return (isArray(routesFolder) ? routesFolder : [routesFolder]).map(
    (routeOption) => (
      // normalizing here allows to have a better type for the resolved options
      normalizeRouteOption(
        typeof routeOption === "string" ? { src: routeOption } : routeOption
      )
    )
  );
}
function normalizeRouteOption(routeOption) {
  return {
    ...routeOption,
    // ensure filePatterns is always an array or a function
    filePatterns: routeOption.filePatterns ? typeof routeOption.filePatterns === "function" ? routeOption.filePatterns : isArray(routeOption.filePatterns) ? routeOption.filePatterns : [routeOption.filePatterns] : void 0,
    // same for exclude
    exclude: routeOption.exclude ? typeof routeOption.exclude === "function" ? routeOption.exclude : isArray(routeOption.exclude) ? routeOption.exclude : [routeOption.exclude] : void 0
  };
}
function resolveOptions(options) {
  const root = options.root || DEFAULT_OPTIONS.root;
  const routesFolder = normalizeRoutesFolderOption(
    options.routesFolder || DEFAULT_OPTIONS.routesFolder
  ).map((routeOption) => ({
    ...routeOption,
    src: _pathe.resolve.call(void 0, root, routeOption.src)
  }));
  const experimental = { ...options.experimental };
  if (experimental.autoExportsDataLoaders) {
    experimental.autoExportsDataLoaders = (Array.isArray(experimental.autoExportsDataLoaders) ? experimental.autoExportsDataLoaders : [experimental.autoExportsDataLoaders]).map((path) => _pathe.resolve.call(void 0, root, path));
  }
  if (options.extensions) {
    options.extensions = options.extensions.map((ext) => {
      if (!ext.startsWith(".")) {
        warn(`Invalid extension "${ext}". Extensions must start with a dot.`);
        return "." + ext;
      }
      return ext;
    }).sort((a, b) => b.length - a.length);
  }
  const filePatterns = options.filePatterns ? isArray(options.filePatterns) ? options.filePatterns : [options.filePatterns] : DEFAULT_OPTIONS.filePatterns;
  const exclude = options.exclude ? isArray(options.exclude) ? options.exclude : [options.exclude] : DEFAULT_OPTIONS.exclude;
  return {
    ...DEFAULT_OPTIONS,
    ...options,
    experimental,
    routesFolder,
    filePatterns,
    exclude
  };
}
function mergeAllExtensions(options) {
  const allExtensions = new Set(options.extensions);
  for (const routeOption of options.routesFolder) {
    if (routeOption.extensions) {
      const extensions = resolveOverridableOption(
        options.extensions,
        routeOption.extensions
      );
      for (const ext of extensions) {
        allExtensions.add(ext);
      }
    }
  }
  return Array.from(allExtensions.values());
}
















exports.warn = warn; exports.logTree = logTree; exports.throttle = throttle; exports.joinPath = joinPath; exports.getPascalCaseRouteName = getPascalCaseRouteName; exports.getFileBasedRouteName = getFileBasedRouteName; exports.mergeRouteRecordOverride = mergeRouteRecordOverride; exports.asRoutePath = asRoutePath; exports.appendExtensionListToPattern = appendExtensionListToPattern; exports.ImportsMap = ImportsMap; exports.resolveOverridableOption = resolveOverridableOption; exports.DEFAULT_OPTIONS = DEFAULT_OPTIONS; exports.resolveOptions = resolveOptions; exports.mergeAllExtensions = mergeAllExtensions;
