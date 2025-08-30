import { H as HookInfo, P as PluginMetric, L as LoadingTimeMetric, a as ServerFunctions, C as ClientFunctions } from './shared/devtools-kit.BMivX6Xf.cjs';
export { A as AnalyzeBuildMeta, b as AnalyzeBuildsInfo, W as AssetEntry, U as AssetInfo, Q as AssetType, q as AutoImportsWithMetadata, B as BasicModuleInfo, l as CategorizedTabs, a6 as ClientUpdateEvent, _ as CodeServerOptions, $ as CodeServerType, X as CodeSnippet, F as CompatibilityStatus, Y as ComponentRelationship, Z as ComponentWithRelationships, ag as GetWizardArgs, K as GitHubContributor, I as ImageMeta, a8 as InstallModuleReturn, y as InstalledModuleInfo, J as MaintainerInfo, j as ModuleBuiltinTab, D as ModuleCompatibility, M as ModuleCustomTab, a1 as ModuleGlobalOptions, i as ModuleIframeTabLazyOptions, e as ModuleIframeView, g as ModuleLaunchAction, d as ModuleLaunchView, a0 as ModuleOptions, z as ModuleStaticInfo, E as ModuleStats, k as ModuleTabInfo, G as ModuleType, f as ModuleVNodeView, h as ModuleView, p as NpmCommandOptions, o as NpmCommandType, a4 as NuxtDevToolsOptions, N as NuxtDevtoolsInfo, a7 as NuxtDevtoolsServerContext, a5 as NuxtServerData, n as PackageManagerName, m as PackageUpdateInfo, u as Payload, x as PluginInfoWithMetic, R as RouteInfo, w as ScannedNitroTasks, aa as ServerDebugContext, a9 as ServerDebugModuleMutationRecord, r as ServerRouteInfo, t as ServerRouteInput, s as ServerRouteInputType, v as ServerTaskInfo, S as SubprocessOptions, c as TabCategory, ac as TerminalAction, ab as TerminalBase, ad as TerminalInfo, T as TerminalState, a2 as VSCodeIntegrationOptions, a3 as VSCodeTunnelOptions, V as VueInspectorClient, O as VueInspectorData, af as WizardActions, ae as WizardFunctions } from './shared/devtools-kit.BMivX6Xf.cjs';
import { BirpcReturn } from 'birpc';
import { Hookable } from 'hookable';
import { NuxtApp } from 'nuxt/app';
import { AppConfig } from 'nuxt/schema';
import { $Fetch } from 'ofetch';
import { BuiltinLanguage } from 'shiki';
import { Ref } from 'vue';
import { StackFrame } from 'error-stack-parser-es';
import 'unimport';
import 'vue-router';
import 'nitropack';
import 'unstorage';
import 'vite';
import '@nuxt/schema';
import 'execa';

interface TimelineEventFunction {
    type: 'function';
    start: number;
    end?: number;
    name: string;
    args?: any[];
    result?: any;
    stacktrace?: StackFrame[];
    isPromise?: boolean;
}
interface TimelineServerState {
    timeSsrStart?: number;
}
interface TimelineEventRoute {
    type: 'route';
    start: number;
    end?: number;
    from: string;
    to: string;
}
interface TimelineOptions {
    enabled: boolean;
    stacktrace: boolean;
    arguments: boolean;
}
type TimelineEvent = TimelineEventFunction | TimelineEventRoute;
interface TimelineMetrics {
    events: TimelineEvent[];
    nonLiteralSymbol: symbol;
    options: TimelineOptions;
}
interface TimelineEventNormalized<T> {
    event: T;
    segment: TimelineEventsSegment;
    relativeStart: number;
    relativeWidth: number;
    layer: number;
}
interface TimelineEventsSegment {
    start: number;
    end: number;
    events: TimelineEvent[];
    functions: TimelineEventNormalized<TimelineEventFunction>[];
    route?: TimelineEventNormalized<TimelineEventRoute>;
    duration: number;
    previousGap?: number;
}

