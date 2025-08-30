import process from 'node:process';
import { defineCommand } from 'citty';
import { resolve } from 'pathe';
import { isTest } from 'std-env';
import { l as legacyRootDirArgs, d as dotEnvArgs, b as envNameArgs, a as logLevelArgs, c as cwdArgs } from '../shared/cli.CTXRG5Cu.mjs';
import 'node:path';
import 'consola';
import '../shared/cli.B9AmABr3.mjs';
import 'node:url';

const devChild = defineCommand({
  meta: {
    name: "_dev",
    description: "Run Nuxt development server (internal command to start child process)"
  },
  args: {
    ...cwdArgs,
    ...logLevelArgs,
    ...envNameArgs,
    ...dotEnvArgs,
    ...legacyRootDirArgs,
    clear: {
      type: "boolean",
      description: "Clear console on restart",
      negativeDescription: "Disable clear console on restart"
    }
  },
  async run(ctx) {
    if (!process.send && !isTest) {
      console.warn("`nuxi _dev` is an internal command and should not be used directly. Please use `nuxi dev` instead.");
    }
    const cwd = resolve(ctx.args.cwd || ctx.args.rootDir);
    const { initialize } = await import('./index.mjs').then(function (n) { return n.c; });
    await initialize({ cwd, args: ctx.args }, ctx);
  }
});

export { devChild as default };
