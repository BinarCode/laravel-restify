import { readFileSync } from 'node:fs';
import { colors } from 'consola/utils';
import { resolveModulePath } from 'exsolve';
import { t as tryResolveNuxt } from './cli.qKvs7FJ2.mjs';
import { l as logger } from './cli.B9AmABr3.mjs';

function showVersions(cwd) {
  const { bold, gray, green } = colors;
  const nuxtDir = tryResolveNuxt(cwd);
  function getPkgVersion(pkg) {
    for (const url of [cwd, nuxtDir]) {
      if (!url) {
        continue;
      }
      const p = resolveModulePath(`${pkg}/package.json`, { from: url, try: true });
      if (p) {
        return JSON.parse(readFileSync(p, "utf-8")).version;
      }
    }
    return "";
  }
  const nuxtVersion = getPkgVersion("nuxt") || getPkgVersion("nuxt-nightly") || getPkgVersion("nuxt3") || getPkgVersion("nuxt-edge");
  const nitroVersion = getPkgVersion("nitropack") || getPkgVersion("nitropack-nightly") || getPkgVersion("nitropack-edge");
  logger.log(gray(green(`Nuxt ${bold(nuxtVersion)}`) + (nitroVersion ? ` with Nitro ${bold(nitroVersion)}` : "")));
}

export { showVersions as s };
