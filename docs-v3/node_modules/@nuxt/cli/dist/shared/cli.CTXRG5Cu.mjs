import { resolve } from 'node:path';
import process from 'node:process';
import { defineCommand, runMain as runMain$1, runCommand as runCommand$1 } from 'citty';
import { provider } from 'std-env';
import { consola } from 'consola';
import { l as logger } from './cli.B9AmABr3.mjs';
import { fileURLToPath } from 'node:url';

const _rDefault = (r) => r.default || r;
const commands = {
  add: () => import('../chunks/add2.mjs').then(_rDefault),
  analyze: () => import('../chunks/analyze.mjs').then(_rDefault),
  build: () => import('../chunks/build.mjs').then(_rDefault),
  cleanup: () => import('../chunks/cleanup.mjs').then(_rDefault),
  _dev: () => import('../chunks/dev-child.mjs').then(_rDefault),
  dev: () => import('../chunks/dev.mjs').then(_rDefault),
  devtools: () => import('../chunks/devtools.mjs').then(_rDefault),
  generate: () => import('../chunks/generate.mjs').then(_rDefault),
  info: () => import('../chunks/info.mjs').then(_rDefault),
  init: () => import('../chunks/init.mjs').then(_rDefault),
  module: () => import('../chunks/index2.mjs').then(_rDefault),
  prepare: () => import('../chunks/prepare.mjs').then(_rDefault),
  preview: () => import('../chunks/preview.mjs').then(_rDefault),
  start: () => import('../chunks/preview.mjs').then(_rDefault),
  test: () => import('../chunks/test.mjs').then(_rDefault),
  typecheck: () => import('../chunks/typecheck.mjs').then(_rDefault),
  upgrade: () => import('../chunks/upgrade.mjs').then(_rDefault)
};

const cwdArgs = {
  cwd: {
    type: "string",
    description: "Specify the working directory",
    valueHint: "directory",
    default: "."
  }
};
const logLevelArgs = {
  logLevel: {
    type: "string",
    description: "Specify build-time log level",
    valueHint: "silent|info|verbose"
  }
};
const envNameArgs = {
  envName: {
    type: "string",
    description: "The environment to use when resolving configuration overrides (default is `production` when building, and `development` when running the dev server)"
  }
};
const dotEnvArgs = {
  dotenv: {
    type: "string",
    description: "Path to `.env` file to load, relative to the root directory"
  }
};
const extendsArgs = {
  extends: {
    type: "string",
    description: "Extend from a Nuxt layer",
    valueHint: "layer-name",
    alias: ["e"]
  }
};
const legacyRootDirArgs = {
  // cwd falls back to rootDir's default (indirect default)
  cwd: {
    ...cwdArgs.cwd,
    description: "Specify the working directory, this takes precedence over ROOTDIR (default: `.`)",
    default: void 0
  },
  rootDir: {
    type: "positional",
    description: "Specifies the working directory (default: `.`)",
    required: false,
    default: "."
  }
};

function wrapReporter(reporter) {
  return {
    log(logObj, ctx) {
      if (!logObj.args || !logObj.args.length) {
        return;
      }
      const msg = logObj.args[0];
      if (typeof msg === "string" && !process.env.DEBUG) {
        if (msg.startsWith(
          "[Vue Router warn]: No match found for location with path"
        )) {
          return;
        }
        if (msg.includes(
          "ExperimentalWarning: The Fetch API is an experimental feature"
        )) {
          return;
        }
        if (msg.startsWith("Sourcemap") && msg.includes("node_modules")) {
          return;
        }
      }
      return reporter.log(logObj, ctx);
    }
  };
}
function setupGlobalConsole(opts = {}) {
  consola.options.reporters = consola.options.reporters.map(wrapReporter);
  if (opts.dev) {
    consola.wrapAll();
  } else {
    consola.wrapConsole();
  }
  process.on("unhandledRejection", (err) => consola.error("[unhandledRejection]", err));
  process.on("uncaughtException", (err) => consola.error("[uncaughtException]", err));
}

async function checkEngines() {
  const satisfies = await import('semver/functions/satisfies.js').then(
    (r) => r.default || r
  );
  const currentNode = process.versions.node;
  const nodeRange = ">= 18.0.0";
  if (!satisfies(currentNode, nodeRange)) {
    logger.warn(
      `Current version of Node.js (\`${currentNode}\`) is unsupported and might cause issues.
       Please upgrade to a compatible version \`${nodeRange}\`.`
    );
  }
}

const name = "@nuxt/cli";
const version = "3.28.0";
const description = "Nuxt CLI";

const main = defineCommand({
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
const runMain = () => runMain$1(main);
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

export { logLevelArgs as a, envNameArgs as b, cwdArgs as c, dotEnvArgs as d, extendsArgs as e, commands as f, checkEngines as g, runMain as h, legacyRootDirArgs as l, main as m, runCommand as r, setupGlobalConsole as s };
