import {
  ImportsMap,
  appendExtensionListToPattern,
  asRoutePath,
  joinPath,
  logTree,
  mergeAllExtensions,
  mergeRouteRecordOverride,
  resolveOptions,
  throttle,
  warn
} from "./chunk-GI4LFDHF.js";

// src/index.ts
import { createUnplugin } from "unplugin";

// src/core/treeNodeValue.ts
var EDITS_OVERRIDE_NAME = "@@edits";
var _TreeNodeValueBase = class {
  /**
   * flag based on the type of the segment
   */
  _type;
  parent;
  /**
   * segment as defined by the file structure e.g. keeps the `index` name, `(group-name)`
   */
  rawSegment;
  /**
   * transformed version of the segment into a vue-router path. e.g. `'index'` becomes `''` and `[param]` becomes
   * `:param`, `prefix-[param]-end` becomes `prefix-:param-end`.
   */
  pathSegment;
  /**
   * Array of sub segments. This is usually one single elements but can have more for paths like `prefix-[param]-end.vue`
   */
  subSegments;
  /**
   * Overrides defined by each file. The map is necessary to handle named views.
   */
  _overrides = /* @__PURE__ */ new Map();
  // TODO: cache the overrides generation
  /**
   * View name (Vue Router feature) mapped to their corresponding file. By default, the view name is `default` unless
   * specified with a `@` e.g. `index@aux.vue` will have a view name of `aux`.
   */
  components = /* @__PURE__ */ new Map();
  constructor(rawSegment, parent, pathSegment = rawSegment, subSegments = [pathSegment]) {
    this._type = 0;
    this.rawSegment = rawSegment;
    this.pathSegment = pathSegment;
    this.subSegments = subSegments;
    this.parent = parent;
  }
  /**
   * fullPath of the node based on parent nodes
   */
  get path() {
    const parentPath = this.parent?.path;
    const pathSegment = this.overrides.path ?? this.pathSegment;
    return (!parentPath || parentPath === "/") && pathSegment === "" ? "/" : joinPath(parentPath || "", pathSegment);
  }
  toString() {
    return this.pathSegment || "<index>";
  }
  isParam() {
    return !!(this._type & 2 /* param */);
  }
  isStatic() {
    return this._type === 0 /* static */;
  }
  isGroup() {
    return this._type === 1 /* group */;
  }
  get overrides() {
    return [...this._overrides.entries()].sort(
      ([nameA], [nameB]) => nameA === nameB ? 0 : (
        // EDITS_OVERRIDE_NAME should always be last
        nameA !== EDITS_OVERRIDE_NAME && (nameA < nameB || nameB === EDITS_OVERRIDE_NAME) ? -1 : 1
      )
    ).reduce((acc, [_path, routeBlock]) => {
      return mergeRouteRecordOverride(acc, routeBlock);
    }, {});
  }
  setOverride(filePath, routeBlock) {
    this._overrides.set(filePath, routeBlock || {});
  }
  /**
   * Remove all overrides for a given key.
   *
   * @param key - key to remove from the override, e.g. path, name, etc
   */
  removeOverride(key) {
    for (const [_filePath, routeBlock] of this._overrides) {
      delete routeBlock[key];
    }
  }
  /**
   * Add an override to the current node by merging with the existing values.
   *
   * @param filePath - The file path to add to the override
   * @param routeBlock - The route block to add to the override
   */
  mergeOverride(filePath, routeBlock) {
    const existing = this._overrides.get(filePath) || {};
    this._overrides.set(
      filePath,
      mergeRouteRecordOverride(existing, routeBlock)
    );
  }
  /**
   * Add an override to the current node using the special file path `@@edits` that makes this added at build time.
   *
   * @param routeBlock -  The route block to add to the override
   */
  addEditOverride(routeBlock) {
    return this.mergeOverride(EDITS_OVERRIDE_NAME, routeBlock);
  }
  /**
   * Set a specific value in the _edits_ override.
   *
   * @param key - key to set in the override, e.g. path, name, etc
   * @param value - value to set in the override
   */
  setEditOverride(key, value) {
    if (!this._overrides.has(EDITS_OVERRIDE_NAME)) {
      this._overrides.set(EDITS_OVERRIDE_NAME, {});
    }
    const existing = this._overrides.get(EDITS_OVERRIDE_NAME);
    existing[key] = value;
  }
};
var TreeNodeValueStatic = class extends _TreeNodeValueBase {
  _type = 0 /* static */;
  constructor(rawSegment, parent, pathSegment = rawSegment) {
    super(rawSegment, parent, pathSegment);
  }
};
var TreeNodeValueGroup = class extends _TreeNodeValueBase {
  _type = 1 /* group */;
  groupName;
  constructor(rawSegment, parent, pathSegment, groupName) {
    super(rawSegment, parent, pathSegment);
    this.groupName = groupName;
  }
};
var TreeNodeValueParam = class extends _TreeNodeValueBase {
  params;
  _type = 2 /* param */;
  constructor(rawSegment, parent, params, pathSegment, subSegments) {
    super(rawSegment, parent, pathSegment, subSegments);
    this.params = params;
  }
};
function resolveTreeNodeValueOptions(options) {
  return {
    format: "file",
    dotNesting: true,
    ...options
  };
}
function createTreeNodeValue(segment, parent, opts = {}) {
  if (!segment || segment === "index") {
    return new TreeNodeValueStatic(segment, parent, "");
  }
  const options = resolveTreeNodeValueOptions(opts);
  const openingPar = segment.indexOf("(");
  if (options.format === "file" && openingPar >= 0) {
    let groupName;
    const closingPar = segment.lastIndexOf(")");
    if (closingPar < 0 || closingPar < openingPar) {
      warn(
        `Segment "${segment}" is missing the closing ")". It will be treated as a static segment.`
      );
      return new TreeNodeValueStatic(segment, parent, segment);
    }
    groupName = segment.slice(openingPar + 1, closingPar);
    const before = segment.slice(0, openingPar);
    const after = segment.slice(closingPar + 1);
    if (!before && !after) {
      return new TreeNodeValueGroup(segment, parent, "", groupName);
    }
  }
  const [pathSegment, params, subSegments] = options.format === "path" ? parseRawPathSegment(segment) : (
    // by default, we use the file format
    parseFileSegment(segment, options)
  );
  if (params.length) {
    return new TreeNodeValueParam(
      segment,
      parent,
      params,
      pathSegment,
      subSegments
    );
  }
  return new TreeNodeValueStatic(segment, parent, pathSegment);
}
var IS_VARIABLE_CHAR_RE = /[0-9a-zA-Z_]/;
function parseFileSegment(segment, { dotNesting = true } = {}) {
  let buffer = "";
  let state = 0 /* static */;
  const params = [];
  let pathSegment = "";
  const subSegments = [];
  let currentTreeRouteParam = createEmptyRouteParam();
  let pos = 0;
  let c;
  function consumeBuffer() {
    if (state === 0 /* static */) {
      pathSegment += buffer;
      subSegments.push(buffer);
    } else if (state === 3 /* modifier */) {
      currentTreeRouteParam.paramName = buffer;
      currentTreeRouteParam.modifier = currentTreeRouteParam.optional ? currentTreeRouteParam.repeatable ? "*" : "?" : currentTreeRouteParam.repeatable ? "+" : "";
      buffer = "";
      pathSegment += `:${currentTreeRouteParam.paramName}${currentTreeRouteParam.isSplat ? "(.*)" : (
        // Only append () if necessary
        pos < segment.length - 1 && IS_VARIABLE_CHAR_RE.test(segment[pos + 1]) ? "()" : (
          // allow routes like /[id]_suffix to make suffix static and not part of the param
          ""
        )
      )}${currentTreeRouteParam.modifier}`;
      params.push(currentTreeRouteParam);
      subSegments.push(currentTreeRouteParam);
      currentTreeRouteParam = createEmptyRouteParam();
    }
    buffer = "";
  }
  for (pos = 0; pos < segment.length; pos++) {
    c = segment[pos];
    if (state === 0 /* static */) {
      if (c === "[") {
        consumeBuffer();
        state = 1 /* paramOptional */;
      } else {
        buffer += dotNesting && c === "." ? "/" : c;
      }
    } else if (state === 1 /* paramOptional */) {
      if (c === "[") {
        currentTreeRouteParam.optional = true;
      } else if (c === ".") {
        currentTreeRouteParam.isSplat = true;
        pos += 2;
      } else {
        buffer += c;
      }
      state = 2 /* param */;
    } else if (state === 2 /* param */) {
      if (c === "]") {
        if (currentTreeRouteParam.optional) {
          pos++;
        }
        state = 3 /* modifier */;
      } else if (c === ".") {
        currentTreeRouteParam.isSplat = true;
        pos += 2;
      } else {
        buffer += c;
      }
    } else if (state === 3 /* modifier */) {
      if (c === "+") {
        currentTreeRouteParam.repeatable = true;
      } else {
        pos--;
      }
      consumeBuffer();
      state = 0 /* static */;
    }
  }
  if (state === 2 /* param */ || state === 1 /* paramOptional */) {
    throw new Error(`Invalid segment: "${segment}"`);
  }
  if (buffer) {
    consumeBuffer();
  }
  return [pathSegment, params, subSegments];
}
var IS_MODIFIER_RE = /[+*?]/;
function parseRawPathSegment(segment) {
  let buffer = "";
  let state = 0 /* static */;
  const params = [];
  const subSegments = [];
  let currentTreeRouteParam = createEmptyRouteParam();
  let pos = 0;
  let c;
  function consumeBuffer() {
    if (state === 0 /* static */) {
      subSegments.push(buffer);
    } else if (state === 1 /* param */ || state === 2 /* regexp */ || state === 3 /* modifier */) {
      subSegments.push(currentTreeRouteParam);
      params.push(currentTreeRouteParam);
      currentTreeRouteParam = createEmptyRouteParam();
    }
    buffer = "";
  }
  for (pos = 0; pos < segment.length; pos++) {
    c = segment[pos];
    if (c === "\\") {
      pos++;
      buffer += segment[pos];
      continue;
    }
    if (state === 0 /* static */) {
      if (c === ":") {
        consumeBuffer();
        state = 1 /* param */;
      } else {
        buffer += c;
      }
    } else if (state === 1 /* param */) {
      if (c === "(") {
        currentTreeRouteParam.paramName = buffer;
        buffer = "";
        state = 2 /* regexp */;
      } else if (IS_MODIFIER_RE.test(c)) {
        currentTreeRouteParam.modifier = c;
        currentTreeRouteParam.optional = c === "?" || c === "*";
        currentTreeRouteParam.repeatable = c === "+" || c === "*";
        consumeBuffer();
        state = 0 /* static */;
      } else if (IS_VARIABLE_CHAR_RE.test(c)) {
        buffer += c;
        currentTreeRouteParam.paramName = buffer;
      } else {
        currentTreeRouteParam.paramName = buffer;
        consumeBuffer();
        pos--;
        state = 0 /* static */;
      }
    } else if (state === 2 /* regexp */) {
      if (c === ")") {
        if (buffer === ".*") {
          currentTreeRouteParam.isSplat = true;
        }
        state = 3 /* modifier */;
      } else {
        buffer += c;
      }
    } else if (state === 3 /* modifier */) {
      if (IS_MODIFIER_RE.test(c)) {
        currentTreeRouteParam.modifier = c;
        currentTreeRouteParam.optional = c === "?" || c === "*";
        currentTreeRouteParam.repeatable = c === "+" || c === "*";
      } else {
        pos--;
      }
      consumeBuffer();
      state = 0 /* static */;
    }
  }
  if (state === 2 /* regexp */) {
    throw new Error(`Invalid segment: "${segment}"`);
  }
  if (buffer || // an empty finished regexp enters this state but must also be consumed
  state === 3 /* modifier */) {
    consumeBuffer();
  }
  return [
    // here the segment is already a valid path segment
    segment,
    params,
    subSegments
  ];
}
function createEmptyRouteParam() {
  return {
    paramName: "",
    modifier: "",
    optional: false,
    repeatable: false,
    isSplat: false
  };
}

