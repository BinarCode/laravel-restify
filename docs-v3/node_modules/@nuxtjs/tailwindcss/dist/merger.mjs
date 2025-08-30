import { createDefu } from 'defu';
import { klona } from 'klona';

const isJSObject = (value) => typeof value === "object" && !Array.isArray(value);
const configMerger = (base, ...defaults) => {
  if (!base) {
    return klona(defaults[0]);
  }
  return createDefu((obj, key, value) => {
    if (key === "content") {
      if (isJSObject(obj[key]) && Array.isArray(value)) {
        obj[key] = { ...obj[key], files: [...obj[key]["files"] || [], ...value] };
        return true;
      } else if (Array.isArray(obj[key]) && isJSObject(value)) {
        obj[key] = { ...value, files: [...obj[key], ...value.files || []] };
        return true;
      }
      if (obj[key] && typeof value === "function") {
        obj[key] = value(Array.isArray(obj[key]) ? obj[key] : obj[key]["files"]);
        return true;
      }
      if (typeof obj[key] === "function" && value) {
        obj[key] = obj[key](Array.isArray(value) ? value : value["files"]);
        return true;
      }
    }
  })(klona(base), ...defaults.map(klona));
};

export { configMerger as default };
