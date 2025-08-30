//#region rolldown:runtime
var __create = Object.create;
var __defProp = Object.defineProperty;
var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
var __getOwnPropNames = Object.getOwnPropertyNames;
var __getProtoOf = Object.getPrototypeOf;
var __hasOwnProp = Object.prototype.hasOwnProperty;
var __commonJS = (cb, mod) => function() {
	return mod || (0, cb[__getOwnPropNames(cb)[0]])((mod = { exports: {} }).exports, mod), mod.exports;
};
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
const __babel_types = __toESM(require("@babel/types"));
const __babel_template = __toESM(require("@babel/template"));
const __babel_plugin_syntax_jsx = __toESM(require("@babel/plugin-syntax-jsx"));
const __babel_helper_module_imports = __toESM(require("@babel/helper-module-imports"));
const __vue_babel_plugin_resolve_type = __toESM(require("@vue/babel-plugin-resolve-type"));
const __babel_helper_plugin_utils = __toESM(require("@babel/helper-plugin-utils"));
const __vue_shared = __toESM(require("@vue/shared"));

//#region src/slotFlags.ts
var SlotFlags = /* @__PURE__ */ function(SlotFlags$1) {
	/**
	* Stable slots that only reference slot props or context state. The slot
	* can fully capture its own dependencies so when passed down the parent won't
	* need to force the child to update.
	*/
	SlotFlags$1[SlotFlags$1["STABLE"] = 1] = "STABLE";
	/**
	* Slots that reference scope variables (v-for or an outer slot prop), or
	* has conditional structure (v-if, v-for). The parent will need to force
	* the child to update because the slot does not fully capture its dependencies.
	*/
	SlotFlags$1[SlotFlags$1["DYNAMIC"] = 2] = "DYNAMIC";
	/**
	* `<slot/>` being forwarded into a child component. Whether the parent needs
	* to update the child is dependent on what kind of slots the parent itself
	* received. This has to be refined at runtime, when the child's vnode
	* is being created (in `normalizeChildren`)
	*/
	SlotFlags$1[SlotFlags$1["FORWARDED"] = 3] = "FORWARDED";
	return SlotFlags$1;
}(SlotFlags || {});
var slotFlags_default = SlotFlags;

