import { MergeHead, ReactiveHead } from '@unhead/vue';
export { ActiveHeadEntry, HeadEntryOptions, HeadTag, MaybeComputedRef, MergeHead, ReactiveHead, Unhead, UseHeadInput, Vue2ProvideUnheadPlugin, VueHeadMixin, createHeadCore, injectHead, unheadVueComposablesImports, useHead, useHeadSafe, useSeoMeta, useServerHead, useServerHeadSafe, useServerSeoMeta } from '@unhead/vue';
import * as _unhead_schema from '@unhead/schema';
import { Head, CreateHeadOptions } from '@unhead/schema';
import { VueHeadClientPollyFill as VueHeadClientPollyFill$1 } from '@unhead/vue/polyfill';
import { Plugin } from 'vue';
export { Head } from '@unhead/vue/components';

declare function createHead<T extends MergeHead = {}>(initHeadObject?: Head<T>, options?: CreateHeadOptions): VueHeadClientPollyFill$1<T>;

declare const HeadVuePlugin: Plugin;
declare const renderHeadToString: <T extends MergeHead = {}>(head: VueHeadClientPollyFill<T>) => Promise<_unhead_schema.SSRHeadPayload>;
type HeadObjectPlain = Head;
type HeadObject = ReactiveHead;

export { type HeadObject, type HeadObjectPlain, HeadVuePlugin, createHead, renderHeadToString };
