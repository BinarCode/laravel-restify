// grab our custom list of placeholder strings
const dictionary = {}

const placeholderClass = "code-placeholder";

// token types to try find/replace (more = slower build)
const searchTypes = [
  "string",
  "other",
  "package",
  "property",
  "single-quoted-string"
];

/**
 * Uses Prism’s `wrap` hook to check each token’s content for placeholder strings.
 *
 * If the wrapped element is an exact match for a placeholder string, a class
 * will be appended with a title attribute when relevant.
 *
 * If the wrapped element contains one or more placeholder strings, each one
 * will be wrapped with the placeholder class and optional title attribute.
 */
Prism.hooks.add("wrap", function(env) {
  if (env.content) {
    if (isDictionaryString(env.content)) {
      env.classes.push(placeholderClass);

      let title = getTitle(env.content);

      if (title) {
        env.attributes["title"] = title;
      }
    } else if (searchTypes.includes(env.type)) {
      let content =
        env.type === "package"
          ? env.content.replace(/<\/?[^>]+(>|$)/g, "")
          : env.content;
      let placeholders = findDictionaryStrings(content);

      if (placeholders.length > 0) {
        placeholders.forEach(placeholder => {
          // https://stackoverflow.com/a/6969486/897279
          let replaceRegex = new RegExp(
            placeholder.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"),
            "g"
          );

          let title = getTitle(placeholder);

          env.content = env.content.replace(
            replaceRegex,
            `<span class="${placeholderClass}" title="${title}">${placeholder}</span>`
          );
        });
      }
    }
  }
});

/**
 * Add simple placeholder support for non-tokenized plain text in
 * shell commands and SQL queries.
 */
Prism.hooks.add("after-tokenize", function(env) {
  let placeholderVars = Object.keys(dictionary);
  let matchPattern = placeholderVars.join("|");
  let match = new RegExp(matchPattern);
  let supported = ["bash", "sql"];

  if (env.code && supported.includes(env.language)) {
    env.grammar["code-placeholder"] = {
      pattern: match
    };
  }
});

/**
 * Is this exact string defined as a placeholder?
 * @param str
 * @returns {boolean}
 */
function isDictionaryString(str) {
  return dictionary.hasOwnProperty(str);
}

/**
 * Does the given string contain one or more placeholders? If so, return
 * relevant keys in an array.
 * @param str
 * @returns {array}
 */
function findDictionaryStrings(str) {
  return Object.keys(dictionary).filter(stringContainsPlaceholder(str));
}

function getTitle(placeholder) {
  return dictionary[placeholder].hasOwnProperty("title")
    ? dictionary[placeholder].title
    : false;
}

/**
 * Callback filter function: check content string `str` for instance of
 * placeholder `element`.
 *
 * @param str
 * @returns {function(*=): boolean}
 */
function stringContainsPlaceholder(str) {
  return function(element) {
    return str.indexOf(element) !== -1;
  };
}