//#endregion
//#region src/utils.ts
const FRAGMENT = "Fragment";
const KEEP_ALIVE = "KeepAlive";
/**
* create Identifier
* @param path NodePath
* @param state
* @param name string
* @returns MemberExpression
*/
const createIdentifier = (state, name) => state.get(name)();
/**
* Checks if string is describing a directive
* @param src string
*/
const isDirective = (src) => src.startsWith("v-") || src.startsWith("v") && src.length >= 2 && src[1] >= "A" && src[1] <= "Z";
/**
* Should transformed to slots
* @param tag string
* @returns boolean
*/
const shouldTransformedToSlots = (tag) => !(tag.match(RegExp(`^_?${FRAGMENT}\\d*$`)) || tag === KEEP_ALIVE);
/**
* Check if a Node is a component
*
* @param t
* @param path JSXOpeningElement
* @returns boolean
*/
const checkIsComponent = (path, state) => {
	var _state$opts$isCustomE, _state$opts;
	const namePath = path.get("name");
	if (namePath.isJSXMemberExpression()) return shouldTransformedToSlots(namePath.node.property.name);
	const tag = namePath.node.name;
	return !((_state$opts$isCustomE = (_state$opts = state.opts).isCustomElement) === null || _state$opts$isCustomE === void 0 ? void 0 : _state$opts$isCustomE.call(_state$opts, tag)) && shouldTransformedToSlots(tag) && !(0, __vue_shared.isHTMLTag)(tag) && !(0, __vue_shared.isSVGTag)(tag);
};
/**
* Transform JSXMemberExpression to MemberExpression
* @param path JSXMemberExpression
* @returns MemberExpression
*/
const transformJSXMemberExpression = (path) => {
	const objectPath = path.node.object;
	const propertyPath = path.node.property;
	const transformedObject = __babel_types.isJSXMemberExpression(objectPath) ? transformJSXMemberExpression(path.get("object")) : __babel_types.isJSXIdentifier(objectPath) ? __babel_types.identifier(objectPath.name) : __babel_types.nullLiteral();
	const transformedProperty = __babel_types.identifier(propertyPath.name);
	return __babel_types.memberExpression(transformedObject, transformedProperty);
};
/**
* Get tag (first attribute for h) from JSXOpeningElement
* @param path JSXElement
* @param state State
* @returns Identifier | StringLiteral | MemberExpression | CallExpression
*/
const getTag = (path, state) => {
	const namePath = path.get("openingElement").get("name");
	if (namePath.isJSXIdentifier()) {
		const { name } = namePath.node;
		if (!(0, __vue_shared.isHTMLTag)(name) && !(0, __vue_shared.isSVGTag)(name)) {
			var _state$opts$isCustomE2, _state$opts2;
			return name === FRAGMENT ? createIdentifier(state, FRAGMENT) : path.scope.hasBinding(name) ? __babel_types.identifier(name) : ((_state$opts$isCustomE2 = (_state$opts2 = state.opts).isCustomElement) === null || _state$opts$isCustomE2 === void 0 ? void 0 : _state$opts$isCustomE2.call(_state$opts2, name)) ? __babel_types.stringLiteral(name) : __babel_types.callExpression(createIdentifier(state, "resolveComponent"), [__babel_types.stringLiteral(name)]);
		}
		return __babel_types.stringLiteral(name);
	}
	if (namePath.isJSXMemberExpression()) return transformJSXMemberExpression(namePath);
	throw new Error(`getTag: ${namePath.type} is not supported`);
};
const getJSXAttributeName = (path) => {
	const nameNode = path.node.name;
	if (__babel_types.isJSXIdentifier(nameNode)) return nameNode.name;
	return `${nameNode.namespace.name}:${nameNode.name.name}`;
};
/**
* Transform JSXText to StringLiteral
* @param path JSXText
* @returns StringLiteral | null
*/
const transformJSXText = (path) => {
	const str = transformText(path.node.value);
	return str !== "" ? __babel_types.stringLiteral(str) : null;
};
const transformText = (text) => {
	const lines = text.split(/\r\n|\n|\r/);
	let lastNonEmptyLine = 0;
	for (let i = 0; i < lines.length; i++) if (lines[i].match(/[^ \t]/)) lastNonEmptyLine = i;
	let str = "";
	for (let i = 0; i < lines.length; i++) {
		const line = lines[i];
		const isFirstLine = i === 0;
		const isLastLine = i === lines.length - 1;
		const isLastNonEmptyLine = i === lastNonEmptyLine;
		let trimmedLine = line.replace(/\t/g, " ");
		if (!isFirstLine) trimmedLine = trimmedLine.replace(/^[ ]+/, "");
		if (!isLastLine) trimmedLine = trimmedLine.replace(/[ ]+$/, "");
		if (trimmedLine) {
			if (!isLastNonEmptyLine) trimmedLine += " ";
			str += trimmedLine;
		}
	}
	return str;
};
/**
* Transform JSXExpressionContainer to Expression
* @param path JSXExpressionContainer
* @returns Expression
*/
const transformJSXExpressionContainer = (path) => path.get("expression").node;
/**
* Transform JSXSpreadChild
* @param path JSXSpreadChild
* @returns SpreadElement
*/
const transformJSXSpreadChild = (path) => __babel_types.spreadElement(path.get("expression").node);
const walksScope = (path, name, slotFlag) => {
	if (path.scope.hasBinding(name) && path.parentPath) {
		if (__babel_types.isJSXElement(path.parentPath.node)) path.parentPath.setData("slotFlag", slotFlag);
		walksScope(path.parentPath, name, slotFlag);
	}
};
const buildIIFE = (path, children) => {
	const { parentPath } = path;
	if (parentPath.isAssignmentExpression()) {
		const { left } = parentPath.node;
		if (__babel_types.isIdentifier(left)) return children.map((child) => {
			if (__babel_types.isIdentifier(child) && child.name === left.name) {
				const insertName = path.scope.generateUidIdentifier(child.name);
				parentPath.insertBefore(__babel_types.variableDeclaration("const", [__babel_types.variableDeclarator(insertName, __babel_types.callExpression(__babel_types.functionExpression(null, [], __babel_types.blockStatement([__babel_types.returnStatement(child)])), []))]));
				return insertName;
			}
			return child;
		});
	}
	return children;
};
const onRE = /^on[^a-z]/;
const isOn = (key) => onRE.test(key);
const mergeAsArray = (existing, incoming) => {
	if (__babel_types.isArrayExpression(existing.value)) existing.value.elements.push(incoming.value);
	else existing.value = __babel_types.arrayExpression([existing.value, incoming.value]);
};
const dedupeProperties = (properties = [], mergeProps) => {
	if (!mergeProps) return properties;
	const knownProps = /* @__PURE__ */ new Map();
	const deduped = [];
	properties.forEach((prop) => {
		if (__babel_types.isStringLiteral(prop.key)) {
			const { value: name } = prop.key;
			const existing = knownProps.get(name);
			if (existing) {
				if (name === "style" || name === "class" || name.startsWith("on")) mergeAsArray(existing, prop);
			} else {
				knownProps.set(name, prop);
				deduped.push(prop);
			}
		} else deduped.push(prop);
	});
	return deduped;
};
/**
*  Check if an attribute value is constant
* @param node
* @returns boolean
*/
const isConstant = (node) => {
	if (__babel_types.isIdentifier(node)) return node.name === "undefined";
	if (__babel_types.isArrayExpression(node)) {
		const { elements } = node;
		return elements.every((element) => element && isConstant(element));
	}
	if (__babel_types.isObjectExpression(node)) return node.properties.every((property) => isConstant(property.value));
	if (__babel_types.isTemplateLiteral(node) ? !node.expressions.length : __babel_types.isLiteral(node)) return true;
	return false;
};
const transformJSXSpreadAttribute = (nodePath, path, mergeProps, args) => {
	const argument = path.get("argument");
	const properties = __babel_types.isObjectExpression(argument.node) ? argument.node.properties : void 0;
	if (!properties) {
		if (argument.isIdentifier()) walksScope(nodePath, argument.node.name, slotFlags_default.DYNAMIC);
		args.push(mergeProps ? argument.node : __babel_types.spreadElement(argument.node));
	} else if (mergeProps) args.push(__babel_types.objectExpression(properties));
	else args.push(...properties);
};

