import process from 'node:process';
import { defineCommand } from 'citty';
import { resolve } from 'pathe';
import { l as logger } from '../shared/cli.B9AmABr3.mjs';
import { l as legacyRootDirArgs, a as logLevelArgs, c as cwdArgs } from '../shared/cli.CTXRG5Cu.mjs';
import 'consola';
import 'node:path';
import 'std-env';
import 'node:url';

const test = defineCommand({
  meta: {
    name: "test",
    description: "Run tests"
  },
  args: {
    ...cwdArgs,
    ...logLevelArgs,
    ...legacyRootDirArgs,
    dev: {
      type: "boolean",
      description: "Run in dev mode"
    },
    watch: {
      type: "boolean",
      description: "Watch mode"
    }
  },
  async run(ctx) {
    process.env.NODE_ENV = process.env.NODE_ENV || "test";
    const cwd = resolve(ctx.args.cwd || ctx.args.rootDir);
    const { runTests } = await importTestUtils();
    await runTests({
      rootDir: cwd,
      dev: ctx.args.dev,
      watch: ctx.args.watch,
      ...{}
    });
  }
});
async function importTestUtils() {
  let err;
  for (const pkg of [
    "@nuxt/test-utils-nightly",
    "@nuxt/test-utils-edge",
    "@nuxt/test-utils"
  ]) {
    try {
      const exports = await import(pkg);
      if (!exports.runTests) {
        throw new Error("Invalid version of `@nuxt/test-utils` is installed!");
      }
      return exports;
    } catch (_err) {
      err = _err;
    }
  }
  logger.error(err);
  throw new Error("`@nuxt/test-utils` seems missing. Run `npm i -D @nuxt/test-utils` or `yarn add -D @nuxt/test-utils` to install.");
}

export { test as default };
