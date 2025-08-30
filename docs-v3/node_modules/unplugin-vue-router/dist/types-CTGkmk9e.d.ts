import { RouteRecordRaw, RouteMeta } from 'vue-router';

interface CustomRouteBlock extends Partial<Omit<RouteRecordRaw, 'components' | 'component' | 'children' | 'beforeEnter' | 'name'>> {
    name?: string | undefined;
}

declare const enum TreeNodeType {
    static = 0,
    group = 1,
    param = 2
}
interface RouteRecordOverride extends Partial<Pick<RouteRecordRaw, 'meta' | 'props' | 'alias' | 'path'>> {
    name?: string | undefined;
}
type SubSegment = string | TreeRouteParam;
declare class _TreeNodeValueBase {
    /**
     * flag based on the type of the segment
     */
    _type: TreeNodeType;
    parent: TreeNodeValue | undefined;
    /**
     * segment as defined by the file structure e.g. keeps the `index` name, `(group-name)`
     */
    rawSegment: string;
    /**
     * transformed version of the segment into a vue-router path. e.g. `'index'` becomes `''` and `[param]` becomes
     * `:param`, `prefix-[param]-end` becomes `prefix-:param-end`.
     */
    pathSegment: string;
    /**
     * Array of sub segments. This is usually one single elements but can have more for paths like `prefix-[param]-end.vue`
     */
    subSegments: SubSegment[];
    /**
     * Overrides defined by each file. The map is necessary to handle named views.
     */
    private _overrides;
    /**
     * View name (Vue Router feature) mapped to their corresponding file. By default, the view name is `default` unless
     * specified with a `@` e.g. `index@aux.vue` will have a view name of `aux`.
     */
    components: Map<string, string>;
    constructor(rawSegment: string, parent: TreeNodeValue | undefined, pathSegment?: string, subSegments?: SubSegment[]);
    /**
     * fullPath of the node based on parent nodes
     */
    get path(): string;
    toString(): string;
    isParam(): this is TreeNodeValueParam;
    isStatic(): this is TreeNodeValueStatic;
    isGroup(): this is TreeNodeValueGroup;
    get overrides(): RouteRecordOverride;
    setOverride(filePath: string, routeBlock: CustomRouteBlock | undefined): void;
    /**
     * Remove all overrides for a given key.
     *
     * @param key - key to remove from the override, e.g. path, name, etc
     */
    removeOverride(key: keyof CustomRouteBlock): void;
    /**
     * Add an override to the current node by merging with the existing values.
     *
     * @param filePath - The file path to add to the override
     * @param routeBlock - The route block to add to the override
     */
    mergeOverride(filePath: string, routeBlock: CustomRouteBlock): void;
    /**
     * Add an override to the current node using the special file path `@@edits` that makes this added at build time.
     *
     * @param routeBlock -  The route block to add to the override
     */
    addEditOverride(routeBlock: CustomRouteBlock): void;
    /**
     * Set a specific value in the _edits_ override.
     *
     * @param key - key to set in the override, e.g. path, name, etc
     * @param value - value to set in the override
     */
    setEditOverride<K extends keyof RouteRecordOverride>(key: K, value: RouteRecordOverride[K]): void;
}
declare class TreeNodeValueStatic extends _TreeNodeValueBase {
    _type: TreeNodeType.static;
    constructor(rawSegment: string, parent: TreeNodeValue | undefined, pathSegment?: string);
}
declare class TreeNodeValueGroup extends _TreeNodeValueBase {
    _type: TreeNodeType.group;
    groupName: string;
    constructor(rawSegment: string, parent: TreeNodeValue | undefined, pathSegment: string, groupName: string);
}
interface TreeRouteParam {
    paramName: string;
    modifier: string;
    optional: boolean;
    repeatable: boolean;
    isSplat: boolean;
}
declare class TreeNodeValueParam extends _TreeNodeValueBase {
    params: TreeRouteParam[];
    _type: TreeNodeType.param;
    constructor(rawSegment: string, parent: TreeNodeValue | undefined, params: TreeRouteParam[], pathSegment: string, subSegments: SubSegment[]);
}
type TreeNodeValue = TreeNodeValueStatic | TreeNodeValueParam | TreeNodeValueGroup;
interface TreeNodeValueOptions extends ParseSegmentOptions {
    /**
     * Format of the route path. Defaults to `file` which is the format used by unplugin-vue-router and matches the file
     * structure (e.g. `index`, ``, or `users/[id]`). In `path` format, routes are expected in the format of vue-router
     * (e.g. `/` or '/users/:id' ).
     *
     * @default `'file'`
     */
    format?: 'file' | 'path';
}
/**
 * Creates a new TreeNodeValue based on the segment. The result can be a static segment, group segment or a param segment.
 *
 * @param segment - path segment
 * @param parent - parent node
 * @param options - options
 */
