export const hashRE = /#.*$/;
export const extRE = /\.(md|html)$/;
export const endingSlashRE = /\/$/;
export const outboundRE = /^[a-z]+:/i;

export function normalize(path) {
  return decodeURI(path)
    .replace(hashRE, "")
    .replace(extRE, "");
}

export function getHash(path) {
  const match = path.match(hashRE);
  if (match) {
    return match[0];
  }
}

export function isExternal(path) {
  return outboundRE.test(path);
}

export function isMailto(path) {
  return /^mailto:/.test(path);
}

export function isTel(path) {
  return /^tel:/.test(path);
}

export function ensureExt(path) {
  if (isExternal(path)) {
    return path;
  }
  const hashMatch = path.match(hashRE);
  const hash = hashMatch ? hashMatch[0] : "";
  const normalized = normalize(path);

  if (endingSlashRE.test(normalized)) {
    return path;
  }
  return normalized + ".html" + hash;
}

export function isActive(route, path) {
  const routeHash = decodeURIComponent(route.hash);
  const linkHash = getHash(path);
  if (linkHash && routeHash !== linkHash) {
    return false;
  }
  const routePath = normalize(route.path);
  const pagePath = normalize(path);
  return routePath === pagePath;
}

export function resolvePage(pages, rawPath, base) {
  if (isExternal(rawPath)) {
    return {
      type: "external",
      path: rawPath
    };
  }
  if (base) {
    rawPath = resolvePath(rawPath, base);
  }
  const path = normalize(rawPath);
  for (let i = 0; i < pages.length; i++) {
    if (normalize(pages[i].regularPath) === path) {
      const resolved = Object.assign({}, pages[i], {
        type: "page",
        path: ensureExt(pages[i].path)
      });
      return resolved;
    }
  }
  console.error(
    `[vuepress] No matching page found for sidebar item "${rawPath}"`
  );
  return {};
}

