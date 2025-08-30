import { existsSync } from 'node:fs';
import process from 'node:process';
import { defineCommand } from 'citty';
import { colors } from 'consola/utils';
import { detectPackageManager, addDependency, dedupeDependencies } from 'nypm';
import { resolve } from 'pathe';
import { readPackageJSON } from 'pkg-types';
import { l as loadKit } from '../shared/cli.qKvs7FJ2.mjs';
import { l as logger } from '../shared/cli.B9AmABr3.mjs';
import { c as cleanupNuxtDirs, n as nuxtVersionToGitIdentifier } from '../shared/cli.At9IMXtr.mjs';
import { g as getPackageManagerVersion } from '../shared/cli.BSm0_9Hr.mjs';
import { g as getNuxtVersion } from '../shared/cli.DHenkA1C.mjs';
import { l as legacyRootDirArgs, a as logLevelArgs, c as cwdArgs } from '../shared/cli.CTXRG5Cu.mjs';
import 'node:url';
import 'exsolve';
import 'consola';
import 'ohash';
import '../shared/cli.pLQ0oPGc.mjs';
import 'node:child_process';
import 'semver';
import 'node:path';
import 'std-env';

function checkNuxtDependencyType(pkg) {
  if (pkg.dependencies?.nuxt) {
    return "dependencies";
  }
  if (pkg.devDependencies?.nuxt) {
    return "devDependencies";
  }
  return "dependencies";
}
const nuxtVersionTags = {
  "3.x": "3x",
  "4.x": "latest"
};
async function getNightlyVersion(packageNames) {
  const nuxtVersion = await logger.prompt(
    "Which nightly Nuxt release channel do you want to install? (3.x or 4.x)",
    {
      type: "select",
      options: ["3.x", "4.x"],
      default: "4.x",
      cancel: "reject"
    }
  ).catch(() => process.exit(1));
  const npmPackages = packageNames.map((p) => `${p}@npm:${p}-nightly@${nuxtVersionTags[nuxtVersion]}`);
  return { npmPackages, nuxtVersion };
}
async function getRequiredNewVersion(packageNames, channel) {
  if (channel === "nightly") {
    return getNightlyVersion(packageNames);
  }
  return { npmPackages: packageNames.map((p) => `${p}@latest`), nuxtVersion: "4" };
}
const upgrade = defineCommand({
  meta: {
    name: "upgrade",
    description: "Upgrade Nuxt"
  },
  args: {
    ...cwdArgs,
    ...logLevelArgs,
    ...legacyRootDirArgs,
    dedupe: {
      type: "boolean",
      description: "Dedupe dependencies after upgrading"
    },
    force: {
      type: "boolean",
      alias: "f",
      description: "Force upgrade to recreate lockfile and node_modules"
    },
    channel: {
      type: "string",
      alias: "ch",
      default: "stable",
      description: "Specify a channel to install from (default: stable)",
      valueHint: "stable|nightly"
    }
  },
  async run(ctx) {
    const cwd = resolve(ctx.args.cwd || ctx.args.rootDir);
    const packageManager = await detectPackageManager(cwd);
    if (!packageManager) {
      logger.error(
        `Unable to determine the package manager used by this project.

No lock files found in \`${cwd}\`, and no \`packageManager\` field specified in \`package.json\`.

Please either add the \`packageManager\` field to \`package.json\` or execute the installation command for your package manager. For example, you can use \`pnpm i\`, \`npm i\`, \`bun i\`, or \`yarn i\`, and then try again.`
      );
      process.exit(1);
    }
    const { name: packageManagerName, lockFile: lockFileCandidates } = packageManager;
    const packageManagerVersion = getPackageManagerVersion(packageManagerName);
    logger.info("Package manager:", packageManagerName, packageManagerVersion);
    const currentVersion = await getNuxtVersion(cwd, false) || "[unknown]";
    logger.info("Current Nuxt version:", currentVersion);
    const pkg = await readPackageJSON(cwd).catch(() => null);
    const nuxtDependencyType = pkg ? checkNuxtDependencyType(pkg) : "dependencies";
    const corePackages = ["@nuxt/kit", "@nuxt/schema", "@nuxt/vite-builder", "@nuxt/webpack-builder", "@nuxt/rspack-builder"];
    const packagesToUpdate = pkg ? corePackages.filter((p) => pkg.dependencies?.[p] || pkg.devDependencies?.[p]) : [];
    const { npmPackages, nuxtVersion } = await getRequiredNewVersion(["nuxt", ...packagesToUpdate], ctx.args.channel);
    const toRemove = ["node_modules"];
    const lockFile = normaliseLockFile(cwd, lockFileCandidates);
    if (lockFile) {
      toRemove.push(lockFile);
    }
    const forceRemovals = toRemove.map((p) => colors.cyan(p)).join(" and ");
    let method = ctx.args.force ? "force" : ctx.args.dedupe ? "dedupe" : void 0;
    method ||= await logger.prompt(
      `Would you like to dedupe your lockfile (recommended) or recreate ${forceRemovals}? This can fix problems with hoisted dependency versions and ensure you have the most up-to-date dependencies.`,
      {
        type: "select",
        initial: "dedupe",
        cancel: "reject",
        options: [
          {
            label: "dedupe lockfile",
            value: "dedupe",
            hint: "recommended"
          },
          {
            label: `recreate ${forceRemovals}`,
            value: "force"
          },
          {
            label: "skip",
            value: "skip"
          }
        ]
      }
    ).catch(() => process.exit(1));
    const versionType = ctx.args.channel === "nightly" ? "nightly" : "latest stable";
    logger.info(`Installing ${versionType} Nuxt ${nuxtVersion} release...`);
    await addDependency(npmPackages, {
      cwd,
      packageManager,
      dev: nuxtDependencyType === "devDependencies"
    });
    if (method === "force") {
      logger.info(
        `Recreating ${forceRemovals}. If you encounter any issues, revert the changes and try with \`--no-force\``
      );
      await dedupeDependencies({ recreateLockfile: true });
    }
    if (method === "dedupe") {
      logger.info("Try deduping dependencies...");
      await dedupeDependencies();
    }
    let buildDir = ".nuxt";
    try {
      const { loadNuxtConfig } = await loadKit(cwd);
      const nuxtOptions = await loadNuxtConfig({ cwd });
      buildDir = nuxtOptions.buildDir;
    } catch {
    }
    await cleanupNuxtDirs(cwd, buildDir);
    const upgradedVersion = await getNuxtVersion(cwd, false) || "[unknown]";
    logger.info("Upgraded Nuxt version:", upgradedVersion);
    if (upgradedVersion === "[unknown]") {
      return;
    }
    if (upgradedVersion === currentVersion) {
      logger.success("You're using the latest version of Nuxt.");
    } else {
      logger.success(
        "Successfully upgraded Nuxt from",
        currentVersion,
        "to",
        upgradedVersion
      );
      if (currentVersion === "[unknown]") {
        return;
      }
      const commitA = nuxtVersionToGitIdentifier(currentVersion);
      const commitB = nuxtVersionToGitIdentifier(upgradedVersion);
      if (commitA && commitB) {
        logger.info(
          "Changelog:",
          `https://github.com/nuxt/nuxt/compare/${commitA}...${commitB}`
        );
      }
    }
  }
});
function normaliseLockFile(cwd, lockFiles) {
  if (typeof lockFiles === "string") {
    lockFiles = [lockFiles];
  }
  const lockFile = lockFiles?.find((file) => existsSync(resolve(cwd, file)));
  if (lockFile === void 0) {
    logger.error(`Unable to find any lock files in ${cwd}`);
    return void 0;
  }
  return lockFile;
}

export { upgrade as default };
