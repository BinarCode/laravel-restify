export type DefaultMessages = Record<"appName" | "version" | "statusCode" | "statusMessage" | "description", string | boolean | number>;
export declare const template: (messages: Partial<DefaultMessages>) => string;
