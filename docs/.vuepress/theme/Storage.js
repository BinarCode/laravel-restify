const storagePrefix = function(base) {
  let p = base
    .replace(/^\//, "")
    .replace(/\/$/, "")
    .replace(/\//g, ".");
  return p ? p + "." : "";
};

const setStorage = function(name, value, base) {
  if (typeof localStorage === "undefined") {
    return;
  }
  localStorage[storagePrefix(base) + name] = value;
};

const getStorage = function(name, base) {
  if (typeof localStorage === "undefined") {
    return;
  }
  name = storagePrefix(base) + name;
  if (typeof localStorage[name] === "undefined") {
    return;
  }
  return localStorage[name];
};

const unsetStorage = function(name, base) {
  if (typeof localStorage === "undefined") {
    return;
  }
  name = storagePrefix(base) + name;
  if (typeof localStorage[name] === "undefined") {
    return;
  }
  delete localStorage[name];
};

export { storagePrefix, getStorage, setStorage, unsetStorage };
