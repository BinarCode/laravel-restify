import MagicString__default, { MagicStringOptions, OverwriteOptions } from 'magic-string';
export * from 'magic-string';
export { default as MagicString } from 'magic-string';
import { Node } from '@babel/types';

interface MagicStringAST extends MagicString__default {
}
/**
 * MagicString with AST manipulation
 */
declare class MagicStringAST implements MagicString__default {
    private prototype;
    s: MagicString__default;
    constructor(str: string | MagicString__default, options?: MagicStringOptions, prototype?: typeof MagicString__default);
    private getNodePos;
    removeNode(node: Node | Node[], { offset }?: {
        offset?: number;
    }): this;
    moveNode(node: Node | Node[], index: number, { offset }?: {
        offset?: number;
    }): this;
    sliceNode(node: Node | Node[], { offset }?: {
        offset?: number;
    }): string;
    overwriteNode(node: Node | Node[], content: string | Node | Node[], { offset, ...options }?: OverwriteOptions & {
        offset?: number;
    }): this;
    snipNode(node: Node | Node[], { offset }?: {
        offset?: number;
    }): MagicStringAST;
    clone(): this;
    toString(): string;
}
/**
 * The result of code transformation.
 */
interface CodeTransform {
    code: string;
    map: any;
}
/**
 * Generate an object of code and source map from MagicString.
 */
declare function generateTransform(s: MagicString__default | undefined, id: string): CodeTransform | undefined;

export { type CodeTransform, MagicStringAST, generateTransform };
