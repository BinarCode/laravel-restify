/**
 * Represent a structured clone value as string.
 */
declare const stringify: (obj: any) => string;
/**
 * Revive a previously stringified structured clone.
 */
declare const parse: (str: string) => unknown;
declare const structuredClone: typeof globalThis.structuredClone;

export { parse, stringify, structuredClone };
