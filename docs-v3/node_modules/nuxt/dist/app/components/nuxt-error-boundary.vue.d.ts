type __VLS_Slots = {
    error(props: {
        error: Error;
        clearError: () => void;
    }): any;
    default(): any;
};
declare function clearError(): void;
declare const __VLS_component: import("vue").DefineComponent<{}, {
    error: import("vue").ShallowRef<Error | null, Error | null>;
    clearError: typeof clearError;
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {
    error: (error: Error) => any;
}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{
    onError?: ((error: Error) => any) | undefined;
}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & {
    new (): {
        $slots: S;
    };
};
