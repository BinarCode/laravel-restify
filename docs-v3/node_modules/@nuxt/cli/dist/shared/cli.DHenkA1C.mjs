import { readPackageJSON } from 'pkg-types';
import { coerce } from 'semver';

async function getNuxtVersion(cwd, cache = true) {
  const nuxtPkg = await readPackageJSON("nuxt", { url: cwd, try: true, cache });
  if (nuxtPkg) {
    return nuxtPkg.version;
  }
  const pkg = await readPackageJSON(cwd);
  const pkgDep = pkg?.dependencies?.nuxt || pkg?.devDependencies?.nuxt;
  return pkgDep && coerce(pkgDep)?.version || "3.0.0";
}

export { getNuxtVersion as g };