//#endregion
//#region src/patchFlags.ts
let PatchFlags = /* @__PURE__ */ function(PatchFlags$1) {
	PatchFlags$1[PatchFlags$1["TEXT"] = 1] = "TEXT";
	PatchFlags$1[PatchFlags$1["CLASS"] = 2] = "CLASS";
	PatchFlags$1[PatchFlags$1["STYLE"] = 4] = "STYLE";
	PatchFlags$1[PatchFlags$1["PROPS"] = 8] = "PROPS";
	PatchFlags$1[PatchFlags$1["FULL_PROPS"] = 16] = "FULL_PROPS";
	PatchFlags$1[PatchFlags$1["HYDRATE_EVENTS"] = 32] = "HYDRATE_EVENTS";
	PatchFlags$1[PatchFlags$1["STABLE_FRAGMENT"] = 64] = "STABLE_FRAGMENT";
	PatchFlags$1[PatchFlags$1["KEYED_FRAGMENT"] = 128] = "KEYED_FRAGMENT";
	PatchFlags$1[PatchFlags$1["UNKEYED_FRAGMENT"] = 256] = "UNKEYED_FRAGMENT";
	PatchFlags$1[PatchFlags$1["NEED_PATCH"] = 512] = "NEED_PATCH";
	PatchFlags$1[PatchFlags$1["DYNAMIC_SLOTS"] = 1024] = "DYNAMIC_SLOTS";
	PatchFlags$1[PatchFlags$1["HOISTED"] = -1] = "HOISTED";
	PatchFlags$1[PatchFlags$1["BAIL"] = -2] = "BAIL";
	return PatchFlags$1;
}({});
const PatchFlagNames = {
	[PatchFlags.TEXT]: "TEXT",
	[PatchFlags.CLASS]: "CLASS",
	[PatchFlags.STYLE]: "STYLE",
	[PatchFlags.PROPS]: "PROPS",
	[PatchFlags.FULL_PROPS]: "FULL_PROPS",
	[PatchFlags.HYDRATE_EVENTS]: "HYDRATE_EVENTS",
	[PatchFlags.STABLE_FRAGMENT]: "STABLE_FRAGMENT",
	[PatchFlags.KEYED_FRAGMENT]: "KEYED_FRAGMENT",
	[PatchFlags.UNKEYED_FRAGMENT]: "UNKEYED_FRAGMENT",
	[PatchFlags.DYNAMIC_SLOTS]: "DYNAMIC_SLOTS",
	[PatchFlags.NEED_PATCH]: "NEED_PATCH",
	[PatchFlags.HOISTED]: "HOISTED",
	[PatchFlags.BAIL]: "BAIL"
};

//#endregion
//#region src/parseDirectives.ts
/**
* Get JSX element type
*
* @param path Path<JSXOpeningElement>
*/
const getType = (path) => {
	const typePath = path.get("attributes").find((attribute) => {
		if (!attribute.isJSXAttribute()) return false;
		return attribute.get("name").isJSXIdentifier() && attribute.get("name").node.name === "type";
	});
	return typePath ? typePath.get("value").node : null;
};
const parseModifiers = (value) => __babel_types.isArrayExpression(value) ? value.elements.map((el) => __babel_types.isStringLiteral(el) ? el.value : "").filter(Boolean) : [];
const parseDirectives = (params) => {
	var _modifiersSet$, _modifiersSet$2;
	const { path, value, state, tag, isComponent } = params;
	const args = [];
	const vals = [];
	const modifiersSet = [];
	let directiveName;
	let directiveArgument;
	let directiveModifiers;
	if ("namespace" in path.node.name) {
		[directiveName, directiveArgument] = params.name.split(":");
		directiveName = path.node.name.namespace.name;
		directiveArgument = path.node.name.name.name;
		directiveModifiers = directiveArgument.split("_").slice(1);
	} else {
		const underscoreModifiers = params.name.split("_");
		directiveName = underscoreModifiers.shift() || "";
		directiveModifiers = underscoreModifiers;
	}
	directiveName = directiveName.replace(/^v/, "").replace(/^-/, "").replace(/^\S/, (s) => s.toLowerCase());
	if (directiveArgument) args.push(__babel_types.stringLiteral(directiveArgument.split("_")[0]));
	const isVModels = directiveName === "models";
	const isVModel = directiveName === "model";
	if (isVModel && !path.get("value").isJSXExpressionContainer()) throw new Error("You have to use JSX Expression inside your v-model");
	if (isVModels && !isComponent) throw new Error("v-models can only use in custom components");
	const shouldResolve = ![
		"html",
		"text",
		"model",
		"slots",
		"models"
	].includes(directiveName) || isVModel && !isComponent;
	let modifiers = directiveModifiers;
	if (__babel_types.isArrayExpression(value)) {
		const elementsList = isVModels ? value.elements : [value];
		elementsList.forEach((element) => {
			if (isVModels && !__babel_types.isArrayExpression(element)) throw new Error("You should pass a Two-dimensional Arrays to v-models");
			const { elements } = element;
			const [first, second, third] = elements;
			if (second && !__babel_types.isArrayExpression(second) && !__babel_types.isSpreadElement(second)) {
				args.push(second);
				modifiers = parseModifiers(third);
			} else if (__babel_types.isArrayExpression(second)) {
				if (!shouldResolve) args.push(__babel_types.nullLiteral());
				modifiers = parseModifiers(second);
			} else if (!shouldResolve) args.push(__babel_types.nullLiteral());
			modifiersSet.push(new Set(modifiers));
			vals.push(first);
		});
	} else if (isVModel && !shouldResolve) {
		args.push(__babel_types.nullLiteral());
		modifiersSet.push(new Set(directiveModifiers));
	} else modifiersSet.push(new Set(directiveModifiers));
	return {
		directiveName,
		modifiers: modifiersSet,
		values: vals.length ? vals : [value],
		args,
		directive: shouldResolve ? [
			resolveDirective(path, state, tag, directiveName),
			vals[0] || value,
			((_modifiersSet$ = modifiersSet[0]) === null || _modifiersSet$ === void 0 ? void 0 : _modifiersSet$.size) ? args[0] || __babel_types.unaryExpression("void", __babel_types.numericLiteral(0), true) : args[0],
			!!((_modifiersSet$2 = modifiersSet[0]) === null || _modifiersSet$2 === void 0 ? void 0 : _modifiersSet$2.size) && __babel_types.objectExpression([...modifiersSet[0]].map((modifier) => __babel_types.objectProperty(__babel_types.identifier(modifier), __babel_types.booleanLiteral(true))))
		].filter(Boolean) : void 0
	};
};
const resolveDirective = (path, state, tag, directiveName) => {
	if (directiveName === "show") return createIdentifier(state, "vShow");
	if (directiveName === "model") {
		let modelToUse;
		const type = getType(path.parentPath);
		switch (tag.value) {
			case "select":
				modelToUse = createIdentifier(state, "vModelSelect");
				break;
			case "textarea":
				modelToUse = createIdentifier(state, "vModelText");
				break;
			default: if (__babel_types.isStringLiteral(type) || !type) switch (type === null || type === void 0 ? void 0 : type.value) {
				case "checkbox":
					modelToUse = createIdentifier(state, "vModelCheckbox");
					break;
				case "radio":
					modelToUse = createIdentifier(state, "vModelRadio");
					break;
				default: modelToUse = createIdentifier(state, "vModelText");
			}
			else modelToUse = createIdentifier(state, "vModelDynamic");
		}
		return modelToUse;
	}
	const referenceName = "v" + directiveName[0].toUpperCase() + directiveName.slice(1);
	if (path.scope.references[referenceName]) return __babel_types.identifier(referenceName);
	return __babel_types.callExpression(createIdentifier(state, "resolveDirective"), [__babel_types.stringLiteral(directiveName)]);
};
var parseDirectives_default = parseDirectives;

