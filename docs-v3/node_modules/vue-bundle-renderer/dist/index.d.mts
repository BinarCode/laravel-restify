import { Manifest } from 'vite';
import { M as Manifest$1 } from './shared/vue-bundle-renderer.DITmAFNH.mjs';
export { R as ResourceMeta, d as defineManifest } from './shared/vue-bundle-renderer.DITmAFNH.mjs';

declare function normalizeViteManifest(manifest: Manifest | Manifest$1): Manifest$1;

type Identifier = string;
type OutputPath = string;
interface WebpackClientManifest {
    publicPath: string;
    all: Array<OutputPath>;
    initial: Array<OutputPath>;
    async: Array<OutputPath>;
    modules: Record<Identifier, Array<number>>;
    hasNoCssVersion?: {
        [file: string]: boolean;
    };
}
declare function normalizeWebpackManifest(manifest: WebpackClientManifest): Manifest$1;

export { Manifest$1 as Manifest, normalizeViteManifest, normalizeWebpackManifest };