declare function createTreeNodeValue(segment: string, parent?: TreeNodeValue, opts?: TreeNodeValueOptions): TreeNodeValue;
/**
 * Options passed to `parseSegment()`to control how a segment of a file path is parsed. e.g. in `/users/[id]`, `users`
 * and `[id]` are segments.
 */
interface ParseSegmentOptions {
    /**
     * Should we allow dot nesting in the param name. e.g. `users.[id]` will be parsed as `users/[id]` if this is `true`,
     * nesting. Note this only works for the `file` format.
     *
     * @default `true`
     */
    dotNesting?: boolean;
}

interface TreeNodeOptions extends ResolvedOptions {
    treeNodeOptions?: TreeNodeValueOptions;
}
declare class TreeNode {
    /**
     * value of the node
     */
    value: TreeNodeValue;
    /**
     * children of the node
     */
    children: Map<string, TreeNode>;
    /**
     * Parent node.
     */
    parent: TreeNode | undefined;
    /**
     * Plugin options taken into account by the tree.
     */
    options: TreeNodeOptions;
    /**
     * Should this page import the page info
     */
    hasDefinePage: boolean;
    /**
     * Creates a new tree node.
     *
     * @param options - TreeNodeOptions shared by all nodes
     * @param pathSegment - path segment of this node e.g. `users` or `:id`
     * @param parent
     */
    constructor(options: TreeNodeOptions, pathSegment: string, parent?: TreeNode);
    /**
     * Adds a path to the tree. `path` cannot start with a `/`.
     *
     * @param path - path segment to insert. **It shouldn't contain the file extension**
     * @param filePath - file path, must be a file (not a folder)
     */
    insert(path: string, filePath: string): TreeNode;
    /**
     * Adds a path that has already been parsed to the tree. `path` cannot start with a `/`. This method is similar to
     * `insert` but the path argument should be already parsed. e.g. `users/:id` for a file named `users/[id].vue`.
     *
     * @param path - path segment to insert, already parsed (e.g. users/:id)
     * @param filePath - file path, defaults to path for convenience and testing
     */
    insertParsedPath(path: string, filePath?: string): TreeNode;
    /**
     * Saves a custom route block for a specific file path. The file path is used as a key. Some special file paths will
     * have a lower or higher priority.
     *
     * @param filePath - file path where the custom block is located
     * @param routeBlock - custom block to set
     */
    setCustomRouteBlock(filePath: string, routeBlock: CustomRouteBlock | undefined): void;
    getSortedChildren(): TreeNode[];
    /**
     * Delete and detach itself from the tree.
     */
    delete(): void;
    /**
     * Remove a route from the tree. The path shouldn't start with a `/` but it can be a nested one. e.g. `foo/bar`.
     * The `path` should be relative to the page folder.
     *
     * @param path - path segment of the file
     */
    remove(path: string): void;
    /**
     * Returns the route path of the node without parent paths. If the path was overridden, it returns the override.
     */
    get path(): string;
    /**
     * Returns the route path of the node including parent paths.
     */
    get fullPath(): string;
    /**
     * Returns the route name of the node. If the name was overridden, it returns the override.
     */
    get name(): string;
    /**
     * Returns the meta property as an object.
     */
    get metaAsObject(): Readonly<RouteMeta>;
    /**
     * Returns the JSON string of the meta object of the node. If the meta was overridden, it returns the override. If
     * there is no override, it returns an empty string.
     */
    get meta(): string;
    get params(): TreeRouteParam[];
    /**
     * Returns wether this tree node is the root node of the tree.
     *
     * @returns true if the node is the root node
     */
    isRoot(): boolean;
    toString(): string;
}

