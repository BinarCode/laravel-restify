import type { QueryBuilder, ParsedContent, ContentQueryBuilder, ContentQueryBuilderParams, ContentQueryFetcher } from '@nuxt/content';
interface QueryOptions {
    initialParams?: ContentQueryBuilderParams;
    legacy?: boolean;
}
export declare function createQuery<T = ParsedContent>(fetcher: ContentQueryFetcher<T>, opts: QueryOptions & {
    legacy: true;
}): QueryBuilder<T>;
export declare function createQuery<T = ParsedContent>(fetcher: ContentQueryFetcher<T>, opts: QueryOptions & {
    legacy: false;
}): ContentQueryBuilder<T>;
export {};
