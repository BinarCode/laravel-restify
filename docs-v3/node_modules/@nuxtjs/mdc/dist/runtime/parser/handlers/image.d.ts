import type { State } from 'mdast-util-to-hast';
import type { Element, Properties } from 'hast';
import type { Image } from 'mdast';
export default function image(state: State, node: Image & {
    attributes?: Properties;
}): Element;