//#endregion
//#region src/transform-vue-jsx.ts
const xlinkRE = new RegExp("^xlink([A-Z])", "");
const getJSXAttributeValue = (path, state) => {
	const valuePath = path.get("value");
	if (valuePath.isJSXElement()) return transformJSXElement(valuePath, state);
	if (valuePath.isStringLiteral()) return __babel_types.stringLiteral(transformText(valuePath.node.value));
	if (valuePath.isJSXExpressionContainer()) return transformJSXExpressionContainer(valuePath);
	return null;
};
const buildProps = (path, state) => {
	const tag = getTag(path, state);
	const isComponent = checkIsComponent(path.get("openingElement"), state);
	const props = path.get("openingElement").get("attributes");
	const directives = [];
	const dynamicPropNames = /* @__PURE__ */ new Set();
	let slots = null;
	let patchFlag = 0;
	if (props.length === 0) return {
		tag,
		isComponent,
		slots,
		props: __babel_types.nullLiteral(),
		directives,
		patchFlag,
		dynamicPropNames
	};
	let properties = [];
	let hasRef = false;
	let hasClassBinding = false;
	let hasStyleBinding = false;
	let hasHydrationEventBinding = false;
	let hasDynamicKeys = false;
	const mergeArgs = [];
	const { mergeProps = true } = state.opts;
	props.forEach((prop) => {
		if (prop.isJSXAttribute()) {
			let name = getJSXAttributeName(prop);
			const attributeValue = getJSXAttributeValue(prop, state);
			if (!isConstant(attributeValue) || name === "ref") {
				if (!isComponent && isOn(name) && name.toLowerCase() !== "onclick" && name !== "onUpdate:modelValue") hasHydrationEventBinding = true;
				if (name === "ref") hasRef = true;
				else if (name === "class" && !isComponent) hasClassBinding = true;
				else if (name === "style" && !isComponent) hasStyleBinding = true;
				else if (name !== "key" && !isDirective(name) && name !== "on") dynamicPropNames.add(name);
			}
			if (state.opts.transformOn && (name === "on" || name === "nativeOn")) {
				if (!state.get("transformOn")) state.set("transformOn", (0, __babel_helper_module_imports.addDefault)(path, "@vue/babel-helper-vue-transform-on", { nameHint: "_transformOn" }));
				mergeArgs.push(__babel_types.callExpression(state.get("transformOn"), [attributeValue || __babel_types.booleanLiteral(true)]));
				return;
			}
			if (isDirective(name)) {
				const { directive, modifiers, values, args, directiveName } = parseDirectives_default({
					tag,
					isComponent,
					name,
					path: prop,
					state,
					value: attributeValue
				});
				if (directiveName === "slots") {
					slots = attributeValue;
					return;
				}
				if (directive) directives.push(__babel_types.arrayExpression(directive));
				else if (directiveName === "html") {
					properties.push(__babel_types.objectProperty(__babel_types.stringLiteral("innerHTML"), values[0]));
					dynamicPropNames.add("innerHTML");
				} else if (directiveName === "text") {
					properties.push(__babel_types.objectProperty(__babel_types.stringLiteral("textContent"), values[0]));
					dynamicPropNames.add("textContent");
				}
				if (["models", "model"].includes(directiveName)) values.forEach((value, index) => {
					const propName = args[index];
					const isDynamic = propName && !__babel_types.isStringLiteral(propName) && !__babel_types.isNullLiteral(propName);
					if (!directive) {
						var _modifiers$index;
						properties.push(__babel_types.objectProperty(__babel_types.isNullLiteral(propName) ? __babel_types.stringLiteral("modelValue") : propName, value, isDynamic));
						if (!isDynamic) dynamicPropNames.add((propName === null || propName === void 0 ? void 0 : propName.value) || "modelValue");
						if ((_modifiers$index = modifiers[index]) === null || _modifiers$index === void 0 ? void 0 : _modifiers$index.size) properties.push(__babel_types.objectProperty(isDynamic ? __babel_types.binaryExpression("+", propName, __babel_types.stringLiteral("Modifiers")) : __babel_types.stringLiteral(`${(propName === null || propName === void 0 ? void 0 : propName.value) || "model"}Modifiers`), __babel_types.objectExpression([...modifiers[index]].map((modifier) => __babel_types.objectProperty(__babel_types.stringLiteral(modifier), __babel_types.booleanLiteral(true)))), isDynamic));
					}
					const updateName = isDynamic ? __babel_types.binaryExpression("+", __babel_types.stringLiteral("onUpdate:"), propName) : __babel_types.stringLiteral(`onUpdate:${(propName === null || propName === void 0 ? void 0 : propName.value) || "modelValue"}`);
					properties.push(__babel_types.objectProperty(updateName, __babel_types.arrowFunctionExpression([__babel_types.identifier("$event")], __babel_types.assignmentExpression("=", value, __babel_types.identifier("$event"))), isDynamic));
					if (!isDynamic) dynamicPropNames.add(updateName.value);
					else hasDynamicKeys = true;
				});
			} else {
				if (name.match(xlinkRE)) name = name.replace(xlinkRE, (_, firstCharacter) => `xlink:${firstCharacter.toLowerCase()}`);
				properties.push(__babel_types.objectProperty(__babel_types.stringLiteral(name), attributeValue || __babel_types.booleanLiteral(true)));
			}
		} else {
			if (properties.length && mergeProps) {
				mergeArgs.push(__babel_types.objectExpression(dedupeProperties(properties, mergeProps)));
				properties = [];
			}
			hasDynamicKeys = true;
			transformJSXSpreadAttribute(path, prop, mergeProps, mergeProps ? mergeArgs : properties);
		}
	});
	if (hasDynamicKeys) patchFlag |= PatchFlags.FULL_PROPS;
	else {
		if (hasClassBinding) patchFlag |= PatchFlags.CLASS;
		if (hasStyleBinding) patchFlag |= PatchFlags.STYLE;
		if (dynamicPropNames.size) patchFlag |= PatchFlags.PROPS;
		if (hasHydrationEventBinding) patchFlag |= PatchFlags.HYDRATE_EVENTS;
	}
	if ((patchFlag === 0 || patchFlag === PatchFlags.HYDRATE_EVENTS) && (hasRef || directives.length > 0)) patchFlag |= PatchFlags.NEED_PATCH;
	let propsExpression = __babel_types.nullLiteral();
	if (mergeArgs.length) {
		if (properties.length) mergeArgs.push(__babel_types.objectExpression(dedupeProperties(properties, mergeProps)));
		if (mergeArgs.length > 1) propsExpression = __babel_types.callExpression(createIdentifier(state, "mergeProps"), mergeArgs);
		else propsExpression = mergeArgs[0];
	} else if (properties.length) if (properties.length === 1 && __babel_types.isSpreadElement(properties[0])) propsExpression = properties[0].argument;
	else propsExpression = __babel_types.objectExpression(dedupeProperties(properties, mergeProps));
	return {
		tag,
		props: propsExpression,
		isComponent,
		slots,
		directives,
		patchFlag,
		dynamicPropNames
	};
};
/**
* Get children from Array of JSX children
* @param paths Array<JSXText | JSXExpressionContainer  | JSXElement | JSXFragment>
* @returns Array<Expression | SpreadElement>
*/
const getChildren = (paths, state) => paths.map((path) => {
	if (path.isJSXText()) {
		const transformedText = transformJSXText(path);
		if (transformedText) return __babel_types.callExpression(createIdentifier(state, "createTextVNode"), [transformedText]);
		return transformedText;
	}
	if (path.isJSXExpressionContainer()) {
		const expression = transformJSXExpressionContainer(path);
		if (__babel_types.isIdentifier(expression)) {
			const { name } = expression;
			const { referencePaths = [] } = path.scope.getBinding(name) || {};
			referencePaths.forEach((referencePath) => {
				walksScope(referencePath, name, slotFlags_default.DYNAMIC);
			});
		}
		return expression;
	}
	if (path.isJSXSpreadChild()) return transformJSXSpreadChild(path);
	if (path.isCallExpression()) return path.node;
	if (path.isJSXElement()) return transformJSXElement(path, state);
	throw new Error(`getChildren: ${path.type} is not supported`);
}).filter(((value) => value != null && !__babel_types.isJSXEmptyExpression(value)));
const transformJSXElement = (path, state) => {
	var _path$getData;
	const children = getChildren(path.get("children"), state);
	const { tag, props, isComponent, directives, patchFlag, dynamicPropNames, slots } = buildProps(path, state);
	const { optimize = false } = state.opts;
	if (directives.length && directives.some((d) => {
		var _d$elements;
		return ((_d$elements = d.elements) === null || _d$elements === void 0 || (_d$elements = _d$elements[0]) === null || _d$elements === void 0 ? void 0 : _d$elements.type) === "CallExpression" && d.elements[0].callee.type === "Identifier" && d.elements[0].callee.name === "_resolveDirective";
	})) {
		var _currentPath$parentPa;
		let currentPath = path;
		while ((_currentPath$parentPa = currentPath.parentPath) === null || _currentPath$parentPa === void 0 ? void 0 : _currentPath$parentPa.isJSXElement()) {
			currentPath = currentPath.parentPath;
			currentPath.setData("slotFlag", 0);
		}
	}
	const slotFlag = (_path$getData = path.getData("slotFlag")) !== null && _path$getData !== void 0 ? _path$getData : slotFlags_default.STABLE;
	const optimizeSlots = optimize && slotFlag !== 0;
	let VNodeChild;
	if (children.length > 1 || slots) VNodeChild = isComponent ? children.length ? __babel_types.objectExpression([
		!!children.length && __babel_types.objectProperty(__babel_types.identifier("default"), __babel_types.arrowFunctionExpression([], __babel_types.arrayExpression(buildIIFE(path, children)))),
		...slots ? __babel_types.isObjectExpression(slots) ? slots.properties : [__babel_types.spreadElement(slots)] : [],
		optimizeSlots && __babel_types.objectProperty(__babel_types.identifier("_"), __babel_types.numericLiteral(slotFlag))
	].filter(Boolean)) : slots : __babel_types.arrayExpression(children);
	else if (children.length === 1) {
		const { enableObjectSlots = true } = state.opts;
		const child = children[0];
		const objectExpression = __babel_types.objectExpression([__babel_types.objectProperty(__babel_types.identifier("default"), __babel_types.arrowFunctionExpression([], __babel_types.arrayExpression(buildIIFE(path, [child])))), optimizeSlots && __babel_types.objectProperty(__babel_types.identifier("_"), __babel_types.numericLiteral(slotFlag))].filter(Boolean));
		if (__babel_types.isIdentifier(child) && isComponent) VNodeChild = enableObjectSlots ? __babel_types.conditionalExpression(__babel_types.callExpression(state.get("@vue/babel-plugin-jsx/runtimeIsSlot")(), [child]), child, objectExpression) : objectExpression;
		else if (__babel_types.isCallExpression(child) && child.loc && isComponent) if (enableObjectSlots) {
			const { scope } = path;
			const slotId = scope.generateUidIdentifier("slot");
			if (scope) scope.push({
				id: slotId,
				kind: "let"
			});
			const alternate = __babel_types.objectExpression([__babel_types.objectProperty(__babel_types.identifier("default"), __babel_types.arrowFunctionExpression([], __babel_types.arrayExpression(buildIIFE(path, [slotId])))), optimizeSlots && __babel_types.objectProperty(__babel_types.identifier("_"), __babel_types.numericLiteral(slotFlag))].filter(Boolean));
			const assignment = __babel_types.assignmentExpression("=", slotId, child);
			const condition = __babel_types.callExpression(state.get("@vue/babel-plugin-jsx/runtimeIsSlot")(), [assignment]);
			VNodeChild = __babel_types.conditionalExpression(condition, slotId, alternate);
		} else VNodeChild = objectExpression;
		else if (__babel_types.isFunctionExpression(child) || __babel_types.isArrowFunctionExpression(child)) VNodeChild = __babel_types.objectExpression([__babel_types.objectProperty(__babel_types.identifier("default"), child)]);
		else if (__babel_types.isObjectExpression(child)) VNodeChild = __babel_types.objectExpression([...child.properties, optimizeSlots && __babel_types.objectProperty(__babel_types.identifier("_"), __babel_types.numericLiteral(slotFlag))].filter(Boolean));
		else VNodeChild = isComponent ? __babel_types.objectExpression([__babel_types.objectProperty(__babel_types.identifier("default"), __babel_types.arrowFunctionExpression([], __babel_types.arrayExpression([child])))]) : __babel_types.arrayExpression([child]);
	}
	const createVNode = __babel_types.callExpression(createIdentifier(state, "createVNode"), [
		tag,
		props,
		VNodeChild || __babel_types.nullLiteral(),
		!!patchFlag && optimize && __babel_types.numericLiteral(patchFlag),
		!!dynamicPropNames.size && optimize && __babel_types.arrayExpression([...dynamicPropNames.keys()].map((name) => __babel_types.stringLiteral(name)))
	].filter(Boolean));
	if (!directives.length) return createVNode;
	return __babel_types.callExpression(createIdentifier(state, "withDirectives"), [createVNode, __babel_types.arrayExpression(directives)]);
};
const visitor$1 = { JSXElement: { exit(path, state) {
	path.replaceWith(transformJSXElement(path, state));
} } };
var transform_vue_jsx_default = visitor$1;

