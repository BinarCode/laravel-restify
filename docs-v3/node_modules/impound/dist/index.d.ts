import * as unplugin from 'unplugin';

interface ImpoundMatcherOptions {
    /** An array of patterns of importers to apply the import protection rules to. */
    include?: Array<string | RegExp>;
    /** An array of patterns of importers where the import protection rules explicitly do not apply. */
    exclude?: Array<string | RegExp>;
    /** Whether to throw an error or not. if set to `false`, an error will be logged to console instead. */
    error?: boolean;
    /** An array of patterns to prevent being imported, along with an optional warning to display.  */
    patterns: [importPattern: string | RegExp | ((id: string) => boolean | string), warning?: string][];
}
interface ImpoundSharedOptions {
    cwd?: string;
}
type ImpoundOptions = (ImpoundSharedOptions & ImpoundMatcherOptions) | (ImpoundSharedOptions & {
    matchers: ImpoundMatcherOptions[];
});
declare const ImpoundPlugin: unplugin.UnpluginInstance<ImpoundOptions, boolean>;

export { ImpoundPlugin };
export type { ImpoundMatcherOptions, ImpoundOptions, ImpoundSharedOptions };
