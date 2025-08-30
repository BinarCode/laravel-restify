import { f as fmtCommand, g as getWorkspaceArgs2 } from './shared/nypm.COyAVAIB.mjs';
export { a as addDependency, b as addDevDependency, c as dedupeDependencies, d as detectPackageManager, e as ensureDependencyInstalled, i as installDependencies, p as packageManagers, r as removeDependency, h as runScript } from './shared/nypm.COyAVAIB.mjs';
import 'pkg-types';
import 'node:module';
import 'pathe';
import 'tinyexec';
import 'node:fs';
import 'node:fs/promises';

function installDependenciesCommand(packageManager, options = {}) {
  const installCmd = options.short ? "i" : "install";
  const pmToFrozenLockfileInstallCommand = {
    npm: ["ci"],
    yarn: [installCmd, "--immutable"],
    bun: [installCmd, "--frozen-lockfile"],
    pnpm: [installCmd, "--frozen-lockfile"],
    deno: [installCmd, "--frozen"]
  };
  const commandArgs = options.frozenLockFile ? pmToFrozenLockfileInstallCommand[packageManager] : [installCmd];
  return fmtCommand([packageManager, ...commandArgs]);
}
function addDependencyCommand(packageManager, name, options = {}) {
  const names = Array.isArray(name) ? name : [name];
  if (packageManager === "deno") {
    for (let i = 0; i < names.length; i++) {
      if (!/^(npm|jsr|file):.+$/.test(names[i])) {
        names[i] = `npm:${names[i]}`;
      }
    }
  }
  const args = (packageManager === "yarn" ? [
    ...getWorkspaceArgs2({ packageManager, ...options }),
    // Global is not supported in berry: yarnpkg/berry#821
    options.global && !options.yarnBerry ? "global" : "",
    "add",
    options.dev ? options.short ? "-D" : "--dev" : "",
    ...names
  ] : [
    packageManager === "npm" ? options.short ? "i" : "install" : "add",
    ...getWorkspaceArgs2({ packageManager, ...options }),
    options.dev ? options.short ? "-D" : "--dev" : "",
    options.global ? "-g" : "",
    ...names
  ]).filter(Boolean);
  return fmtCommand([packageManager, ...args]);
}
function runScriptCommand(packageManager, name, options = {}) {
  const args = [
    packageManager === "deno" ? "task" : "run",
    name,
    ...options.args || []
  ];
  return fmtCommand([packageManager, ...args]);
}
function dlxCommand(packageManager, name, options) {
  const pmToDlxCommand = {
    npm: options.short ? "npx" : "npm dlx",
    yarn: "yarn dlx",
    pnpm: options.short ? "pnpx" : "pnpm dlx",
    bun: options.short ? "bunx" : "bun x",
    deno: "deno run -A"
  };
  const command = pmToDlxCommand[packageManager];
  if (packageManager === "deno" && !name.startsWith("npm:")) {
    name = `npm:${name}`;
  }
  return fmtCommand([command, name, ...options.args || []]);
}

export { addDependencyCommand, dlxCommand, installDependenciesCommand, runScriptCommand };
