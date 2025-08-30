import * as unplugin from 'unplugin';
import { R as ResolvedOptions, S as ServerContext, O as Options } from './types-CTGkmk9e.cjs';
export { D as DEFAULT_OPTIONS, E as EditableTreeNode, T as TreeNode, b as TreeNodeValueParam, d as TreeNodeValueStatic, c as createTreeNodeValue, g as getFileBasedRouteName, a as getPascalCaseRouteName } from './types-CTGkmk9e.cjs';
import { Plugin } from 'vite';
import 'vue-router';

declare function createRoutesContext(options: ResolvedOptions): {
    scanPages: (startWatchers?: boolean) => Promise<void>;
    writeConfigFiles: () => void;
    setServerContext: (_server: ServerContext) => void;
    stopWatcher: () => void;
    generateRoutes: () => string;
    generateVueRouterProxy: () => string;
    definePageTransform(code: string, id: string): unplugin.Thenable<unplugin.TransformResult>;
};

/**
 * {@link AutoExportLoaders} options.
 */
interface AutoExportLoadersOptions {
    /**
     * Filter page components to apply the auto-export (defined with `createFilter()` from `unplugin-utils`) or array
     * of globs.
     */
    filterPageComponents: ((id: string) => boolean) | string[];
    /**
     * Globs to match the paths of the loaders.
     */
    loadersPathsGlobs: string | string[];
    /**
     * Root of the project. All paths are resolved relatively to this one.
     * @default `process.cwd()`
     */
    root?: string;
}
/**
 * Vite Plugin to automatically export loaders from page components.
 *
 * @param options Options
 * @experimental - This API is experimental and can be changed in the future. It's used internally by `experimental.autoExportsDataLoaders`

 */
declare function AutoExportLoaders({ filterPageComponents: filterPagesOrGlobs, loadersPathsGlobs, root, }: AutoExportLoadersOptions): Plugin;

declare const _default: unplugin.UnpluginInstance<Options | undefined, boolean>;

/**
 * Adds useful auto imports to the AutoImport config:
 * @example
 * ```js
 * import { VueRouterAutoImports } from 'unplugin-vue-router'
 *
 * AutoImport({
 *   imports: [VueRouterAutoImports],
 * }),
 * ```
 */
declare const VueRouterAutoImports: Record<string, Array<string | [importName: string, alias: string]>>;

export { AutoExportLoaders, type AutoExportLoadersOptions, Options, VueRouterAutoImports, createRoutesContext, _default as default };
