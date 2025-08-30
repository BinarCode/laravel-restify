import process from 'node:process';
import { defineCommand } from 'citty';
import { resolve, relative } from 'pathe';
import { a as clearBuildDir } from '../shared/cli.pLQ0oPGc.mjs';
import { l as loadKit } from '../shared/cli.qKvs7FJ2.mjs';
import { l as logger } from '../shared/cli.B9AmABr3.mjs';
import { l as legacyRootDirArgs, e as extendsArgs, b as envNameArgs, a as logLevelArgs, c as cwdArgs, d as dotEnvArgs } from '../shared/cli.CTXRG5Cu.mjs';
import 'node:fs';
import 'node:url';
import 'exsolve';
import 'consola';
import 'node:path';
import 'std-env';

const prepare = defineCommand({
  meta: {
    name: "prepare",
    description: "Prepare Nuxt for development/build"
  },
  args: {
    ...dotEnvArgs,
    ...cwdArgs,
    ...logLevelArgs,
    ...envNameArgs,
    ...extendsArgs,
    ...legacyRootDirArgs
  },
  async run(ctx) {
    process.env.NODE_ENV = process.env.NODE_ENV || "production";
    const cwd = resolve(ctx.args.cwd || ctx.args.rootDir);
    const { loadNuxt, buildNuxt, writeTypes } = await loadKit(cwd);
    const nuxt = await loadNuxt({
      cwd,
      dotenv: {
        cwd,
        fileName: ctx.args.dotenv
      },
      envName: ctx.args.envName,
      // c12 will fall back to NODE_ENV
      overrides: {
        _prepare: true,
        logLevel: ctx.args.logLevel,
        ...ctx.args.extends && { extends: ctx.args.extends },
        ...ctx.data?.overrides
      }
    });
    await clearBuildDir(nuxt.options.buildDir);
    await buildNuxt(nuxt);
    await writeTypes(nuxt);
    logger.success(
      "Types generated in",
      relative(process.cwd(), nuxt.options.buildDir)
    );
  }
});

export { prepare as default };
