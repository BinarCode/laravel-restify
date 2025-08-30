import type { State, Raw } from 'mdast-util-to-hast';
import type { Html } from 'mdast';
export default function html(state: State, node: Html): import("hast").Element | Raw | undefined;
