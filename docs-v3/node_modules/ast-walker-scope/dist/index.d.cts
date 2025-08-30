import * as _babel_parser from '@babel/parser';
import { ParserPlugin } from '@babel/parser';
import * as _babel_types from '@babel/types';
import { Node, Function, Identifier, VariableDeclaration } from '@babel/types';

interface ParseOptions {
    filename?: string;
    parserPlugins?: ParserPlugin[];
}
type Scope = Record<string, Node>;
interface WalkerContext {
    skip: () => void;
    remove: () => void;
    replace: (node: Node) => void;
}
interface ScopeContext {
    parent: Node | undefined | null;
    key: string | undefined | null;
    index: number | undefined | null;
    scope: Scope;
    scopes: Scope[];
    level: number;
}
interface WalkerHooks {
    enter?: (this: WalkerContext & ScopeContext, node: Node) => void;
    enterAfter?: (this: ScopeContext, node: Node) => void;
    leave?: (this: WalkerContext & ScopeContext, node: Node) => void;
    leaveAfter?: (this: ScopeContext, node: Node) => void;
}

declare const isNewScope: (node: Node | undefined | null) => boolean;
declare function walkFunctionParams(node: Function, onIdent: (id: Identifier) => void): void;
declare function extractIdentifiers(param: Node, nodes?: Identifier[]): Identifier[];
declare function babelParse(code: string, filename?: string, parserPlugins?: ParserPlugin[]): _babel_parser.ParseResult<_babel_types.File>;
declare function walkVariableDeclaration(stmt: VariableDeclaration, register: (id: Identifier) => void): void;
declare function walkNewIdentifier(node: Node, register: (id: Identifier) => void): void;

declare const walk: (code: string, walkHooks: WalkerHooks, { filename, parserPlugins }?: ParseOptions) => _babel_parser.ParseResult<_babel_types.File>;
declare const walkAST: (node: Node | Node[], { enter, leave, enterAfter, leaveAfter }: WalkerHooks) => void;
declare const getRootScope: (nodes: Node[]) => Scope;

export { type ParseOptions, type Scope, type ScopeContext, type WalkerContext, type WalkerHooks, babelParse, extractIdentifiers, getRootScope, isNewScope, walk, walkAST, walkFunctionParams, walkNewIdentifier, walkVariableDeclaration };
