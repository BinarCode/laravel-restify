import * as net from 'net';
import * as listhen from 'listhen';
import { ListenURL, HTTPSOptions, Listener, ListenOptions } from 'listhen';
import { NuxtConfig } from '@nuxt/schema';
import { DotenvOptions } from 'c12';
import { RequestListener, IncomingMessage, ServerResponse } from 'node:http';
import { AddressInfo } from 'node:net';
import EventEmitter from 'node:events';

interface NuxtDevContext {
    cwd: string;
    public?: boolean;
    hostname?: string;
    publicURLs?: string[];
    args: {
        clear: boolean;
        logLevel: string;
        dotenv: string;
        envName: string;
        extends?: string;
    };
    proxy?: {
        url?: string;
        urls?: ListenURL[];
        https?: boolean | HTTPSOptions;
        addr?: AddressInfo;
    };
}
interface NuxtDevServerOptions {
    cwd: string;
    logLevel?: 'silent' | 'info' | 'verbose';
    dotenv: DotenvOptions;
    envName?: string;
    clear?: boolean;
    defaults: NuxtConfig;
    overrides: NuxtConfig;
    loadingTemplate?: ({ loading }: {
        loading: string;
    }) => string;
    devContext: Pick<NuxtDevContext, 'proxy'>;
}
interface DevServerEventMap {
    'loading:error': [error: Error];
    'loading': [loadingMessage: string];
    'ready': [address: string];
    'restart': [];
}
declare class NuxtDevServer extends EventEmitter<DevServerEventMap> {
    private options;
    private _handler?;
    private _distWatcher?;
    private _configWatcher?;
    private _currentNuxt?;
    private _loadingMessage?;
    private _loadingError?;
    private cwd;
    loadDebounced: (reload?: boolean, reason?: string) => void;
    handler: RequestListener;
    listener: Pick<Listener, 'server' | 'getURLs' | 'https' | 'url' | 'close'> & {
        _url?: string;
        address: Omit<AddressInfo, 'family'> & {
            socketPath: string;
        } | AddressInfo;
    };
    constructor(options: NuxtDevServerOptions);
    _renderError(req: IncomingMessage, res: ServerResponse): void;
    _renderLoadingScreen(req: IncomingMessage, res: ServerResponse): Promise<void>;
    init(): Promise<void>;
    closeWatchers(): void;
    load(reload?: boolean, reason?: string): Promise<void>;
    close(): Promise<void>;
    _load(reload?: boolean, reason?: string): Promise<void>;
    _watchConfig(): void;
}

interface InitializeOptions {
    data?: {
        overrides?: NuxtConfig;
    };
}
declare function initialize(devContext: NuxtDevContext, ctx?: InitializeOptions, _listenOptions?: true | Partial<ListenOptions>): Promise<{
    listener: Pick<listhen.Listener, "https" | "server" | "url" | "getURLs" | "close"> & {
        _url?: string;
        address: (Omit<net.AddressInfo, "family"> & {
            socketPath: string;
        }) | net.AddressInfo;
    };
    close: () => Promise<void>;
    onReady: (callback: (address: string) => void) => void;
    onRestart: (callback: (devServer: NuxtDevServer) => void) => void;
}>;

export { initialize };
