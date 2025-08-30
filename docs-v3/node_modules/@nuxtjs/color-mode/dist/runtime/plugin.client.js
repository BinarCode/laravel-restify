import { computed, reactive, watch } from "vue";
import { defineNuxtPlugin, isVue2, isVue3, useRouter, useHead, useState } from "#imports";
import { globalName, storageKey, dataValue, disableTransition, storage } from "#color-mode-options";
const helper = window[globalName] || {};
export default defineNuxtPlugin((nuxtApp) => {
  const colorMode = useState("color-mode", () => reactive({
    // For SPA mode or fallback
    preference: helper.preference,
    value: helper.value,
    unknown: false,
    forced: false
  })).value;
  if (dataValue) {
    if (isVue3) {
      useHead({
        htmlAttrs: { [`data-${dataValue}`]: computed(() => colorMode.value) }
      });
    } else {
      const app = nuxtApp.nuxt2Context.app;
      const originalHead = app.head;
      app.head = function() {
        const head = (typeof originalHead === "function" ? originalHead.call(this) : originalHead) || {};
        head.htmlAttrs = head.htmlAttrs || {};
        head.htmlAttrs[`data-${dataValue}`] = colorMode.value;
        return head;
      };
    }
  }
  useRouter().afterEach((to) => {
    const forcedColorMode = isVue2 ? to.matched[0]?.components.default?.options.colorMode : to.meta.colorMode;
    if (forcedColorMode && forcedColorMode !== "system") {
      colorMode.value = forcedColorMode;
      colorMode.forced = true;
    } else {
      if (forcedColorMode === "system") {
        console.warn("You cannot force the colorMode to system at the page level.");
      }
      colorMode.forced = false;
      colorMode.value = colorMode.preference === "system" ? helper.getColorScheme() : colorMode.preference;
    }
  });
  let darkWatcher;
  function watchMedia() {
    if (darkWatcher || !window.matchMedia) {
      return;
    }
    darkWatcher = window.matchMedia("(prefers-color-scheme: dark)");
    darkWatcher.addEventListener("change", () => {
      if (!colorMode.forced && colorMode.preference === "system") {
        colorMode.value = helper.getColorScheme();
      }
    });
  }
  function setPreferenceToStorage(storageType, preference) {
    switch (storageType) {
      case "cookie":
        window.document.cookie = storageKey + "=" + preference;
        break;
      case "sessionStorage":
        window.sessionStorage?.setItem(storageKey, preference);
        break;
      case "localStorage":
      default:
        window.localStorage?.setItem(storageKey, preference);
    }
  }
  watch(() => colorMode.preference, (preference) => {
    if (colorMode.forced) {
      return;
    }
    if (preference === "system") {
      colorMode.value = helper.getColorScheme();
      watchMedia();
    } else {
      colorMode.value = preference;
    }
    setPreferenceToStorage(storage, preference);
  }, { immediate: true });
  watch(() => colorMode.value, (newValue, oldValue) => {
    let style;
    if (disableTransition) {
      style = window.document.createElement("style");
      style.appendChild(document.createTextNode("*{-webkit-transition:none!important;-moz-transition:none!important;-o-transition:none!important;-ms-transition:none!important;transition:none!important}"));
      window.document.head.appendChild(style);
    }
    helper.removeColorScheme(oldValue);
    helper.addColorScheme(newValue);
    if (disableTransition) {
      const _ = window.getComputedStyle(style).opacity;
      document.head.removeChild(style);
    }
  });
  if (colorMode.preference === "system") {
    watchMedia();
  }
  nuxtApp.hook("app:mounted", () => {
    if (colorMode.unknown) {
      colorMode.preference = helper.preference;
      colorMode.value = helper.value;
      colorMode.unknown = false;
    }
  });
  nuxtApp.provide("colorMode", colorMode);
});
