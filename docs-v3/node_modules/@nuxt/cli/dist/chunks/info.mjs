import os from 'node:os';
import process from 'node:process';
import { defineCommand } from 'citty';
import clipboardy from 'clipboardy';
import { detectPackageManager } from 'nypm';
import { resolve } from 'pathe';
import { readPackageJSON } from 'pkg-types';
import { splitByCase } from 'scule';
import { isMinimal } from 'std-env';
import { v as version } from '../shared/cli.DhJ3cH8w.mjs';
import { t as tryResolveNuxt } from '../shared/cli.qKvs7FJ2.mjs';
import { l as logger } from '../shared/cli.B9AmABr3.mjs';
import { g as getPackageManagerVersion } from '../shared/cli.BSm0_9Hr.mjs';
import { l as legacyRootDirArgs, c as cwdArgs } from '../shared/cli.CTXRG5Cu.mjs';
import 'node:url';
import 'exsolve';
import 'consola';
import 'node:child_process';
import 'node:path';

const info = defineCommand({
  meta: {
    name: "info",
    description: "Get information about Nuxt project"
  },
  args: {
    ...cwdArgs,
    ...legacyRootDirArgs
  },
  async run(ctx) {
    const cwd = resolve(ctx.args.cwd || ctx.args.rootDir);
    const nuxtConfig = await getNuxtConfig(cwd);
    const { dependencies = {}, devDependencies = {} } = await readPackageJSON(cwd).catch(() => ({}));
    const nuxtPath = tryResolveNuxt(cwd);
    async function getDepVersion(name) {
      for (const url of [cwd, nuxtPath]) {
        if (!url) {
          continue;
        }
        const pkg = await readPackageJSON(name, { url }).catch(() => null);
        if (pkg) {
          return pkg.version;
        }
      }
      return dependencies[name] || devDependencies[name];
    }
    async function listModules(arr = []) {
      const info = [];
      for (let m of arr) {
        if (Array.isArray(m)) {
          m = m[0];
        }
        const name = normalizeConfigModule(m, cwd);
        if (name) {
          const npmName = name.split("/").splice(0, 2).join("/");
          const v = await getDepVersion(npmName);
          info.push(`\`${v ? `${name}@${v}` : name}\``);
        }
      }
      return info.join(", ");
    }
    const nuxtVersion = await getDepVersion("nuxt") || await getDepVersion("nuxt-nightly") || await getDepVersion("nuxt-edge") || await getDepVersion("nuxt3") || "-";
    const isLegacy = nuxtVersion.startsWith("2");
    const builder = !isLegacy ? nuxtConfig.builder || "-" : nuxtConfig.bridge?.vite ? "vite" : nuxtConfig.buildModules?.includes("nuxt-vite") ? "vite" : "webpack";
    let packageManager = (await detectPackageManager(cwd))?.name;
    if (packageManager) {
      packageManager += `@${getPackageManagerVersion(packageManager)}`;
    }
    const infoObj = {
      OperatingSystem: os.type(),
      NodeVersion: process.version,
      NuxtVersion: nuxtVersion,
      CLIVersion: version,
      NitroVersion: await getDepVersion("nitropack"),
      PackageManager: packageManager ?? "unknown",
      Builder: typeof builder === "string" ? builder : "custom",
      UserConfig: Object.keys(nuxtConfig).map((key) => `\`${key}\``).join(", "),
      RuntimeModules: await listModules(nuxtConfig.modules),
      BuildModules: await listModules(nuxtConfig.buildModules || [])
    };
    logger.log("Working directory:", cwd);
    let maxLength = 0;
    const entries = Object.entries(infoObj).map(([key, val]) => {
      const label = splitByCase(key).join(" ");
      if (label.length > maxLength) {
        maxLength = label.length;
      }
      return [label, val || "-"];
    });
    let infoStr = "";
    for (const [label, value] of entries) {
      infoStr += `- ${`${label}: `.padEnd(maxLength + 2)}${value.includes("`") ? value : `\`${value}\``}
`;
    }
    const copied = !isMinimal && await clipboardy.write(infoStr).then(() => true).catch(() => false);
    const isNuxt3 = !isLegacy;
    const isBridge = !isNuxt3 && infoObj.BuildModules.includes("bridge");
    const repo = isBridge ? "nuxt/bridge" : "nuxt/nuxt";
    const log = [
      (isNuxt3 || isBridge) && `\u{1F449} Report an issue: https://github.com/${repo}/issues/new?template=bug-report.yml`,
      (isNuxt3 || isBridge) && `\u{1F449} Suggest an improvement: https://github.com/${repo}/discussions/new`,
      `\u{1F449} Read documentation: ${isNuxt3 || isBridge ? "https://nuxt.com" : "https://v2.nuxt.com"}`
    ].filter(Boolean).join("\n");
    const splitter = "------------------------------";
    logger.log(`Nuxt project info: ${copied ? "(copied to clipboard)" : ""}

${splitter}
${infoStr}${splitter}

${log}
`);
  }
});
function normalizeConfigModule(module, rootDir) {
  if (!module) {
    return null;
  }
  if (typeof module === "string") {
    return module.split(rootDir).pop().split("node_modules").pop().replace(/^\//, "");
  }
  if (typeof module === "function") {
    return `${module.name}()`;
  }
  if (Array.isArray(module)) {
    return normalizeConfigModule(module[0], rootDir);
  }
  return null;
}
async function getNuxtConfig(rootDir) {
  try {
    const { createJiti } = await import('jiti');
    const jiti = createJiti(rootDir, {
      interopDefault: true,
      // allow using `~` and `@` in `nuxt.config`
      alias: {
        "~": rootDir,
        "@": rootDir
      }
    });
    globalThis.defineNuxtConfig = (c) => c;
    const result = await jiti.import("./nuxt.config", { default: true });
    delete globalThis.defineNuxtConfig;
    return result;
  } catch {
    return {};
  }
}

export { info as default };
