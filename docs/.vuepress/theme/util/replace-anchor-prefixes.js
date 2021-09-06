const dictionary = require("../../anchor-prefixes");
const placeholders = Object.keys(dictionary);

function replacePrefixes(md) {
  // expand custom prefix into full URL
  md.normalizeLink = url => {
    return replacePrefix(url);
  }

  // remove custom prefix from link text
  md.normalizeLinkText = linkText => {
    if (usesCustomPrefix(linkText)) {
      return removePrefix(linkText);
    }

    return linkText;
  }
}

/**
 * Replace any of our special prefixes within links.
 * @param {*} link href value
 */
function replacePrefix(link) {
  link = decodeURIComponent(link);

  // do we have a protocol or prefix?
  const prefix = getPrefix(link);

  if (!prefix) {
    return link;
  }

  // is one of our custom placeholders being used?
  const inUse = placeholders.filter(placeholder => {
    return placeholder === prefix;
  });

  if (prefix === "api" || prefix === "config") {
    console.log('broken legacy `' + prefix + '` link: "' + link + '"');
  }

  if (!inUse || inUse.length === 0) {
    return link;
  }

  // get relevant settings from `anchor-prefixes.js`
  const prefixSettings = dictionary[inUse[0]];

  if (prefixSettings.hasOwnProperty("format")) {
    // get class name, subject, whether it’s a method, and hash
    const ref = parseReference(link);

    if (ref && prefixSettings.format === "internal") {
      let url = `${prefixSettings.base}${slugifyClassName(ref.className)}.html`;
      let hash = ref.hash;

      if (ref.subject) {
        hash = "";
        if (ref.isMethod) {
          hash = "method-";
        } else if (!ref.subject.match(/^EVENT_/)) {
          hash = "property-";
        }

        hash += ref.subject.replace(/_/g, "-").toLowerCase();
      }

      return url + (hash ? `#${hash}` : "");
    } else if (ref && prefixSettings.format === "yii") {
      // v1 does not lowercase class name, and it strips `()` from method names
      let isVersion1 = prefixSettings.base.includes("1.1");
      let url = isVersion1
        ? `${prefixSettings.base}${ref.className}`
        : `${prefixSettings.base}${slugifyClassName(ref.className)}`;
      let hash = ref.hash;

      if (ref.subject) {
        let parens = isVersion1 ? '' : '()';
        hash =
          (ref.isMethod ? `${ref.subject}${parens}` : `\$${ref.subject}`) + "-detail";
      }

      return url + (hash ? `#${hash}` : "");
    } else if (prefixSettings.format === "config") {
      m = link.match(/^config[2|3]:(.+)/);
      let setting = m[1].toLowerCase();

      if (m) {
        return `${prefixSettings.base}${setting}`;
      }
    } else if (prefixSettings.format === "generic") {
      return link.replace(`${prefix}:`, prefixSettings.base);
    }
  }

  return link;
}

/**
 * Grabs characters prior to `:`, returning undefined if there isn’t a colon.
 * @param string link
 */
function getPrefix(link) {
  const linkParts = link.split(":");
  return linkParts.length === 0 ? undefined : linkParts[0];
}

/**
 * Returns `true` if the provided string uses one of our custom anchor prefixes.
 * @param {string} link
 */
function usesCustomPrefix(link) {
  const prefix = getPrefix(link);

  const inUse = placeholders.filter(placeholder => {
    return placeholder === prefix;
  });

  return inUse.length > 0;
}

/**
 * Kebab-cases presumed class name for use in a URL.
 * @param string className
 */
function slugifyClassName(className) {
  return className.replace(/\\/g, "-").toLowerCase();
}

/**
 * Returns the given string with any valid prefixes removed (`foo:bar` → `bar`).
 * @param string link
 */
function removePrefix(link) {
  return link.replace(`${getPrefix(link)}:`, "");
}

/**
 * Takes link content without prefix and parses for class + method details.
 * @param string link
 * @returns object or null
 */
function parseReference(link) {
  let m = removePrefix(link).match(
    /^\\?([\w\\]+)(?:::\$?(\w+)(\(\))?)?(?:#([\w\-]+))?$/
  );

  if (!m) {
    return;
  }

  return {
    className: m[1],
    subject: m[2],
    isMethod: typeof m[3] !== "undefined",
    hash: m[4]
  };
}

module.exports = {
  replacePrefixes,
  replacePrefix
};
