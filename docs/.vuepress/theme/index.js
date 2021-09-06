const { path } = require("@vuepress/shared-utils");
const { resolve } = require("path");
const htmlToText = require("html-to-text");

module.exports = options => ({
  extendPageData($page) {
    // add lang, set, version
    try {
      const hasContent =
        typeof $page._strippedContent === "string" &&
        $page._strippedContent !== "";

      const { html } = hasContent
        ? $page._context.markdown.render($page._strippedContent)
        : "";

      const plaintext = htmlToText.fromString(html, {
        wordwrap: null,
        hideLinkHrefIfSameAsText: true,
        ignoreImage: true,
        uppercaseHeadings: false
      });

      for (const h of $page.headers || []) {
        const titlePlaintext = $page._context.markdown.renderInline(h.title);
        // find the position of the header within the plain-text content
        h.charIndex = plaintext.indexOf('# ' + titlePlaintext);
        if (h.charIndex === -1) h.charIndex = null;
      }

      $page.headersStr = $page.headers
        ? $page.headers.map(h => h.title).join(" ")
        : null;

      $page.keywords = $page.frontmatter.keywords
        ? $page.frontmatter.keywords
        : "";
      $page.content = plaintext;
      $page.contentLowercase = plaintext.toLowerCase();
      $page.charsets = getCharsets(plaintext);

      const {
        lang,
        docSet,
        primarySet,
        primaryVersion,
        version
      } = getPageDocSetContext($page);

      $page.lang = lang;
      $page.docSetHandle = docSet.handle || false;
      $page.docSetTitle = docSet.setTitle || false;
      $page.isPrimary = primarySet && primaryVersion;
      $page.version = version;
    } catch (e) {
      // incorrect markdown
      console.error("Error when applying fulltext-search plugin:", e);
    }
  },
  clientRootMixin: resolve(__dirname, "clientRootMixin.js"),
  alias: {
    "@SearchBox": path.resolve(__dirname, "components/SearchBox.vue")
  }
});

function getCharsets(text) {
  const cyrillicRegex = /[\u0400-\u04FF]/iu;
  const cjkRegex = /[\u3131-\u314e|\u314f-\u3163|\uac00-\ud7a3]|[\u4E00-\u9FCC\u3400-\u4DB5\uFA0E\uFA0F\uFA11\uFA13\uFA14\uFA1F\uFA21\uFA23\uFA24\uFA27-\uFA29]|[\ud840-\ud868][\udc00-\udfff]|\ud869[\udc00-\uded6\udf00-\udfff]|[\ud86a-\ud86c][\udc00-\udfff]|\ud86d[\udc00-\udf34\udf40-\udfff]|\ud86e[\udc00-\udc1d]/iu;

  const result = {};
  if (cyrillicRegex.test(text)) result.cyrillic = true;
  if (cjkRegex.test(text)) result.cjk = true;
  return result;
}

function getPageDocSetContext(page) {
  const { docSets } = page._context.themeConfig;

  let setBaseUri;

  let currentSet = false;
  let currentVersion = false;
  let currentLang = "en-US";

  // find active set and version
  for (let index = 0; index < docSets.length; index++) {
    const set = docSets[index];

    if (set.versions) {
      for (let version of set.versions) {
        const key = version[0];
        const setVersionBase =
          (set.baseDir ? "/" + set.baseDir : "") + "/" + key;
        const searchPattern = new RegExp("^" + setVersionBase, "i");

        if (searchPattern.test(page.path)) {
          currentVersion = key;
          currentSet = set;
          setBaseUri = setVersionBase;
          break;
        }
      }
    } else {
      const setVersionBase = set.baseDir ? "/" + set.baseDir : "";
      const searchPattern = new RegExp("^" + setVersionBase, "i");

      if (searchPattern.test(page.path)) {
        currentSet = set;
        setBaseUri = setVersionBase;
        break;
      }
    }
  }

  if (currentSet && currentSet.locales) {
    // get path without version base to isolate locale + content path
    const localeContentPath = page.path.replace(setBaseUri, "");

    for (const key in currentSet.locales) {
      if (key !== "/" && localeContentPath.indexOf(key) !== -1) {
        currentLang = currentSet.locales[key].lang;
        break;
      }
    }
  }

  return {
    docSet: currentSet,
    primarySet: currentSet.primarySet || false,
    primaryVersion:
      !currentSet.versions || currentVersion === currentSet.defaultVersion,
    lang: currentLang,
    version: currentVersion
  };
}
