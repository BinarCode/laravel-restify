const IS_JS_RE = /\.[cm]?js(?:\?[^.]+)?$/;
const HAS_EXT_RE = /[^./]+\.[^./]+$/;
const IS_CSS_RE = /\.(?:css|postcss|pcss|sass|scss|less|stylus|styl)(?:\?[^.]+)?$/;
function isJS(file) {
  return IS_JS_RE.test(file) || !HAS_EXT_RE.test(file);
}
function isCSS(file) {
  return IS_CSS_RE.test(file);
}
const IMAGE_RE = /^(?:jpe?g|png|svg|gif|webp|ico)$/;
const FONT_RE = /^(?:woff2?|ttf|otf|eot)$/;
const AUDIO_RE = /^(?:mp3|wav|ogg|flac|aac|m4a|wma|aiff|aif|au|raw|vox|opus)$/;
const VIDEO_RE = /^(?:mp4|webm|ogv|mkv|avi|mov|flv|wmv|mpg|mpeg|m4v|3gp|3g2|mxf|rm|rmvb|asf|asx|m3u8|m3u|pls|cue)$/;
const contentTypeMap = {
  ico: "image/x-icon",
  jpg: "image/jpeg",
  svg: "image/svg+xml"
};
function getContentType(asType, extension) {
  if (asType === "font") {
    return `font/${extension}`;
  }
  if (asType === "image") {
    return contentTypeMap[extension] || `image/${extension}`;
  }
}
function getAsType(ext) {
  if (ext === "js" || ext === "cjs" || ext === "mjs") {
    return "script";
  } else if (ext === "css") {
    return "style";
  } else if (IMAGE_RE.test(ext)) {
    return "image";
  } else if (FONT_RE.test(ext)) {
    return "font";
  } else if (AUDIO_RE.test(ext)) {
    return "audio";
  } else if (VIDEO_RE.test(ext)) {
    return "video";
  }
}
const parseResource = (path) => {
  const chunk = {};
  const extension = path.replace(/\?.*/, "").split(".").pop() || "";
  const asType = getAsType(extension);
  if (asType) {
    chunk.resourceType = asType;
    if (asType === "script" && extension !== "cjs") {
      chunk.module = true;
    }
  }
  if (chunk.resourceType !== "font") {
    chunk.prefetch = true;
  }
  if (chunk.resourceType && ["module", "script", "style"].includes(chunk.resourceType)) {
    chunk.preload = true;
  }
  const contentType = getContentType(asType, extension);
  if (contentType) {
    chunk.mimeType = contentType;
  }
  return chunk;
};

function normalizeViteManifest(manifest) {
  const _manifest = {};
  for (const file in manifest) {
    const chunk = manifest[file];
    _manifest[file] = { ...parseResource(chunk.file || file), ...chunk };
    for (const item of chunk.css || []) {
      if (!_manifest[item]) {
        _manifest[item] = { file: item, resourceType: "style", ...parseResource(item) };
      }
    }
    for (const item of chunk.assets || []) {
      if (!_manifest[item]) {
        _manifest[item] = { file: item, ...parseResource(item) };
      }
    }
  }
  return _manifest;
}

function normalizeWebpackManifest(manifest) {
  const clientManifest = {};
  for (const outfile of manifest.all) {
    if (isJS(outfile)) {
      clientManifest[getIdentifier(outfile)] = {
        file: outfile,
        ...parseResource(outfile)
      };
    }
  }
  const first = getIdentifier(manifest.initial.find(isJS));
  if (first) {
    if (!(first in clientManifest)) {
      throw new Error(
        `Invalid manifest - initial entrypoint not in \`all\`: ${manifest.initial.find(isJS)}`
      );
    }
    clientManifest[first].css = [];
    clientManifest[first].assets = [];
    clientManifest[first].dynamicImports = [];
  }
  for (const outfile of manifest.initial) {
    if (isJS(outfile)) {
      clientManifest[getIdentifier(outfile)].isEntry = true;
    } else if (isCSS(outfile) && first) {
      clientManifest[first].css.push(outfile);
      clientManifest[outfile] = { file: outfile, ...parseResource(outfile) };
    } else if (first) {
      clientManifest[first].assets.push(outfile);
      clientManifest[outfile] = { file: outfile, ...parseResource(outfile) };
    }
  }
  for (const outfile of manifest.async) {
    if (isJS(outfile)) {
      const identifier = getIdentifier(outfile);
      if (!(identifier in clientManifest)) {
        throw new Error(`Invalid manifest - async module not in \`all\`: ${outfile}`);
      }
      clientManifest[identifier].isDynamicEntry = true;
      clientManifest[identifier].sideEffects = true;
      clientManifest[first].dynamicImports.push(identifier);
    } else if (first) {
      const key = isCSS(outfile) ? "css" : "assets";
      const identifier = getIdentifier(outfile);
      clientManifest[identifier] = {
        file: "",
        [key]: [outfile]
      };
      clientManifest[outfile] = {
        file: outfile,
        ...parseResource(outfile)
      };
      clientManifest[first].dynamicImports.push(identifier);
    }
  }
  for (const [moduleId, importIndexes] of Object.entries(manifest.modules)) {
    const jsFiles = importIndexes.map((index) => manifest.all[index]).filter(isJS);
    jsFiles.forEach((file) => {
      const identifier = getIdentifier(file);
      clientManifest[identifier] = {
        ...clientManifest[identifier],
        file
      };
    });
    const mappedIndexes = importIndexes.map((index) => manifest.all[index]);
    clientManifest[moduleId] = {
      file: "",
      ...parseResource(moduleId),
      imports: jsFiles.map((id) => getIdentifier(id)),
      css: mappedIndexes.filter(isCSS),
      assets: mappedIndexes.filter((i) => !isJS(i) && !isCSS(i))
    };
    for (const key of ["css", "assets"]) {
      for (const file of clientManifest[moduleId][key] || []) {
        clientManifest[file] = clientManifest[file] || { file, ...parseResource(file) };
      }
    }
  }
  return clientManifest;
}
function getIdentifier(output) {
  return output ? `_${output}` : null;
}

function defineManifest(manifest) {
  return manifest;
}

export { defineManifest, normalizeViteManifest, normalizeWebpackManifest };
