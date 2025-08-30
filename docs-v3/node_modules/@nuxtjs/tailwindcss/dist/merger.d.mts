import { T as TWConfig } from './shared/tailwindcss.7c01d049.mjs';
import 'tailwindcss/resolveConfig';
import 'tailwindcss';
import 'nuxt/kit';

type Input = Partial<TWConfig> | Record<PropertyKey, any> | null | undefined;
/**
 * Merges Tailwind CSS configuration objects. This has special logic to merge Content as Array or Object.
 *
 * Read <https://tailwindcss.com/docs/content-configuration>.
 */
declare const _default: (base: Input, ...defaults: Input[]) => Partial<TWConfig>;

export { _default as default };