//#endregion
//#region src/sugar-fragment.ts
const transformFragment = (path, Fragment) => {
	const children = path.get("children") || [];
	return __babel_types.jsxElement(__babel_types.jsxOpeningElement(Fragment, []), __babel_types.jsxClosingElement(Fragment), children.map(({ node }) => node), false);
};
const visitor = { JSXFragment: { enter(path, state) {
	const fragmentCallee = createIdentifier(state, FRAGMENT);
	path.replaceWith(transformFragment(path, __babel_types.isIdentifier(fragmentCallee) ? __babel_types.jsxIdentifier(fragmentCallee.name) : __babel_types.jsxMemberExpression(__babel_types.jsxIdentifier(fragmentCallee.object.name), __babel_types.jsxIdentifier(fragmentCallee.property.name))));
} } };
var sugar_fragment_default = visitor;

//#endregion
//#region ../../node_modules/.pnpm/@oxc-project+runtime@0.80.0/node_modules/@oxc-project/runtime/src/helpers/typeof.js
var require_typeof = /* @__PURE__ */ __commonJS({ "../../node_modules/.pnpm/@oxc-project+runtime@0.80.0/node_modules/@oxc-project/runtime/src/helpers/typeof.js": ((exports, module) => {
	function _typeof$2(o) {
		"@babel/helpers - typeof";
		return module.exports = _typeof$2 = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(o$1) {
			return typeof o$1;
		} : function(o$1) {
			return o$1 && "function" == typeof Symbol && o$1.constructor === Symbol && o$1 !== Symbol.prototype ? "symbol" : typeof o$1;
		}, module.exports.__esModule = true, module.exports["default"] = module.exports, _typeof$2(o);
	}
	module.exports = _typeof$2, module.exports.__esModule = true, module.exports["default"] = module.exports;
}) });

