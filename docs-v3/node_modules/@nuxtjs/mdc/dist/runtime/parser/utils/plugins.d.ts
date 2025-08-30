import type { Processor } from 'remark-rehype/lib';
import type { MDCParseOptions } from '@nuxtjs/mdc';
export declare const useProcessorPlugins: (processor: Processor, plugins?: Exclude<MDCParseOptions["rehype"] | MDCParseOptions["remark"], undefined>["plugins"]) => Promise<void>;
