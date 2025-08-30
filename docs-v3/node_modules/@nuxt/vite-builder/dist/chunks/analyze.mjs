import { transform } from 'esbuild';
import { visualizer } from 'rollup-plugin-visualizer';
import defu from 'defu';

function analyzePlugin(ctx) {
  const analyzeOptions = defu({}, ctx.nuxt.options.build.analyze);
  if (!analyzeOptions.enabled) {
    return [];
  }
  return [
    {
      name: "nuxt:analyze-minify",
      async generateBundle(_opts, outputBundle) {
        for (const _bundleId in outputBundle) {
          const bundle = outputBundle[_bundleId];
          if (!bundle || bundle.type !== "chunk") {
            continue;
          }
          const minifiedModuleEntryPromises = [];
          for (const [moduleId, module] of Object.entries(bundle.modules)) {
            minifiedModuleEntryPromises.push(
              transform(module.code || "", { minify: true }).then((result) => [moduleId, { ...module, code: result.code }])
            );
          }
          bundle.modules = Object.fromEntries(await Promise.all(minifiedModuleEntryPromises));
        }
      }
    },
    visualizer({
      ...analyzeOptions,
      filename: "filename" in analyzeOptions ? analyzeOptions.filename.replace("{name}", "client") : void 0,
      title: "Client bundle stats",
      gzipSize: true,
      brotliSize: true
    })
  ];
}

export { analyzePlugin };
