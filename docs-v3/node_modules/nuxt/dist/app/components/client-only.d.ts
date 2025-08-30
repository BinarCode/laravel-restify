import type { ComponentOptions, InjectionKey } from 'vue';
export declare const clientOnlySymbol: InjectionKey<boolean>;
declare const _default: import("vue").DefineComponent<{
    fallback?: any;
    placeholder?: any;
    placeholderTag?: any;
    fallbackTag?: any;
}, () => import("vue").VNode<import("vue").RendererNode, import("vue").RendererElement, {
    [key: string]: any;
}> | import("vue").VNode<import("vue").RendererNode, import("vue").RendererElement, {
    [key: string]: any;
}>[] | undefined, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{
    fallback?: any;
    placeholder?: any;
    placeholderTag?: any;
    fallbackTag?: any;
}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
export default _default;
export declare function createClientOnly<T extends ComponentOptions>(component: T): any;
