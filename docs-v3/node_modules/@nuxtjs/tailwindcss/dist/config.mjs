import { createDefineConfig } from 'c12';
import { getContext } from 'unctx';
import { tryUseNuxt, requireModule } from '@nuxt/kit';

const ctx = getContext("tw-config-ctx");
createDefineConfig();
const defineConfig = (config) => {
  const isNuxt = !!tryUseNuxt();
  if (isNuxt || ctx.tryUse()) {
    return config;
  }
  const nuxtTwConfig = requireModule("./.nuxt/tailwind/postcss.mjs", { paths: [process.cwd()] });
  return nuxtTwConfig?.default || nuxtTwConfig;
};

export { ctx, defineConfig as default, defineConfig };
