import process from 'node:process';
import { fileURLToPath } from 'node:url';
import { defineCommand, runCommand as runCommand$1 } from 'citty';
import { f as commands, c as cwdArgs, s as setupGlobalConsole, g as checkEngines } from './cli.CTXRG5Cu.mjs';
import nodeCrypto from 'node:crypto';
import { resolve } from 'node:path';
import { provider } from 'std-env';
import { d as description, v as version, n as name } from './cli.DhJ3cH8w.mjs';
import { l as logger } from './cli.B9AmABr3.mjs';

if (!globalThis.crypto) {
  globalThis.crypto = nodeCrypto.webcrypto;
}
defineCommand({
  meta: {
    name: name.endsWith("nightly") ? name : "nuxi",
    version,
    description
  },
  args: {
    ...cwdArgs,
    command: {
      type: "positional",
      required: false
    }
  },
  subCommands: commands,
  async setup(ctx) {
    const command = ctx.args._[0];
    logger.debug(`Running \`nuxt ${command}\` command`);
    const dev = command === "dev";
    setupGlobalConsole({ dev });
    let backgroundTasks;
    if (command !== "_dev" && provider !== "stackblitz") {
      backgroundTasks = Promise.all([
        checkEngines()
      ]).catch((err) => logger.error(err));
    }
    if (command === "init") {
      await backgroundTasks;
    }
    if (ctx.args.command && !(ctx.args.command in commands)) {
      const cwd = resolve(ctx.args.cwd);
      try {
        const { x } = await import('tinyexec');
        await x(`nuxt-${ctx.args.command}`, ctx.rawArgs.slice(1), {
          nodeOptions: { stdio: "inherit", cwd },
          throwOnError: true
        });
      } catch (err) {
        if (err instanceof Error && "code" in err && err.code === "ENOENT") {
          return;
        }
      }
      process.exit();
    }
  }
});

globalThis.__nuxt_cli__ = globalThis.__nuxt_cli__ || {
  // Programmatic usage fallback
  startTime: Date.now(),
  entry: fileURLToPath(
    new URL("../../bin/nuxi.mjs", import.meta.url)
  ),
  devEntry: fileURLToPath(
    new URL("../dev/index.mjs", import.meta.url)
  )
};
async function runCommand(name, argv = process.argv.slice(2), data = {}) {
  argv.push("--no-clear");
  if (!(name in commands)) {
    throw new Error(`Invalid command ${name}`);
  }
  return await runCommand$1(await commands[name](), {
    rawArgs: argv,
    data: {
      overrides: data.overrides || {}
    }
  });
}

export { runCommand as r };
