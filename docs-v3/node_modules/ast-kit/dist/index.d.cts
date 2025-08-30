import * as t$7 from "@babel/types";
import * as t$6 from "@babel/types";
import * as t$5 from "@babel/types";
import * as t$4 from "@babel/types";
import * as t$3 from "@babel/types";
import * as t$2 from "@babel/types";
import * as t$1 from "@babel/types";
import * as t from "@babel/types";
import { Node } from "@babel/types";
import { ParseError, ParseResult, ParserOptions } from "@babel/parser";

//#region src/check.d.ts
/**
* All possible node types.
*/
type NodeType = t$7.Node["type"] | "Function" | "Literal" | "Expression";
/**
* Represents the corresponding node based on the given node type.
*/
type GetNode<K extends NodeType> = K extends "Function" ? t$7.Function : K extends "Literal" ? t$7.Literal : Extract<t$7.Node, {
	type: K
}>;
/**
* Checks if the given node matches the specified type(s).
*
* @param node - The node to check.
* @param types - The type(s) to match against. It can be a single type or an array of types.
* @returns True if the node matches the specified type(s), false otherwise.
*/
declare function isTypeOf<K extends NodeType>(node: t$7.Node | undefined | null, types: K | Readonly<K[]>): node is GetNode<K>;
/**
* Checks if the given node is a CallExpression with the specified callee.
*
* @param node - The node to check.
* @param test - The callee to compare against. It can be a string, an array of strings, or a function that takes a string and returns a boolean.
* @returns True if the node is a CallExpression with the specified callee, false otherwise.
*/
declare function isCallOf(node: t$7.Node | null | undefined, test: string | string[] | ((id: string) => boolean)): node is t$7.CallExpression;
/**
* Checks if the given node is a TaggedTemplateExpression with the specified callee.
*
* @param node - The node to check.
* @param test - The callee to compare against. It can be a string, an array of strings, or a function that takes a string and returns a boolean.
* @returns True if the node is a TaggedTemplateExpression with the specified callee, false otherwise.
*/
declare function isTaggedFunctionCallOf(node: t$7.Node | null | undefined, test: string | string[] | ((id: string) => boolean)): node is t$7.TaggedTemplateExpression;
/**
* Checks if the given node is an Identifier with the specified name.
*
* @param node - The node to check.
* @param test - The name to compare against. It can be a string or an array of strings.
* @returns True if the node is an Identifier with the specified name, false otherwise.
*/
declare function isIdentifierOf(node: t$7.Node | undefined | null, test: string | string[] | ((id: string) => boolean)): node is t$7.Identifier;
/**
* Checks if the given node is a literal type.
*
* @param node - The node to check.
* @returns True if the node is a literal type, false otherwise.
*/
declare function isLiteralType(node: t$7.Node | undefined | null): node is t$7.Literal;
/**
* Checks if the given node is a function type.
*
* @param node - The node to check.
* @returns True if the node is a function type, false otherwise.
*/
declare function isFunctionType(node: t$7.Node | undefined | null): node is t$7.Function;
/**
* Checks if the given node is a declaration type.
*
* @param node - The node to check.
* @returns True if the node is a declaration type, false otherwise.
*/
declare function isDeclarationType(node: t$7.Node | undefined | null): node is t$7.Declaration;
/**
* Checks if the given node is an expression type.
*
* @param node - The node to check.
* @returns True if the node is an expression type, false otherwise.
*/
declare function isExpressionType(node: t$7.Node | null | undefined): node is t$7.Expression;
/* v8 ignore start */
/**
* Checks if the input `node` is a reference to a bound variable.
*
* Copied from https://github.com/babel/babel/blob/main/packages/babel-types/src/validators/isReferenced.ts
*
* To avoid runtime dependency on `@babel/types` (which includes process references)
* This file should not change very often in babel but we may need to keep it
* up-to-date from time to time.
*
* @param node - The node to check.
* @param parent - The parent node of the input `node`.
* @param grandparent - The grandparent node of the input `node`.
* @returns True if the input `node` is a reference to a bound variable, false otherwise.
*/
declare function isReferenced(node: t$7.Node, parent: t$7.Node, grandparent?: t$7.Node): boolean;

