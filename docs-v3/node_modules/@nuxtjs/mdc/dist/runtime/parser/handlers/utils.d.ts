/**
 * Parses the value defined next to 3 back ticks
 * in a codeblock and set line-highlights or
 * filename from it
 */
export declare function parseThematicBlock(lang: string): {
    language: undefined;
    highlights: undefined;
    filename: undefined;
    meta: undefined;
} | {
    language: string | undefined;
    highlights: number[] | undefined;
    filename: string | undefined;
    meta: string;
};
export declare function getTagName(value: string): string | null;
