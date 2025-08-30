import { parseINI } from 'confbox';
import { $fetch } from 'ofetch';
import { satisfies } from 'semver';

async function fetchModules() {
  const { modules } = await $fetch(
    `https://api.nuxt.com/modules?version=all`
  );
  return modules;
}
function checkNuxtCompatibility(module, nuxtVersion) {
  if (!module.compatibility?.nuxt) {
    return true;
  }
  return satisfies(nuxtVersion, module.compatibility.nuxt, {
    includePrerelease: true
  });
}
function getRegistryFromContent(content, scope) {
  try {
    const npmConfig = parseINI(content);
    if (scope) {
      const scopeKey = `${scope}:registry`;
      if (npmConfig[scopeKey]) {
        return npmConfig[scopeKey].trim();
      }
    }
    if (npmConfig.registry) {
      return npmConfig.registry.trim();
    }
    return null;
  } catch {
    return null;
  }
}

export { checkNuxtCompatibility as c, fetchModules as f, getRegistryFromContent as g };
