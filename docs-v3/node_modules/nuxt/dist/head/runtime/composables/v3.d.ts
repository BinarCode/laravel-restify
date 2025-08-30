import type { ActiveHeadEntry, UseHeadInput, UseHeadOptions, UseHeadSafeInput, UseSeoMetaInput, VueHeadClient } from '@unhead/vue';
import type { NuxtApp } from '#app/nuxt';
/**
 * Injects the head client from the Nuxt context or Vue inject.
 *
 * In Nuxt v3 this function will not throw an error if the context is missing.
 */
export declare function injectHead(nuxtApp?: NuxtApp): VueHeadClient;
interface NuxtUseHeadOptions extends UseHeadOptions {
    nuxt?: NuxtApp;
}
export declare function useHead(input: UseHeadInput, options?: NuxtUseHeadOptions): ActiveHeadEntry<UseHeadInput> | void;
export declare function useHeadSafe(input: UseHeadSafeInput, options?: NuxtUseHeadOptions): ActiveHeadEntry<UseHeadSafeInput> | void;
export declare function useSeoMeta(input: UseSeoMetaInput, options?: NuxtUseHeadOptions): ActiveHeadEntry<UseSeoMetaInput> | void;
export declare function useServerHead(input: UseHeadInput, options?: NuxtUseHeadOptions): ActiveHeadEntry<UseHeadInput> | void;
export declare function useServerHeadSafe(input: UseHeadSafeInput, options?: NuxtUseHeadOptions): ActiveHeadEntry<UseHeadSafeInput> | void;
export declare function useServerSeoMeta(input: UseSeoMetaInput, options?: NuxtUseHeadOptions): ActiveHeadEntry<UseSeoMetaInput> | void;
export {};
