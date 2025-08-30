"use strict";
var __defProp = Object.defineProperty;
var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
var __getOwnPropNames = Object.getOwnPropertyNames;
var __hasOwnProp = Object.prototype.hasOwnProperty;
var __export = (target, all) => {
  for (var name in all)
    __defProp(target, name, { get: all[name], enumerable: true });
};
var __copyProps = (to, from, except, desc) => {
  if (from && typeof from === "object" || typeof from === "function") {
    for (let key of __getOwnPropNames(from))
      if (!__hasOwnProp.call(to, key) && key !== except)
        __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
  }
  return to;
};
var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);

// src/index.ts
var src_exports = {};
__export(src_exports, {
  babelParse: () => babelParse,
  extractIdentifiers: () => extractIdentifiers,
  getRootScope: () => getRootScope,
  isNewScope: () => isNewScope,
  walk: () => walk,
  walkAST: () => walkAST,
  walkFunctionParams: () => walkFunctionParams,
  walkNewIdentifier: () => walkNewIdentifier,
  walkVariableDeclaration: () => walkVariableDeclaration
});
module.exports = __toCommonJS(src_exports);
var import_ast_kit2 = require("ast-kit");

// src/utils/babel.ts
var import_parser = require("@babel/parser");
var import_ast_kit = require("ast-kit");
var NEW_SCOPE = [
  "CatchClause",
  "ForInStatement",
  "ForOfStatement"
];
var isNewScope = (node) => node && NEW_SCOPE.includes(node.type) || (0, import_ast_kit.isFunctionType)(node);
function walkFunctionParams(node, onIdent) {
  for (const p of node.params) {
    for (const id of extractIdentifiers(p)) {
      onIdent(id);
    }
  }
}
function extractIdentifiers(param, nodes = []) {
  switch (param.type) {
    case "Identifier":
      nodes.push(param);
      break;
    case "MemberExpression": {
      let object = param;
      while (object.type === "MemberExpression") {
        object = object.object;
      }
      nodes.push(object);
      break;
    }
    case "ObjectPattern":
      for (const prop of param.properties) {
        if (prop.type === "RestElement") {
          extractIdentifiers(prop.argument, nodes);
        } else {
          extractIdentifiers(prop.value, nodes);
        }
      }
      break;
    case "ArrayPattern":
      param.elements.forEach((element) => {
        if (element) extractIdentifiers(element, nodes);
      });
      break;
    case "RestElement":
      extractIdentifiers(param.argument, nodes);
      break;
    case "AssignmentPattern":
      extractIdentifiers(param.left, nodes);
      break;
  }
  return nodes;
}
function babelParse(code, filename, parserPlugins = []) {
  const plugins = parserPlugins || [];
  if (filename) {
    if (/\.tsx?$/.test(filename)) plugins.push("typescript");
    if (filename.endsWith("x")) plugins.push("jsx");
  }
  const ast = (0, import_parser.parse)(code, {
    sourceType: "module",
    plugins
  });
  return ast;
}
function walkVariableDeclaration(stmt, register) {
  if (stmt.declare) return;
  for (const decl of stmt.declarations) {
    for (const id of extractIdentifiers(decl.id)) {
      register(id);
    }
  }
}
function walkNewIdentifier(node, register) {
  if (node.type === "ExportNamedDeclaration" && node.declaration) {
    node = node.declaration;
  }
  if (node.type === "VariableDeclaration") {
    walkVariableDeclaration(node, register);
  } else if (node.type === "FunctionDeclaration" || node.type === "ClassDeclaration") {
    if (node.declare || !node.id) return;
    register(node.id);
  } else if (node.type === "ExportNamedDeclaration" && node.declaration && node.declaration.type === "VariableDeclaration") {
    walkVariableDeclaration(node.declaration, register);
  }
}

