import type { RehypeHighlightOption } from '@nuxtjs/mdc';
export default rehypeHighlight;
export declare function rehypeHighlight(opts?: Partial<RehypeHighlightOption>): (tree: import("hast").Root) => Promise<void>;