// src/core/tree.ts
var TreeNode = class _TreeNode {
  /**
   * value of the node
   */
  value;
  /**
   * children of the node
   */
  children = /* @__PURE__ */ new Map();
  /**
   * Parent node.
   */
  parent;
  /**
   * Plugin options taken into account by the tree.
   */
  options;
  // FIXME: refactor this code. It currently helps to keep track if a page has at least one component with `definePage()` but it doesn't tell which. It should keep track of which one while still caching the result per file.
  /**
   * Should this page import the page info
   */
  hasDefinePage = false;
  /**
   * Creates a new tree node.
   *
   * @param options - TreeNodeOptions shared by all nodes
   * @param pathSegment - path segment of this node e.g. `users` or `:id`
   * @param parent
   */
  constructor(options, pathSegment, parent) {
    this.options = options;
    this.parent = parent;
    this.value = createTreeNodeValue(
      pathSegment,
      parent?.value,
      options.treeNodeOptions || options.pathParser
    );
  }
  /**
   * Adds a path to the tree. `path` cannot start with a `/`.
   *
   * @param path - path segment to insert. **It shouldn't contain the file extension**
   * @param filePath - file path, must be a file (not a folder)
   */
  insert(path, filePath) {
    const { tail, segment, viewName } = splitFilePath(path);
    if (!this.children.has(segment)) {
      this.children.set(segment, new _TreeNode(this.options, segment, this));
    }
    const child = this.children.get(segment);
    if (!tail) {
      child.value.components.set(viewName, filePath);
    } else {
      return child.insert(tail, filePath);
    }
    return child;
  }
  /**
   * Adds a path that has already been parsed to the tree. `path` cannot start with a `/`. This method is similar to
   * `insert` but the path argument should be already parsed. e.g. `users/:id` for a file named `users/[id].vue`.
   *
   * @param path - path segment to insert, already parsed (e.g. users/:id)
   * @param filePath - file path, defaults to path for convenience and testing
   */
  insertParsedPath(path, filePath = path) {
    const isComponent = true;
    const node = new _TreeNode(
      {
        ...this.options,
        // force the format to raw
        treeNodeOptions: {
          ...this.options.pathParser,
          format: "path"
        }
      },
      path,
      this
    );
    this.children.set(path, node);
    if (isComponent) {
      node.value.components.set("default", filePath);
    }
    return node;
  }
  /**
   * Saves a custom route block for a specific file path. The file path is used as a key. Some special file paths will
   * have a lower or higher priority.
   *
   * @param filePath - file path where the custom block is located
   * @param routeBlock - custom block to set
   */
  setCustomRouteBlock(filePath, routeBlock) {
    this.value.setOverride(filePath, routeBlock);
  }
  getSortedChildren() {
    return Array.from(this.children.values()).sort(
      (a, b) => a.path.localeCompare(b.path)
    );
  }
  /**
   * Delete and detach itself from the tree.
   */
  delete() {
    if (!this.parent) {
      throw new Error("Cannot delete the root node.");
    }
    this.parent.children.delete(this.value.rawSegment);
    this.parent = void 0;
  }
  /**
   * Remove a route from the tree. The path shouldn't start with a `/` but it can be a nested one. e.g. `foo/bar`.
   * The `path` should be relative to the page folder.
   *
   * @param path - path segment of the file
   */
  remove(path) {
    const { tail, segment, viewName } = splitFilePath(path);
    const child = this.children.get(segment);
    if (!child) {
      throw new Error(
        `Cannot Delete "${path}". "${segment}" not found at "${this.path}".`
      );
    }
    if (tail) {
      child.remove(tail);
      if (child.children.size === 0 && child.value.components.size === 0) {
        this.children.delete(segment);
      }
    } else {
      child.value.components.delete(viewName);
      if (child.children.size === 0 && child.value.components.size === 0) {
        this.children.delete(segment);
      }
    }
  }
  /**
   * Returns the route path of the node without parent paths. If the path was overridden, it returns the override.
   */
  get path() {
    return this.value.overrides.path ?? (this.parent?.isRoot() ? "/" : "") + this.value.pathSegment;
  }
  /**
   * Returns the route path of the node including parent paths.
   */
  get fullPath() {
    return this.value.overrides.path ?? this.value.path;
  }
  /**
   * Returns the route name of the node. If the name was overridden, it returns the override.
   */
  get name() {
    return this.value.overrides.name || this.options.getRouteName(this);
  }
  /**
   * Returns the meta property as an object.
   */
  get metaAsObject() {
    return {
      ...this.value.overrides.meta
    };
  }
  /**
   * Returns the JSON string of the meta object of the node. If the meta was overridden, it returns the override. If
   * there is no override, it returns an empty string.
   */
  get meta() {
    const overrideMeta = this.metaAsObject;
    return Object.keys(overrideMeta).length > 0 ? JSON.stringify(overrideMeta, null, 2) : "";
  }
  get params() {
    const params = this.value.isParam() ? [...this.value.params] : [];
    let node = this.parent;
    while (node) {
      if (node.value.isParam()) {
        params.unshift(...node.value.params);
      }
      node = node.parent;
    }
    return params;
  }
  /**
   * Returns wether this tree node is the root node of the tree.
   *
   * @returns true if the node is the root node
   */
  isRoot() {
    return !this.parent && this.value.path === "/" && !this.value.components.size;
  }
  toString() {
    return `${this.value}${// either we have multiple names
    this.value.components.size > 1 || // or we have one name and it's not default
    this.value.components.size === 1 && !this.value.components.get("default") ? ` \u2388(${Array.from(this.value.components.keys()).join(", ")})` : ""}${this.hasDefinePage ? " \u2691 definePage()" : ""}`;
  }
};
var PrefixTree = class extends TreeNode {
  map = /* @__PURE__ */ new Map();
  constructor(options) {
    super(options, "");
  }
  insert(path, filePath) {
    const node = super.insert(path, filePath);
    this.map.set(filePath, node);
    return node;
  }
  /**
   * Returns the tree node of the given file path.
   *
   * @param filePath - file path of the tree node to get
   */
  getChild(filePath) {
    return this.map.get(filePath);
  }
  /**
   * Removes the tree node of the given file path.
   *
   * @param filePath - file path of the tree node to remove
   */
  removeChild(filePath) {
    if (this.map.has(filePath)) {
      this.map.get(filePath).delete();
      this.map.delete(filePath);
    }
  }
};
function splitFilePath(filePath) {
  const slashPos = filePath.indexOf("/");
  let head = slashPos < 0 ? filePath : filePath.slice(0, slashPos);
  const tail = slashPos < 0 ? "" : filePath.slice(slashPos + 1);
  let segment = head;
  let viewName = "default";
  const namedSeparatorPos = segment.indexOf("@");
  if (namedSeparatorPos > 0) {
    viewName = segment.slice(namedSeparatorPos + 1);
    segment = segment.slice(0, namedSeparatorPos);
  }
  return {
    segment,
    tail,
    viewName
  };
}