// src/index.ts
var walk = (code, walkHooks, { filename, parserPlugins } = {}) => {
  const ast = babelParse(code, filename, parserPlugins);
  walkAST(ast.program, walkHooks);
  return ast;
};
var walkAST = (node, { enter, leave, enterAfter, leaveAfter }) => {
  let currentScope = {};
  const scopeStack = [currentScope];
  const ast = Array.isArray(node) ? { type: "Program", body: node } : node;
  (0, import_ast_kit2.walkAST)(ast, {
    enter(node2, parent, ...args) {
      const { scopeCtx, walkerCtx, isSkip, isRemoved, getNode } = getHookContext(this, node2, [parent, ...args]);
      enter?.call({ ...scopeCtx(), ...walkerCtx }, node2);
      node2 = getNode();
      if (!isSkip() && !isRemoved()) {
        enterNode(node2, parent);
        enterAfter?.call(scopeCtx(), node2);
      }
    },
    leave(node2, parent, ...args) {
      const { scopeCtx, walkerCtx, isSkip, isRemoved, getNode } = getHookContext(this, node2, [parent, ...args]);
      leave?.call({ ...scopeCtx(), ...walkerCtx }, node2);
      node2 = getNode();
      if (!isSkip() && !isRemoved()) {
        leaveNode(node2, parent);
        leaveAfter?.call(scopeCtx(), node2);
      }
    }
  });
  function getHookContext(ctx, node2, [parent, key, index]) {
    const scopeCtx = () => ({
      parent,
      key,
      index,
      scope: scopeStack.reduce((prev, curr) => ({ ...prev, ...curr }), {}),
      scopes: scopeStack,
      level: scopeStack.length
    });
    let isSkip = false;
    let isRemoved = false;
    let newNode = node2;
    const walkerCtx = {
      skip() {
        isSkip = true;
        ctx.skip();
      },
      replace(node3) {
        newNode = node3;
      },
      remove() {
        isRemoved = true;
      }
    };
    return {
      scopeCtx,
      walkerCtx,
      isSkip: () => isSkip,
      isRemoved: () => isRemoved,
      getNode: () => newNode
    };
  }
  function enterNode(node2, parent) {
    if (isNewScope(node2) || node2.type === "BlockStatement" && !isNewScope(parent))
      scopeStack.push(currentScope = {});
    if ((0, import_ast_kit2.isFunctionType)(node2)) {
      walkFunctionParams(node2, registerBinding);
    } else if (
      // catch param
      node2.type === "CatchClause" && node2.param && node2.param.type === "Identifier"
    )
      registerBinding(node2.param);
    if (node2.type === "BlockStatement" || node2.type === "Program") {
      for (const stmt of node2.body) {
        if (stmt.type === "VariableDeclaration" && stmt.kind === "var") {
          walkVariableDeclaration(stmt, registerBinding);
        } else if (stmt.type === "FunctionDeclaration" && stmt.id) {
          registerBinding(stmt.id);
        }
      }
    }
  }
  function leaveNode(node2, parent) {
    if (isNewScope(node2) || node2.type === "BlockStatement" && !isNewScope(parent)) {
      scopeStack.pop();
      currentScope = scopeStack.at(-1);
    }
    walkNewIdentifier(node2, registerBinding);
  }
  function registerBinding(id) {
    if (currentScope) {
      currentScope[id.name] = id;
    } else {
      error(
        "registerBinding called without active scope, something is wrong.",
        id
      );
    }
  }
  function error(msg, node2) {
    const e = new Error(msg);
    e.node = node2;
    throw e;
  }
};
var getRootScope = (nodes) => {
  const scope = {};
  for (const node of nodes) {
    walkNewIdentifier(node, (id) => {
      scope[id.name] = id;
    });
  }
  return scope;
};
// Annotate the CommonJS export names for ESM import in node:
0 && (module.exports = {
  babelParse,
  extractIdentifiers,
  getRootScope,
  isNewScope,
  walk,
  walkAST,
  walkFunctionParams,
  walkNewIdentifier,
  walkVariableDeclaration
});