/**
 * Creates a name based of the node path segments.
 *
 * @param node - the node to get the path from
 * @param parent - the parent node
 * @returns a route name
 */
declare function getPascalCaseRouteName(node: TreeNode): string;
/**
 * Joins the path segments of a node into a name that corresponds to the filepath represented by the node.
 *
 * @param node - the node to get the path from
 * @returns a route name
 */
declare function getFileBasedRouteName(node: TreeNode): string;

/**
 * A route node that can be modified by the user. The tree can be iterated to be traversed.
 * @example
 * ```js
 * [...node] // creates an array of all the children
 * for (const child of node) {
 *   // do something with the child node
 * }
 * ```
 *
 * @experimental
 */
declare class EditableTreeNode {
    private node;
    constructor(node: TreeNode);
    /**
     * Remove and detach the current route node from the tree. Subsequently, its children will be removed as well.
     */
    delete(): void;
    /**
     * Inserts a new route as a child of this route. This route cannot use `definePage()`. If it was meant to be included,
     * add it to the `routesFolder` option.
     *
     * @param path - path segment to insert. Note this is relative to the current route. **It shouldn't start with `/`**. If it does, it will be added to the root of the tree.
     * @param filePath - file path
     * @returns the new editable route node
     */
    insert(path: string, filePath: string): EditableTreeNode;
    /**
     * Get an editable version of the parent node if it exists.
     */
    get parent(): EditableTreeNode | undefined;
    /**
     * Return a Map of the files associated to the current route. The key of the map represents the name of the view (Vue
     * Router feature) while the value is the **resolved** file path.
     * By default, the name of the view is `default`.
     */
    get components(): Map<string, string>;
    /**
     * Alias for `route.components.get('default')`.
     */
    get component(): string | undefined;
    /**
     * Name of the route. Note that **all routes are named** but when the final `routes` array is generated, routes
     * without a `component` will not include their `name` property to avoid accidentally navigating to them and display
     * nothing.
     * @see {@link isPassThrough}
     */
    get name(): string;
    /**
     * Override the name of the route.
     */
    set name(name: string | undefined);
    /**
     * Whether the route is a pass-through route. A pass-through route is a route that does not have a component and is
     * used to group other routes under the same prefix `path` and/or `meta` properties.
     */
    get isPassThrough(): boolean;
    /**
     * Meta property of the route as an object. Note this property is readonly and will be serialized as JSON. It won't contain the meta properties defined with `definePage()` as it could contain expressions **but it does contain the meta properties defined with `<route>` blocks**.
     */
    get meta(): Readonly<RouteMeta>;
    /**
     * Override the meta property of the route. This will discard any other meta property defined with `<route>` blocks or
     * through other means. If you want to keep the existing meta properties, use `addToMeta`.
     * @see {@link addToMeta}
     */
    set meta(meta: RouteMeta);
    /**
     * Add meta properties to the route keeping the existing ones. The passed object will be deeply merged with the
     * existing meta object if any. Note that the meta property is later on serialized as JSON so you can't pass functions
     * or any other non-serializable value.
     */
    addToMeta(meta: Partial<RouteMeta>): void;
    /**
     * Path of the route without parent paths.
     */
    get path(): string;
    /**
     * Override the path of the route. You must ensure `params` match with the existing path.
     */
    set path(path: string);
    /**
     * Alias of the route.
     */
    get alias(): string | string[] | undefined;
    /**
     * Add an alias to the route.
     *
     * @param alias - Alias to add to the route
     */
    addAlias(alias: CustomRouteBlock['alias']): void;
    /**
     * Array of the route params and all of its parent's params. Note that changing the params will not update the path,
     * you need to update both.
     */
    get params(): TreeRouteParam[];
    /**
     * Path of the route including parent paths.
     */
    get fullPath(): string;
    /**
     * Computes an array of EditableTreeNode from the current node. Differently from iterating over the tree, this method
     * **only returns direct children**.
     */
    get children(): EditableTreeNode[];
    /**
     * DFS traversal of the tree.
     * @example
     * ```ts
     * for (const node of tree) {
     *   // ...
     * }
     * ```
     */
    traverseDFS(): Generator<EditableTreeNode, void, unknown>;
    [Symbol.iterator](): Generator<EditableTreeNode, void, unknown>;
    /**
     * BFS traversal of the tree as a generator.
     *
     * @example
     * ```ts
     * for (const node of tree) {
     *   // ...
     * }
     * ```
     */
    traverseBFS(): Generator<EditableTreeNode, void, unknown>;
}

