import type { State } from 'mdast-util-to-hast';
import type { Element } from 'hast';
import type { Paragraph } from 'mdast';
export default function paragraph(state: State, node: Paragraph): Element | import("hast").ElementContent[];
