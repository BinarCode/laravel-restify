declare namespace parsePath {
    interface ParsedPath {
        /** The url hash. */
        hash: string;
        /** The url domain (including subdomain and port). */
        host: string;
        /** The normalized input url. */
        href: string;
        /**
         * The authentication password.
         * @default ''
         */
        password: string;
        /**
         * Whether the parsing failed or not.
         */
        parse_failed: boolean;
        /** The url pathname. */
        pathname: string;
        /**
         * The domain port.
         * @default ''
         */
        port: string;
        /** The first protocol or `"file"`. */
        protocol: string;
        /** An array with the url protocols (usually it has one element). */
        protocols: string[];
        /** The url querystring, parsed as object. */
        query: Record<string, string>;
        /** The url domain/hostname. */
        resource: string;
        /** The url querystring value (excluding `?`). */
        search: string;
        /**
         * The authentication user.
         * @default ''
         */
        user: string;
    }
}

declare function parsePath(url: string): parsePath.ParsedPath;
export = parsePath;
