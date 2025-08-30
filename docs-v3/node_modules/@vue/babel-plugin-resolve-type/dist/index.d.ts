import * as BabelCore from "@babel/core";
import { SimpleTypeResolveOptions } from "@vue/compiler-sfc";

//#region src/index.d.ts
declare const plugin: (api: object, options: SimpleTypeResolveOptions | null | undefined, dirname: string) => BabelCore.PluginObj<BabelCore.PluginPass>;
//#endregion
export { SimpleTypeResolveOptions as Options, plugin as default };