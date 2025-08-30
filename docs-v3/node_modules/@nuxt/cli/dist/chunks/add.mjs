import * as fs from 'node:fs';
import { homedir } from 'node:os';
import { join } from 'node:path';
import process from 'node:process';
import { updateConfig } from 'c12/update';
import { defineCommand } from 'citty';
import { colors } from 'consola/utils';
import { addDependency } from 'nypm';
import { $fetch } from 'ofetch';
import { resolve } from 'pathe';
import { readPackageJSON } from 'pkg-types';
import { satisfies } from 'semver';
import { joinURL } from 'ufo';
import { r as runCommand } from '../shared/cli.vXg4eLNu.mjs';
import { l as logger } from '../shared/cli.B9AmABr3.mjs';
import { g as getNuxtVersion } from '../shared/cli.DHenkA1C.mjs';
import { a as logLevelArgs, c as cwdArgs } from '../shared/cli.CTXRG5Cu.mjs';
import { f as fetchModules, c as checkNuxtCompatibility, g as getRegistryFromContent } from '../shared/cli.Cr-OCgdO.mjs';
import 'node:url';
import 'node:crypto';
import 'std-env';
import '../shared/cli.DhJ3cH8w.mjs';
import 'consola';
import 'confbox';

const add = defineCommand({
  meta: {
    name: "add",
    description: "Add Nuxt modules"
  },
  args: {
    ...cwdArgs,
    ...logLevelArgs,
    moduleName: {
      type: "positional",
      description: "Specify one or more modules to install by name, separated by spaces"
    },
    skipInstall: {
      type: "boolean",
      description: "Skip npm install"
    },
    skipConfig: {
      type: "boolean",
      description: "Skip nuxt.config.ts update"
    },
    dev: {
      type: "boolean",
      description: "Install modules as dev dependencies"
    }
  },
  async setup(ctx) {
    const cwd = resolve(ctx.args.cwd);
    const modules = ctx.args._.map((e) => e.trim()).filter(Boolean);
    const projectPkg = await readPackageJSON(cwd).catch(() => ({}));
    if (!projectPkg.dependencies?.nuxt && !projectPkg.devDependencies?.nuxt) {
      logger.warn(`No \`nuxt\` dependency detected in \`${cwd}\`.`);
      const shouldContinue = await logger.prompt(
        `Do you want to continue anyway?`,
        {
          type: "confirm",
          initial: false,
          cancel: "default"
        }
      );
      if (shouldContinue !== true) {
        process.exit(1);
      }
    }
    const maybeResolvedModules = await Promise.all(modules.map((moduleName) => resolveModule(moduleName, cwd)));
    const resolvedModules = maybeResolvedModules.filter((x) => x != null);
    logger.info(`Resolved \`${resolvedModules.map((x) => x.pkgName).join("`, `")}\`, adding module${resolvedModules.length > 1 ? "s" : ""}...`);
    await addModules(resolvedModules, { ...ctx.args, cwd }, projectPkg);
    if (!ctx.args.skipInstall) {
      const args = Object.entries(ctx.args).filter(([k]) => k in cwdArgs || k in logLevelArgs).map(([k, v]) => `--${k}=${v}`);
      await runCommand("prepare", args);
    }
  }
});
async function addModules(modules, { skipInstall, skipConfig, cwd, dev }, projectPkg) {
  if (!skipInstall) {
    const installedModules = [];
    const notInstalledModules = [];
    const dependencies = /* @__PURE__ */ new Set([
      ...Object.keys(projectPkg.dependencies || {}),
      ...Object.keys(projectPkg.devDependencies || {})
    ]);
    for (const module of modules) {
      if (dependencies.has(module.pkgName)) {
        installedModules.push(module);
      } else {
        notInstalledModules.push(module);
      }
    }
    if (installedModules.length > 0) {
      const installedModulesList = installedModules.map((module) => module.pkgName).join("`, `");
      const are = installedModules.length > 1 ? "are" : "is";
      logger.info(`\`${installedModulesList}\` ${are} already installed`);
    }
    if (notInstalledModules.length > 0) {
      const isDev = Boolean(projectPkg.devDependencies?.nuxt) || dev;
      const notInstalledModulesList = notInstalledModules.map((module) => module.pkg).join("`, `");
      const dependency = notInstalledModules.length > 1 ? "dependencies" : "dependency";
      const a = notInstalledModules.length > 1 ? "" : " a";
      logger.info(`Installing \`${notInstalledModulesList}\` as${a}${isDev ? " development" : ""} ${dependency}`);
      const res = await addDependency(notInstalledModules.map((module) => module.pkg), {
        cwd,
        dev: isDev,
        installPeerDependencies: true
      }).then(() => true).catch(
        (error) => {
          logger.error(error);
          const failedModulesList = notInstalledModules.map((module) => colors.cyan(module.pkg)).join("`, `");
          const s = notInstalledModules.length > 1 ? "s" : "";
          return logger.prompt(`Install failed for \`${failedModulesList}\`. Do you want to continue adding the module${s} to ${colors.cyan("nuxt.config")}?`, {
            type: "confirm",
            initial: false,
            cancel: "default"
          });
        }
      );
      if (res !== true) {
        return;
      }
    }
  }
  if (!skipConfig) {
    await updateConfig({
      cwd,
      configFile: "nuxt.config",
      async onCreate() {
        logger.info(`Creating \`nuxt.config.ts\``);
        return getDefaultNuxtConfig();
      },
      async onUpdate(config) {
        if (!config.modules) {
          config.modules = [];
        }
        for (const resolved of modules) {
          if (config.modules.includes(resolved.pkgName)) {
            logger.info(`\`${resolved.pkgName}\` is already in the \`modules\``);
            continue;
          }
          logger.info(`Adding \`${resolved.pkgName}\` to the \`modules\``);
          config.modules.push(resolved.pkgName);
        }
      }
    }).catch((error) => {
      logger.error(`Failed to update \`nuxt.config\`: ${error.message}`);
      logger.error(`Please manually add \`${modules.map((module) => module.pkgName).join("`, `")}\` to the \`modules\` in \`nuxt.config.ts\``);
      return null;
    });
  }
}
function getDefaultNuxtConfig() {
  return `
// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  modules: []
})`;
}
const packageRegex = /^(@[a-z0-9-~][a-z0-9-._~]*\/)?([a-z0-9-~][a-z0-9-._~]*)(@[^@]+)?$/;
async function resolveModule(moduleName, cwd) {
  let pkgName = moduleName;
  let pkgVersion;
  const reMatch = moduleName.match(packageRegex);
  if (reMatch) {
    if (reMatch[3]) {
      pkgName = `${reMatch[1] || ""}${reMatch[2] || ""}`;
      pkgVersion = reMatch[3].slice(1);
    }
  } else {
    logger.error(`Invalid package name \`${pkgName}\`.`);
    return false;
  }
  const modulesDB = await fetchModules().catch((err) => {
    logger.warn(`Cannot search in the Nuxt Modules database: ${err}`);
    return [];
  });
  const matchedModule = modulesDB.find(
    (module) => module.name === moduleName || pkgVersion && module.name === pkgName || module.npm === pkgName || module.aliases?.includes(pkgName)
  );
  if (matchedModule?.npm) {
    pkgName = matchedModule.npm;
  }
  if (matchedModule && matchedModule.compatibility.nuxt) {
    const nuxtVersion = await getNuxtVersion(cwd);
    if (!checkNuxtCompatibility(matchedModule, nuxtVersion)) {
      logger.warn(
        `The module \`${pkgName}\` is not compatible with Nuxt \`${nuxtVersion}\` (requires \`${matchedModule.compatibility.nuxt}\`)`
      );
      const shouldContinue = await logger.prompt(
        "Do you want to continue installing incompatible version?",
        {
          type: "confirm",
          initial: false,
          cancel: "default"
        }
      );
      if (!shouldContinue) {
        return false;
      }
    }
    const versionMap = matchedModule.compatibility.versionMap;
    if (versionMap) {
      for (const [_nuxtVersion, _moduleVersion] of Object.entries(versionMap)) {
        if (satisfies(nuxtVersion, _nuxtVersion)) {
          if (!pkgVersion) {
            pkgVersion = _moduleVersion;
          } else {
            logger.warn(
              `Recommended version of \`${pkgName}\` for Nuxt \`${nuxtVersion}\` is \`${_moduleVersion}\` but you have requested \`${pkgVersion}\``
            );
            pkgVersion = await logger.prompt("Choose a version:", {
              type: "select",
              options: [_moduleVersion, pkgVersion],
              cancel: "undefined"
            });
            if (!pkgVersion) {
              return false;
            }
          }
          break;
        }
      }
    }
  }
  let version = pkgVersion || "latest";
  const pkgScope = pkgName.startsWith("@") ? pkgName.split("/")[0] : null;
  const meta = await detectNpmRegistry(pkgScope);
  const headers = {};
  if (meta.authToken) {
    headers.Authorization = `Bearer ${meta.authToken}`;
  }
  const pkgDetails = await $fetch(joinURL(meta.registry, `${pkgName}`), { headers });
  if (pkgDetails["dist-tags"]?.[version]) {
    version = pkgDetails["dist-tags"][version];
  } else {
    version = Object.keys(pkgDetails.versions)?.findLast((v) => satisfies(v, version)) || version;
  }
  const pkg = pkgDetails.versions[version];
  const pkgDependencies = Object.assign(
    pkg.dependencies || {},
    pkg.devDependencies || {}
  );
  if (!pkgDependencies.nuxt && !pkgDependencies["nuxt-edge"] && !pkgDependencies["@nuxt/kit"]) {
    logger.warn(`It seems that \`${pkgName}\` is not a Nuxt module.`);
    const shouldContinue = await logger.prompt(
      `Do you want to continue installing ${colors.cyan(pkgName)} anyway?`,
      {
        type: "confirm",
        initial: false,
        cancel: "default"
      }
    );
    if (!shouldContinue) {
      return false;
    }
  }
  return {
    nuxtModule: matchedModule,
    pkg: `${pkgName}@${version}`,
    pkgName,
    pkgVersion: version
  };
}
function getNpmrcPaths() {
  const userNpmrcPath = join(homedir(), ".npmrc");
  const cwdNpmrcPath = join(process.cwd(), ".npmrc");
  return [cwdNpmrcPath, userNpmrcPath];
}
async function getAuthToken(registry) {
  const paths = getNpmrcPaths();
  const authTokenRegex = new RegExp(`^//${registry.replace(/^https?:\/\//, "").replace(/\/$/, "")}/:_authToken=(.+)$`, "m");
  for (const npmrcPath of paths) {
    let fd;
    try {
      fd = await fs.promises.open(npmrcPath, "r");
      if (await fd.stat().then((r) => r.isFile())) {
        const npmrcContent = await fd.readFile("utf-8");
        const authTokenMatch = npmrcContent.match(authTokenRegex)?.[1];
        if (authTokenMatch) {
          return authTokenMatch.trim();
        }
      }
    } catch {
    } finally {
      await fd?.close();
    }
  }
  return null;
}
async function detectNpmRegistry(scope) {
  const registry = await getRegistry(scope);
  const authToken = await getAuthToken(registry);
  return {
    registry,
    authToken
  };
}
async function getRegistry(scope) {
  if (process.env.COREPACK_NPM_REGISTRY) {
    return process.env.COREPACK_NPM_REGISTRY;
  }
  const registry = await getRegistryFromFile(getNpmrcPaths(), scope);
  if (registry) {
    process.env.COREPACK_NPM_REGISTRY = registry;
  }
  return registry || "https://registry.npmjs.org";
}
async function getRegistryFromFile(paths, scope) {
  for (const npmrcPath of paths) {
    let fd;
    try {
      fd = await fs.promises.open(npmrcPath, "r");
      if (await fd.stat().then((r) => r.isFile())) {
        const npmrcContent = await fd.readFile("utf-8");
        const registry = getRegistryFromContent(npmrcContent, scope);
        if (registry) {
          return registry;
        }
      }
    } catch {
    } finally {
      await fd?.close();
    }
  }
  return null;
}

export { add as default };
