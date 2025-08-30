import { resolveModulePath } from 'exsolve';
import { join, isAbsolute, relative } from 'pathe';
import { createUnplugin } from 'unplugin';
import { createFilter } from 'unplugin-utils';

const RELATIVE_IMPORT_RE = /^\.\.?\//;
const ImpoundPlugin = createUnplugin((globalOptions) => {
  const matchers = "matchers" in globalOptions ? globalOptions.matchers : [globalOptions];
  return matchers.map((options) => {
    const filter = createFilter(options.include, options.exclude, { resolve: globalOptions.cwd });
    const proxy = resolveModulePath("mocked-exports/proxy", { from: import.meta.url });
    return {
      name: "impound",
      enforce: "pre",
      resolveId(id, importer) {
        if (!importer || !filter(importer)) {
          return;
        }
        if (RELATIVE_IMPORT_RE.test(id)) {
          id = join(importer, "..", id);
        }
        if (isAbsolute(id) && globalOptions.cwd) {
          id = relative(globalOptions.cwd, id);
        }
        let matched = false;
        const logError = options.error === false ? console.error : this.error.bind(this);
        for (const [pattern, warning] of options.patterns) {
          const usesImport = pattern instanceof RegExp ? pattern.test(id) : typeof pattern === "string" ? pattern === id : pattern(id);
          if (usesImport) {
            const relativeImporter = isAbsolute(importer) && globalOptions.cwd ? relative(globalOptions.cwd, importer) : importer;
            logError(`${typeof usesImport === "string" ? usesImport : warning || "Invalid import"} [importing \`${id}\` from \`${relativeImporter}\`]`);
            matched = true;
          }
        }
        return matched ? proxy : null;
      }
    };
  });
});

export { ImpoundPlugin };
