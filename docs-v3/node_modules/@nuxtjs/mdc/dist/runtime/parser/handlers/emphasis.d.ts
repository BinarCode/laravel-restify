import type { State } from 'mdast-util-to-hast';
import type { Element, Properties } from 'hast';
import type { Emphasis } from 'mdast';
export default function emphasis(state: State, node: Emphasis & {
    attributes?: Properties;
}): Element;