// src/core/context.ts
import { promises as fs } from "fs";

// src/codegen/generateRouteParams.ts
function generateRouteParams(node, isRaw) {
  const nodeParams = node.params;
  return nodeParams.length > 0 ? `{ ${nodeParams.map(
    (param) => `${param.paramName}${param.optional ? "?" : ""}: ` + (param.modifier === "+" ? `ParamValueOneOrMore<${isRaw}>` : param.modifier === "*" ? `ParamValueZeroOrMore<${isRaw}>` : param.modifier === "?" ? `ParamValueZeroOrOne<${isRaw}>` : `ParamValue<${isRaw}>`)
  ).join(", ")} }` : (
    // no params allowed
    "Record<never, never>"
  );
}

// src/codegen/generateRouteMap.ts
function generateRouteNamedMap(node) {
  if (node.isRoot()) {
    return `export interface RouteNamedMap {
${node.getSortedChildren().map(generateRouteNamedMap).join("")}}`;
  }
  return (
    // if the node has a filePath, it's a component, it has a routeName and it should be referenced in the RouteNamedMap
    // otherwise it should be skipped to avoid navigating to a route that doesn't render anything
    (node.value.components.size ? `  '${node.name}': ${generateRouteRecordInfo(node)},
` : "") + (node.children.size > 0 ? node.getSortedChildren().map(generateRouteNamedMap).join("\n") : "")
  );
}
function generateRouteRecordInfo(node) {
  return `RouteRecordInfo<'${node.name}', '${node.fullPath}', ${generateRouteParams(node, true)}, ${generateRouteParams(node, false)}>`;
}

// src/core/moduleConstants.ts
var MODULE_VUE_ROUTER_AUTO = "vue-router/auto";
var MODULE_ROUTES_PATH = `${MODULE_VUE_ROUTER_AUTO}-routes`;
var time = Date.now();
var ROUTES_LAST_LOAD_TIME = {
  get value() {
    return time;
  },
  update(when = Date.now()) {
    time = when;
  }
};
var VIRTUAL_PREFIX = "/__";
var ROUTE_BLOCK_ID = `${VIRTUAL_PREFIX}/vue-router/auto/route-block`;
function getVirtualId(id) {
  return id.startsWith(VIRTUAL_PREFIX) ? id.slice(VIRTUAL_PREFIX.length) : null;
}
var routeBlockQueryRE = /\?vue&type=route/;
function asVirtualId(id) {
  return VIRTUAL_PREFIX + id;
}

