import * as _nuxt_schema from '@nuxt/schema';
import { M as ModuleHooks, a as ModuleOptions } from './shared/tailwindcss.7c01d049.cjs';
import 'tailwindcss/resolveConfig';
import 'tailwindcss';
import 'nuxt/kit';

declare const _default: _nuxt_schema.NuxtModule<ModuleOptions, ModuleOptions, false>;

declare module 'nuxt/schema' {
    interface NuxtHooks extends ModuleHooks {
        'tailwindcss:internal:regenerateTemplates': (data?: {
            configTemplateUpdated?: boolean;
        }) => void | Promise<void>;
    }
}
declare module '@nuxt/schema' {
    interface NuxtHooks extends ModuleHooks {
        'tailwindcss:internal:regenerateTemplates': (data?: {
            configTemplateUpdated?: boolean;
        }) => void | Promise<void>;
    }
}

export { ModuleHooks, ModuleOptions, _default as default };
