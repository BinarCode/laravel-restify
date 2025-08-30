import type { State } from 'mdast-util-to-hast';
import type { Element, Properties } from 'hast';
import type { Link } from 'mdast';
export default function link(state: State, node: Link & {
    attributes?: Properties;
}): Element;
