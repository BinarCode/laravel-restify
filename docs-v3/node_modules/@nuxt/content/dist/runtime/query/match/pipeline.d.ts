import type { ContentQueryResponse, ContentQueryBuilder } from '@nuxt/content';
export declare function createPipelineFetcher<T>(getContentsList: () => Promise<T[]>): (query: ContentQueryBuilder<T>) => Promise<ContentQueryResponse<T>>;
