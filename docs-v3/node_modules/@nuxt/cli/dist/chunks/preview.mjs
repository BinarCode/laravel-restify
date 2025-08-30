import { existsSync, promises } from 'node:fs';
import { dirname, relative } from 'node:path';
import process from 'node:process';
import { setupDotenv } from 'c12';
import { defineCommand } from 'citty';
import { box, colors } from 'consola/utils';
import { getArgs } from 'listhen/cli';
import { resolve } from 'pathe';
import { x } from 'tinyexec';
import { l as loadKit } from '../shared/cli.qKvs7FJ2.mjs';
import { l as logger } from '../shared/cli.B9AmABr3.mjs';
import { d as dotEnvArgs, l as legacyRootDirArgs, e as extendsArgs, b as envNameArgs, a as logLevelArgs, c as cwdArgs } from '../shared/cli.CTXRG5Cu.mjs';
import 'node:url';
import 'exsolve';
import 'consola';
import 'std-env';

const command = defineCommand({
  meta: {
    name: "preview",
    description: "Launches Nitro server for local testing after `nuxi build`."
  },
  args: {
    ...cwdArgs,
    ...logLevelArgs,
    ...envNameArgs,
    ...extendsArgs,
    ...legacyRootDirArgs,
    port: getArgs().port,
    ...dotEnvArgs
  },
  async run(ctx) {
    process.env.NODE_ENV = process.env.NODE_ENV || "production";
    const cwd = resolve(ctx.args.cwd || ctx.args.rootDir);
    const { loadNuxt } = await loadKit(cwd);
    const resolvedOutputDir = await new Promise((res) => {
      loadNuxt({
        cwd,
        envName: ctx.args.envName,
        // c12 will fall back to NODE_ENV
        ready: true,
        overrides: {
          ...ctx.args.extends && { extends: ctx.args.extends },
          modules: [
            function(_, nuxt) {
              nuxt.hook("nitro:init", (nitro) => {
                res(resolve(nuxt.options.srcDir || cwd, nitro.options.output.dir || ".output", "nitro.json"));
              });
            }
          ]
        }
      }).then((nuxt) => nuxt.close()).catch(() => "");
    });
    const defaultOutput = resolve(cwd, ".output", "nitro.json");
    const nitroJSONPaths = [resolvedOutputDir, defaultOutput].filter(Boolean);
    const nitroJSONPath = nitroJSONPaths.find((p) => existsSync(p));
    if (!nitroJSONPath) {
      logger.error(
        "Cannot find `nitro.json`. Did you run `nuxi build` first? Search path:\n",
        nitroJSONPaths
      );
      process.exit(1);
    }
    const outputPath = dirname(nitroJSONPath);
    const nitroJSON = JSON.parse(await promises.readFile(nitroJSONPath, "utf-8"));
    if (!nitroJSON.commands.preview) {
      logger.error("Preview is not supported for this build.");
      process.exit(1);
    }
    const info = [
      ["Node.js:", `v${process.versions.node}`],
      ["Nitro Preset:", nitroJSON.preset],
      ["Working directory:", relative(process.cwd(), outputPath)]
    ];
    const _infoKeyLen = Math.max(...info.map(([label]) => label.length));
    logger.log(
      box(
        [
          "You are running Nuxt production build in preview mode.",
          `For production deployments, please directly use ${colors.cyan(
            nitroJSON.commands.preview
          )} command.`,
          "",
          ...info.map(
            ([label, value]) => `${label.padEnd(_infoKeyLen, " ")} ${colors.cyan(value)}`
          )
        ].join("\n"),
        {
          title: colors.yellow("Preview Mode"),
          style: {
            borderColor: "yellow"
          }
        }
      )
    );
    const envFileName = ctx.args.dotenv || ".env";
    const envExists = existsSync(resolve(cwd, envFileName));
    if (envExists) {
      logger.info(
        `Loading \`${envFileName}\`. This will not be loaded when running the server in production.`
      );
      await setupDotenv({ cwd, fileName: envFileName });
    } else if (ctx.args.dotenv) {
      logger.error(`Cannot find \`${envFileName}\`.`);
    }
    const { port } = _resolveListenOptions(ctx.args);
    logger.info(`Starting preview command: \`${nitroJSON.commands.preview}\``);
    const [command2, ...commandArgs] = nitroJSON.commands.preview.split(" ");
    logger.log("");
    await x(command2, commandArgs, {
      throwOnError: true,
      nodeOptions: {
        stdio: "inherit",
        cwd: outputPath,
        env: {
          ...process.env,
          NUXT_PORT: port,
          NITRO_PORT: port
        }
      }
    });
  }
});
function _resolveListenOptions(args) {
  const _port = args.port ?? args.p ?? process.env.NUXT_PORT ?? process.env.NITRO_PORT ?? process.env.PORT;
  return {
    port: _port
  };
}

export { command as default };
