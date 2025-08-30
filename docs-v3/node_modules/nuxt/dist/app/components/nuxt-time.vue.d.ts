type __VLS_Props = {
    locale?: string;
    datetime: string | number | Date;
    localeMatcher?: 'best fit' | 'lookup';
    weekday?: 'long' | 'short' | 'narrow';
    era?: 'long' | 'short' | 'narrow';
    year?: 'numeric' | '2-digit';
    month?: 'numeric' | '2-digit' | 'long' | 'short' | 'narrow';
    day?: 'numeric' | '2-digit';
    hour?: 'numeric' | '2-digit';
    minute?: 'numeric' | '2-digit';
    second?: 'numeric' | '2-digit';
    timeZoneName?: 'short' | 'long' | 'shortOffset' | 'longOffset' | 'shortGeneric' | 'longGeneric';
    formatMatcher?: 'best fit' | 'basic';
    hour12?: boolean;
    timeZone?: string;
    calendar?: string;
    dayPeriod?: 'narrow' | 'short' | 'long';
    numberingSystem?: string;
    dateStyle?: 'full' | 'long' | 'medium' | 'short';
    timeStyle?: 'full' | 'long' | 'medium' | 'short';
    hourCycle?: 'h11' | 'h12' | 'h23' | 'h24';
    relative?: boolean;
    title?: boolean | string;
};
declare global {
    interface Window {
        _nuxtTimeNow?: number;
    }
}
declare const _default: import("vue").DefineComponent<__VLS_Props, {}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<__VLS_Props> & Readonly<{}>, {
    hour12: boolean;
}, {}, {}, {}, string, import("vue").ComponentProvideOptions, false, {}, any>;
export default _default;
