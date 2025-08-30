import { createError as createH3Error } from "h3";
import { toRef } from "vue";
import { useNuxtApp } from "../nuxt.js";
import { useRouter } from "./router.js";
import { nuxtDefaultErrorValue } from "#build/nuxt.config.mjs";
export const NUXT_ERROR_SIGNATURE = "__nuxt_error";
export const useError = () => toRef(useNuxtApp().payload, "error");
export const showError = (error) => {
  const nuxtError = createError(error);
  try {
    const nuxtApp = useNuxtApp();
    const error2 = useError();
    if (import.meta.client) {
      nuxtApp.hooks.callHook("app:error", nuxtError);
    }
    error2.value ||= nuxtError;
  } catch {
    throw nuxtError;
  }
  return nuxtError;
};
export const clearError = async (options = {}) => {
  const nuxtApp = useNuxtApp();
  const error = useError();
  nuxtApp.callHook("app:error:cleared", options);
  if (options.redirect) {
    await useRouter().replace(options.redirect);
  }
  error.value = nuxtDefaultErrorValue;
};
export const isNuxtError = (error) => !!error && typeof error === "object" && NUXT_ERROR_SIGNATURE in error;
export const createError = (error) => {
  const nuxtError = createH3Error(error);
  Object.defineProperty(nuxtError, NUXT_ERROR_SIGNATURE, {
    value: true,
    configurable: false,
    writable: false
  });
  return nuxtError;
};
