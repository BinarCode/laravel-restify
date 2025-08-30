const defaultOptions = {
  /**
   * API endpoint for fetching package versions
   *
   * @default 'https://npm.antfu.dev/'
   */
  apiEndpoint: "https://npm.antfu.dev/"
};
async function getLatestVersionBatch(packages, options = {}) {
  const {
    apiEndpoint = defaultOptions.apiEndpoint,
    fetch: fetchApi = fetch,
    throw: throwError = true
  } = options;
  let query = [
    options.force ? "force=true" : "",
    options.metadata ? "metadata=true" : "",
    throwError ? "" : "throw=false"
  ].filter(Boolean).join("&");
  if (query)
    query = `?${query}`;
  const data = await fetchApi(new URL(packages.join("+") + query, apiEndpoint)).then((r) => r.json());
  const list = toArray(data);
  return throwError ? throwErrorObject(list) : list;
}
async function getLatestVersion(name, options = {}) {
  const [data] = await getLatestVersionBatch([name], options);
  return data;
}
async function getVersionsBatch(packages, options = {}) {
  const {
    apiEndpoint = defaultOptions.apiEndpoint,
    fetch: fetchApi = fetch,
    throw: throwError = true
  } = options;
  let query = [
    options.force ? "force=true" : "",
    options.loose ? "loose=true" : "",
    options.metadata ? "metadata=true" : "",
    options.after ? `after=${encodeURIComponent(options.after)}` : "",
    throwError ? "" : "throw=false"
  ].filter(Boolean).join("&");
  if (query)
    query = `?${query}`;
  const data = await fetchApi(new URL(`/versions/${packages.join("+")}${query}`, apiEndpoint)).then((r) => r.json());
  const list = toArray(data);
  return throwError ? throwErrorObject(list) : list;
}
async function getVersions(name, options = {}) {
  const [data] = await getVersionsBatch([name], options);
  return data;
}
function throwErrorObject(data) {
  for (const item of toArray(data)) {
    if (item && "error" in item)
      throw new Error(item.message || item.error);
  }
  return data;
}
function toArray(data) {
  if (Array.isArray(data))
    return data;
  return [data];
}

const NPM_REGISTRY = "https://registry.npmjs.org/";
function pickRegistry(scope, npmConfigs, defaultRegistry = NPM_REGISTRY) {
  let registry = scope ? npmConfigs[`${scope.replace(/^@?/, "@")}:registry`] : void 0;
  if (!registry && typeof npmConfigs.scope === "string") {
    registry = npmConfigs[`${npmConfigs.scope.replace(/^@?/, "@")}:registry`];
  }
  if (!registry) {
    registry = npmConfigs.registry || defaultRegistry;
  }
  return registry;
}

export { NPM_REGISTRY, defaultOptions, getLatestVersion, getLatestVersionBatch, getVersions, getVersionsBatch, pickRegistry };