// src/codegen/generateRouteRecords.ts
function generateRouteRecord(node, options, importsMap, indent = 0) {
  if (node.isRoot()) {
    return `[
${node.getSortedChildren().map((child) => generateRouteRecord(child, options, importsMap, indent + 1)).join(",\n")}
]`;
  }
  const definePageDataList = [];
  if (node.hasDefinePage) {
    for (const [name, filePath] of node.value.components) {
      const pageDataImport = `_definePage_${name}_${importsMap.size}`;
      definePageDataList.push(pageDataImport);
      importsMap.addDefault(
        // TODO: apply the language used in the sfc
        `${filePath}?definePage&vue&lang.tsx`,
        pageDataImport
      );
    }
    if (definePageDataList.length > 0) {
      indent++;
    }
  }
  const startIndent = " ".repeat(indent * 2);
  const indentStr = " ".repeat((indent + 1) * 2);
  const overrides = node.value.overrides;
  const routeRecord = `${startIndent}{
${indentStr}path: '${node.path}',
${indentStr}${node.value.components.size ? `name: '${node.name}',` : `/* internal name: '${node.name}' */`}
${// component
  indentStr}${node.value.components.size ? generateRouteRecordComponent(
    node,
    indentStr,
    options.importMode,
    importsMap
  ) : "/* no component */"}
${overrides.props != null ? indentStr + `props: ${overrides.props},
` : ""}${overrides.alias != null ? indentStr + `alias: ${JSON.stringify(overrides.alias)},
` : ""}${// children
  indentStr}${node.children.size > 0 ? `children: [
${node.getSortedChildren().map((child) => generateRouteRecord(child, options, importsMap, indent + 2)).join(",\n")}
${indentStr}],` : "/* no children */"}${formatMeta(node, indentStr)}
${startIndent}}`;
  if (definePageDataList.length > 0) {
    const mergeCallIndent = startIndent.slice(2);
    importsMap.add("unplugin-vue-router/runtime", "_mergeRouteRecord");
    return `${mergeCallIndent}_mergeRouteRecord(
${routeRecord},
${definePageDataList.map((s) => startIndent + s).join(",\n")}
${mergeCallIndent})`;
  }
  return routeRecord;
}
function generateRouteRecordComponent(node, indentStr, importMode, importsMap) {
  const files = Array.from(node.value.components);
  const isDefaultExport = files.length === 1 && files[0][0] === "default";
  return isDefaultExport ? `component: ${generatePageImport(files[0][1], importMode, importsMap)},` : (
    // files has at least one entry
    `components: {
${files.map(
      ([key, path]) => `${indentStr + "  "}'${key}': ${generatePageImport(
        path,
        importMode,
        importsMap
      )}`
    ).join(",\n")}
${indentStr}},`
  );
}
function generatePageImport(filepath, importMode, importsMap) {
  const mode = typeof importMode === "function" ? importMode(filepath) : importMode;
  if (mode === "async") {
    return `() => import('${filepath}')`;
  }
  const existingEntry = importsMap.getImportList(filepath).find((entry) => entry.name === "default");
  if (existingEntry) {
    return existingEntry.as;
  }
  const importName = `_page_${importsMap.size}`;
  importsMap.addDefault(filepath, importName);
  return importName;
}
function formatMeta(node, indent) {
  const meta = node.meta;
  const formatted = meta && meta.split("\n").map((line) => indent + line).join("\n");
  return formatted ? "\n" + indent + "meta: " + formatted.trimStart() : "";
}

// src/core/context.ts
import fg from "fast-glob";
import { dirname, relative, resolve as resolve2 } from "pathe";

// src/core/customBlock.ts
import { parse } from "@vue/compiler-sfc";
import JSON5 from "json5";
import { parse as YAMLParser } from "yaml";
function getRouteBlock(path, content, options) {
  const parsedSFC = parse(content, { pad: "space" }).descriptor;
  const blockStr = parsedSFC?.customBlocks.find((b) => b.type === "route");
  if (!blockStr) return;
  let result = parseCustomBlock(blockStr, path, options);
  if (result) {
    if (result.path != null && !result.path.startsWith("/")) {
      warn(`Overridden path must start with "/". Found in "${path}".`);
    }
  }
  return result;
}
function parseCustomBlock(block, filePath, options) {
  const lang = block.lang ?? options.routeBlockLang;
  if (lang === "json5") {
    try {
      return JSON5.parse(block.content);
    } catch (err) {
      warn(
        `Invalid JSON5 format of <${block.type}> content in ${filePath}
${err.message}`
      );
    }
  } else if (lang === "json") {
    try {
      return JSON.parse(block.content);
    } catch (err) {
      warn(
        `Invalid JSON format of <${block.type}> content in ${filePath}
${err.message}`
      );
    }
  } else if (lang === "yaml" || lang === "yml") {
    try {
      return YAMLParser(block.content);
    } catch (err) {
      warn(
        `Invalid YAML format of <${block.type}> content in ${filePath}
${err.message}`
      );
    }
  } else {
    warn(
      `Language "${lang}" for <${block.type}> is not supported. Supported languages are: json5, json, yaml, yml. Found in in ${filePath}.`
    );
  }
}

// src/core/RoutesFolderWatcher.ts
import { watch as fsWatch } from "chokidar";
import micromatch from "micromatch";
import { resolve } from "pathe";
var RoutesFolderWatcher = class {
  src;
  path;
  extensions;
  filePatterns;
  exclude;
  watcher;
  constructor(folderOptions) {
    this.src = folderOptions.src;
    this.path = folderOptions.path;
    this.exclude = folderOptions.exclude;
    this.extensions = folderOptions.extensions;
    this.filePatterns = folderOptions.filePatterns;
    this.watcher = fsWatch(".", {
      cwd: this.src,
      ignoreInitial: true,
      ignorePermissionErrors: true,
      ignored: (filepath, stats) => {
        if (stats?.isDirectory()) {
          return false;
        }
        const isMatch = micromatch.isMatch(filepath, this.filePatterns, {
          cwd: this.src,
          ignore: this.exclude
        });
        return !isMatch;
      }
      // TODO: allow user options
    });
  }
  on(event, handler) {
    this.watcher.on(event, (filePath) => {
      if (this.extensions.every((extension) => !filePath.endsWith(extension))) {
        return;
      }
      filePath = resolve(this.src, filePath);
      handler({
        filePath,
        routePath: asRoutePath(
          {
            src: this.src,
            path: this.path,
            extensions: this.extensions
          },
          filePath
        )
      });
    });
    return this;
  }
  close() {
    this.watcher.close();
  }
};
function resolveFolderOptions(globalOptions, folderOptions) {
  const extensions = overrideOption(
    globalOptions.extensions,
    folderOptions.extensions
  );
  const filePatterns = overrideOption(
    globalOptions.filePatterns,
    folderOptions.filePatterns
  );
  return {
    src: folderOptions.src,
    pattern: appendExtensionListToPattern(
      filePatterns,
      // also override the extensions if the folder has a custom extensions
      extensions
    ),
    path: folderOptions.path || "",
    extensions,
    filePatterns,
    exclude: overrideOption(globalOptions.exclude, folderOptions.exclude).map(
      (p) => p.startsWith("**") ? p : resolve(p)
    )
  };
}
function overrideOption(existing, newValue) {
  const asArray = typeof existing === "string" ? [existing] : existing;
  if (typeof newValue === "function") {
    return newValue(asArray);
  }
  if (typeof newValue !== "undefined") {
    return typeof newValue === "string" ? [newValue] : newValue;
  }
  return asArray;
}

