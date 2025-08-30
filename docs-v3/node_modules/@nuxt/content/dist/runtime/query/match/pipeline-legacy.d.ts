import type { QueryBuilder } from '@nuxt/content';
export declare function createPipelineFetcherLegacy<T>(getContentsList: () => Promise<T[]>): (query: QueryBuilder<T>) => Promise<T | T[]>;
