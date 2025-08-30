declare module 'micromark-util-types' {
    interface TokenTypeMap {
        row: 'row';
        column: 'column';
        columnSeparator: 'columnSeparator';
        newline: 'newline';
        quotedData: 'quotedData';
        quotedDataChunk: 'quotedDataChunk';
        quoteFence: 'quoteFence';
        emptyLine: 'emptyLine';
    }
}
export declare const parse: (options: any) => {
    previous: null;
    code: null;
    containerState: {};
    events: never[];
    parser: any;
    sliceStream: (token: any) => any;
    sliceSerialize: (token: any, expandTabs: any) => string;
    now: () => any;
    defineSkip: (value: any) => void;
    write: (slice: any) => never[];
};
