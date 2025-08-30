import { FilterPattern, Plugin } from 'vite';
import { VueJSXPluginOptions } from '@vue/babel-plugin-jsx';

interface FilterOptions {
    include?: FilterPattern;
    exclude?: FilterPattern;
}
interface Options extends VueJSXPluginOptions, FilterOptions {
    babelPlugins?: any[];
    /** @default ['defineComponent'] */
    defineComponentName?: string[];
    tsPluginOptions?: any;
}

declare function vueJsxPlugin(options?: Options): Plugin;

// @ts-ignore
export = vueJsxPlugin;
export type { FilterOptions, Options };
