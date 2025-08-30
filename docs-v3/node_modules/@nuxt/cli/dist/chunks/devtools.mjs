import process from 'node:process';
import { defineCommand } from 'citty';
import { resolve } from 'pathe';
import { x } from 'tinyexec';
import { l as logger } from '../shared/cli.B9AmABr3.mjs';
import { l as legacyRootDirArgs, c as cwdArgs } from '../shared/cli.CTXRG5Cu.mjs';
import 'consola';
import 'node:path';
import 'std-env';
import 'node:url';

const devtools = defineCommand({
  meta: {
    name: "devtools",
    description: "Enable or disable devtools in a Nuxt project"
  },
  args: {
    ...cwdArgs,
    command: {
      type: "positional",
      description: "Command to run",
      valueHint: "enable|disable"
    },
    ...legacyRootDirArgs
  },
  async run(ctx) {
    const cwd = resolve(ctx.args.cwd || ctx.args.rootDir);
    if (!["enable", "disable"].includes(ctx.args.command)) {
      logger.error(`Unknown command \`${ctx.args.command}\`.`);
      process.exit(1);
    }
    await x(
      "npx",
      ["@nuxt/devtools-wizard@latest", ctx.args.command, cwd],
      {
        throwOnError: true,
        nodeOptions: {
          stdio: "inherit",
          cwd
        }
      }
    );
  }
});

export { devtools as default };
