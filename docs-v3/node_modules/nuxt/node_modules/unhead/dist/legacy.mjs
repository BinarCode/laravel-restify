import { a as createUnhead } from './shared/unhead.DH45uomy.mjs';
import { D as DeprecationsPlugin, P as PromisesPlugin, T as TemplateParamsPlugin, A as AliasSortingPlugin } from './shared/unhead.Djo8ep_Y.mjs';
export { u as useHead, a as useHeadSafe, b as useSeoMeta, c as useServerHead, d as useServerHeadSafe, e as useServerSeoMeta } from './shared/unhead.BPM0-cfG.mjs';
export { u as useScript } from './shared/unhead.B578PsDV.mjs';
import 'hookable';
import './shared/unhead.BpRRHAhY.mjs';
import './shared/unhead.yem5I2v_.mjs';
import './shared/unhead.DZbvapt-.mjs';
import './shared/unhead.CApf5sj3.mjs';
import './shared/unhead.DQc16pHI.mjs';
import './shared/unhead.BYvz9V1x.mjs';

const activeHead = { value: null };
function getActiveHead() {
  return activeHead?.value;
}
function createServerHead(options = {}) {
  return activeHead.value = createUnhead({
    disableCapoSorting: true,
    ...options,
    // @ts-expect-error untyped
    document: false,
    plugins: [
      ...options.plugins || [],
      DeprecationsPlugin,
      PromisesPlugin,
      TemplateParamsPlugin,
      AliasSortingPlugin
    ]
  });
}
function createHead(options = {}) {
  return activeHead.value = createUnhead({
    disableCapoSorting: true,
    ...options,
    plugins: [
      ...options.plugins || [],
      DeprecationsPlugin,
      PromisesPlugin,
      TemplateParamsPlugin,
      AliasSortingPlugin
    ]
  });
}
const createHeadCore = createUnhead;

export { activeHead, createHead, createHeadCore, createServerHead, createUnhead, getActiveHead };
