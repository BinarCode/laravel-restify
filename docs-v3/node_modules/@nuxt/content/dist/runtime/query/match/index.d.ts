import type { QueryMatchOperator } from '@nuxt/content';
interface MatchFactoryOptions {
    operators?: Record<string, QueryMatchOperator>;
}
export declare function createMatch(opts?: MatchFactoryOptions): (item: any, conditions: any) => boolean;
export {};
