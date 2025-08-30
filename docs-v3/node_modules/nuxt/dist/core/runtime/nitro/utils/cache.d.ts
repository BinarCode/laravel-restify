export declare const payloadCache: any;
export declare const islandCache: any;
export declare const islandPropCache: any;
export declare const sharedPrerenderPromises: Map<string, Promise<any>> | null;
export declare const sharedPrerenderCache: {
    get<T = unknown>(key: string): Promise<T> | undefined;
    set<T>(key: string, value: Promise<T>): Promise<void>;
} | null;