//#endregion
//#region ../../node_modules/.pnpm/@oxc-project+runtime@0.80.0/node_modules/@oxc-project/runtime/src/helpers/toPrimitive.js
var require_toPrimitive = /* @__PURE__ */ __commonJS({ "../../node_modules/.pnpm/@oxc-project+runtime@0.80.0/node_modules/@oxc-project/runtime/src/helpers/toPrimitive.js": ((exports, module) => {
	var _typeof$1 = require_typeof()["default"];
	function toPrimitive$1(t, r) {
		if ("object" != _typeof$1(t) || !t) return t;
		var e = t[Symbol.toPrimitive];
		if (void 0 !== e) {
			var i = e.call(t, r || "default");
			if ("object" != _typeof$1(i)) return i;
			throw new TypeError("@@toPrimitive must return a primitive value.");
		}
		return ("string" === r ? String : Number)(t);
	}
	module.exports = toPrimitive$1, module.exports.__esModule = true, module.exports["default"] = module.exports;
}) });

//#endregion
//#region ../../node_modules/.pnpm/@oxc-project+runtime@0.80.0/node_modules/@oxc-project/runtime/src/helpers/toPropertyKey.js
var require_toPropertyKey = /* @__PURE__ */ __commonJS({ "../../node_modules/.pnpm/@oxc-project+runtime@0.80.0/node_modules/@oxc-project/runtime/src/helpers/toPropertyKey.js": ((exports, module) => {
	var _typeof = require_typeof()["default"];
	var toPrimitive = require_toPrimitive();
	function toPropertyKey$1(t) {
		var i = toPrimitive(t, "string");
		return "symbol" == _typeof(i) ? i : i + "";
	}
	module.exports = toPropertyKey$1, module.exports.__esModule = true, module.exports["default"] = module.exports;
}) });

