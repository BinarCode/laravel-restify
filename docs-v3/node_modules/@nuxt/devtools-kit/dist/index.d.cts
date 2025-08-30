import * as _nuxt_schema from '@nuxt/schema';
import { BirpcGroup } from 'birpc';
import { ExecaChildProcess } from 'execa';
import { M as ModuleCustomTab, S as SubprocessOptions, T as TerminalState, N as NuxtDevtoolsInfo } from './shared/devtools-kit.BMivX6Xf.cjs';
import 'vue';
import 'nuxt/schema';
import 'unimport';
import 'vue-router';
import 'nitropack';
import 'unstorage';
import 'vite';

/**
 * Hooks to extend a custom tab in devtools.
 *
 * Provide a function to pass a factory that can be updated dynamically.
 */
declare function addCustomTab(tab: ModuleCustomTab | (() => ModuleCustomTab | Promise<ModuleCustomTab>), nuxt?: _nuxt_schema.Nuxt): void;
/**
 * Retrigger update for custom tabs, `devtools:customTabs` will be called again.
 */
declare function refreshCustomTabs(nuxt?: _nuxt_schema.Nuxt): Promise<any>;
/**
 * Create a subprocess that handled by the DevTools.
 */
declare function startSubprocess(execaOptions: SubprocessOptions, tabOptions: TerminalState, nuxt?: _nuxt_schema.Nuxt): {
    getProcess: () => ExecaChildProcess<string>;
    terminate: () => void;
    restart: () => void;
    clear: () => void;
};
declare function extendServerRpc<ClientFunctions = Record<string, never>, ServerFunctions = Record<string, never>>(namespace: string, functions: ServerFunctions, nuxt?: _nuxt_schema.Nuxt): BirpcGroup<ClientFunctions, ServerFunctions>;
declare function onDevToolsInitialized(fn: (info: NuxtDevtoolsInfo) => void, nuxt?: _nuxt_schema.Nuxt): void;

export { addCustomTab, extendServerRpc, onDevToolsInitialized, refreshCustomTabs, startSubprocess };