// src/utils/index.ts
var ts = String.raw;

// src/codegen/generateDTS.ts
function generateDTS({
  routesModule,
  routeNamedMap
}) {
  return ts`
/* eslint-disable */
/* prettier-ignore */
// @ts-nocheck
// Generated by unplugin-vue-router. ‼️ DO NOT MODIFY THIS FILE ‼️
// It's recommended to commit this file.
// Make sure to add this file to your tsconfig.json file as an "includes" or "files" entry.

declare module '${routesModule}' {
  import type {
    RouteRecordInfo,
    ParamValue,
    ParamValueOneOrMore,
    ParamValueZeroOrMore,
    ParamValueZeroOrOne,
  } from 'vue-router'

  /**
   * Route name map generated by unplugin-vue-router
   */
${routeNamedMap.split("\n").filter((line) => line.length !== 0).map((line) => "  " + line).join("\n")}
}
`.trimStart();
}

// src/codegen/vueRouterModule.ts
function generateVueRouterProxy(_routesModule, _options, { addPiniaColada }) {
  return ts`
import { createRouter as _createRouter } from 'vue-router'

export * from 'vue-router'
export { definePage } from 'unplugin-vue-router/runtime'
export {
  DataLoaderPlugin,
  NavigationResult,
} from 'unplugin-vue-router/data-loaders'

export * from 'unplugin-vue-router/data-loaders/basic'
${addPiniaColada ? "export * from 'unplugin-vue-router/data-loaders/pinia-colada'" : ""}

export function createRouter(options) {
  const { extendRoutes, routes } = options
  if (extendRoutes) {
    console.warn('"extendRoutes()" is deprecated, please modify the routes directly. See https://uvr.esm.is/guide/extending-routes.html#extending-routes-at-runtime for an alternative.')
  }
  // use Object.assign for better browser support
  const router = _createRouter(Object.assign(
    options,
    { routes: typeof extendRoutes === 'function' ? (extendRoutes(routes) || routes) : routes },
  ))

  return router
}
`.trimStart();
}

// src/core/definePage.ts
import {
  generateTransform,
  isCallOf,
  parseSFC,
  MagicString,
  checkInvalidScopeReference
} from "@vue-macros/common";
import { walkAST } from "ast-walker-scope";
import { findStaticImports, parseStaticImport } from "mlly";
var MACRO_DEFINE_PAGE = "definePage";
var MACRO_DEFINE_PAGE_QUERY = /[?&]definePage\b/;
function definePageTransform({
  code,
  id
}) {
  const isExtractingDefinePage = MACRO_DEFINE_PAGE_QUERY.test(id);
  if (!code.includes(MACRO_DEFINE_PAGE)) {
    return isExtractingDefinePage ? "export default {}" : void 0;
  }
  const sfc = parseSFC(code, id);
  if (!sfc.scriptSetup) return;
  const { scriptSetup, getSetupAst } = sfc;
  const setupAst = getSetupAst();
  const definePageNodes = (setupAst?.body || []).map((node) => {
    if (node.type === "ExpressionStatement") node = node.expression;
    return isCallOf(node, MACRO_DEFINE_PAGE) ? node : null;
  }).filter((node) => !!node);
  if (!definePageNodes.length) {
    return isExtractingDefinePage ? (
      // e.g. index.vue?definePage that contains a commented `definePage()
      "export default {}"
    ) : (
      // e.g. index.vue that contains a commented `definePage()
      null
    );
  } else if (definePageNodes.length > 1) {
    throw new SyntaxError(`duplicate definePage() call`);
  }
  const definePageNode = definePageNodes[0];
  const setupOffset = scriptSetup.loc.start.offset;
  if (isExtractingDefinePage) {
    const s = new MagicString(code);
    const routeRecord = definePageNode.arguments[0];
    if (!routeRecord) {
      throw new SyntaxError(
        `[${id}]: definePage() expects an object expression as its only argument`
      );
    }
    const scriptBindings = setupAst?.body ? getIdentifiers(setupAst.body) : [];
    checkInvalidScopeReference(routeRecord, MACRO_DEFINE_PAGE, scriptBindings);
    s.remove(setupOffset + routeRecord.end, code.length);
    s.remove(0, setupOffset + routeRecord.start);
    s.prepend(`export default `);
    const staticImports = findStaticImports(code);
    const usedIds = /* @__PURE__ */ new Set();
    const localIds = /* @__PURE__ */ new Set();
    walkAST(routeRecord, {
      enter(node) {
        if (this.parent?.type === "ObjectProperty" && this.parent.key === node && // still track computed keys [a + b]: 1
        !this.parent.computed && node.type === "Identifier") {
          this.skip();
        } else if (
          // filter out things like 'log' in console.log
          this.parent?.type === "MemberExpression" && this.parent.property === node && !this.parent.computed && node.type === "Identifier"
        ) {
          this.skip();
        } else if (node.type === "TSTypeAnnotation") {
          this.skip();
        } else if (node.type === "Identifier" && !localIds.has(node.name)) {
          usedIds.add(node.name);
        } else if ("scopeIds" in node && node.scopeIds instanceof Set) {
          for (const id2 of node.scopeIds) {
            localIds.add(id2);
          }
        }
      },
      leave(node) {
        if ("scopeIds" in node && node.scopeIds instanceof Set) {
          for (const id2 of node.scopeIds) {
            localIds.delete(id2);
          }
        }
      }
    });
    for (const imp of staticImports) {
      const importCode = generateFilteredImportStatement(
        parseStaticImport(imp),
        usedIds
      );
      if (importCode) {
        s.prepend(importCode + "\n");
      }
    }
    return generateTransform(s, id);
  } else {
    const s = new MagicString(code);
    s.remove(
      setupOffset + definePageNode.start,
      setupOffset + definePageNode.end
    );
    return generateTransform(s, id);
  }
}
function extractDefinePageNameAndPath(sfcCode, id) {
  if (!sfcCode.includes(MACRO_DEFINE_PAGE)) return;
  const sfc = parseSFC(sfcCode, id);
  if (!sfc.scriptSetup) return;
  const { getSetupAst } = sfc;
  const setupAst = getSetupAst();
  const definePageNodes = (setupAst?.body ?? []).map((node) => {
    if (node.type === "ExpressionStatement") node = node.expression;
    return isCallOf(node, MACRO_DEFINE_PAGE) ? node : null;
  }).filter((node) => !!node);
  if (!definePageNodes.length) {
    return;
  } else if (definePageNodes.length > 1) {
    throw new SyntaxError(`duplicate definePage() call`);
  }
  const definePageNode = definePageNodes[0];
  const routeRecord = definePageNode.arguments[0];
  if (!routeRecord) {
    throw new SyntaxError(
      `[${id}]: definePage() expects an object expression as its only argument`
    );
  }
  if (routeRecord.type !== "ObjectExpression") {
    throw new SyntaxError(
      `[${id}]: definePage() expects an object expression as its only argument`
    );
  }
  const routeInfo = {};
  for (const prop of routeRecord.properties) {
    if (prop.type === "ObjectProperty" && prop.key.type === "Identifier") {
      if (prop.key.name === "name") {
        if (prop.value.type !== "StringLiteral") {
          warn(`route name must be a string literal. Found in "${id}".`);
        } else {
          routeInfo.name = prop.value.value;
        }
      } else if (prop.key.name === "path") {
        if (prop.value.type !== "StringLiteral") {
          warn(`route path must be a string literal. Found in "${id}".`);
        } else {
          routeInfo.path = prop.value.value;
        }
      }
    }
  }
  return routeInfo;
}
var getIdentifiers = (stmts) => {
  let ids = [];
  walkAST(
    {
      type: "Program",
      body: stmts,
      directives: [],
      sourceType: "module"
    },
    {
      enter(node) {
        if (node.type === "BlockStatement") {
          this.skip();
        }
      },
      leave(node) {
        if (node.type !== "Program") return;
        ids = Object.keys(this.scope);
      }
    }
  );
  return ids;
};
function generateFilteredImportStatement(parsedImports, usedIds) {
  if (!parsedImports || usedIds.size < 1) return null;
  const { namedImports, defaultImport, namespacedImport } = parsedImports;
  if (namespacedImport && usedIds.has(namespacedImport)) {
    return `import * as ${namespacedImport} from '${parsedImports.specifier}'`;
  }
  let importListCode = "";
  if (defaultImport && usedIds.has(defaultImport)) {
    importListCode += defaultImport;
  }
  let namedImportListCode = "";
  for (const importName in namedImports) {
    if (usedIds.has(importName)) {
      namedImportListCode += namedImportListCode ? `, ` : "";
      namedImportListCode += importName === namedImports[importName] ? importName : `${importName} as ${namedImports[importName]}`;
    }
  }
  importListCode += importListCode && namedImportListCode ? ", " : "";
  importListCode += namedImportListCode ? `{${namedImportListCode}}` : "";
  if (!importListCode) return null;
  return `import ${importListCode} from '${parsedImports.specifier}'`;
}

