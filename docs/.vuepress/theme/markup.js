const Token = require("markdown-it/lib/token");
const container = require("markdown-it-container");
const { escapeHtml } = require("markdown-it/lib/common/utils");

function renderInlineCode(tokens, idx, options, env, renderer) {
  var token = tokens[idx];

  return (
    "<code v-pre" +
    renderer.renderAttrs(token) +
    ">" +
    escapeHtml(tokens[idx].content) +
    "</code>"
  );
}

function findPrev(tokens, idx, check) {
  for (let i = idx - 1; i >= 0; i--) {
    if (check(tokens[i])) {
      return i;
    }
  }
  return false;
}

function findNext(tokens, idx, check) {
  for (let i = idx + 1; i < tokens.length; i++) {
    if (check(tokens[i])) {
      return i;
    }
  }
  return false;
}

function increment(tokens) {
  tokens.forEach(t => t.level++);
}

/**
 * Wrap tables in div.table for stylistic improvement.
 * @param {*} tokens
 */
function tables(tokens) {
  for (let i = 0; i < tokens.length; i++) {
    let t = tokens[i];
    if (
      t.type === "table_open" &&
      (i === 0 || tokens[i - 1].content !== `<div class="table">\n`)
    ) {
      for (let j = i + 1; j < tokens.length; j++) {
        let t2 = tokens[j];
        if (t2.type === "table_close") {
          let replaceTokens = [
            openBlock("table", t.level),
            ...tokens.slice(i, j + 1),
            closeBlock(t.level)
          ];
          tokens.splice(i, j - i, ...replaceTokens);

          // skip ahead
          i += replaceTokens.length - 1;
          break;
        }
      }
    }
  }
}

function split(tokens) {
  for (let i = 0; i < tokens.length; i++) {
    let t = tokens[i];
    if (t.type === "hr") {
      let leftContentStart = 0,
        rightContentEnd = tokens.length - 1;

      // see if there's a previous h2/h3
      for (let j = i - 1; j >= 0; j--) {
        if (isHeading(tokens[j], "heading_close")) {
          leftContentStart = j + 1;
          break;
        }
      }

      // see if there's another h2/h3 afterwards
      for (let j = i + 1; j < rightContentEnd; j++) {
        if (isHeading(tokens[j], "heading_open")) {
          rightContentEnd = j - 1;
          break;
        }
      }

      let leftTokens = tokens.slice(leftContentStart, i);
      let rightTokens = tokens.slice(i + 1, rightContentEnd + 1);

      let replaceTokens = [
        openBlock("split"),
        openBlock("left"),
        ...leftTokens,
        closeBlock(),
        openBlock("right"),
        ...rightTokens,
        closeBlock(),
        closeBlock()
      ];

      tokens.splice(
        leftContentStart,
        rightContentEnd - leftContentStart + 1,
        ...replaceTokens
      );

      // skip ahead
      i = leftContentStart + replaceTokens.length;
    }
  }
}

function codeToggles(tokens) {
  for (let i = 0; i < tokens.length; i++) {
    let t = tokens[i];
    if (t.type === "container_code_open") {
      // find the close tag
      for (let j = i + 1; j < tokens.length; j++) {
        if (tokens[j].type === "container_code_close") {
          let innerTokens = tokens.slice(i + 1, j);
          let slotNames = [];
          let labels = {};

          codeBlocks(innerTokens, (t, i) => {
            let slotName;

            // does the slot have a custom label?
            let labelMatch = t.info.match(/([^ ]) +(.*)/);
            if (labelMatch) {
              // give the slot a random slot name
              slotName = "slot" + i;
              labels[slotName] = labelMatch[2];

              // remove the label from the code info
              t.info = t.info.replace(labelMatch[0], labelMatch[1]);
            } else {
              // set the slot name to the language (w/out line numbers)
              slotName = t.info.replace(/\{.*\}/, "").trim();
            }

            slotNames.push(slotName);

            return [
              block(`<template slot="${slotName}">`, t.level),
              t,
              block("</template>", t.level)
            ];
          });

          let openBlock = block(
            `<code-toggle :languages='${JSON.stringify(
              slotNames
            )}' :labels='${JSON.stringify(labels)}'>`,
            tokens[i].level
          );
          let closeBlock = block("</code-toggle>", tokens[j].level);
          openBlock.nesting = tokens[i].nesting;
          closeBlock.nesting = tokens[j].nesting;

          let replaceTokens = [openBlock, ...innerTokens, closeBlock];

          tokens.splice(i, j - i, ...replaceTokens);

          // skip ahead
          i += replaceTokens.length - 1;

          break;
        }
      }
    }
  }
}