//#endregion
//#region src/create.d.ts
/**
* Creates a string literal AST node.
*
* @param value - The value of the string literal.
* @returns The string literal AST node.
*/
declare function createStringLiteral(value: string): t$6.StringLiteral;
/**
* Creates a TypeScript union type AST node.
*
* @param types - An array of TypeScript types.
* @returns The TypeScript union type AST node.
*/
declare function createTSUnionType(types: t$6.TSType[]): t$6.TSUnionType;
/**
* Creates a TypeScript literal type AST node.
*
* @param literal - The literal value.
* @returns The TypeScript literal type AST node.
*/
declare function createTSLiteralType(literal: t$6.TSLiteralType["literal"]): t$6.TSLiteralType;

//#endregion
//#region src/extract.d.ts
/**
* Extract identifiers of the given node.
* @param node The node to extract.
* @param identifiers The array to store the extracted identifiers.
* @see https://github.com/vuejs/core/blob/1f6a1102aa09960f76a9af2872ef01e7da8538e3/packages/compiler-core/src/babelUtils.ts#L208
*/
declare function extractIdentifiers(node: t$5.Node, identifiers?: t$5.Identifier[]): t$5.Identifier[];

//#endregion
//#region src/lang.d.ts
declare const REGEX_DTS: RegExp;
declare const REGEX_LANG_TS: RegExp;
declare const REGEX_LANG_JSX: RegExp;
/**
* Returns the language (extension name) of a given filename.
* @param filename - The name of the file.
* @returns The language of the file.
*/
declare function getLang(filename: string): string;
/**
* Checks if a filename represents a TypeScript declaration file (.d.ts).
* @param filename - The name of the file to check.
* @returns A boolean value indicating whether the filename is a TypeScript declaration file.
*/
declare function isDts(filename: string): boolean;
/**
* Checks if the given language (ts, mts, cjs, dts, tsx...) is TypeScript.
* @param lang - The language to check.
* @returns A boolean indicating whether the language is TypeScript.
*/
declare function isTs(lang?: string): boolean;

//#endregion
//#region src/loc.d.ts
/**
* Locates the trailing comma in the given code within the specified range.
*
* @param code - The code to search for the trailing comma.
* @param start - The start index of the range to search within.
* @param end - The end index of the range to search within.
* @param comments - Optional array of comments to exclude from the search range.
* @returns The index of the trailing comma, or -1 if not found.
*/
declare function locateTrailingComma(code: string, start: number, end: number, comments?: t$4.Comment[]): number;

//#endregion
//#region src/parse.d.ts
/**
* Gets the Babel parser options for the given language.
* @param lang - The language of the code (optional).
* @param options - The parser options (optional).
* @returns The Babel parser options for the given language.
*/
declare function getBabelParserOptions(lang?: string, options?: ParserOptions): ParserOptions;
/**
* Parses the given code using Babel parser.
*
* @param code - The code to parse.
* @param lang - The language of the code (optional).
* @param options - The parser options (optional).
* @param options.cache - Whether to cache the result (optional).
* @returns The parse result, including the program, errors, and comments.
*/
declare function babelParse(code: string, lang?: string, { cache,...options }?: ParserOptions & {
	cache?: boolean
}): t$3.Program & Omit<ParseResult<t$3.File>, "type" | "program">;
declare const parseCache: Map<string, ParseResult<t$3.File>>;
/**
* Parses the given code using the Babel parser as an expression.
*
* @template T - The type of the parsed AST node.
* @param {string} code - The code to parse.
* @param {string} [lang] - The language to parse. Defaults to undefined.
* @param {ParserOptions} [options] - The options to configure the parser. Defaults to an empty object.
* @returns {ParseResult<T>} - The result of the parsing operation.
*/
declare function babelParseExpression<T extends t$3.Node = t$3.Expression>(code: string, lang?: string, options?: ParserOptions): T & {
	errors: null | ParseError[]
};

