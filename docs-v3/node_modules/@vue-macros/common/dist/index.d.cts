import { MagicString, generateTransform, CodeTransform, MagicStringAST } from 'magic-string-ast';
export * from 'magic-string-ast';
export * from 'ast-kit';
import * as t from '@babel/types';
import { Program, Node } from '@babel/types';
import { ResolvedOptions } from '@vitejs/plugin-vue';
import { Plugin } from 'rollup';
import { Plugin as Plugin$1, HmrContext } from 'vite';
import { SFCScriptBlock as SFCScriptBlock$1, SFCDescriptor, SFCParseResult } from '@vue/compiler-sfc';

declare function checkInvalidScopeReference(node: t.Node | undefined, method: string, setupBindings: string[]): void;
declare function isStaticExpression(node: t.Node, options?: Partial<Record<"object" | "fn" | "objectMethod" | "array" | "unary" | "regex", boolean> & { magicComment?: string }>): boolean;
declare function isStaticObjectKey(node: t.ObjectExpression): boolean;
/**
* @param node must be a static expression, SpreadElement is not supported
*/
declare function resolveObjectExpression(node: t.ObjectExpression): Record<string | number, t.ObjectMethod | t.ObjectProperty> | undefined;
declare const HELPER_PREFIX = "__MACROS_";
declare function importHelperFn(s: MagicString, offset: number, local: string, from?: string, isDefault?: boolean): string;

declare const DEFINE_PROPS = "defineProps";
declare const DEFINE_PROPS_DOLLAR = "$defineProps";
declare const DEFINE_PROPS_REFS = "definePropsRefs";
declare const DEFINE_EMITS = "defineEmits";
declare const WITH_DEFAULTS = "withDefaults";
declare const DEFINE_OPTIONS = "defineOptions";
declare const DEFINE_MODELS = "defineModels";
declare const DEFINE_MODELS_DOLLAR = "$defineModels";
declare const DEFINE_SETUP_COMPONENT = "defineSetupComponent";
declare const DEFINE_RENDER = "defineRender";
declare const DEFINE_SLOTS = "defineSlots";
declare const DEFINE_STYLEX = "defineStyleX";
declare const DEFINE_PROP = "defineProp";
declare const DEFINE_PROP_DOLLAR = "$defineProp";
declare const DEFINE_EMIT = "defineEmit";
declare const REPO_ISSUE_URL: "https://github.com/vue-macros/vue-macros/issues";
declare const REGEX_SRC_FILE: RegExp;
declare const REGEX_SETUP_SFC: RegExp;
declare const REGEX_SETUP_SFC_SUB: RegExp;
declare const REGEX_VUE_SFC: RegExp;
/** webpack only */
declare const REGEX_VUE_SUB: RegExp;
/** webpack only */
declare const REGEX_VUE_SUB_SETUP: RegExp;
declare const REGEX_NODE_MODULES: RegExp;
declare const REGEX_SUPPORTED_EXT: RegExp;
declare const VIRTUAL_ID_PREFIX = "/vue-macros";

declare function detectVueVersion(root?: string, defaultVersion?: number): number;

/**
* A valid `picomatch` glob pattern, or array of patterns.
*/
type FilterPattern = ReadonlyArray<string | RegExp> | string | RegExp | null;
/**
* Constructs a filter function which can be used to determine whether or not
* certain modules should be operated upon.
* @param include If `include` is omitted or has zero length, filter will return `true` by default.
* @param exclude ID must not match any of the `exclude` patterns.
* @param options Additional options.
* @param options.resolve Optionally resolves the patterns against a directory other than `process.cwd()`.
* If a `string` is specified, then the value will be used as the base directory.
* Relative paths will be resolved against `process.cwd()` first.
* If `false`, then the patterns will not be resolved against any directory.
* This can be useful if you want to create a filter for virtual module names.
*/
declare function createRollupFilter(include?: FilterPattern, exclude?: FilterPattern, options?: { resolve?: string | false | null }): (id: string | unknown) => boolean;

declare function toArray<T>(thing: readonly T[] | T | undefined | null): readonly T[];

