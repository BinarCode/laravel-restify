import { createHead as createHead$1 } from 'unhead/server';
export { extractUnheadInputFromHtml, propsToString, renderSSRHead, transformHtmlTemplate } from 'unhead/server';
import { v as vueInstall } from './shared/vue.BYLJNEcq.mjs';
import { V as VueResolver } from './shared/vue.N9zWjxoK.mjs';
export { V as VueHeadMixin } from './shared/vue.nvpYXC6D.mjs';
import 'unhead/plugins';
import 'unhead/utils';
import 'vue';

function createHead(options = {}) {
  const head = createHead$1({
    ...options,
    propResolvers: [VueResolver]
  });
  head.install = vueInstall(head);
  return head;
}

export { createHead };
