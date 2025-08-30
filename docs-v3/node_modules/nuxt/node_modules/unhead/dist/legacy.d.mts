import { createUnhead } from './index.mjs';
export { useHead, useHeadSafe, useSeoMeta, useServerHead, useServerHeadSafe, useServerSeoMeta } from './index.mjs';
import { U as Unhead, R as ResolvableHead, C as CreateHeadOptions } from './shared/unhead.BxIzrSMV.mjs';
export { u as useScript } from './shared/unhead.YQlj2HXR.mjs';
import './shared/unhead.DcRvKVx9.mjs';
import 'hookable';

declare const activeHead: {
    value: Unhead<any> | null;
};
declare function getActiveHead(): Unhead<any> | null;
declare function createServerHead<T extends Record<string, any> = ResolvableHead>(options?: CreateHeadOptions): Unhead<T>;
declare function createHead<T extends Record<string, any> = ResolvableHead>(options?: CreateHeadOptions): Unhead<T>;
declare const createHeadCore: typeof createUnhead;

export { activeHead, createHead, createHeadCore, createServerHead, createUnhead, getActiveHead };
