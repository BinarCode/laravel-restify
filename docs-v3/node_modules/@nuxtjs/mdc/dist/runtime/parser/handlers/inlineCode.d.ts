import type { State } from 'mdast-util-to-hast';
import type { Element, Properties } from 'hast';
import type { InlineCode } from 'mdast';
export default function inlineCode(state: State, node: InlineCode & {
    attributes?: Properties;
}): Element;
