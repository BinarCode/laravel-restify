import * as c12 from 'c12';
import * as tailwindcss_types_config from 'tailwindcss/types/config';
import * as unctx from 'unctx';

declare const ctx: unctx.UseContext<boolean>;
declare const _defineConfig: c12.DefineConfig<Partial<tailwindcss_types_config.Config>, c12.ConfigLayerMeta>;
declare const defineConfig: typeof _defineConfig;

export { ctx, defineConfig as default, defineConfig };
