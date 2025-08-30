import { defineCommand } from 'citty';
import { colors } from 'consola/utils';
import Fuse from 'fuse.js';
import { upperFirst, kebabCase } from 'scule';
import { l as logger } from '../shared/cli.B9AmABr3.mjs';
import { g as getNuxtVersion } from '../shared/cli.DHenkA1C.mjs';
import { c as cwdArgs } from '../shared/cli.CTXRG5Cu.mjs';
import { f as fetchModules, c as checkNuxtCompatibility } from '../shared/cli.Cr-OCgdO.mjs';
import 'consola';
import 'pkg-types';
import 'semver';
import 'node:path';
import 'node:process';
import 'std-env';
import 'node:url';
import 'confbox';
import 'ofetch';

const { format: formatNumber } = Intl.NumberFormat("en-GB", {
  notation: "compact",
  maximumFractionDigits: 1
});
const search = defineCommand({
  meta: {
    name: "search",
    description: "Search in Nuxt modules"
  },
  args: {
    ...cwdArgs,
    query: {
      type: "positional",
      description: "keywords to search for",
      required: true
    },
    nuxtVersion: {
      type: "string",
      description: "Filter by Nuxt version and list compatible modules only (auto detected by default)",
      required: false,
      valueHint: "2|3"
    }
  },
  async setup(ctx) {
    const nuxtVersion = await getNuxtVersion(ctx.args.cwd);
    return findModuleByKeywords(ctx.args._.join(" "), nuxtVersion);
  }
});
async function findModuleByKeywords(query, nuxtVersion) {
  const allModules = await fetchModules();
  const compatibleModules = allModules.filter(
    (m) => checkNuxtCompatibility(m, nuxtVersion)
  );
  const fuse = new Fuse(compatibleModules, {
    threshold: 0.1,
    keys: [
      { name: "name", weight: 1 },
      { name: "npm", weight: 1 },
      { name: "repo", weight: 1 },
      { name: "tags", weight: 1 },
      { name: "category", weight: 1 },
      { name: "description", weight: 0.5 },
      { name: "maintainers.name", weight: 0.5 },
      { name: "maintainers.github", weight: 0.5 }
    ]
  });
  const { bold, green, magenta, cyan, gray, yellow } = colors;
  const results = fuse.search(query).map((result) => {
    const res = {
      name: bold(result.item.name),
      homepage: cyan(result.item.website),
      compatibility: `nuxt: ${result.item.compatibility?.nuxt || "*"}`,
      repository: gray(result.item.github),
      description: gray(result.item.description),
      package: gray(result.item.npm),
      install: cyan(`npx nuxi module add ${result.item.name}`),
      stars: yellow(formatNumber(result.item.stats.stars)),
      monthlyDownloads: yellow(formatNumber(result.item.stats.downloads))
    };
    if (result.item.github === result.item.website) {
      delete res.homepage;
    }
    if (result.item.name === result.item.npm) {
      delete res.packageName;
    }
    return res;
  });
  if (!results.length) {
    logger.info(
      `No Nuxt modules found matching query ${magenta(query)} for Nuxt ${cyan(nuxtVersion)}`
    );
    return;
  }
  logger.success(
    `Found ${results.length} Nuxt ${results.length > 1 ? "modules" : "module"} matching ${cyan(query)} ${nuxtVersion ? `for Nuxt ${cyan(nuxtVersion)}` : ""}:
`
  );
  for (const foundModule of results) {
    let maxLength = 0;
    const entries = Object.entries(foundModule).map(([key, val]) => {
      const label = upperFirst(kebabCase(key)).replace(/-/g, " ");
      if (label.length > maxLength) {
        maxLength = label.length;
      }
      return [label, val || "-"];
    });
    let infoStr = "";
    for (const [label, value] of entries) {
      infoStr += `${bold(label === "Install" ? "\u2192 " : "- ") + green(label.padEnd(maxLength + 2)) + value}
`;
    }
    logger.log(infoStr);
  }
}

export { search as default };
