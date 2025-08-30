import type { MDCNode } from '@nuxtjs/mdc';
export declare function flattenNodeText(node: MDCNode): string;
export declare function flattenNode(node: MDCNode, maxDepth?: number, _depth?: number): Array<MDCNode>;
export declare function setNodeData(node: MDCNode & {
    data: any;
}, name: string, value: any, pageData: any): void;
