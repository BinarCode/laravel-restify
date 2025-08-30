import { promises } from 'node:fs';
import { hash } from 'ohash';
import { resolve, dirname } from 'pathe';
import { l as logger } from './cli.B9AmABr3.mjs';
import { r as rmRecursive } from './cli.pLQ0oPGc.mjs';

async function cleanupNuxtDirs(rootDir, buildDir) {
  logger.info("Cleaning up generated Nuxt files and caches...");
  await rmRecursive(
    [
      buildDir,
      ".output",
      "dist",
      "node_modules/.vite",
      "node_modules/.cache"
    ].map((dir) => resolve(rootDir, dir))
  );
}
function nuxtVersionToGitIdentifier(version) {
  const id = /\.([0-9a-f]{7,8})$/.exec(version);
  if (id?.[1]) {
    return id[1];
  }
  return `v${version}`;
}
function resolveNuxtManifest(nuxt) {
  const manifest = {
    _hash: null,
    project: {
      rootDir: nuxt.options.rootDir
    },
    versions: {
      nuxt: nuxt._version
    }
  };
  manifest._hash = hash(manifest);
  return manifest;
}
async function writeNuxtManifest(nuxt, manifest = resolveNuxtManifest(nuxt)) {
  const manifestPath = resolve(nuxt.options.buildDir, "nuxt.json");
  await promises.mkdir(dirname(manifestPath), { recursive: true });
  await promises.writeFile(manifestPath, JSON.stringify(manifest, null, 2), "utf-8");
  return manifest;
}
async function loadNuxtManifest(buildDir) {
  const manifestPath = resolve(buildDir, "nuxt.json");
  const manifest = await promises.readFile(manifestPath, "utf-8").then((data) => JSON.parse(data)).catch(() => null);
  return manifest;
}

export { cleanupNuxtDirs as c, loadNuxtManifest as l, nuxtVersionToGitIdentifier as n, resolveNuxtManifest as r, writeNuxtManifest as w };
