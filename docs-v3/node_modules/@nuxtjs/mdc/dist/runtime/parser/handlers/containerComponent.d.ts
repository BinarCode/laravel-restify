import type { State } from 'mdast-util-to-hast';
import type { Element, Properties } from 'hast';
import type { Nodes as MdastContent } from 'mdast';
type Node = MdastContent & {
    name: string;
    attributes?: Properties;
    fmAttributes?: Properties;
};
export default function containerComponent(state: State, node: Node): Element;
export {};
