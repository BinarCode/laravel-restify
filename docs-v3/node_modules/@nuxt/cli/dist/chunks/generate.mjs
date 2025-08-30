import { defineCommand } from 'citty';
import { l as legacyRootDirArgs, e as extendsArgs, b as envNameArgs, d as dotEnvArgs, a as logLevelArgs, c as cwdArgs } from '../shared/cli.CTXRG5Cu.mjs';
import buildCommand from './build.mjs';
import 'node:path';
import 'node:process';
import 'std-env';
import 'consola';
import '../shared/cli.B9AmABr3.mjs';
import 'node:url';
import 'pathe';
import '../shared/cli.Dz2be-Ai.mjs';
import 'node:fs';
import 'consola/utils';
import 'exsolve';
import '../shared/cli.qKvs7FJ2.mjs';
import '../shared/cli.BEUGgaW4.mjs';
import '../shared/cli.pLQ0oPGc.mjs';

const generate = defineCommand({
  meta: {
    name: "generate",
    description: "Build Nuxt and prerender all routes"
  },
  args: {
    ...cwdArgs,
    ...logLevelArgs,
    preset: {
      type: "string",
      description: "Nitro server preset"
    },
    ...dotEnvArgs,
    ...envNameArgs,
    ...extendsArgs,
    ...legacyRootDirArgs
  },
  async run(ctx) {
    ctx.args.prerender = true;
    await buildCommand.run(
      // @ts-expect-error types do not match
      ctx
    );
  }
});

export { generate as default };
