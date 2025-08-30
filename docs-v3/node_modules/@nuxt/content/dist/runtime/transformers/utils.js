export const defineTransformer = (transformer) => {
  return transformer;
};
export const createSingleton = (fn) => {
  let instance;
  return (...args) => {
    if (!instance) {
      instance = fn(...args);
    }
    return instance;
  };
};
