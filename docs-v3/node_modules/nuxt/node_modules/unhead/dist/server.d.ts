import { R as ResolvableHead, p as CreateServerHeadOptions, U as Unhead, u as RenderSSRHeadOptions, a4 as SerializableHead, au as HeadTag } from './shared/unhead.BxIzrSMV.js';
export { t as SSRHeadPayload } from './shared/unhead.BxIzrSMV.js';
import 'hookable';

declare function createHead<T = ResolvableHead>(options?: CreateServerHeadOptions): Unhead<T>;

declare function renderSSRHead(head: Unhead<any>, options?: RenderSSRHeadOptions): Promise<{
    headTags: string;
    bodyTags: string;
    bodyTagsOpen: string;
    htmlAttrs: string;
    bodyAttrs: string;
}>;

declare function transformHtmlTemplate(head: Unhead<any>, html: string, options?: RenderSSRHeadOptions): Promise<string>;

declare function extractUnheadInputFromHtml(html: string): {
    html: string;
    input: SerializableHead;
};

declare function propsToString(props: Record<string, any>): string;

declare function ssrRenderTags<T extends HeadTag>(tags: T[], options?: RenderSSRHeadOptions): {
    headTags: string;
    bodyTags: string;
    bodyTagsOpen: string;
    htmlAttrs: string;
    bodyAttrs: string;
};

declare function escapeHtml(str: string): string;
declare function tagToString<T extends HeadTag>(tag: T): string;

export { CreateServerHeadOptions, Unhead, createHead, escapeHtml, extractUnheadInputFromHtml, propsToString, renderSSRHead, ssrRenderTags, tagToString, transformHtmlTemplate };
