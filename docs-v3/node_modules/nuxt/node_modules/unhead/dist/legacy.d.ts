import { createUnhead } from './index.js';
export { useHead, useHeadSafe, useSeoMeta, useServerHead, useServerHeadSafe, useServerSeoMeta } from './index.js';
import { U as Unhead, R as ResolvableHead, C as CreateHeadOptions } from './shared/unhead.BxIzrSMV.js';
export { u as useScript } from './shared/unhead.jgc6RGmf.js';
import './shared/unhead.BzieZHWV.js';
import 'hookable';

declare const activeHead: {
    value: Unhead<any> | null;
};
declare function getActiveHead(): Unhead<any> | null;
declare function createServerHead<T extends Record<string, any> = ResolvableHead>(options?: CreateHeadOptions): Unhead<T>;
declare function createHead<T extends Record<string, any> = ResolvableHead>(options?: CreateHeadOptions): Unhead<T>;
declare const createHeadCore: typeof createUnhead;

export { activeHead, createHead, createHeadCore, createServerHead, createUnhead, getActiveHead };
