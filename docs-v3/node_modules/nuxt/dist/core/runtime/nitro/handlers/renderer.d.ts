export interface NuxtRenderHTMLContext {
    htmlAttrs: string[];
    head: string[];
    bodyAttrs: string[];
    bodyPrepend: string[];
    body: string[];
    bodyAppend: string[];
}
export interface NuxtRenderResponse {
    body: string;
    statusCode: number;
    statusMessage?: string;
    headers: Record<string, string>;
}
declare const _default: any;
export default _default;
