//#region rolldown:runtime
var __create = Object.create;
var __defProp = Object.defineProperty;
var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
var __getOwnPropNames = Object.getOwnPropertyNames;
var __getProtoOf = Object.getPrototypeOf;
var __hasOwnProp = Object.prototype.hasOwnProperty;
var __copyProps = (to, from, except, desc) => {
	if (from && typeof from === "object" || typeof from === "function") for (var keys = __getOwnPropNames(from), i = 0, n = keys.length, key; i < n; i++) {
		key = keys[i];
		if (!__hasOwnProp.call(to, key) && key !== except) __defProp(to, key, {
			get: ((k) => from[k]).bind(null, key),
			enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable
		});
	}
	return to;
};
var __toESM = (mod, isNodeMode, target) => (target = mod != null ? __create(__getProtoOf(mod)) : {}, __copyProps(isNodeMode || !mod || !mod.__esModule ? __defProp(target, "default", {
	value: mod,
	enumerable: true
}) : target, mod));

//#endregion
const __babel_parser = __toESM(require("@babel/parser"));
const __vue_compiler_sfc = __toESM(require("@vue/compiler-sfc"));
const __babel_code_frame = __toESM(require("@babel/code-frame"));
const __babel_helper_module_imports = __toESM(require("@babel/helper-module-imports"));
const __babel_helper_plugin_utils = __toESM(require("@babel/helper-plugin-utils"));

