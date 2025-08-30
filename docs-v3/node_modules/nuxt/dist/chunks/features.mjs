import { addDependency } from 'nypm';
import { resolvePackageJSON } from 'pkg-types';
import { useNuxt } from '@nuxt/kit';
import { isCI, provider } from 'std-env';
import { l as logger } from '../shared/nuxt.DAkeCY9U.mjs';
import 'node:fs';
import 'node:fs/promises';
import 'node:crypto';
import 'node:async_hooks';
import 'pathe';
import 'hookable';
import 'ignore';
import 'ohash';
import 'consola';
import 'on-change';
import 'consola/utils';
import 'compatx';
import 'escape-string-regexp';
import 'ufo';
import 'impound';
import 'defu';
import 'semver';
import 'knitwork';
import 'exsolve';
import 'unplugin-vue-router';
import 'unplugin-vue-router/options';
import 'radix3';
import 'node:url';
import 'node:vm';
import 'pathe/utils';
import 'klona';
import 'estree-walker';
import 'esbuild';
import 'oxc-parser';
import 'scule';
import 'unplugin';
import 'mlly';
import 'magic-string';
import 'strip-literal';
import '@unhead/vue';
import 'tinyglobby';
import 'ultrahtml';
import 'unimport';
import 'vue-router';
import 'unctx/transform';
import 'node:os';
import 'nitropack';
import 'h3';
import 'chokidar';
import 'perfect-debounce';
import 'untyped';
import 'untyped/babel-plugin';
import 'jiti';
import 'node:path';
import 'nanotar';

const isStackblitz = provider === "stackblitz";
async function promptToInstall(name, installCommand, options) {
  for (const parent of options.searchPaths || []) {
    if (await resolvePackageJSON(name, { parent }).catch(() => null)) {
      return true;
    }
  }
  logger.info(`Package ${name} is missing`);
  if (isCI) {
    return false;
  }
  if (options.prompt === true || options.prompt !== false && !isStackblitz) {
    const confirm = await logger.prompt(`Do you want to install ${name} package?`, {
      type: "confirm",
      name: "confirm",
      initial: true
    });
    if (!confirm) {
      return false;
    }
  }
  logger.info(`Installing ${name}...`);
  try {
    await installCommand();
    logger.success(`Installed ${name}`);
    return true;
  } catch (err) {
    logger.error(err);
    return false;
  }
}
const installPrompts = /* @__PURE__ */ new Set();
function installNuxtModule(name, options) {
  if (installPrompts.has(name)) {
    return;
  }
  installPrompts.add(name);
  const nuxt = useNuxt();
  return promptToInstall(name, async () => {
    const { runCommand } = await import('@nuxt/cli');
    await runCommand("module", ["add", name, "--cwd", nuxt.options.rootDir]);
  }, { rootDir: nuxt.options.rootDir, searchPaths: nuxt.options.modulesDir, ...options });
}
function ensurePackageInstalled(name, options) {
  return promptToInstall(name, () => addDependency(name, {
    cwd: options.rootDir,
    dev: true
  }), options);
}

export { ensurePackageInstalled, installNuxtModule };