//#endregion
//#region src/resolve.d.ts
/**
* Resolves a string representation of the given node.
* @param node The node to resolve.
* @param computed Whether the node is computed or not.
* @returns The resolved string representation of the node.
*/
declare function resolveString(node: string | t$2.Identifier | t$2.Literal | t$2.PrivateName | t$2.ThisExpression | t$2.Super, computed?: boolean): string;
/**
* Resolves the value of a literal node.
* @param node The literal node to resolve.
* @returns The resolved value of the literal node.
*/
declare function resolveLiteral(node: t$2.Literal): string | number | boolean | null | RegExp | bigint;
/**
* Resolves a template literal node into a string.
* @param node The template literal node to resolve.
* @returns The resolved string representation of the template literal.
*/
declare function resolveTemplateLiteral(node: t$2.TemplateLiteral): string;
/**
* Resolves the identifier node into an array of strings.
* @param node The identifier node to resolve.
* @returns An array of resolved strings representing the identifier.
* @throws TypeError If the identifier is invalid.
*/
declare function resolveIdentifier(node: t$2.Identifier | t$2.PrivateName | t$2.MemberExpression | t$2.ThisExpression | t$2.Super | t$2.TSEntityName): string[];
declare function tryResolveIdentifier(...args: Parameters<typeof resolveIdentifier>): string[] | undefined;
type ObjectPropertyLike = t$2.ObjectMethod | t$2.ObjectProperty | t$2.TSMethodSignature | t$2.TSPropertySignature | t$2.ImportAttribute;
/**
* Resolves the key of an object property-like node.
* @param node The object property-like node to resolve.
* @param raw Whether to return the raw value of the key or not.
* @returns The resolved key of the object property-like node.
*/
declare function resolveObjectKey(node: ObjectPropertyLike, raw?: false): string | number;
declare function resolveObjectKey(node: ObjectPropertyLike, raw: true): string;

//#endregion
//#region src/scope.d.ts
interface AttachedScope {
	parent?: AttachedScope;
	isBlockScope: boolean;
	declarations: {
		[key: string]: boolean
	};
	addDeclaration: (node: Node, isBlockDeclaration: boolean, isVar: boolean) => void;
	contains: (name: string) => boolean;
}
type WithScope<T> = T & {
	scope?: AttachedScope
};
/**
* Options for creating a new scope.
*/
interface ScopeOptions {
	parent?: AttachedScope;
	block?: boolean;
	params?: Node[];
}
/**
* Represents a scope.
*/
declare class Scope implements AttachedScope {
	parent?: AttachedScope;
	isBlockScope: boolean;
	declarations: {
		[key: string]: boolean
	};
	constructor(options?: ScopeOptions);
	addDeclaration(node: Node, isBlockDeclaration: boolean, isVar: boolean): void;
	contains(name: string): boolean;
}
/**
* Attaches scopes to the given AST
*
* @param ast - The AST to attach scopes to.
* @param propertyName - The name of the property to attach the scopes to. Default is 'scope'.
* @returns The root scope of the AST.
*/
declare function attachScopes(ast: Node, propertyName?: string): Scope;

//#endregion
//#region src/types.d.ts
/**
* A type that represents a literal union.
*/
type LiteralUnion<
	LiteralType,
	BaseType extends null | undefined | string | number | boolean | symbol | bigint = string
> = LiteralType | (BaseType & Record<never, never>);

//#endregion
//#region src/utils.d.ts
declare const TS_NODE_TYPES: readonly ["TSAsExpression", "TSTypeAssertion", "TSNonNullExpression", "TSInstantiationExpression", "TSSatisfiesExpression"];
/**
* Unwraps a TypeScript node by recursively traversing the AST until a non-TypeScript node is found.
* @param node - The TypeScript node to unwrap.
* @returns The unwrapped node.
*/
declare function unwrapTSNode(node: t$1.Node): t$1.Node;
/**
* Escapes a raw key by checking if it needs to be wrapped with quotes or not.
*
* @param rawKey - The raw key to escape.
* @returns The escaped key.
*/
declare function escapeKey(rawKey: string): string;

