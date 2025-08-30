import { createHead as createHead$1, Vue2ProvideUnheadPlugin } from '@unhead/vue';
export { Vue2ProvideUnheadPlugin, VueHeadMixin, createHeadCore, injectHead, unheadVueComposablesImports, useHead, useHeadSafe, useSeoMeta, useServerHead, useServerHeadSafe, useServerSeoMeta } from '@unhead/vue';
import { polyfillAsVueUseHead } from '@unhead/vue/polyfill';
import { renderSSRHead } from '@unhead/ssr';
export { Head } from '@unhead/vue/components';

function createHead(initHeadObject, options) {
  const unhead = createHead$1(options || {});
  const legacyHead = polyfillAsVueUseHead(unhead);
  if (initHeadObject)
    legacyHead.push(initHeadObject);
  return legacyHead;
}

const HeadVuePlugin = Vue2ProvideUnheadPlugin;
const renderHeadToString = (head) => renderSSRHead(head.unhead);

export { HeadVuePlugin, createHead, renderHeadToString };