/**
 * Maybe a promise maybe not
 * @internal
 */
type _Awaitable<T> = T | PromiseLike<T>;

/**
 * Options for a routes folder.
 */
interface RoutesFolderOption {
    /**
     * Folder to scan files that should be used for routes. **Cannot be a glob**, use the `path`, `filePatterns`, and
     * `exclude` options to filter out files. This section will **be removed** from the resulting path.
     */
    src: string;
    /**
     * Prefix to add to the route path **as is**. Defaults to `''`. Can also be a function
     * to reuse parts of the filepath, in that case you should return a **modified version of the filepath**.
     *
     * @example
     * ```js
     * {
     *   src: 'src/pages',
     *   // this is equivalent to the default behavior
     *   path: (file) => file.slice(file.lastIndexOf('src/pages') + 'src/pages'.length
     * },
     * {
     *   src: 'src/features',
     *   // match all files (note the \ is not needed in real code)
     *   filePatterns: '*‍/pages/**\/',
     *   path: (file) => {
     *     const prefix = 'src/features'
     *     // remove the everything before src/features and removes /pages
     *     // /src/features/feature1/pages/index.vue -> feature1/index.vue
     *     return file.slice(file.lastIndexOf(prefix) + prefix.length + 1).replace('/pages', '')
     *   },
     * },
     * {
     *   src: 'src/docs',
     *   // adds a prefix with a param
     *   path: 'docs/[lang]/',
     * },
     * ```
     */
    path?: string | ((filepath: string) => string);
    /**
     * Allows to override the global `filePattern` option for this folder. It can also extend the global values by passing
     * a function that returns an array.
     */
    filePatterns?: _OverridableOption<string[], string | string[]>;
    /**
     * Allows to override the global `exclude` option for this folder. It can also extend the global values by passing a
     * function that returns an array.
     */
    exclude?: _OverridableOption<string[], string | string[]>;
    /**
     * Allows to override the global `extensions` option for this folder. It can also extend the global values by passing
     * a function that returns an array.
     */
    extensions?: _OverridableOption<string[]>;
}
/**
 * Normalized options for a routes folder.
 */
interface RoutesFolderOptionResolved extends RoutesFolderOption {
    path: string | ((filepath: string) => string);
    /**
     * Final glob pattern to match files in the folder.
     */
    pattern: string[];
    filePatterns: string[];
    exclude: string[];
    extensions: string[];
}
type _OverridableOption<T, AllowedTypes = T> = AllowedTypes | ((existing: T) => T);
/**
 * Resolves an overridable option by calling the function with the existing value if it's a function, otherwise
 * returning the passed `value`. If `value` is undefined, it returns the `defaultValue` instead.
 *
 * @param defaultValue default value for the option
 * @param value and overridable option
 */
declare function resolveOverridableOption<T>(defaultValue: T, value?: _OverridableOption<T, T>): T;
type _RoutesFolder = string | RoutesFolderOption;
type RoutesFolder = _RoutesFolder[] | _RoutesFolder;
/**
 * unplugin-vue-router plugin options.
 */