//#endregion
//#region ../../node_modules/.pnpm/@oxc-project+runtime@0.80.0/node_modules/@oxc-project/runtime/src/helpers/defineProperty.js
var require_defineProperty = /* @__PURE__ */ __commonJS({ "../../node_modules/.pnpm/@oxc-project+runtime@0.80.0/node_modules/@oxc-project/runtime/src/helpers/defineProperty.js": ((exports, module) => {
	var toPropertyKey = require_toPropertyKey();
	function _defineProperty(e, r, t) {
		return (r = toPropertyKey(r)) in e ? Object.defineProperty(e, r, {
			value: t,
			enumerable: !0,
			configurable: !0,
			writable: !0
		}) : e[r] = t, e;
	}
	module.exports = _defineProperty, module.exports.__esModule = true, module.exports["default"] = module.exports;
}) });

//#endregion
//#region ../../node_modules/.pnpm/@oxc-project+runtime@0.80.0/node_modules/@oxc-project/runtime/src/helpers/objectSpread2.js
var require_objectSpread2 = /* @__PURE__ */ __commonJS({ "../../node_modules/.pnpm/@oxc-project+runtime@0.80.0/node_modules/@oxc-project/runtime/src/helpers/objectSpread2.js": ((exports, module) => {
	var defineProperty = require_defineProperty();
	function ownKeys(e, r) {
		var t = Object.keys(e);
		if (Object.getOwnPropertySymbols) {
			var o = Object.getOwnPropertySymbols(e);
			r && (o = o.filter(function(r$1) {
				return Object.getOwnPropertyDescriptor(e, r$1).enumerable;
			})), t.push.apply(t, o);
		}
		return t;
	}
	function _objectSpread2(e) {
		for (var r = 1; r < arguments.length; r++) {
			var t = null != arguments[r] ? arguments[r] : {};
			r % 2 ? ownKeys(Object(t), !0).forEach(function(r$1) {
				defineProperty(e, r$1, t[r$1]);
			}) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function(r$1) {
				Object.defineProperty(e, r$1, Object.getOwnPropertyDescriptor(t, r$1));
			});
		}
		return e;
	}
	module.exports = _objectSpread2, module.exports.__esModule = true, module.exports["default"] = module.exports;
}) });

