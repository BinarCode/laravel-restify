import { R as ResolvableHead, q as CreateClientHeadOptions, U as Unhead, G as RenderDomHeadOptions } from './shared/unhead.BxIzrSMV.js';
import 'hookable';

declare function createHead<T = ResolvableHead>(options?: CreateClientHeadOptions): Unhead<T>;

/**
 * Render the head tags to the DOM.
 */
declare function renderDOMHead<T extends Unhead<any>>(head: T, options?: RenderDomHeadOptions): Promise<void>;

declare function createDebouncedFn(callee: () => void, delayer: (fn: () => void) => void): () => void;

export { CreateClientHeadOptions, Unhead, createDebouncedFn, createHead, renderDOMHead };
