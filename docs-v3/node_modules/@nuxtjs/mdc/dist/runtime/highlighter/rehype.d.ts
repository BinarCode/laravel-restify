import type { Root } from 'hast';
import type { RehypeHighlightOption } from '@nuxtjs/mdc';
export default rehypeHighlight;
export declare function rehypeHighlight(opts: RehypeHighlightOption): (tree: Root) => Promise<void>;