// src/core/extendRoutes.ts
var EditableTreeNode = class _EditableTreeNode {
  node;
  // private _parent?: EditableTreeNode
  constructor(node) {
    this.node = node;
  }
  /**
   * Remove and detach the current route node from the tree. Subsequently, its children will be removed as well.
   */
  delete() {
    return this.node.delete();
  }
  /**
   * Inserts a new route as a child of this route. This route cannot use `definePage()`. If it was meant to be included,
   * add it to the `routesFolder` option.
   *
   * @param path - path segment to insert. Note this is relative to the current route. **It shouldn't start with `/`**. If it does, it will be added to the root of the tree.
   * @param filePath - file path
   * @returns the new editable route node
   */
  insert(path, filePath) {
    let addBackLeadingSlash = false;
    if (path.startsWith("/")) {
      path = path.slice(1);
      addBackLeadingSlash = !this.node.isRoot();
    }
    const node = this.node.insertParsedPath(path, filePath);
    const editable = new _EditableTreeNode(node);
    if (addBackLeadingSlash) {
      editable.path = "/" + node.path;
    }
    return editable;
  }
  /**
   * Get an editable version of the parent node if it exists.
   */
  get parent() {
    return this.node.parent && new _EditableTreeNode(this.node.parent);
  }
  /**
   * Return a Map of the files associated to the current route. The key of the map represents the name of the view (Vue
   * Router feature) while the value is the **resolved** file path.
   * By default, the name of the view is `default`.
   */
  get components() {
    return this.node.value.components;
  }
  /**
   * Alias for `route.components.get('default')`.
   */
  get component() {
    return this.node.value.components.get("default");
  }
  /**
   * Name of the route. Note that **all routes are named** but when the final `routes` array is generated, routes
   * without a `component` will not include their `name` property to avoid accidentally navigating to them and display
   * nothing.
   * @see {@link isPassThrough}
   */
  get name() {
    return this.node.name;
  }
  /**
   * Override the name of the route.
   */
  set name(name) {
    this.node.value.addEditOverride({ name });
  }
  /**
   * Whether the route is a pass-through route. A pass-through route is a route that does not have a component and is
   * used to group other routes under the same prefix `path` and/or `meta` properties.
   */
  get isPassThrough() {
    return this.node.value.components.size === 0;
  }
  /**
   * Meta property of the route as an object. Note this property is readonly and will be serialized as JSON. It won't contain the meta properties defined with `definePage()` as it could contain expressions **but it does contain the meta properties defined with `<route>` blocks**.
   */
  get meta() {
    return this.node.metaAsObject;
  }
  /**
   * Override the meta property of the route. This will discard any other meta property defined with `<route>` blocks or
   * through other means. If you want to keep the existing meta properties, use `addToMeta`.
   * @see {@link addToMeta}
   */
  set meta(meta) {
    this.node.value.removeOverride("meta");
    this.node.value.setEditOverride("meta", meta);
  }
  /**
   * Add meta properties to the route keeping the existing ones. The passed object will be deeply merged with the
   * existing meta object if any. Note that the meta property is later on serialized as JSON so you can't pass functions
   * or any other non-serializable value.
   */
  addToMeta(meta) {
    this.node.value.addEditOverride({ meta });
  }
  /**
   * Path of the route without parent paths.
   */
  get path() {
    return this.node.path;
  }
  /**
   * Override the path of the route. You must ensure `params` match with the existing path.
   */
  set path(path) {
    if (!path.startsWith("/")) {
      warn(
        `Only absolute paths are supported. Make sure that "${path}" starts with a slash "/".`
      );
      return;
    }
    this.node.value.addEditOverride({ path });
  }
  /**
   * Alias of the route.
   */
  get alias() {
    return this.node.value.overrides.alias;
  }
  /**
   * Add an alias to the route.
   *
   * @param alias - Alias to add to the route
   */
  addAlias(alias) {
    this.node.value.addEditOverride({ alias });
  }
  /**
   * Array of the route params and all of its parent's params. Note that changing the params will not update the path,
   * you need to update both.
   */
  get params() {
    return this.node.params;
  }
  /**
   * Path of the route including parent paths.
   */
  get fullPath() {
    return this.node.fullPath;
  }
  /**
   * Computes an array of EditableTreeNode from the current node. Differently from iterating over the tree, this method
   * **only returns direct children**.
   */
  get children() {
    return [...this.node.children.values()].map(
      (node) => new _EditableTreeNode(node)
    );
  }
  /**
   * DFS traversal of the tree.
   * @example
   * ```ts
   * for (const node of tree) {
   *   // ...
   * }
   * ```
   */
  *traverseDFS() {
    if (!this.node.isRoot()) {
      yield this;
    }
    for (const [_name, child] of this.node.children) {
      yield* new _EditableTreeNode(child).traverseDFS();
    }
  }
  *[Symbol.iterator]() {
    yield* this.traverseBFS();
  }
  /**
   * BFS traversal of the tree as a generator.
   *
   * @example
   * ```ts
   * for (const node of tree) {
   *   // ...
   * }
   * ```
   */
  *traverseBFS() {
    for (const [_name, child] of this.node.children) {
      yield new _EditableTreeNode(child);
    }
    for (const [_name, child] of this.node.children) {
      yield* new _EditableTreeNode(child).traverseBFS();
    }
  }
};

