export type DefaultMessages = Record<"appName" | "version" | "statusCode" | "statusMessage" | "description" | "stack", string | boolean | number>;
export declare const template: (messages: Partial<DefaultMessages>) => string;