interface DevToolsFrameState {
    width: number;
    height: number;
    top: number;
    left: number;
    open: boolean;
    route: string;
    position: 'left' | 'right' | 'bottom' | 'top';
    closeOnOutsideClick: boolean;
    minimizePanelInactive: number;
}
interface NuxtDevtoolsClientHooks {
    /**
     * When the DevTools navigates, used for persisting the current tab
     */
    'devtools:navigate': (path: string) => void;
    /**
     * Event emitted when the component inspector is clicked
     */
    'host:inspector:click': (path: string) => void;
    /**
     * Event to close the component inspector
     */
    'host:inspector:close': () => void;
    /**
     * Triggers reactivity manually, since Vue won't be reactive across frames)
     */
    'host:update:reactivity': () => void;
    /**
     * Host action to control the DevTools navigation
     */
    'host:action:navigate': (path: string) => void;
    /**
     * Host action to reload the DevTools
     */
    'host:action:reload': () => void;
}
/**
 * Host client from the App
 */
interface NuxtDevtoolsHostClient {
    nuxt: NuxtApp;
    hooks: Hookable<NuxtDevtoolsClientHooks>;
    getIframe: () => HTMLIFrameElement | undefined;
    inspector?: {
        enable: () => void;
        disable: () => void;
        toggle: () => void;
        isEnabled: Ref<boolean>;
        isAvailable: Ref<boolean>;
    };
    devtools: {
        close: () => void;
        open: () => void;
        toggle: () => void;
        reload: () => void;
        navigate: (path: string) => void;
        /**
         * Popup the DevTools frame into Picture-in-Picture mode
         *
         * Requires Chrome 111 with experimental flag enabled.
         *
         * Function is undefined when not supported.
         *
         * @see https://developer.chrome.com/docs/web-platform/document-picture-in-picture/
         */
        popup?: () => any;
    };
    app: {
        reload: () => void;
        navigate: (path: string, hard?: boolean) => void;
        appConfig: AppConfig;
        colorMode: Ref<'dark' | 'light'>;
        frameState: Ref<DevToolsFrameState>;
        $fetch: $Fetch;
    };
    metrics: {
        clientHooks: () => HookInfo[];
        clientPlugins: () => PluginMetric[] | undefined;
        clientTimeline: () => TimelineMetrics | undefined;
        loading: () => LoadingTimeMetric;
    };
    /**
     * A counter to trigger reactivity updates
     */
    revision: Ref<number>;
    /**
     * Update client
     * @internal
     */
    syncClient: () => NuxtDevtoolsHostClient;
}
interface CodeHighlightOptions {
    grammarContextCode?: string;
}
interface NuxtDevtoolsClient {
    rpc: BirpcReturn<ServerFunctions, ClientFunctions>;
    renderCodeHighlight: (code: string, lang?: BuiltinLanguage, options?: CodeHighlightOptions) => {
        code: string;
        supported: boolean;
    };
    renderMarkdown: (markdown: string) => string;
    colorMode: string;
    extendClientRpc: <ServerFunctions = Record<string, never>, ClientFunctions = Record<string, never>>(name: string, functions: ClientFunctions) => BirpcReturn<ServerFunctions, ClientFunctions>;
}
interface NuxtDevtoolsIframeClient {
    host: NuxtDevtoolsHostClient;
    devtools: NuxtDevtoolsClient;
}
interface NuxtDevtoolsGlobal {
    setClient: (client: NuxtDevtoolsHostClient) => void;
}

export { ClientFunctions, HookInfo, LoadingTimeMetric, PluginMetric, ServerFunctions };
export type { CodeHighlightOptions, DevToolsFrameState, NuxtDevtoolsClient, NuxtDevtoolsClientHooks, NuxtDevtoolsGlobal, NuxtDevtoolsHostClient, NuxtDevtoolsIframeClient, TimelineEvent, TimelineEventFunction, TimelineEventNormalized, TimelineEventRoute, TimelineEventsSegment, TimelineMetrics, TimelineOptions, TimelineServerState };
