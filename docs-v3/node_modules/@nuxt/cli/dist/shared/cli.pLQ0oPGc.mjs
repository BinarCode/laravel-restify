import { promises, existsSync } from 'node:fs';
import { join } from 'pathe';
import { l as logger } from './cli.B9AmABr3.mjs';

async function clearDir(path, exclude) {
  if (!exclude) {
    await promises.rm(path, { recursive: true, force: true });
  } else if (existsSync(path)) {
    const files = await promises.readdir(path);
    await Promise.all(
      files.map(async (name) => {
        if (!exclude.includes(name)) {
          await promises.rm(join(path, name), { recursive: true, force: true });
        }
      })
    );
  }
  await promises.mkdir(path, { recursive: true });
}
function clearBuildDir(path) {
  return clearDir(path, ["cache", "analyze", "nuxt.json"]);
}
async function rmRecursive(paths) {
  await Promise.all(
    paths.filter((p) => typeof p === "string").map(async (path) => {
      logger.debug("Removing recursive path", path);
      await promises.rm(path, { recursive: true, force: true }).catch(() => {
      });
    })
  );
}

export { clearBuildDir as a, clearDir as c, rmRecursive as r };
