import { joinRelativeURL } from "ufo";
import { useRuntimeConfig } from "#internal/nitro";
export function baseURL() {
  return useRuntimeConfig().app.baseURL;
}
export function buildAssetsDir() {
  return useRuntimeConfig().app.buildAssetsDir;
}
export function buildAssetsURL(...path) {
  return joinRelativeURL(publicAssetsURL(), buildAssetsDir(), ...path);
}
export function publicAssetsURL(...path) {
  const app = useRuntimeConfig().app;
  const publicBase = app.cdnURL || app.baseURL;
  return path.length ? joinRelativeURL(publicBase, ...path) : publicBase;
}
