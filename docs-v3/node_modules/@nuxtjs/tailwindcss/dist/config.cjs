'use strict';

Object.defineProperty(exports, '__esModule', { value: true });

const c12 = require('c12');
const unctx = require('unctx');
const kit = require('@nuxt/kit');

const ctx = unctx.getContext("tw-config-ctx");
c12.createDefineConfig();
const defineConfig = (config) => {
  const isNuxt = !!kit.tryUseNuxt();
  if (isNuxt || ctx.tryUse()) {
    return config;
  }
  const nuxtTwConfig = kit.requireModule("./.nuxt/tailwind/postcss.mjs", { paths: [process.cwd()] });
  return nuxtTwConfig?.default || nuxtTwConfig;
};

exports.ctx = ctx;
exports.default = defineConfig;
exports.defineConfig = defineConfig;