interface Options {
    /**
     * Extensions of files to be considered as pages. Cannot be empty. This allows to strip a
     * bigger part of the filename e.g. `index.page.vue` -> `index` if an extension of `.page.vue` is provided.
     * @default `['.vue']`
     */
    extensions?: string[];
    /**
     * Folder(s) to scan for files and generate routes. Can also be an array if you want to add multiple
     * folders, or an object if you want to define a route prefix. Supports glob patterns but must be a folder, use
     * `extensions` and `exclude` to filter files.
     *
     * @default `"src/pages"`
     */
    routesFolder?: RoutesFolder;
    /**
     * Array of `picomatch` globs to ignore. Note the globs are relative to the cwd, so avoid writing
     * something like `['ignored']` to match folders named that way, instead provide a path similar to the `routesFolder`:
     * `['src/pages/ignored/**']` or use `['**​/ignored']` to match every folder named `ignored`.
     * @default `[]`
     */
    exclude?: string[] | string;
    /**
     * Pattern to match files in the `routesFolder`. Defaults to `**‍/*` plus a combination of all the possible extensions,
     * e.g. `**‍/*.{vue,md}` if `extensions` is set to `['.vue', '.md']`.
     * @default `['**‍/*']`
     */
    filePatterns?: string[] | string;
    /**
     * Method to generate the name of a route. It's recommended to keep the default value to guarantee a consistent,
     * unique, and predictable naming.
     */
    getRouteName?: (node: TreeNode) => string;
    /**
     * Allows to extend a route by modifying its node, adding children, or even deleting it. This will be invoked once for
     * each route.
     *
     * @param route - {@link EditableTreeNode} of the route to extend
     */
    extendRoute?: (route: EditableTreeNode) => _Awaitable<void>;
    /**
     * Allows to do some changes before writing the files. This will be invoked **every time** the files need to be written.
     *
     * @param rootRoute - {@link EditableTreeNode} of the root route
     */
    beforeWriteFiles?: (rootRoute: EditableTreeNode) => _Awaitable<void>;
    /**
     * Defines how page components should be imported. Defaults to dynamic imports to enable lazy loading of pages.
     * @default `'async'`
     */
    importMode?: 'sync' | 'async' | ((filepath: string) => 'sync' | 'async');
    /**
     * Root of the project. All paths are resolved relatively to this one.
     * @default `process.cwd()`
     */
    root?: string;
    /**
     * Language for `<route>` blocks in SFC files.
     * @default `'json5'`
     */
    routeBlockLang?: 'yaml' | 'yml' | 'json5' | 'json';
    /**
     * Should we generate d.ts files or ont. Defaults to `true` if `typescript` is installed. Can be set to a string of
     * the filepath to write the d.ts files to. By default it will generate a file named `typed-router.d.ts`.
     * @default `true`
     */
    dts?: boolean | string;
    /**
     * Allows inspection by vite-plugin-inspect by not adding the leading `\0` to the id of virtual modules.
     * @internal
     */
    _inspect?: boolean;
    /**
     * Activates debug logs.
     */
    logs?: boolean;
    /**
     * @inheritDoc ParseSegmentOptions
     */
    pathParser?: ParseSegmentOptions;
    /**
     * Whether to watch the files for changes.
     *
     * Defaults to `true` unless the `CI` environment variable is set.
     *
     * @default `!process.env.CI`
     */
    watch?: boolean;
    /**
     * Experimental options. **Warning**: these can change or be removed at any time, even it patch releases. Keep an eye
     * on the Changelog.
     */
    experimental?: {
        /**
         * (Vite only). File paths or globs where loaders are exported. This will be used to filter out imported loaders and
         * automatically re export them in page components. You can for example set this to `'src/loaders/**\/*'` (without
         * the backslash) to automatically re export any imported variable from files in the `src/loaders` folder within a
         * page component.
         */
        autoExportsDataLoaders?: string | string[];
    };
}
declare const DEFAULT_OPTIONS: {
    extensions: string[];
    exclude: never[];
    routesFolder: string;
    filePatterns: string[];
    routeBlockLang: "json5";
    getRouteName: typeof getFileBasedRouteName;
    importMode: "async";
    root: string;
    dts: boolean;
    logs: false;
    _inspect: false;
    pathParser: {
        dotNesting: true;
    };
    watch: boolean;
    experimental: {};
};
interface ServerContext {
    invalidate: (module: string) => void;
    updateRoutes: () => Promise<void>;
    reload: () => void;
}
/**
 * Normalize user options with defaults and resolved paths.
 *
 * @param options - user provided options
 * @returns normalized options
 */