// src/core/context.ts
import { isPackageExists as isPackageInstalled } from "local-pkg";
function createRoutesContext(options) {
  const { dts: preferDTS, root, routesFolder } = options;
  const dts = preferDTS === false ? false : preferDTS === true ? resolve2(root, "typed-router.d.ts") : resolve2(root, preferDTS);
  const routeTree = new PrefixTree(options);
  const editableRoutes = new EditableTreeNode(routeTree);
  const logger = new Proxy(console, {
    get(target, prop) {
      const res = Reflect.get(target, prop);
      if (typeof res === "function") {
        return options.logs ? res : () => {
        };
      }
      return res;
    }
  });
  const watchers = [];
  async function scanPages(startWatchers = true) {
    if (options.extensions.length < 1) {
      throw new Error(
        '"extensions" cannot be empty. Please specify at least one extension.'
      );
    }
    if (watchers.length > 0) {
      return;
    }
    await Promise.all(
      routesFolder.map((folder) => resolveFolderOptions(options, folder)).map((folder) => {
        if (startWatchers) {
          watchers.push(setupWatcher(new RoutesFolderWatcher(folder)));
        }
        const ignorePattern = folder.exclude.map(
          (f) => (
            // if it starts with ** then it will work as expected
            f.startsWith("**") ? f : relative(folder.src, f)
          )
        );
        return fg(folder.pattern, {
          cwd: folder.src,
          // TODO: do they return the symbolic link path or the original file?
          // followSymbolicLinks: false,
          ignore: ignorePattern
        }).then(
          (files) => Promise.all(
            files.map((file) => resolve2(folder.src, file)).map(
              (file) => addPage({
                routePath: asRoutePath(folder, file),
                filePath: file
              })
            )
          )
        );
      })
    );
    for (const route of editableRoutes) {
      await options.extendRoute?.(route);
    }
    await _writeConfigFiles();
  }
  async function writeRouteInfoToNode(node, filePath) {
    const content = await fs.readFile(filePath, "utf8");
    node.hasDefinePage ||= content.includes("definePage");
    const definedPageNameAndPath = extractDefinePageNameAndPath(
      content,
      filePath
    );
    const routeBlock = getRouteBlock(filePath, content, options);
    node.setCustomRouteBlock(filePath, {
      ...routeBlock,
      ...definedPageNameAndPath
    });
  }
  async function addPage({ filePath, routePath }, triggerExtendRoute = false) {
    logger.log(`added "${routePath}" for "${filePath}"`);
    const node = routeTree.insert(routePath, filePath);
    await writeRouteInfoToNode(node, filePath);
    if (triggerExtendRoute) {
      await options.extendRoute?.(new EditableTreeNode(node));
    }
    server?.updateRoutes();
  }
  async function updatePage({ filePath, routePath }) {
    logger.log(`updated "${routePath}" for "${filePath}"`);
    const node = routeTree.getChild(filePath);
    if (!node) {
      logger.warn(`Cannot update "${filePath}": Not found.`);
      return;
    }
    await writeRouteInfoToNode(node, filePath);
    await options.extendRoute?.(new EditableTreeNode(node));
  }
  function removePage({ filePath, routePath }) {
    logger.log(`remove "${routePath}" for "${filePath}"`);
    routeTree.removeChild(filePath);
    server?.updateRoutes();
  }
  function setupWatcher(watcher) {
    logger.log(`\u{1F916} Scanning files in ${watcher.src}`);
    return watcher.on("change", async (ctx) => {
      await updatePage(ctx);
      writeConfigFiles();
    }).on("add", async (ctx) => {
      await addPage(ctx, true);
      writeConfigFiles();
    }).on("unlink", (ctx) => {
      removePage(ctx);
      writeConfigFiles();
    });
  }
  function generateRoutes() {
    const importsMap = new ImportsMap();
    const routeList = `export const routes = ${generateRouteRecord(
      routeTree,
      options,
      importsMap
    )}
`;
    let hmr = ts`
export function handleHotUpdate(_router, _hotUpdateCallback) {
  if (import.meta.hot) {
    import.meta.hot.data.router = _router
    import.meta.hot.data.router_hotUpdateCallback = _hotUpdateCallback
  }
}

if (import.meta.hot) {
  import.meta.hot.accept((mod) => {
    const router = import.meta.hot.data.router
    if (!router) {
      import.meta.hot.invalidate('[unplugin-vue-router:HMR] Cannot replace the routes because there is no active router. Reloading.')
      return
    }
    router.clearRoutes()
    for (const route of mod.routes) {
      router.addRoute(route)
    }
    // call the hotUpdateCallback for custom updates
    import.meta.hot.data.router_hotUpdateCallback?.(mod.routes)
    const route = router.currentRoute.value
    router.replace({
      ...route,
      // NOTE: we should be able to just do ...route but the router
      // currently skips resolving and can give errors with renamed routes
      // so we explicitly set remove matched and name
      name: undefined,
      matched: undefined,
      force: true
    })
  })
}
`;
    let imports = importsMap.toString();
    if (imports) {
      imports += "\n";
    }
    const newAutoRoutes = `${imports}${routeList}${hmr}
`;
    return newAutoRoutes;
  }
  function generateDTS2() {
    return generateDTS({
      vueRouterModule: MODULE_VUE_ROUTER_AUTO,
      routesModule: MODULE_ROUTES_PATH,
      routeNamedMap: generateRouteNamedMap(routeTree)
    });
  }
  const isPiniaColadaInstalled = isPackageInstalled("@pinia/colada");
  function generateVueRouterProxy2() {
    return generateVueRouterProxy(MODULE_ROUTES_PATH, options, {
      addPiniaColada: isPiniaColadaInstalled
    });
  }
  let lastDTS;
  async function _writeConfigFiles() {
    logger.time("writeConfigFiles");
    if (options.beforeWriteFiles) {
      await options.beforeWriteFiles(editableRoutes);
      logger.timeLog("writeConfigFiles", "beforeWriteFiles()");
    }
    logTree(routeTree, logger.log);
    if (dts) {
      const content = generateDTS2();
      if (lastDTS !== content) {
        await fs.mkdir(dirname(dts), { recursive: true });
        await fs.writeFile(dts, content, "utf-8");
        logger.timeLog("writeConfigFiles", "wrote dts file");
        lastDTS = content;
      }
    }
    logger.timeEnd("writeConfigFiles");
  }
  const writeConfigFiles = throttle(_writeConfigFiles, 500, 100);
  function stopWatcher() {
    if (watchers.length) {
      logger.log("\u{1F6D1} stopping watcher");
      watchers.forEach((watcher) => watcher.close());
    }
  }
  let server;
  function setServerContext(_server) {
    server = _server;
  }
  return {
    scanPages,
    writeConfigFiles,
    setServerContext,
    stopWatcher,
    generateRoutes,
    generateVueRouterProxy: generateVueRouterProxy2,
    definePageTransform(code, id) {
      return definePageTransform({
        code,
        id
      });
    }
  };
}

