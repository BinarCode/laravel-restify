import { useNuxtApp } from "../nuxt.js";
export async function callOnce(...args) {
  const autoKey = typeof args[args.length - 1] === "string" ? args.pop() : void 0;
  if (typeof args[0] !== "string") {
    args.unshift(autoKey);
  }
  const [_key, fn, options] = args;
  if (!_key || typeof _key !== "string") {
    throw new TypeError("[nuxt] [callOnce] key must be a string: " + _key);
  }
  if (fn !== void 0 && typeof fn !== "function") {
    throw new Error("[nuxt] [callOnce] fn must be a function: " + fn);
  }
  const nuxtApp = useNuxtApp();
  if (options?.mode === "navigation") {
    nuxtApp.hooks.hookOnce("page:start", () => {
      nuxtApp.payload.once.delete(_key);
    });
  }
  if (nuxtApp.payload.once.has(_key)) {
    return;
  }
  nuxtApp._once ||= {};
  nuxtApp._once[_key] ||= fn() || true;
  await nuxtApp._once[_key];
  nuxtApp.payload.once.add(_key);
  delete nuxtApp._once[_key];
}
