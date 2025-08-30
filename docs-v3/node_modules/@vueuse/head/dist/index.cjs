'use strict';

const vue = require('@unhead/vue');
const polyfill = require('@unhead/vue/polyfill');
const ssr = require('@unhead/ssr');
const components = require('@unhead/vue/components');

function createHead(initHeadObject, options) {
  const unhead = vue.createHead(options || {});
  const legacyHead = polyfill.polyfillAsVueUseHead(unhead);
  if (initHeadObject)
    legacyHead.push(initHeadObject);
  return legacyHead;
}

const HeadVuePlugin = vue.Vue2ProvideUnheadPlugin;
const renderHeadToString = (head) => ssr.renderSSRHead(head.unhead);

exports.Vue2ProvideUnheadPlugin = vue.Vue2ProvideUnheadPlugin;
exports.VueHeadMixin = vue.VueHeadMixin;
exports.createHeadCore = vue.createHeadCore;
exports.injectHead = vue.injectHead;
exports.unheadVueComposablesImports = vue.unheadVueComposablesImports;
exports.useHead = vue.useHead;
exports.useHeadSafe = vue.useHeadSafe;
exports.useSeoMeta = vue.useSeoMeta;
exports.useServerHead = vue.useServerHead;
exports.useServerHeadSafe = vue.useServerHeadSafe;
exports.useServerSeoMeta = vue.useServerSeoMeta;
exports.Head = components.Head;
exports.HeadVuePlugin = HeadVuePlugin;
exports.createHead = createHead;
exports.renderHeadToString = renderHeadToString;
