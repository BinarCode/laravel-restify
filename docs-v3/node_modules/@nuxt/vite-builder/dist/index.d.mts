import * as vite from 'vite';
import { NuxtBuilder, Nuxt, ViteConfig } from '@nuxt/schema';

interface ViteBuildContext {
    nuxt: Nuxt;
    config: ViteConfig;
    entry: string;
    clientServer?: vite.ViteDevServer;
    ssrServer?: vite.ViteDevServer;
}
declare const bundle: NuxtBuilder['bundle'];

export { bundle };
export type { ViteBuildContext };