// src/core/vite/index.ts
function createViteContext(server) {
  function invalidate(path) {
    const { moduleGraph } = server;
    const foundModule = moduleGraph.getModuleById(path);
    if (foundModule) {
      moduleGraph.invalidateModule(foundModule, void 0, void 0, true);
      setTimeout(() => {
        console.log(`Sending update for ${foundModule.url}`);
        server.ws.send({
          type: "update",
          updates: [
            {
              acceptedPath: path,
              path,
              // NOTE: this was in the
              // timestamp: ROUTES_LAST_LOAD_TIME.value,
              timestamp: Date.now(),
              type: "js-update"
            }
          ]
        });
      }, 100);
    }
    return !!foundModule;
  }
  function reload() {
    server.ws.send({
      type: "full-reload",
      path: "*"
    });
  }
  async function updateRoutes() {
    const modId = asVirtualId(MODULE_ROUTES_PATH);
    const mod = server.moduleGraph.getModuleById(modId);
    if (mod) {
      return server.reloadModule(mod);
    }
  }
  return {
    invalidate,
    updateRoutes,
    reload
  };
}

// src/index.ts
import { createFilter as createFilter2 } from "unplugin-utils";
import { join } from "pathe";

// src/data-loaders/auto-exports.ts
import { createFilter } from "unplugin-utils";
import MagicString2 from "magic-string";
import { findStaticImports as findStaticImports2, parseStaticImport as parseStaticImport2 } from "mlly";
import { resolve as resolve3 } from "pathe";
function extractLoadersToExport(code, filterPaths, root) {
  const imports = findStaticImports2(code);
  const importNames = imports.flatMap((i) => {
    const parsed = parseStaticImport2(i);
    const specifier = resolve3(
      root,
      parsed.specifier.startsWith("/") ? parsed.specifier.slice(1) : parsed.specifier
    );
    if (!filterPaths(specifier)) return [];
    return [
      parsed.defaultImport,
      ...Object.values(parsed.namedImports || {})
    ].filter((v) => !!v && !v.startsWith("_"));
  });
  return importNames;
}
var PLUGIN_NAME = "unplugin-vue-router:data-loaders-auto-export";
function AutoExportLoaders({
  filterPageComponents: filterPagesOrGlobs,
  loadersPathsGlobs,
  root = process.cwd()
}) {
  const filterPaths = createFilter(loadersPathsGlobs);
  const filterPageComponents = typeof filterPagesOrGlobs === "function" ? filterPagesOrGlobs : createFilter(filterPagesOrGlobs);
  return {
    name: PLUGIN_NAME,
    transform: {
      order: "post",
      handler(code, id) {
        const queryIndex = id.indexOf("?");
        const idWithoutQuery = queryIndex >= 0 ? id.slice(0, queryIndex) : id;
        if (!filterPageComponents(idWithoutQuery)) {
          return;
        }
        const loadersToExports = extractLoadersToExport(code, filterPaths, root);
        if (loadersToExports.length <= 0) return;
        const s = new MagicString2(code);
        s.append(
          `
export const __loaders = [
${loadersToExports.join(",\n")}
];
`
        );
        return {
          code: s.toString(),
          map: s.generateMap()
        };
      }
    }
  };
}
function createAutoExportPlugin(options) {
  return {
    name: PLUGIN_NAME,
    vite: AutoExportLoaders(options)
  };
}

// src/index.ts
var index_default = createUnplugin((opt = {}, _meta) => {
  const options = resolveOptions(opt);
  const ctx = createRoutesContext(options);
  function getVirtualId2(id) {
    if (options._inspect) return id;
    return getVirtualId(id);
  }
  function asVirtualId2(id) {
    if (options._inspect) return id;
    return asVirtualId(id);
  }
  const pageFilePattern = appendExtensionListToPattern(
    options.filePatterns,
    mergeAllExtensions(options)
  );
  const filterPageComponents = createFilter2(
    [
      ...options.routesFolder.flatMap(
        (routeOption) => pageFilePattern.map((pattern) => join(routeOption.src, pattern))
      ),
      // importing the definePage block
      /\?.*\bdefinePage\&vue\b/
    ],
    options.exclude
  );
  const plugins = [
    {
      name: "unplugin-vue-router",
      enforce: "pre",
      resolveId(id) {
        if (
          // vue-router/auto-routes
          id === MODULE_ROUTES_PATH || // NOTE: it wasn't possible to override or add new exports to vue-router
          // so we need to override it with a different package name
          id === MODULE_VUE_ROUTER_AUTO
        ) {
          return asVirtualId2(id);
        }
        if (routeBlockQueryRE.test(id)) {
          return ROUTE_BLOCK_ID;
        }
        return;
      },
      buildStart() {
        return ctx.scanPages(options.watch);
      },
      buildEnd() {
        ctx.stopWatcher();
      },
      // we only need to transform page components
      transformInclude(id) {
        return filterPageComponents(id);
      },
      transform(code, id) {
        return ctx.definePageTransform(code, id);
      },
      // loadInclude is necessary for webpack
      loadInclude(id) {
        if (id === ROUTE_BLOCK_ID) return true;
        const resolvedId = getVirtualId2(id);
        return resolvedId === MODULE_ROUTES_PATH || resolvedId === MODULE_VUE_ROUTER_AUTO;
      },
      load(id) {
        if (id === ROUTE_BLOCK_ID) {
          return {
            code: `export default {}`,
            map: null
          };
        }
        const resolvedId = getVirtualId2(id);
        if (resolvedId === MODULE_ROUTES_PATH) {
          ROUTES_LAST_LOAD_TIME.update();
          return ctx.generateRoutes();
        }
        if (resolvedId === MODULE_VUE_ROUTER_AUTO) {
          return ctx.generateVueRouterProxy();
        }
        return;
      },
      // improves DX
      vite: {
        configureServer(server) {
          ctx.setServerContext(createViteContext(server));
        },
        handleHotUpdate: {
          order: "post",
          handler({ server, file, modules }) {
            const moduleList = server.moduleGraph.getModulesByFile(file);
            const definePageModule = Array.from(moduleList || []).find(
              (mod) => {
                return mod?.id && MACRO_DEFINE_PAGE_QUERY.test(mod.id);
              }
            );
            if (definePageModule) {
              const routesModule = server.moduleGraph.getModuleById(
                asVirtualId2(MODULE_ROUTES_PATH)
              );
              if (!routesModule) {
                console.error("\u{1F525} HMR routes module not found");
                return;
              }
              return [
                ...modules,
                // TODO: only if the definePage changed
                definePageModule,
                // TODO: only if ether the definePage or the route block changed
                routesModule
              ];
            }
            return;
          }
        }
      }
    }
  ];
  if (options.experimental.autoExportsDataLoaders) {
    plugins.push(
      createAutoExportPlugin({
        filterPageComponents,
        loadersPathsGlobs: options.experimental.autoExportsDataLoaders,
        root: options.root
      })
    );
  }
  return plugins;
});
var VueRouterAutoImports = {
  "vue-router": [
    "useRoute",
    "useRouter",
    "onBeforeRouteUpdate",
    "onBeforeRouteLeave"
    // NOTE: the typing seems broken locally, so instead we export it directly from unplugin-vue-router/runtime
    // 'definePage',
  ],
  "unplugin-vue-router/runtime": []
};

export {
  createTreeNodeValue,
  EditableTreeNode,
  createRoutesContext,
  AutoExportLoaders,
  index_default,
  VueRouterAutoImports
};
