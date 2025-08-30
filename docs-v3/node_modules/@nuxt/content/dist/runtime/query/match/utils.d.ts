import type { SortOptions } from '@nuxt/content';
/**
 * Retrive nested value from object by path
 */
export declare const get: (obj: any, path: string) => any;
/**
 * Returns a new object with the specified keys
 **/
export declare const pick: (keys?: string[]) => (obj: any) => any;
/**
 * Returns a new object with all the keys of the original object execept the ones specified.
 **/
export declare const omit: (keys?: string[]) => (obj: any) => any;
/**
 * Apply a function to each element of an array
 */
export declare const apply: (fn: (d: any) => any) => (data: any) => any;
export declare const detectProperties: (keys: string[]) => {
    prefixes: string[];
    properties: string[];
};
export declare const withoutKeys: (keys?: string[]) => (obj: any) => any;
export declare const withKeys: (keys?: string[]) => (obj: any) => any;
/**
 * Sort list of items by givin options
 */
export declare const sortList: (data: any[], params: SortOptions) => any[];
/**
 * Raise TypeError if value is not an array
 */
export declare const assertArray: (value: any, message?: string) => void;
/**
 * Ensure result is an array
 */
export declare const ensureArray: <T>(value: T) => T extends Array<any> ? T : T[];
