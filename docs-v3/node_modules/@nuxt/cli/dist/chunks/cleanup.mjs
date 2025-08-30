import { defineCommand } from 'citty';
import { resolve } from 'pathe';
import { l as loadKit } from '../shared/cli.qKvs7FJ2.mjs';
import { c as cleanupNuxtDirs } from '../shared/cli.At9IMXtr.mjs';
import { l as legacyRootDirArgs, c as cwdArgs } from '../shared/cli.CTXRG5Cu.mjs';
import 'node:url';
import 'exsolve';
import 'node:fs';
import 'ohash';
import '../shared/cli.B9AmABr3.mjs';
import 'consola';
import '../shared/cli.pLQ0oPGc.mjs';
import 'node:path';
import 'node:process';
import 'std-env';

const cleanup = defineCommand({
  meta: {
    name: "cleanup",
    description: "Clean up generated Nuxt files and caches"
  },
  args: {
    ...cwdArgs,
    ...legacyRootDirArgs
  },
  async run(ctx) {
    const cwd = resolve(ctx.args.cwd || ctx.args.rootDir);
    const { loadNuxtConfig } = await loadKit(cwd);
    const nuxtOptions = await loadNuxtConfig({ cwd, overrides: { dev: true } });
    await cleanupNuxtDirs(nuxtOptions.rootDir, nuxtOptions.buildDir);
  }
});

export { cleanup as default };