//#endregion
//#region src/index.ts
var import_objectSpread2 = /* @__PURE__ */ __toESM(require_objectSpread2());
const hasJSX = (parentPath) => {
	let fileHasJSX = false;
	parentPath.traverse({
		JSXElement(path) {
			fileHasJSX = true;
			path.stop();
		},
		JSXFragment(path) {
			fileHasJSX = true;
			path.stop();
		}
	});
	return fileHasJSX;
};
const JSX_ANNOTATION_REGEX = new RegExp("\\*?\\s*@jsx\\s+([^\\s]+)", "");
/* @__NO_SIDE_EFFECTS__ */
function interopDefault(m) {
	return m.default || m;
}
const syntaxJsx = /* @__PURE__ */ interopDefault(__babel_plugin_syntax_jsx.default);
const template = /* @__PURE__ */ interopDefault(__babel_template.default);
const plugin = (0, __babel_helper_plugin_utils.declare)((api, opt, dirname) => {
	const { types } = api;
	let resolveType;
	if (opt.resolveType) {
		if (typeof opt.resolveType === "boolean") opt.resolveType = {};
		resolveType = (0, __vue_babel_plugin_resolve_type.default)(api, opt.resolveType, dirname);
	}
	return (0, import_objectSpread2.default)((0, import_objectSpread2.default)({}, resolveType || {}), {}, {
		name: "babel-plugin-jsx",
		inherits: /* @__PURE__ */ interopDefault(syntaxJsx),
		visitor: (0, import_objectSpread2.default)((0, import_objectSpread2.default)((0, import_objectSpread2.default)((0, import_objectSpread2.default)({}, resolveType === null || resolveType === void 0 ? void 0 : resolveType.visitor), transform_vue_jsx_default), sugar_fragment_default), {}, { Program: { enter(path, state) {
			if (hasJSX(path)) {
				const importNames = [
					"createVNode",
					"Fragment",
					"resolveComponent",
					"withDirectives",
					"vShow",
					"vModelSelect",
					"vModelText",
					"vModelCheckbox",
					"vModelRadio",
					"vModelText",
					"vModelDynamic",
					"resolveDirective",
					"mergeProps",
					"createTextVNode",
					"isVNode"
				];
				if ((0, __babel_helper_module_imports.isModule)(path)) {
					const importMap = {};
					importNames.forEach((name) => {
						state.set(name, () => {
							if (importMap[name]) return types.cloneNode(importMap[name]);
							const identifier = (0, __babel_helper_module_imports.addNamed)(path, name, "vue", { ensureLiveReference: true });
							importMap[name] = identifier;
							return identifier;
						});
					});
					const { enableObjectSlots = true } = state.opts;
					if (enableObjectSlots) state.set("@vue/babel-plugin-jsx/runtimeIsSlot", () => {
						if (importMap.runtimeIsSlot) return importMap.runtimeIsSlot;
						const { name: isVNodeName } = state.get("isVNode")();
						const isSlot = path.scope.generateUidIdentifier("isSlot");
						const ast = template.ast`
                    function ${isSlot.name}(s) {
                      return typeof s === 'function' || (Object.prototype.toString.call(s) === '[object Object]' && !${isVNodeName}(s));
                    }
                  `;
						const lastImport = path.get("body").filter((p) => p.isImportDeclaration()).pop();
						if (lastImport) lastImport.insertAfter(ast);
						importMap.runtimeIsSlot = isSlot;
						return isSlot;
					});
				} else {
					let sourceName;
					importNames.forEach((name) => {
						state.set(name, () => {
							if (!sourceName) sourceName = (0, __babel_helper_module_imports.addNamespace)(path, "vue", { ensureLiveReference: true });
							return __babel_types.memberExpression(sourceName, __babel_types.identifier(name));
						});
					});
					const helpers = {};
					const { enableObjectSlots = true } = state.opts;
					if (enableObjectSlots) state.set("@vue/babel-plugin-jsx/runtimeIsSlot", () => {
						if (helpers.runtimeIsSlot) return helpers.runtimeIsSlot;
						const isSlot = path.scope.generateUidIdentifier("isSlot");
						const { object: objectName } = state.get("isVNode")();
						const ast = template.ast`
                    function ${isSlot.name}(s) {
                      return typeof s === 'function' || (Object.prototype.toString.call(s) === '[object Object]' && !${objectName.name}.isVNode(s));
                    }
                  `;
						const nodePaths = path.get("body");
						const lastImport = nodePaths.filter((p) => p.isVariableDeclaration() && p.node.declarations.some((d) => {
							var _d$id;
							return ((_d$id = d.id) === null || _d$id === void 0 ? void 0 : _d$id.name) === sourceName.name;
						})).pop();
						if (lastImport) lastImport.insertAfter(ast);
						return isSlot;
					});
				}
				const { opts: { pragma = "" }, file } = state;
				if (pragma) state.set("createVNode", () => __babel_types.identifier(pragma));
				if (file.ast.comments) for (const comment of file.ast.comments) {
					const jsxMatches = JSX_ANNOTATION_REGEX.exec(comment.value);
					if (jsxMatches) state.set("createVNode", () => __babel_types.identifier(jsxMatches[1]));
				}
			}
		} } })
	});
});
var src_default = plugin;

//#endregion
module.exports = src_default;