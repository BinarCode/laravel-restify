import process from 'node:process';
import defu from 'defu';
import { listen } from 'listhen';
import { Server } from 'node:http';
import { getSocketAddress, cleanSocket } from 'get-port-please';
import EventEmitter from 'node:events';
import { watch, existsSync } from 'node:fs';
import { mkdir } from 'node:fs/promises';
import { pathToFileURL } from 'node:url';
import { resolveModulePath } from 'exsolve';
import { toNodeListener } from 'h3';
import { resolve } from 'pathe';
import { debounce } from 'perfect-debounce';
import { provider } from 'std-env';
import { joinURL } from 'ufo';
import { a as clearBuildDir } from '../shared/cli.pLQ0oPGc.mjs';
import { l as loadKit } from '../shared/cli.qKvs7FJ2.mjs';
import { l as loadNuxtManifest, r as resolveNuxtManifest, w as writeNuxtManifest } from '../shared/cli.At9IMXtr.mjs';
import { Youch } from 'youch';

function formatSocketURL(socketPath, ssl = false) {
  const protocol = ssl ? "https" : "http";
  const encodedPath = process.platform === "win32" ? encodeURIComponent(socketPath) : socketPath.replace(/\//g, "%2F");
  return `${protocol}+unix://${encodedPath}`;
}
function isSocketURL(url) {
  return url.startsWith("http+unix://") || url.startsWith("https+unix://");
}
function parseSocketURL(url) {
  if (!isSocketURL(url)) {
    throw new Error(`Invalid socket URL: ${url}`);
  }
  const ssl = url.startsWith("https+unix://");
  const path = url.slice(ssl ? "https+unix://".length : "http+unix://".length);
  const socketPath = decodeURIComponent(path.replace(/%2F/g, "/"));
  return { socketPath, protocol: ssl ? "https" : "http" };
}
async function createSocketListener(handler, proxyAddress) {
  const socketPath = getSocketAddress({
    name: "nuxt-dev",
    random: true
  });
  const server = new Server(handler);
  await cleanSocket(socketPath);
  await new Promise((resolve) => server.listen({ path: socketPath }, resolve));
  const url = formatSocketURL(socketPath);
  return {
    url,
    address: { address: "localhost", port: 3e3, ...proxyAddress, socketPath },
    async close() {
      try {
        server.removeAllListeners();
        await new Promise((resolve, reject) => server.close((err) => err ? reject(err) : resolve()));
      } finally {
        await cleanSocket(socketPath);
      }
    },
    getURLs: async () => [{ url, type: "network" }],
    https: false,
    server
  };
}

async function renderError(req, res, error) {
  const youch = new Youch();
  res.statusCode = 500;
  res.setHeader("Content-Type", "text/html");
  const html = await youch.toHTML(error, {
    request: {
      url: req.url,
      method: req.method,
      headers: req.headers
    }
  });
  res.end(html);
}

const RESTART_RE = /^(?:nuxt\.config\.[a-z0-9]+|\.nuxtignore|\.nuxtrc|\.config\/nuxt(?:\.config)?\.[a-z0-9]+)$/;
class NuxtDevServer extends EventEmitter {
  constructor(options) {
    super();
    this.options = options;
    this.loadDebounced = debounce(this.load);
    let _initResolve;
    const _initPromise = new Promise((resolve2) => {
      _initResolve = resolve2;
    });
    this.once("ready", () => {
      _initResolve();
    });
    this.cwd = options.cwd;
    this.handler = async (req, res) => {
      if (this._loadingError) {
        this._renderError(req, res);
        return;
      }
      await _initPromise;
      if (this._handler) {
        this._handler(req, res);
      } else {
        this._renderLoadingScreen(req, res);
      }
    };
    this.listener = void 0;
  }
  _handler;
  _distWatcher;
  _configWatcher;
  _currentNuxt;
  _loadingMessage;
  _loadingError;
  cwd;
  loadDebounced;
  handler;
  listener;
  _renderError(req, res) {
    renderError(req, res, this._loadingError);
  }
  async _renderLoadingScreen(req, res) {
    res.statusCode = 503;
    res.setHeader("Content-Type", "text/html");
    const loadingTemplate = this.options.loadingTemplate || this._currentNuxt?.options.devServer.loadingTemplate || await resolveLoadingTemplate(this.cwd);
    res.end(
      loadingTemplate({
        loading: this._loadingMessage || "Loading..."
      })
    );
  }
  async init() {
    await this.load();
    this._watchConfig();
  }
  closeWatchers() {
    this._distWatcher?.close();
    this._configWatcher?.();
  }
  async load(reload, reason) {
    try {
      await this._load(reload, reason);
      this._loadingError = void 0;
    } catch (error) {
      console.error(`Cannot ${reload ? "restart" : "start"} nuxt: `, error);
      this._handler = void 0;
      this._loadingError = error;
      this._loadingMessage = "Error while loading Nuxt. Please check console and fix errors.";
      this.emit("loading:error", error);
    }
  }
  async close() {
    if (this._currentNuxt) {
      await this._currentNuxt.close();
    }
  }
  async _load(reload, reason) {
    const action = reload ? "Restarting" : "Starting";
    this._loadingMessage = `${reason ? `${reason}. ` : ""}${action} Nuxt...`;
    this._handler = void 0;
    this.emit("loading", this._loadingMessage);
    if (reload) {
      console.info(this._loadingMessage);
    }
    await this.close();
    const kit = await loadKit(this.options.cwd);
    const devServerDefaults = resolveDevServerDefaults({}, await this.listener.getURLs().then((r) => r.map((r2) => r2.url)));
    this._currentNuxt = await kit.loadNuxt({
      cwd: this.options.cwd,
      dev: true,
      ready: false,
      envName: this.options.envName,
      dotenv: {
        cwd: this.options.cwd,
        fileName: this.options.dotenv.fileName
      },
      defaults: defu(this.options.defaults, devServerDefaults),
      overrides: {
        logLevel: this.options.logLevel,
        ...this.options.overrides,
        vite: {
          clearScreen: this.options.clear,
          ...this.options.overrides.vite
        }
      }
    });
    if (!process.env.NUXI_DISABLE_VITE_HMR) {
      this._currentNuxt.hooks.hook("vite:extend", ({ config }) => {
        if (config.server) {
          config.server.hmr = {
            protocol: void 0,
            ...config.server.hmr,
            port: void 0,
            host: void 0,
            server: this.listener.server
          };
        }
      });
    }
    this._currentNuxt.hooks.hookOnce("close", () => {
      this.listener.server.removeAllListeners("upgrade");
    });
    if (!reload) {
      const previousManifest = await loadNuxtManifest(this._currentNuxt.options.buildDir);
      const newManifest = resolveNuxtManifest(this._currentNuxt);
      const promise = writeNuxtManifest(this._currentNuxt, newManifest);
      this._currentNuxt.hooks.hookOnce("ready", async () => {
        await promise;
      });
      if (previousManifest && newManifest && previousManifest._hash !== newManifest._hash) {
        await clearBuildDir(this._currentNuxt.options.buildDir);
      }
    }
    await this._currentNuxt.ready();
    const unsub = this._currentNuxt.hooks.hook("restart", async (options) => {
      unsub();
      if (options?.hard) {
        this.emit("restart");
        return;
      }
      await this.load(true);
    });
    if (this._currentNuxt.server && "upgrade" in this._currentNuxt.server) {
      this.listener.server.on("upgrade", (req, socket, head) => {
        const nuxt = this._currentNuxt;
        if (!nuxt || !nuxt.server)
          return;
        const viteHmrPath = joinURL(
          nuxt.options.app.baseURL.startsWith("./") ? nuxt.options.app.baseURL.slice(1) : nuxt.options.app.baseURL,
          nuxt.options.app.buildAssetsDir
        );
        if (req.url?.startsWith(viteHmrPath)) {
          return;
        }
        nuxt.server.upgrade(req, socket, head);
      });
    }
    await this._currentNuxt.hooks.callHook("listen", this.listener.server, this.listener);
    const addr = this.listener.address;
    this._currentNuxt.options.devServer.host = addr.address;
    this._currentNuxt.options.devServer.port = addr.port;
    this._currentNuxt.options.devServer.url = getAddressURL(addr, !!this.listener.https);
    this._currentNuxt.options.devServer.https = this.options.devContext.proxy?.https;
    if (this.listener.https && !process.env.NODE_TLS_REJECT_UNAUTHORIZED) {
      console.warn("You might need `NODE_TLS_REJECT_UNAUTHORIZED=0` environment variable to make https work.");
    }
    await Promise.all([
      kit.writeTypes(this._currentNuxt).catch(console.error),
      kit.buildNuxt(this._currentNuxt)
    ]);
    if (!this._currentNuxt.server) {
      throw new Error("Nitro server has not been initialized.");
    }
    const distDir = resolve(this._currentNuxt.options.buildDir, "dist");
    await mkdir(distDir, { recursive: true });
    this._distWatcher = watch(distDir);
    this._distWatcher.on("change", () => {
      this.loadDebounced(true, ".nuxt/dist directory has been removed");
    });
    this._handler = toNodeListener(this._currentNuxt.server.app);
    this.emit("ready", "socketPath" in addr ? formatSocketURL(addr.socketPath, !!this.listener.https) : `http://127.0.0.1:${addr.port}`);
  }
  _watchConfig() {
    this._configWatcher = createConfigWatcher(
      this.cwd,
      this.options.dotenv.fileName,
      () => this.emit("restart"),
      (file) => this.loadDebounced(true, `${file} updated`)
    );
  }
}
function getAddressURL(addr, https) {
  const proto = https ? "https" : "http";
  let host = addr.address.includes(":") ? `[${addr.address}]` : addr.address;
  if (host === "[::]") {
    host = "localhost";
  }
  const port = addr.port || 3e3;
  return `${proto}://${host}:${port}/`;
}
function resolveDevServerOverrides(listenOptions) {
  if (listenOptions.public || provider === "codesandbox") {
    return {
      devServer: { cors: { origin: "*" } },
      vite: { server: { allowedHosts: true } }
    };
  }
  return {};
}
function resolveDevServerDefaults(listenOptions, urls = []) {
  const defaultConfig = {};
  if (urls) {
    defaultConfig.vite = {
      server: {
        allowedHosts: urls.filter((u) => !isSocketURL(u)).map((u) => new URL(u).hostname)
      }
    };
  }
  if (listenOptions.hostname) {
    const protocol = listenOptions.https ? "https" : "http";
    defaultConfig.devServer = { cors: { origin: [`${protocol}://${listenOptions.hostname}`, ...urls] } };
    defaultConfig.vite = defu(defaultConfig.vite, { server: { allowedHosts: [listenOptions.hostname] } });
  }
  return defaultConfig;
}
function createConfigWatcher(cwd, dotenvFileName = ".env", onRestart, onReload) {
  const configWatcher = watch(cwd);
  let configDirWatcher = existsSync(resolve(cwd, ".config")) ? createConfigDirWatcher(cwd, onReload) : void 0;
  const dotenvFileNames = new Set(Array.isArray(dotenvFileName) ? dotenvFileName : [dotenvFileName]);
  configWatcher.on("change", (_event, file) => {
    if (dotenvFileNames.has(file)) {
      onRestart();
    }
    if (RESTART_RE.test(file)) {
      onReload(file);
    }
    if (file === ".config") {
      configDirWatcher ||= createConfigDirWatcher(cwd, onReload);
    }
  });
  return () => {
    configWatcher.close();
    configDirWatcher?.();
  };
}
function createConfigDirWatcher(cwd, onReload) {
  const configDir = resolve(cwd, ".config");
  const configDirWatcher = watch(configDir);
  configDirWatcher.on("change", (_event, file) => {
    if (RESTART_RE.test(file)) {
      onReload(file);
    }
  });
  return () => configDirWatcher.close();
}
async function resolveLoadingTemplate(cwd) {
  const nuxtPath = resolveModulePath("nuxt", { from: cwd, try: true });
  const uiTemplatesPath = resolveModulePath("@nuxt/ui-templates", { from: nuxtPath || cwd });
  const r = await import(pathToFileURL(uiTemplatesPath).href);
  return r.loading || ((params) => `<h2>${params.loading}</h2>`);
}

const start = Date.now();
process.env.NODE_ENV = "development";
class IPC {
  enabled = !!process.send && !process.title?.includes("vitest") && process.env.__NUXT__FORK;
  constructor() {
    if (this.enabled) {
      process.once("unhandledRejection", (reason) => {
        this.send({ type: "nuxt:internal:dev:rejection", message: reason instanceof Error ? reason.toString() : "Unhandled Rejection" });
        process.exit();
      });
    }
    process.on("message", (message) => {
      if (message.type === "nuxt:internal:dev:context") {
        initialize(message.context, {}, message.socket ? void 0 : true);
      }
    });
    this.send({ type: "nuxt:internal:dev:fork-ready" });
  }
  send(message) {
    if (this.enabled) {
      process.send?.(message);
    }
  }
}
const ipc = new IPC();
async function initialize(devContext, ctx = {}, _listenOptions) {
  const devServerOverrides = resolveDevServerOverrides({
    public: devContext.public
  });
  const devServerDefaults = resolveDevServerDefaults({
    hostname: devContext.hostname,
    https: devContext.proxy?.https
  }, devContext.publicURLs);
  const devServer = new NuxtDevServer({
    cwd: devContext.cwd,
    overrides: defu(
      ctx.data?.overrides,
      { extends: devContext.args.extends },
      devServerOverrides
    ),
    defaults: devServerDefaults,
    logLevel: devContext.args.logLevel,
    clear: !!devContext.args.clear,
    dotenv: { cwd: devContext.cwd, fileName: devContext.args.dotenv },
    envName: devContext.args.envName,
    devContext: {
      proxy: devContext.proxy
    }
  });
  const listenOptions = _listenOptions === true || process.env._PORT ? { port: process.env._PORT ?? 0, hostname: "127.0.0.1", showURL: false } : _listenOptions;
  devServer.listener = listenOptions ? await listen(devServer.handler, listenOptions) : await createSocketListener(devServer.handler, devContext.proxy?.addr);
  if (process.env.DEBUG) {
    console.debug(`Using ${listenOptions ? "network" : "socket"} listener for Nuxt dev server.`);
  }
  devServer.listener._url = devServer.listener.url;
  if (devContext.proxy?.url) {
    devServer.listener.url = devContext.proxy.url;
  }
  if (devContext.proxy?.urls) {
    const _getURLs = devServer.listener.getURLs.bind(devServer.listener);
    devServer.listener.getURLs = async () => Array.from(/* @__PURE__ */ new Set([...devContext.proxy?.urls || [], ...await _getURLs()]));
  }
  let address;
  if (ipc.enabled) {
    devServer.on("loading:error", (_error) => {
      ipc.send({
        type: "nuxt:internal:dev:loading:error",
        error: {
          message: _error.message,
          stack: _error.stack,
          name: _error.name,
          code: "code" in _error ? _error.code : void 0
        }
      });
    });
    devServer.on("loading", (message) => {
      ipc.send({ type: "nuxt:internal:dev:loading", message });
    });
    devServer.on("restart", () => {
      ipc.send({ type: "nuxt:internal:dev:restart" });
    });
    devServer.on("ready", (payload) => {
      ipc.send({ type: "nuxt:internal:dev:ready", address: payload });
    });
  } else {
    devServer.on("ready", (payload) => {
      address = payload;
    });
  }
  await devServer.init();
  if (process.env.DEBUG) {
    console.debug(`Dev server (internal) initialized in ${Date.now() - start}ms`);
  }
  return {
    listener: devServer.listener,
    close: async () => {
      devServer.closeWatchers();
      await devServer.close();
    },
    onReady: (callback) => {
      if (address) {
        callback(address);
      } else {
        devServer.once("ready", (payload) => callback(payload));
      }
    },
    onRestart: (callback) => {
      let restarted = false;
      function restart() {
        if (!restarted) {
          restarted = true;
          callback(devServer);
        }
      }
      devServer.once("restart", restart);
      process.once("uncaughtException", restart);
      process.once("unhandledRejection", restart);
    }
  };
}

const index = {
  __proto__: null,
  initialize: initialize
};

export { renderError as a, isSocketURL as b, index as c, initialize as i, parseSocketURL as p, resolveLoadingTemplate as r };
