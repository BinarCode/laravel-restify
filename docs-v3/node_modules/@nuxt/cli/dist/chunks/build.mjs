import process from 'node:process';
import { defineCommand } from 'citty';
import { resolve, relative } from 'pathe';
import { s as showVersions } from '../shared/cli.Dz2be-Ai.mjs';
import { o as overrideEnv } from '../shared/cli.BEUGgaW4.mjs';
import { a as clearBuildDir } from '../shared/cli.pLQ0oPGc.mjs';
import { l as loadKit } from '../shared/cli.qKvs7FJ2.mjs';
import { l as logger } from '../shared/cli.B9AmABr3.mjs';
import { l as legacyRootDirArgs, e as extendsArgs, b as envNameArgs, d as dotEnvArgs, a as logLevelArgs, c as cwdArgs } from '../shared/cli.CTXRG5Cu.mjs';
import 'node:fs';
import 'consola/utils';
import 'exsolve';
import 'node:url';
import 'consola';
import 'node:path';
import 'std-env';

const buildCommand = defineCommand({
  meta: {
    name: "build",
    description: "Build Nuxt for production deployment"
  },
  args: {
    ...cwdArgs,
    ...logLevelArgs,
    prerender: {
      type: "boolean",
      description: "Build Nuxt and prerender static routes"
    },
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
    overrideEnv("production");
    const cwd = resolve(ctx.args.cwd || ctx.args.rootDir);
    showVersions(cwd);
    const kit = await loadKit(cwd);
    const nuxt = await kit.loadNuxt({
      cwd,
      dotenv: {
        cwd,
        fileName: ctx.args.dotenv
      },
      envName: ctx.args.envName,
      // c12 will fall back to NODE_ENV
      overrides: {
        logLevel: ctx.args.logLevel,
        // TODO: remove in 3.8
        _generate: ctx.args.prerender,
        nitro: {
          static: ctx.args.prerender,
          preset: ctx.args.preset || process.env.NITRO_PRESET || process.env.SERVER_PRESET
        },
        ...ctx.args.extends && { extends: ctx.args.extends },
        ...ctx.data?.overrides
      }
    });
    let nitro;
    try {
      nitro = kit.useNitro?.();
      logger.info(`Building for Nitro preset: \`${nitro.options.preset}\``);
    } catch {
    }
    await clearBuildDir(nuxt.options.buildDir);
    await kit.writeTypes(nuxt);
    nuxt.hook("build:error", (err) => {
      logger.error("Nuxt Build Error:", err);
      process.exit(1);
    });
    await kit.buildNuxt(nuxt);
    if (ctx.args.prerender) {
      if (!nuxt.options.ssr) {
        logger.warn(
          "HTML content not prerendered because `ssr: false` was set. You can read more in `https://nuxt.com/docs/getting-started/deployment#static-hosting`."
        );
      }
      const dir = nitro?.options.output.publicDir;
      const publicDir = dir ? relative(process.cwd(), dir) : ".output/public";
      logger.success(
        `You can now deploy \`${publicDir}\` to any static hosting!`
      );
    }
  }
});

export { buildCommand as default };
