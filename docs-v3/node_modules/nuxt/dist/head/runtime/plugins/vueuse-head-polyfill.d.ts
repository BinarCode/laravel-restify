import type { UseHeadInput, UseHeadOptions, VueHeadClient } from '@unhead/vue';
export type VueHeadClientPollyFill = VueHeadClient & {
    /**
     * @deprecated use `resolveTags`
     */
    headTags: VueHeadClient['resolveTags'];
    /**
     * @deprecated use `push`
     */
    addEntry: VueHeadClient['push'];
    /**
     * @deprecated use `push`
     */
    addHeadObjs: VueHeadClient['push'];
    /**
     * @deprecated use `useHead`
     */
    addReactiveEntry: (input: UseHeadInput, options?: UseHeadOptions) => (() => void);
    /**
     * @deprecated Use useHead API.
     */
    removeHeadObjs: () => void;
    /**
     * @deprecated Call hook `entries:resolve` or update an entry
     */
    updateDOM: () => void;
    /**
     * @deprecated Access unhead properties directly.
     */
    unhead: VueHeadClient;
};
declare const _default: any;
export default _default;
