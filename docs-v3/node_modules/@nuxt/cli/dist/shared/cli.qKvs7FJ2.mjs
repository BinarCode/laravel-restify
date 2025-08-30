import { pathToFileURL } from 'node:url';
import { resolveModulePath } from 'exsolve';

async function loadKit(rootDir) {
  try {
    const kitPath = resolveModulePath("@nuxt/kit", { from: tryResolveNuxt(rootDir) || rootDir });
    let kit = await import(pathToFileURL(kitPath).href);
    if (!kit.writeTypes) {
      kit = {
        ...kit,
        writeTypes: () => {
          throw new Error("`writeTypes` is not available in this version of `@nuxt/kit`. Please upgrade to v3.7 or newer.");
        }
      };
    }
    return kit;
  } catch (e) {
    if (e.toString().includes("Cannot find module '@nuxt/kit'")) {
      throw new Error(
        "nuxi requires `@nuxt/kit` to be installed in your project. Try installing `nuxt` v3+ or `@nuxt/bridge` first."
      );
    }
    throw e;
  }
}
function tryResolveNuxt(rootDir) {
  for (const pkg of ["nuxt-nightly", "nuxt", "nuxt3", "nuxt-edge"]) {
    const path = resolveModulePath(pkg, { from: rootDir, try: true });
    if (path) {
      return path;
    }
  }
  return null;
}

export { loadKit as l, tryResolveNuxt as t };
