import type { H3Event } from 'h3';
export declare const isPreview: (event: H3Event) => boolean;
export declare const getPreview: (event: H3Event) => {
    key: string | undefined;
};