/** @deprecated use `generateTransform` instead */
declare const getTransformResult: typeof generateTransform;
interface FilterOptions {
	include?: FilterPattern;
	exclude?: FilterPattern;
}
declare function createFilter(options: FilterOptions): (id: string | unknown) => boolean;
interface VuePluginApi {
	options: ResolvedOptions;
	version: string;
}
declare function getVuePluginApi(plugins: Readonly<(Plugin | Plugin$1)[]> | undefined): VuePluginApi | null;
declare enum FilterFileType {
	/** Vue SFC */
	VUE_SFC = 0,
	/** Vue SFC with `<script setup>` */
	VUE_SFC_WITH_SETUP = 1,
	/** foo.setup.tsx */
	SETUP_SFC = 2,
	/** Source files */
	SRC_FILE = 3,
}
declare function getFilterPattern(types: FilterFileType[], framework?: string): RegExp[];
declare function hackViteHMR(ctx: HmrContext, filter: (id: string | unknown) => boolean, callback: (code: string, id: string) => CodeTransform | undefined | Promise<CodeTransform | undefined>): void;

interface BaseOptions extends FilterOptions {
	version?: number;
	isProduction?: boolean;
}

/**
* Converts path separators to forward slash.
*/
declare function normalizePath(filename: string): string;

type MarkRequired<
	T,
	K extends keyof T
> = Omit<T, K> & Required<Pick<T, K>>;
type Overwrite<
	T,
	U
> = Pick<T, Exclude<keyof T, keyof U>> & U;
type RecordToUnion<T extends Record<string, any>> = T[keyof T];
type UnionToIntersection<U> = (U extends unknown ? (arg: U) => 0 : never) extends (arg: infer I) => 0 ? I : never;

type SFCScriptBlock = Omit<SFCScriptBlock$1, "scriptAst" | "scriptSetupAst">;
type SFC = Omit<SFCDescriptor, "script" | "scriptSetup"> & {
	sfc: SFCParseResult;
	script?: SFCScriptBlock | null;
	scriptSetup?: SFCScriptBlock | null;
	lang: string | undefined;
	getScriptAst: () => Program | undefined;
	getSetupAst: () => Program | undefined;
	offset: number;
} & Pick<SFCParseResult, "errors">;
declare function parseSFC(code: string, id: string): SFC;
declare function getFileCodeAndLang(code: string, id: string): {
	code: string;
	lang: string;
};
declare function addNormalScript({ script, lang }: SFC, s: MagicString): {
	start(): number;
	end(): void;
};
declare function removeMacroImport(node: Node, s: MagicStringAST, offset: number): true | undefined;

export { type BaseOptions, DEFINE_EMIT, DEFINE_EMITS, DEFINE_MODELS, DEFINE_MODELS_DOLLAR, DEFINE_OPTIONS, DEFINE_PROP, DEFINE_PROPS, DEFINE_PROPS_DOLLAR, DEFINE_PROPS_REFS, DEFINE_PROP_DOLLAR, DEFINE_RENDER, DEFINE_SETUP_COMPONENT, DEFINE_SLOTS, DEFINE_STYLEX, FilterFileType, type FilterOptions, type FilterPattern, HELPER_PREFIX, type MarkRequired, type Overwrite, REGEX_NODE_MODULES, REGEX_SETUP_SFC, REGEX_SETUP_SFC_SUB, REGEX_SRC_FILE, REGEX_SUPPORTED_EXT, REGEX_VUE_SFC, REGEX_VUE_SUB, REGEX_VUE_SUB_SETUP, REPO_ISSUE_URL, type RecordToUnion, type SFC, type SFCScriptBlock, type UnionToIntersection, VIRTUAL_ID_PREFIX, type VuePluginApi, WITH_DEFAULTS, addNormalScript, checkInvalidScopeReference, createFilter, createRollupFilter, detectVueVersion, getFileCodeAndLang, getFilterPattern, getTransformResult, getVuePluginApi, hackViteHMR, importHelperFn, isStaticExpression, isStaticObjectKey, normalizePath, parseSFC, removeMacroImport, resolveObjectExpression, toArray };
