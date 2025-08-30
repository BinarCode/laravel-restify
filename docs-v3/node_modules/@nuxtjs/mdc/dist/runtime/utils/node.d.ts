import type { VNode } from 'vue';
import type { MDCElement, MDCNode } from '@nuxtjs/mdc';
/**
 * List of text nodes
 */
export declare const TEXT_TAGS: string[];
/**
 * Check virtual node's tag
 * @param vnode Virtual node from Vue virtual DOM
 * @param tag tag name
 * @returns `true` it the virtual node match the tag
 */
export declare function isTag(vnode: VNode | MDCNode, tag: string | symbol): boolean;
/**
 * Check if virtual node is text node
 */
export declare function isText(vnode: VNode | MDCNode): boolean;
/**
 * Find children of a virtual node
 * @param node Virtuel node from Vue virtual DOM
 * @returns Children of given node
 */
export declare function nodeChildren(node: VNode | MDCElement): any;
/**
 * Calculate text content of a virtual node
 * @param node Virtuel node from Vue virtual DOM
 * @returns text content of given node
 */
export declare function nodeTextContent(node: VNode | MDCNode): string;
/**
 * Unwrap tags within a virtual node
 * @param vnode Virtuel node from Vue virtual DOM
 * @param tags list of tags to unwrap
 *
 */
export declare function unwrap(vnode: VNode, tags?: string[]): VNode | VNode[];
export declare function flatUnwrap(vnodes: VNode | VNode[], tags?: string | string[]): Array<VNode | string> | VNode;
