import type { App } from 'vue';
import '#build/fetch.mjs';
import '#build/global-polyfills.mjs';
import type { CreateOptions } from './nuxt.js';
import '#build/css';
declare const _default: (ssrContext?: CreateOptions["ssrContext"]) => Promise<App<Element>>;
export default _default;
