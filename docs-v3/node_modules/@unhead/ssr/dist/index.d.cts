import { Unhead, RenderSSRHeadOptions, SSRHeadPayload, HeadTag } from '@unhead/schema';
export { SSRHeadPayload } from '@unhead/schema';

declare function renderSSRHead<T extends {}>(head: Unhead<T>, options?: RenderSSRHeadOptions): Promise<SSRHeadPayload>;

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

export { escapeHtml, propsToString, renderSSRHead, ssrRenderTags, tagToString };
