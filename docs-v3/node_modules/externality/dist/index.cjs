'use strict';

const node_url = require('node:url');
const node_util = require('node:util');
const ufo = require('ufo');
const enhancedResolve = require('enhanced-resolve');
const mlly = require('mlly');
const pathe = require('pathe');

function _interopDefaultCompat (e) { return e && typeof e === 'object' && 'default' in e ? e.default : e; }

const enhancedResolve__default = /*#__PURE__*/_interopDefaultCompat(enhancedResolve);

const ProtocolRegex = /^(?<proto>.{2,}):.+$/;
function getProtocol(id) {
  const proto = id.match(ProtocolRegex);
  return proto ? proto.groups.proto : null;
}
function matches(input, matchers, context) {
  return matchers.some((matcher) => {
    if (matcher instanceof RegExp) {
      return matcher.test(input);
    }
    if (typeof matcher === "function") {
      return matcher(input, context);
    }
    return false;
  });
}
function toMatcher(pattern) {
  if (typeof pattern !== "string") {
    return pattern;
  }
  pattern = pattern.replace(/\//g, "[\\\\/+]");
  return new RegExp(`([\\/]|^)${pattern}(@[^\\/]*)?([\\/](?!node_modules)|$)`);
}
function getType(id, fallback = "commonjs") {
  if (id.endsWith(".cjs")) {
    return "commonjs";
  }
  if (id.endsWith(".mjs")) {
    return "module";
  }
  return fallback;
}

const DefaultResolveOptions = {
  extensions: [".ts", ".mjs", ".cjs", ".js", ".json"],
  type: "commonjs"
};
async function resolveId(id, base = ".", options = {}) {
  options = { ...DefaultResolveOptions, ...options };
  if (!options.conditionNames) {
    options.conditionNames = [options.type === "commonjs" ? "require" : "import"];
  }
  if (mlly.isNodeBuiltin(id)) {
    return {
      id: id.replace(/^node:/, ""),
      path: id,
      type: options.type,
      external: true
    };
  }
  if (id.startsWith("file:/")) {
    id = node_url.fileURLToPath(id);
  }
  if (ufo.hasProtocol(id)) {
    const url = new URL(id);
    return {
      id: url.href,
      path: url.pathname,
      type: getType(id, options.type),
      external: true
    };
  }
  if (base.includes("\0")) {
    base = options.roots?.[0] || ".";
  }
  const _resolve = node_util.promisify(enhancedResolve__default.create(options));
  const resolvedModule = await _resolve(base, id);
  return {
    id,
    path: resolvedModule || id,
    type: getType(resolvedModule || id, "unknown")
  };
}

const ExternalsDefaults = {
  inline: [
    // Rollup
    // eslint-disable-next-line no-control-regex
    /\u0000/,
    // Webpack
    /^!/,
    /^-!/,
    // Common
    /^#/,
    /\?/
  ],
  external: [],
  externalProtocols: ["node", "file", "data"],
  externalExtensions: [".js", ".mjs", ".cjs", ".node"],
  resolve: {},
  detectInvalidNodeImports: true
};
async function isExternal(id, importer, options = {}) {
  options = { ...ExternalsDefaults, ...options };
  const inlineMatchers = options.inline.map((p) => toMatcher(p));
  const externalMatchers = options.external.map((p) => toMatcher(p));
  const context = { opts: options, id, resolved: null };
  if (!id || matches(id, inlineMatchers, context)) {
    return null;
  }
  const proto = getProtocol(id);
  if (proto && !options.externalProtocols.includes(proto)) {
    return null;
  }
  if (proto === "data") {
    return { id, external: true };
  }
  const r = context.resolved = await resolveId(id, importer, options.resolve).catch(() => {
    return { id, path: id, external: null };
  });
  const idExtension = pathe.extname(r.path);
  if (idExtension && !options.externalExtensions.includes(idExtension)) {
    return null;
  }
  if (matches(r.id, inlineMatchers, context) || matches(r.path, inlineMatchers, context)) {
    return null;
  }
  if (r.external || matches(id, externalMatchers, context) || matches(r.id, externalMatchers, context) || matches(r.path, externalMatchers, context)) {
    if (options.detectInvalidNodeImports && !await mlly.isValidNodeImport(r.path)) {
      return null;
    }
    return { id: r.id, external: true };
  }
  return null;
}

function rollupExternals(options) {
  return {
    name: "node-externals",
    resolveId(id, importer) {
      return isExternal(id, importer, options);
    }
  };
}

function webpackExternals(options) {
  const _isExternal = async ({ request }, callback) => {
    try {
      const res = await isExternal(request, ".", options);
      callback(void 0, res && res.id);
    } catch (error) {
      callback(error, null);
    }
  };
  return _isExternal;
}

exports.ExternalsDefaults = ExternalsDefaults;
exports.getProtocol = getProtocol;
exports.getType = getType;
exports.isExternal = isExternal;
exports.matches = matches;
exports.resolveId = resolveId;
exports.rollupExternals = rollupExternals;
exports.toMatcher = toMatcher;
exports.webpackExternals = webpackExternals;