function resolvePath(relative, base, append) {
  const firstChar = relative.charAt(0);
  if (firstChar === "/") {
    return relative;
  }

  if (firstChar === "?" || firstChar === "#") {
    return base + relative;
  }

  const stack = base.split("/");

  // remove trailing segment if:
  // - not appending
  // - appending to trailing slash (last segment is empty)
  if (!append || !stack[stack.length - 1]) {
    stack.pop();
  }

  // resolve relative path
  const segments = relative.replace(/^\//, "").split("/");
  for (let i = 0; i < segments.length; i++) {
    const segment = segments[i];
    if (segment === "..") {
      stack.pop();
    } else if (segment !== ".") {
      stack.push(segment);
    }
  }

  // ensure leading slash
  if (stack[0] !== "") {
    stack.unshift("");
  }

  return stack.join("/");
}

/**
 * @param { Page } page
 * @param { string } regularPath
 * @param { SiteData } site
 * @param { string } localePath
 * @returns { SidebarGroup }
 */
export function resolveSidebarItems(
  page,
  regularPath,
  site,
  localePath,
  activeSet,
  activeVersion,
  localeConfig
) {
  const { pages, themeConfig } = site;

  // get the config object for whatever sidebar items we should be showing
  const sidebarConfig = resolveSidebarConfig(
    site,
    page,
    activeSet,
    activeVersion,
    localeConfig,
    themeConfig
  );

  if (!sidebarConfig) {
    return [];
  } else {
    // get the correct sidebar, whether the config is an array or path-indexed object
    let { base, config } = resolveMatchingConfig(
      regularPath,
      sidebarConfig,
      activeSet,
      activeVersion
    );

    if (!config) {
      console.log("didn’t resolve config");
      return [];
    }

    const resolved = config.map(item => {
      return resolveItem(item, pages, base);
    });

    return resolved;
  }
}

/**
 * @param { Page } page
 * @param { string } regularPath
 * @param { SiteData } site
 * @param { string } localePath
 * @returns { SidebarGroup }
 */
export function resolveExtraSidebarItems(
  page,
  regularPath,
  site,
  localePath,
  activeSet,
  activeVersion,
  localeConfig
) {
  const { pages, themeConfig } = site;

  // get the config object for whatever sidebar items we should be showing
  const sidebarConfig = resolveExtraSidebarConfig(
    site,
    page,
    activeSet,
    activeVersion,
    localeConfig,
    themeConfig
  );

  if (!sidebarConfig) {
    return [];
  } else {
    // get the correct sidebar, whether the config is an array or path-indexed object
    let { base, config } = resolveMatchingConfig(
      regularPath,
      sidebarConfig,
      activeSet,
      activeVersion
    );

    if (!config) {
      console.log("didn’t resolve config");
      return [];
    }

    const resolved = config.map(item => {
      return resolveExtraItem(item, pages, base);
    });

    return resolved;
  }
}

export function resolveSidebarConfig(
  site,
  page,
  activeSet,
  activeVersion,
  localeConfig,
  themeConfig
) {
  // no set, no sidebar items (just list sets)
  if (!activeSet) {
    //console.log("no sidebar set available");
    return [];
  }

  // get the active set locale config if it exists, otherwise the set config
  const appliedConfig = activeSet.locales ? localeConfig.config : activeSet;
  let sidebarConfig;

  if (page.frontmatter.sidebar) {
    sidebarConfig = page.frontmatter.sidebar;
  } else if (appliedConfig.sidebar) {
    sidebarConfig = appliedConfig.sidebar;
  } else if (themeConfig.sidebar) {
    sidebarConfig = themeConfig.sidebar;
  }

  if (activeVersion) {
    sidebarConfig = sidebarConfig[activeVersion];
  }

  return sidebarConfig;
}

export function resolveExtraSidebarConfig(
  site,
  page,
  activeSet,
  activeVersion,
  localeConfig,
  themeConfig
) {
  // no set, no sidebar items (just list sets)
  if (!activeSet) {
    //console.log("no sidebar set available");
    return [];
  }

  // get the active set locale config if it exists, otherwise the set config
  const appliedConfig = activeSet.locales ? localeConfig.config : activeSet;
  let sidebarConfig;

  if (appliedConfig.sidebarExtra) {
    sidebarConfig = appliedConfig.sidebarExtra;
  } else if (themeConfig.sidebarExtra) {
    sidebarConfig = themeConfig.sidebarExtra;
  }

  if (!sidebarConfig) {
    return [];
  }

  if (activeVersion) {
    sidebarConfig = sidebarConfig[activeVersion];
  }

  return sidebarConfig;
}

/**
 * Translate page content headers into sidebar items.
 * @param { Page } page
 * @returns { SidebarGroup }
 */
export function resolveHeaders(page) {
  const headers = groupHeaders(
    page.headers || [],
    page.frontmatter.sidebarLevel
  );
  return [
    {
      type: "group",
      collapsable: false,
      title: page.title,
      path: null,
      children: headers.map(h => ({
        type: "auto",
        title: h.title,
        basePath: page.path,
        path: page.path + "#" + h.slug,
        children: h.children || []
      }))
    }
  ];
}

/**
 * Collect headers grouped by specified target level. (Default is `h2`.)
 * @param {*} headers
 * @param {*} level
 */
export function groupHeaders(headers, level = 2) {
  // normalize objects
  headers = headers.map(h => Object.assign({}, h));
  let lastHeadingAtLevel;

  // collect children of target level
  headers.forEach(h => {
    if (h.level === level) {
      lastHeadingAtLevel = h;
    } else if (lastHeadingAtLevel) {
      (lastHeadingAtLevel.children || (lastHeadingAtLevel.children = [])).push(
        h
      );
    }
  });

  return headers.filter(h => h.level === level);
}

export function resolveNavLinkItem(linkItem) {
  return Object.assign(linkItem, {
    type: linkItem.items && linkItem.items.length ? "links" : "link"
  });
}

/**
 * Takes the regular path (like `/3.x/extend/widget-types.html`) and locale-resolved config
 * to return the current base and relevant section of the sidebar config.
 *
 * Modified to account for the active docSet and version.
 *
 * @param { Route } route
 * @param { Array<string|string[]> | Array<SidebarGroup> | [link: string]: SidebarConfig } config
 * @returns { base: string, config: SidebarConfig }
 */
export function resolveMatchingConfig(
  regularPath,
  config,
  activeSet,
  activeVersion
) {
  // always starts with `/`
  let base = "/";

  // include the `baseDir` of our active set
  if (activeSet) {
    base += activeSet.baseDir;
  }

  // account for the active set version
  if (activeSet.versions) {
    // include with base
    base += "/" + activeVersion + "/";
  }

  base = fixDoubleSlashes(ensureEndingSlash(base));

  // simpler array
  if (Array.isArray(config)) {
    return {
      base: base,
      config: config
    };
  }

  // get a relative path with the docset or version
  const modifiedRegularPath = getRelativeRegularPath(
    regularPath,
    activeSet,
    activeVersion
  );

  // sidebar config by path, where `/` is the default
  const activeBase = getRelativeActiveBaseFromConfig(
    modifiedRegularPath,
    config
  );

  if (activeBase) {
    return {
      base: fixDoubleSlashes(base + activeBase),
      config: config[activeBase]
    };
  }

  return {};
}

/**
 * Returns the regular path without its version and docset segments.
 *
 * @param {*} regularPath
 * @param {*} activeSet
 * @param {*} activeVersion
 */
export function getRelativeRegularPath(regularPath, activeSet, activeVersion) {
  let modifiedRegularPath = regularPath;

  if (activeSet) {
    // strip docset baseDir from path
    modifiedRegularPath = fixDoubleSlashes(modifiedRegularPath.replace(activeSet.baseDir, ""));
  }

  if (activeVersion) {
    // strip version segment from path
    modifiedRegularPath = fixDoubleSlashes(modifiedRegularPath.replace(activeVersion, ""));
  }

  return modifiedRegularPath;
}

/**
 * Returns the active sidebar config key.
 *
 * @param {*} path
 * @param {*} config
 */
export function getRelativeActiveBaseFromConfig(path, config) {
  if (Array.isArray(config)) {
    return;
  }

  for (const activeBase in config) {
    if (ensureEndingSlash(path).indexOf(encodeURI(activeBase)) === 0) {
      return activeBase;
    }
  }

  return;
}

function ensureEndingSlash(path) {
  return /(\.html|\/)$/.test(path) ? path : path + "/";
}

export function fixDoubleSlashes(path) {
  return path.replace(/\/\//g, "/");
}

/**
 * Find the given item among the available pages, taking into account
 * the provided base and depth.
 *
 * @param {*} item  Can be a string like `coc`, or an object with `title`,`collapsable` and `children`.
 * @param {*} pages
 * @param {*} base
 * @param {*} groupDepth
 */
export function resolveItem(item, pages, base, groupDepth = 1) {
  if (typeof item === "string") {
    return resolvePage(pages, item, base);
  } else if (Array.isArray(item)) {
    return Object.assign(resolvePage(pages, item[0], base), {
      title: item[1]
    });
  } else {
    const children = item.children || [];
    if (children.length === 0 && item.path) {
      return Object.assign(resolvePage(pages, item.path, base), {
        title: item.title
      });
    }
    const toggleChildren = item.toggleChildren || [];
    return {
      type: "group",
      path: item.path,
      title: item.title,
      sidebarDepth: item.sidebarDepth,
      children: children.map(child =>
        resolveItem(child, pages, base, groupDepth + 1)
      ),
      toggleChildren: toggleChildren.map(child =>
        resolveItem(child, pages, base, groupDepth + 1)
      ),
      collapsable: item.collapsable !== false
    };
  }
}

/**
 * Find the given item among the available pages, taking into account
 * the provided base and depth.
 *
 * @param {*} item  Can be a string like `coc`, or an object with `title`,`collapsable` and `children`.
 * @param {*} pages
 * @param {*} base
 * @param {*} groupDepth
 */
export function resolveExtraItem(item, pages, base, groupDepth = 1) {
  return {
    path: item.path,
    title: item.title,
    link: item.link,
    icon: item.icon,
    sidebarDepth: item.sidebarDepth
  };
}

/**
 * Returns the relative path of the docSet’s default landing,
 * accounting for versions if present. Example:
 * `/commerce/3.x/`
 *
 * @param {*} set docSet object
 */
export function getDocSetDefaultUri(set) {
  let uri = set.baseDir !== "" ? "/" + set.baseDir : set.baseDir;

  if (set.versions && set.defaultVersion) {
    set.versions.forEach(key => {
      const version = key[0];
      if (version == set.defaultVersion) {
        uri += "/" + version;
      }
    });
  }

  return ensureEndingSlash(uri);
}

export function getDocSetDefaultVersionByHandle(docSets, handle) {}

/**
 * Returns relativePath string if it exists in the filesystem or a
 * frontmatter reference for targetVersion. Or `false`.
 *
 * @param {*} relativePath
 * @param {*} activeVersion
 * @param {*} targetVersion
 * @param {*} pages
 * @param {*} localOnly
 */
export function getAlternateVersion(
  page,
  activeVersion,
  targetVersion,
  pages,
  localOnly = false
) {
  // if we don’t have a current version, there won’t be a new one
  if (!activeVersion) {
    return false;
  }

  // see if the page frontmatter manually specifies a new version
  if (page.frontmatter.updatedVersion) {
    const updatedLocation = page.frontmatter.updatedVersion;

    // only return external links if we want them
    if (isExternal(updatedLocation) && localOnly === false) {
      return updatedLocation;
    }

    const updatedPage = getPageWithRelativePath(pages, updatedLocation);

    // return the updated path if it exists for a page
    if (updatedPage) {
      const anchorHash = getAnchorHash(updatedLocation);
      return updatedPage.relativePath + (anchorHash ? "#" + anchorHash : "");
    }
  }

  // look for exact filename match with new version
  const targetPath = page.relativePath.replace(activeVersion, targetVersion);
  const updatedPage = getPageWithRelativePath(pages, targetPath);

  if (updatedPage) {
    return updatedPage.relativePath;
  }
}

export function getPageWithRelativePath(pages, relativePath) {
  for (let i = 0; i < pages.length; i++) {
    const sitePage = pages[i];

    // make sure the specified update actually exists
    if (sitePage.relativePath == getPathWithoutHash(relativePath)) {
      return sitePage;
    }
  }

  return null;
}

function getPathWithoutHash(path) {
  if (path.includes("#")) {
    let parts = path.split("#");
    return parts[0];
  }

  return path;
}

function getAnchorHash(path) {
  if (path.includes("#")) {
    let parts = path.split("#");
    return parts[1];
  }

  return false;
}

/**
 * Returns doc set base paths combinations with their configs,
 * accounting for set base, version, and/or language.
 *
 * Craft (two versions + JA translation):
 * - `/2.x/`
 * - `/2.x/ja/`
 * - `/3.x/`
 * - `/3.x/ja/`
 *
 * Commerce (three versions, no translations):
 * - `/commerce/1.x/`
 * - `/commerce/2.x/`
 * - `/commerce/3.x/`
 *
 * Tutorial (one version, no translations):
 * - `/getting-started-tutorial/`
 */
export function getDocSetLocaleSettings(docSet) {
  let localeSettings = [];

  // do we have translations?
  if (docSet.locales) {
    for (const key in docSet.locales) {
      if (docSet.locales.hasOwnProperty(key)) {
        // modify locale key to include set base and version
        const settings = docSet.locales[key];
        let basePath = docSet.baseDir;

        if (docSet.versions) {
          for (let i = 0; i < docSet.versions.length; i++) {
            const version = docSet.versions[i];

            let versionLabel = version[0];
            if (basePath === "") {
              basePath = "/";
            }

            let localeKey = `${basePath}${versionLabel}${key}`;
            localeSettings[localeKey] = settings;
          }
        } else {
          let localeKey = `${basePath}${key}`;
          localeSettings[localeKey] = settings;
        }
      }
    }
  } else {
    if (docSet.versions) {
      for (let i = 0; i < docSet.versions.length; i++) {
        let basePath = docSet.baseDir;

        const version = docSet.versions[i];

        let versionLabel = version[0];
        if (basePath === "") {
          basePath = "/";
        } else {
          basePath = "/" + basePath + "/";
        }

        let localeKey = `${basePath}${versionLabel}/`;
        localeSettings[localeKey] = docSet;
      }
    } else {
      let basePath = docSet.baseDir;

      if (basePath === "") {
        basePath = "/";
      } else {
        basePath = "/" + basePath + "/";
      }

      let localeKey = `${basePath}`;
      localeSettings[localeKey] = docSet;
    }
  }

  return localeSettings;
}

/**
 * Returns the path to the equivalent content in the specified set version,
 * or `false` if there’s no match and the `strict` parameter is `true`.
 */
export function getSameContentForVersion(
  version,
  activeSet,
  activeVersion,
  page,
  pages,
  strict = false
) {
  // default to target version in current docset
  let targetPath = "/" + activeSet.baseDir + "/" + version + "/";

  const alternatePath = getAlternateVersion(
    page,
    activeVersion,
    version,
    pages,
    true
  );

  if (alternatePath) {
    const targetPage = getPageWithRelativePath(pages, alternatePath);
    const anchorHash = getAnchorHash(alternatePath);
    targetPath = "/" + targetPage.path + (anchorHash ? "#" + anchorHash : "");
  } else if (strict) {
    return false;
  }

  return fixDoubleSlashes(targetPath);
}