function codeBlocks(tokens, replace) {
  for (let i = 0; i < tokens.length; i++) {
    let t = tokens[i];
    if (t.type === "fence" && t.info) {
      let replaceTokens = replace(t, i);
      tokens.splice(i, 1, ...replaceTokens);

      // skip ahead
      i += replaceTokens.length - 1;
    }
  }
}

function isHeading(t, type) {
  return (
    t.type === type && (t.tag === "h1" || t.tag === "h2" || t.tag === "h3")
  );
}

function block(tag, level) {
  var t = new Token("html_block", "", 0);
  t.content = `${tag}\n`;
  t.block = true;
  t.level = level || 0;
  return t;
}

function openBlock(klass, level) {
  return block(`<div class="${klass}">`, level);
}

function closeBlock(level) {
  return block("</div>", level);
}

/**
 * Surround first `<h1>` with special PreHeading and PostHeading components.
 *
 * We use these for things like smaller intro headings, displaying post metadata,
 * and the conditionally-displayed automatic table of contents.
 */
function customHeadingSlots(tokens) {
  for (let i = 0; i < tokens.length; i++) {
    let t = tokens[i];

    if (t.tag === "h1") {
      if (t.type === "heading_open") {
        let preHeading = [block(`<pre-heading></pre-heading>`, t.level)];

        tokens.splice(i > 0 ? i - 1 : 0, 0, ...preHeading);
        i += 1;
      }

      if (t.type === "heading_close") {
        let postHeading = [block(`<post-heading></post-heading>`, t.level)];

        tokens.splice(i + 1, 0, ...postHeading);

        // stop after first h1 close; we only need to do this once
        break;
      }
    }
  }
}

/**
 * Adds a non-breaking space between the last two words of text to avoid
 * typographic widows/orphans.
 * @param {*} tokens 
 * @param {*} idx 
 * @param {*} options 
 * @param {*} env 
 * @param {*} renderer 
 */
function dewidowText(tokens, idx, options, env, renderer) {
  // inner text content
  let content = tokens[idx].content;
  // characters that indicate the end of a sentence
  const endSentenceChars = ['.', ':', '!', '…', '?'];
  // last character of content
  const lastChar = content.slice(-1);
  // only consider strings likely to occupy more than one line
  const minContentLength = 60;
  // avoid joining really long words
  const maxWordLength = 50;

  // make sure we’ve got text at the end of a sentence
  if (endSentenceChars.includes(lastChar) && content.length > minContentLength) {
    const words = content.split(' ');
    const len = words.length;
    if (len > 1 && words[len - 2].length + words[len - 1].length < maxWordLength) {
      words[len - 2] += '&nbsp;' + words[len - 1];
      var lastWord = words.pop().replace(/.*((?:<\/\w+>)*)$/, '$1');
      content = words.join(' ') + lastWord;
    }
  }

  return content;
}

module.exports = md => {
  // Custom <code> renders
  md.renderer.rules.code_inline = renderInlineCode;
  md.renderer.rules.text = dewidowText;

  // override parse()
  const parse = md.parse;
  md.parse = (...args) => {
    const tokens = parse.call(md, ...args);
    tables(tokens);
    codeToggles(tokens);
    split(tokens);
    customHeadingSlots(tokens);
    return tokens;
  };

  md.use(container, "code", {
    render(tokens, idx) {
      return "";
    }
  });
};
