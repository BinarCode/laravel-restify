// src/data-loaders/createDataLoader.ts
var toLazyValue = (lazy, to, from) => typeof lazy === "function" ? lazy(to, from) : lazy;

export {
  toLazyValue
};
