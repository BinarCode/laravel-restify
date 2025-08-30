import type { useContent } from './content';
export declare const withContentBase: (url: string) => string;
export declare const useContentDisabled: () => ReturnType<typeof useContent>;
export declare const navigationDisabled: () => never;
export declare const addPrerenderPath: (path: string) => void;
export declare const shouldUseClientDB: () => boolean;