//#endregion
//#region src/walk.d.ts
interface WalkThis<T> {
	skip: () => void;
	remove: () => void;
	replace: (node: T) => void;
}
type WalkCallback<
	T,
	R
> = (this: WalkThis<T>, node: T, parent: T | null | undefined, key: string | null | undefined, index: number | null | undefined) => R;
interface WalkHandlers<
	T,
	R
> {
	enter?: WalkCallback<T, R>;
	leave?: WalkCallback<T, R>;
}
/**
* Walks the AST and applies the provided handlers.
*
* @template T - The type of the AST node.
* @param {T} node - The root node of the AST.
* @param {WalkHandlers<T, void>} hooks - The handlers to be applied during the walk.
* @returns {T | null} - The modified AST node or null if the node is removed.
*/
declare const walkAST: <T = t.Node>(node: NoInfer<T>, hooks: WalkHandlers<T, void>) => T | null;
/**
* Asynchronously walks the AST starting from the given node,
* applying the provided handlers to each node encountered.
*
* @template T - The type of the AST node.
* @param {T} node - The root node of the AST.
* @param {WalkHandlers<T, Promise<void>>} handlers - The handlers to be applied to each node.
* @returns {Promise<T | null>} - A promise that resolves to the modified AST or null if the AST is empty.
*/
declare const walkASTAsync: <T = t.Node>(node: NoInfer<T>, handlers: WalkHandlers<T, Promise<void>>) => Promise<T | null>;
interface ImportBinding {
	local: string;
	imported: LiteralUnion<"*" | "default">;
	source: string;
	specifier: t.ImportSpecifier | t.ImportDefaultSpecifier | t.ImportNamespaceSpecifier;
	isType: boolean;
}
/**
* Walks through an ImportDeclaration node and populates the provided imports object.
*
* @param imports - The object to store the import bindings.
* @param node - The ImportDeclaration node to walk through.
*/
declare function walkImportDeclaration(imports: Record<string, ImportBinding>, node: t.ImportDeclaration): void;
/**
* Represents an export binding.
*/
interface ExportBinding {
	local: string;
	exported: LiteralUnion<"*" | "default">;
	isType: boolean;
	source: string | null;
	specifier: t.ExportSpecifier | t.ExportDefaultSpecifier | t.ExportNamespaceSpecifier | null;
	declaration: t.Declaration | t.ExportDefaultDeclaration["declaration"] | null;
}
/**
* Walks through an ExportDeclaration node and populates the exports object with the relevant information.
* @param exports - The object to store the export information.
* @param node - The ExportDeclaration node to process.
*/
declare function walkExportDeclaration(exports: Record<string, ExportBinding>, node: t.ExportDeclaration): void;

//#endregion
export { AttachedScope, ExportBinding, GetNode, ImportBinding, LiteralUnion, NodeType, ObjectPropertyLike, REGEX_DTS, REGEX_LANG_JSX, REGEX_LANG_TS, Scope, ScopeOptions, TS_NODE_TYPES, WithScope, attachScopes, babelParse, babelParseExpression, createStringLiteral, createTSLiteralType, createTSUnionType, escapeKey, extractIdentifiers, getBabelParserOptions, getLang, isCallOf, isDeclarationType, isDts, isExpressionType, isFunctionType, isIdentifierOf, isLiteralType, isReferenced, isTaggedFunctionCallOf, isTs, isTypeOf, locateTrailingComma, parseCache, resolveIdentifier, resolveLiteral, resolveObjectKey, resolveString, resolveTemplateLiteral, tryResolveIdentifier, unwrapTSNode, walkAST, walkASTAsync, walkExportDeclaration, walkImportDeclaration };