//#region src/index.ts
const plugin = (0, __babel_helper_plugin_utils.declare)(({ types: t }, options) => {
	let ctx;
	let helpers;
	return {
		name: "babel-plugin-resolve-type",
		pre(file) {
			const filename = file.opts.filename || "unknown.js";
			helpers = /* @__PURE__ */ new Set();
			ctx = {
				filename,
				source: file.code,
				options,
				ast: file.ast.program.body,
				isCE: false,
				error(msg, node) {
					throw new Error(`[@vue/babel-plugin-resolve-type] ${msg}\n\n${filename}\n${(0, __babel_code_frame.codeFrameColumns)(file.code, {
						start: {
							line: node.loc.start.line,
							column: node.loc.start.column + 1
						},
						end: {
							line: node.loc.end.line,
							column: node.loc.end.column + 1
						}
					})}`);
				},
				helper(key) {
					helpers.add(key);
					return `_${key}`;
				},
				getString(node) {
					return file.code.slice(node.start, node.end);
				},
				propsTypeDecl: void 0,
				propsRuntimeDefaults: void 0,
				propsDestructuredBindings: {},
				emitsTypeDecl: void 0
			};
		},
		visitor: {
			CallExpression(path) {
				if (!ctx) throw new Error("[@vue/babel-plugin-resolve-type] context is not loaded.");
				const { node } = path;
				if (!t.isIdentifier(node.callee, { name: "defineComponent" })) return;
				if (!checkDefineComponent(path)) return;
				const comp = node.arguments[0];
				if (!comp || !t.isFunction(comp)) return;
				let options$1 = node.arguments[1];
				if (!options$1) {
					options$1 = t.objectExpression([]);
					node.arguments.push(options$1);
				}
				let propsGenerics;
				let emitsGenerics;
				if (node.typeParameters && node.typeParameters.params.length > 0) {
					propsGenerics = node.typeParameters.params[0];
					emitsGenerics = node.typeParameters.params[1];
				}
				node.arguments[1] = processProps(comp, propsGenerics, options$1) || options$1;
				node.arguments[1] = processEmits(comp, emitsGenerics, node.arguments[1]) || options$1;
			},
			VariableDeclarator(path) {
				inferComponentName(path);
			}
		},
		post(file) {
			for (const helper of helpers) (0, __babel_helper_module_imports.addNamed)(file.path, `_${helper}`, "vue");
		}
	};
	function inferComponentName(path) {
		var _init$get;
		const id = path.get("id");
		const init = path.get("init");
		if (!id || !id.isIdentifier() || !init || !init.isCallExpression()) return;
		if (!((_init$get = init.get("callee")) === null || _init$get === void 0 ? void 0 : _init$get.isIdentifier({ name: "defineComponent" }))) return;
		if (!checkDefineComponent(init)) return;
		const nameProperty = t.objectProperty(t.identifier("name"), t.stringLiteral(id.node.name));
		const { arguments: args } = init.node;
		if (args.length === 0) return;
		if (args.length === 1) init.node.arguments.push(t.objectExpression([]));
		args[1] = addProperty(t, args[1], nameProperty);
	}
	function processProps(comp, generics, options$1) {
		const props = comp.params[0];
		if (!props) return;
		if (props.type === "AssignmentPattern") {
			if (generics) ctx.propsTypeDecl = resolveTypeReference(generics);
			else ctx.propsTypeDecl = getTypeAnnotation(props.left);
			ctx.propsRuntimeDefaults = props.right;
		} else if (generics) ctx.propsTypeDecl = resolveTypeReference(generics);
		else ctx.propsTypeDecl = getTypeAnnotation(props);
		if (!ctx.propsTypeDecl) return;
		const runtimeProps = (0, __vue_compiler_sfc.extractRuntimeProps)(ctx);
		if (!runtimeProps) return;
		const ast = (0, __babel_parser.parseExpression)(runtimeProps);
		return addProperty(t, options$1, t.objectProperty(t.identifier("props"), ast));
	}
	function processEmits(comp, generics, options$1) {
		let emitType;
		if (generics) emitType = resolveTypeReference(generics);
		const setupCtx = comp.params[1] && getTypeAnnotation(comp.params[1]);
		if (!emitType && setupCtx && t.isTSTypeReference(setupCtx) && t.isIdentifier(setupCtx.typeName, { name: "SetupContext" })) {
			var _setupCtx$typeParamet;
			emitType = (_setupCtx$typeParamet = setupCtx.typeParameters) === null || _setupCtx$typeParamet === void 0 ? void 0 : _setupCtx$typeParamet.params[0];
		}
		if (!emitType) return;
		ctx.emitsTypeDecl = emitType;
		const runtimeEmits = (0, __vue_compiler_sfc.extractRuntimeEmits)(ctx);
		const ast = t.arrayExpression(Array.from(runtimeEmits).map((e) => t.stringLiteral(e)));
		return addProperty(t, options$1, t.objectProperty(t.identifier("emits"), ast));
	}
	function resolveTypeReference(typeNode) {
		if (!ctx) return;
		if (t.isTSTypeReference(typeNode)) {
			const typeName = getTypeReferenceName(typeNode);
			if (typeName) {
				const typeDeclaration = findTypeDeclaration(typeName);
				if (typeDeclaration) return typeDeclaration;
			}
		}
	}
	function getTypeReferenceName(typeRef) {
		if (t.isIdentifier(typeRef.typeName)) return typeRef.typeName.name;
		else if (t.isTSQualifiedName(typeRef.typeName)) {
			const parts = [];
			let current = typeRef.typeName;
			while (t.isTSQualifiedName(current)) {
				if (t.isIdentifier(current.right)) parts.unshift(current.right.name);
				current = current.left;
			}
			if (t.isIdentifier(current)) parts.unshift(current.name);
			return parts.join(".");
		}
		return null;
	}
	function findTypeDeclaration(typeName) {
		if (!ctx) return null;
		for (const statement of ctx.ast) {
			if (t.isTSInterfaceDeclaration(statement) && statement.id.name === typeName) return t.tsTypeLiteral(statement.body.body);
			if (t.isTSTypeAliasDeclaration(statement) && statement.id.name === typeName) return statement.typeAnnotation;
			if (t.isExportNamedDeclaration(statement) && statement.declaration) {
				if (t.isTSInterfaceDeclaration(statement.declaration) && statement.declaration.id.name === typeName) return t.tsTypeLiteral(statement.declaration.body.body);
				if (t.isTSTypeAliasDeclaration(statement.declaration) && statement.declaration.id.name === typeName) return statement.declaration.typeAnnotation;
			}
		}
		return null;
	}
});
var src_default = plugin;
function getTypeAnnotation(node) {
	if ("typeAnnotation" in node && node.typeAnnotation && node.typeAnnotation.type === "TSTypeAnnotation") return node.typeAnnotation.typeAnnotation;
}
function checkDefineComponent(path) {
	var _path$scope$getBindin;
	const defineCompImport = (_path$scope$getBindin = path.scope.getBinding("defineComponent")) === null || _path$scope$getBindin === void 0 ? void 0 : _path$scope$getBindin.path.parent;
	if (!defineCompImport) return true;
	return defineCompImport.type === "ImportDeclaration" && new RegExp("^@?vue(\\/|$)", "").test(defineCompImport.source.value);
}
function addProperty(t, object, property) {
	if (t.isObjectExpression(object)) object.properties.unshift(property);
	else if (t.isExpression(object)) return t.objectExpression([property, t.spreadElement(object)]);
	return object;
}

//#endregion
module.exports = src_default;