import type { State } from 'mdast-util-to-hast';
import type { Element, Properties } from 'hast';
import type { Strong } from 'mdast';
export default function strong(state: State, node: Strong & {
    attributes?: Properties;
}): Element;