declare function resolveOptions(options: Options): {
    experimental: {
        autoExportsDataLoaders?: string | string[];
    };
    routesFolder: {
        src: string;
        filePatterns: string[] | ((existing: string[]) => string[]) | undefined;
        exclude: string[] | ((existing: string[]) => string[]) | undefined;
        /**
         * Prefix to add to the route path **as is**. Defaults to `''`. Can also be a function
         * to reuse parts of the filepath, in that case you should return a **modified version of the filepath**.
         *
         * @example
         * ```js
         * {
         *   src: 'src/pages',
         *   // this is equivalent to the default behavior
         *   path: (file) => file.slice(file.lastIndexOf('src/pages') + 'src/pages'.length
         * },
         * {
         *   src: 'src/features',
         *   // match all files (note the \ is not needed in real code)
         *   filePatterns: '*‍/pages/**\/',
         *   path: (file) => {
         *     const prefix = 'src/features'
         *     // remove the everything before src/features and removes /pages
         *     // /src/features/feature1/pages/index.vue -> feature1/index.vue
         *     return file.slice(file.lastIndexOf(prefix) + prefix.length + 1).replace('/pages', '')
         *   },
         * },
         * {
         *   src: 'src/docs',
         *   // adds a prefix with a param
         *   path: 'docs/[lang]/',
         * },
         * ```
         */
        path?: string | ((filepath: string) => string);
        /**
         * Allows to override the global `extensions` option for this folder. It can also extend the global values by passing
         * a function that returns an array.
         */
        extensions?: _OverridableOption<string[]>;
    }[];
    filePatterns: string[];
    exclude: string[];
    extensions: string[];
    getRouteName: typeof getFileBasedRouteName;
    /**
     * Allows to extend a route by modifying its node, adding children, or even deleting it. This will be invoked once for
     * each route.
     *
     * @param route - {@link EditableTreeNode} of the route to extend
     */
    extendRoute?: (route: EditableTreeNode) => _Awaitable<void>;
    /**
     * Allows to do some changes before writing the files. This will be invoked **every time** the files need to be written.
     *
     * @param rootRoute - {@link EditableTreeNode} of the root route
     */
    beforeWriteFiles?: (rootRoute: EditableTreeNode) => _Awaitable<void>;
    importMode: "sync" | "async" | ((filepath: string) => "sync" | "async");
    root: string;
    routeBlockLang: "yaml" | "yml" | "json5" | "json";
    dts: boolean | string;
    _inspect: boolean;
    logs: boolean;
    pathParser: ParseSegmentOptions;
    watch: boolean;
};
type ResolvedOptions = ReturnType<typeof resolveOptions>;
/**
 * Merge all the possible extensions as an array of unique values
 * @param options - user provided options
 * @internal
 */
declare function mergeAllExtensions(options: ResolvedOptions): string[];

export { DEFAULT_OPTIONS as D, EditableTreeNode as E, type Options as O, type ResolvedOptions as R, type ServerContext as S, TreeNode as T, type _OverridableOption as _, getPascalCaseRouteName as a, TreeNodeValueParam as b, createTreeNodeValue as c, TreeNodeValueStatic as d, type RoutesFolderOption as e, type RoutesFolderOptionResolved as f, getFileBasedRouteName as g, type _RoutesFolder as h, type RoutesFolder as i, resolveOptions as j, mergeAllExtensions as m, resolveOverridableOption as r };
