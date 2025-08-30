/**
 * Converts a JavaScript value to a JavaScript Object Notation (JSON) string.
 * This function is equivalent to `JSON.stringify`, but it also handles RegExp objects.
 */
export declare function jsonStringify(value: any): string;
/**
 * Converts a JavaScript Object Notation (JSON) string into an object.
 * This function is equivalent to `JSON.parse`, but it also handles RegExp objects.
 */
export declare function jsonParse(value: string): any;
