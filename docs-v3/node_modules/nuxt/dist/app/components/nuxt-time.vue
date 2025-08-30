<script setup>
import { computed, getCurrentInstance, onBeforeUnmount, ref } from "vue";
import { onPrehydrate } from "../composables/ssr";
import { useNuxtApp } from "../nuxt";
const props = defineProps({
  locale: { type: String, required: false },
  datetime: { type: [String, Number, Date], required: true },
  localeMatcher: { type: String, required: false },
  weekday: { type: String, required: false },
  era: { type: String, required: false },
  year: { type: String, required: false },
  month: { type: String, required: false },
  day: { type: String, required: false },
  hour: { type: String, required: false },
  minute: { type: String, required: false },
  second: { type: String, required: false },
  timeZoneName: { type: String, required: false },
  formatMatcher: { type: String, required: false },
  hour12: { type: Boolean, required: false, default: void 0 },
  timeZone: { type: String, required: false },
  calendar: { type: String, required: false },
  dayPeriod: { type: String, required: false },
  numberingSystem: { type: String, required: false },
  dateStyle: { type: String, required: false },
  timeStyle: { type: String, required: false },
  hourCycle: { type: String, required: false },
  relative: { type: Boolean, required: false },
  title: { type: [Boolean, String], required: false }
});
const el = getCurrentInstance()?.vnode.el;
const renderedDate = el?.getAttribute("datetime");
const _locale = el?.getAttribute("data-locale");
const nuxtApp = useNuxtApp();
const date = computed(() => {
  const date2 = props.datetime;
  if (renderedDate && nuxtApp.isHydrating) {
    return new Date(renderedDate);
  }
  if (!props.datetime) {
    return /* @__PURE__ */ new Date();
  }
  return new Date(date2);
});
const now = ref(import.meta.client && nuxtApp.isHydrating && window._nuxtTimeNow ? new Date(window._nuxtTimeNow) : /* @__PURE__ */ new Date());
if (import.meta.client && props.relative) {
  const handler = () => {
    now.value = /* @__PURE__ */ new Date();
  };
  const interval = setInterval(handler, 1e3);
  onBeforeUnmount(() => clearInterval(interval));
}
const formatter = computed(() => {
  const { locale: propsLocale, relative, ...rest } = props;
  if (relative) {
    return new Intl.RelativeTimeFormat(_locale ?? propsLocale, rest);
  }
  return new Intl.DateTimeFormat(_locale ?? propsLocale, rest);
});
const formattedDate = computed(() => {
  if (props.relative) {
    const diffInSeconds = (date.value.getTime() - now.value.getTime()) / 1e3;
    const units = [
      { unit: "second", value: diffInSeconds },
      { unit: "minute", value: diffInSeconds / 60 },
      { unit: "hour", value: diffInSeconds / 3600 },
      { unit: "day", value: diffInSeconds / 86400 },
      { unit: "month", value: diffInSeconds / 2592e3 },
      { unit: "year", value: diffInSeconds / 31536e3 }
    ];
    const { unit, value } = units.find(({ value: value2 }) => Math.abs(value2) < 60) || units[units.length - 1];
    return formatter.value.format(Math.round(value), unit);
  }
  return formatter.value.format(date.value);
});
const isoDate = computed(() => date.value.toISOString());
const title = computed(() => props.title === true ? isoDate.value : typeof props.title === "string" ? props.title : void 0);
const dataset = {};
if (import.meta.server) {
  for (const prop in props) {
    if (prop !== "datetime") {
      const value = props?.[prop];
      if (value) {
        const propInKebabCase = prop.split(/(?=[A-Z])/).join("-");
        dataset[`data-${propInKebabCase}`] = props?.[prop];
      }
    }
  }
  onPrehydrate((el2) => {
    const now2 = window._nuxtTimeNow ||= Date.now();
    const toCamelCase = (name, index) => {
      if (index > 0) {
        return name[0].toUpperCase() + name.slice(1);
      }
      return name;
    };
    const date2 = new Date(el2.getAttribute("datetime"));
    const options = {};
    for (const name of el2.getAttributeNames()) {
      if (name.startsWith("data-")) {
        const optionName = name.slice(5).split("-").map(toCamelCase).join("");
        options[optionName] = el2.getAttribute(name);
      }
    }
    if (options.relative) {
      const diffInSeconds = (date2.getTime() - now2) / 1e3;
      const units = [
        { unit: "second", value: diffInSeconds },
        { unit: "minute", value: diffInSeconds / 60 },
        { unit: "hour", value: diffInSeconds / 3600 },
        { unit: "day", value: diffInSeconds / 86400 },
        { unit: "month", value: diffInSeconds / 2592e3 },
        { unit: "year", value: diffInSeconds / 31536e3 }
      ];
      const { unit, value } = units.find(({ value: value2 }) => Math.abs(value2) < 60) || units[units.length - 1];
      const formatter2 = new Intl.RelativeTimeFormat(options.locale, options);
      el2.textContent = formatter2.format(Math.round(value), unit);
    } else {
      const formatter2 = new Intl.DateTimeFormat(options.locale, options);
      el2.textContent = formatter2.format(date2);
    }
  });
}
</script>

<template>
  <time
    v-bind="dataset"
    :datetime="isoDate"
    :title="title"
  >{{ formattedDate }}</time>
</template>
