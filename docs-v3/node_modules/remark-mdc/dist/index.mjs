import { kebabCase } from 'scule';
import { visit } from 'unist-util-visit';
import { stringify, parse } from 'yaml';
import * as flat from 'flat';
import { stringifyEntitiesLight } from 'stringify-entities';
import { defaultHandlers } from 'mdast-util-to-markdown';
import { parseEntities } from 'parse-entities';
import { markdownLineEnding, markdownSpace, asciiAlpha, markdownLineEndingOrSpace, asciiAlphanumeric } from 'micromark-util-character';
import { factorySpace } from 'micromark-factory-space';
import { factoryWhitespace } from 'micromark-factory-whitespace';
import { codeFenced } from 'micromark-core-commonmark';

const FRONTMATTER_DELIMITER_DEFAULT = "---";
const FRONTMATTER_DELIMITER_CODEBLOCK_STYLE = "```yaml [props]";
const LF = "\n";
const CR = "\r";
function stringifyFrontMatter(data, content = "") {
  if (!Object.keys(data).length) {
    return content.trim();
  }
  data = flat.unflatten(data || {}, {});
  const frontmatter = [
    FRONTMATTER_DELIMITER_DEFAULT,
    stringify(data).trim(),
    FRONTMATTER_DELIMITER_DEFAULT,
    ""
  ].join("\n");
  if (content) {
    return [frontmatter, content.trim(), ""].join("\n");
  }
  return frontmatter;
}
function stringifyCodeBlockProps(data, content = "") {
  if (!Object.keys(data).length) {
    return "";
  }
  data = flat.unflatten(data || {}, {});
  return [
    FRONTMATTER_DELIMITER_CODEBLOCK_STYLE,
    stringify(data).trim(),
    "```",
    content
  ].join("\n");
}
function parseFrontMatter(content) {
  let data = {};
  if (content.startsWith(FRONTMATTER_DELIMITER_DEFAULT)) {
    const idx = content.indexOf(LF + FRONTMATTER_DELIMITER_DEFAULT);
    if (idx !== -1) {
      const hasCarriageReturn = content[idx - 1] === CR;
      const frontmatter = content.slice(4, idx - (hasCarriageReturn ? 1 : 0));
      if (frontmatter) {
        data = parse(frontmatter);
        content = content.slice(idx + 4 + (hasCarriageReturn ? 1 : 0));
      }
    }
  }
  return {
    content,
    // unflatten frontmatter data. convert `parent.child` keys into `parent: { child: ... }`
    data: flat.unflatten(data || {}, {})
  };
}

function track(options_) {
  const options = options_ || {};
  const now = options.now || {};
  let lineShift = options.lineShift || 0;
  let line = now.line || 1;
  let column = now.column || 1;
  return { move, current, shift };
  function current() {
    return { now: { line, column }, lineShift };
  }
  function shift(value) {
    lineShift += value;
  }
  function move(value = "") {
    const chunks = value.split(/\r?\n|\r/g);
    const tail = chunks[chunks.length - 1];
    line += chunks.length - 1;
    column = chunks.length === 1 ? column + tail.length : 1 + tail.length + lineShift;
    return value;
  }
}
function inlineContainerFlow(parent, context, safeOptions = {}) {
  const indexStack = context.indexStack;
  const children = parent.children || [];
  const tracker = track(safeOptions);
  const results = [];
  let index = -1;
  indexStack.push(-1);
  while (++index < children.length) {
    const child = children[index];
    indexStack[indexStack.length - 1] = index;
    results.push(
      tracker.move(
        context.handle(child, parent, context, {
          before: "",
          after: "",
          ...tracker.current()
        })
      )
    );
  }
  indexStack.pop();
  return results.join("");
}
function containerFlow(parent, context, safeOptions = {}) {
  const indexStack = context.indexStack;
  const children = parent.children || [];
  const tracker = track(safeOptions);
  const results = [];
  let index = -1;
  indexStack.push(-1);
  while (++index < children.length) {
    const child = children[index];
    indexStack[indexStack.length - 1] = index;
    results.push(
      tracker.move(
        context.handle(child, parent, context, {
          before: "\n",
          after: "\n",
          ...tracker.current()
        })
      )
    );
    if (child.type !== "list") {
      context.bulletLastUsed = void 0;
    }
    if (index < children.length - 1) {
      results.push(tracker.move(between(child, children[index + 1])));
    }
  }
  indexStack.pop();
  return results.join("");
  function between(left, right) {
    let index2 = context.join.length;
    while (index2--) {
      const result = context.join[index2](left, right, parent, context);
      if (result === true || result === 1) {
        break;
      }
      if (typeof result === "number") {
        return "\n".repeat(1 + result);
      }
      if (result === false) {
        return "\n\n<!---->\n\n";
      }
    }
    return "\n\n";
  }
}
function containerPhrasing(parent, context, safeOptions) {
  const indexStack = context.indexStack;
  const children = parent.children || [];
  const results = [];
  let index = -1;
  let before = safeOptions.before;
  indexStack.push(-1);
  let tracker = track(safeOptions);
  while (++index < children.length) {
    const child = children[index];
    let after;
    indexStack[indexStack.length - 1] = index;
    if (index + 1 < children.length) {
      let handle = context.handle.handlers[children[index + 1].type];
      if (handle && handle.peek) {
        handle = handle.peek;
      }
      after = "";
      if (handle) {
        after = handle(
          children[index + 1],
          parent,
          context,
          {
            before: "",
            after: "",
            ...tracker.current()
          }
        ).charAt(0);
      }
    } else {
      after = safeOptions.after;
    }
    if (results.length > 0 && (before === "\r" || before === "\n") && child.type === "html") {
      results[results.length - 1] = results[results.length - 1].replace(
        /(\r?\n|\r)$/,
        " "
      );
      before = " ";
      tracker = track(safeOptions);
      tracker.move(results.join(""));
    }
    results.push(
      tracker.move(
        context.handle(child, parent, context, {
          ...tracker.current(),
          before,
          after
        })
      )
    );
    before = results[results.length - 1].slice(-1);
  }
  indexStack.pop();
  return results.join("");
}
function checkQuote(context) {
  const marker = context.options.quote || '"';
  if (marker !== '"' && marker !== "'") {
    throw new Error(
      "Cannot serialize title with `" + marker + "` for `options.quote`, expected `\"`, or `'`"
    );
  }
  return marker;
}

const CONTAINER_NODE_TYPES = /* @__PURE__ */ new Set([
  "componentContainerSection",
  "containerComponent",
  "leafComponent"
]);
const NON_UNWRAPPABLE_TYPES = /* @__PURE__ */ new Set([
  "componentContainerSection",
  "componentContainerDataSection",
  "containerComponent",
  "leafComponent",
  "table",
  "pre",
  "code",
  "textComponent"
]);
function convertHtmlEntitiesToChars(text) {
  return text.replace(/&#x([0-9A-Fa-f]+);/g, (_, hexCode) => {
    return String.fromCodePoint(Number.parseInt(hexCode, 16));
  });
}

const own = {}.hasOwnProperty;
const shortcut = /^[^\t\n\r "#'.<=>`}]+$/;
const baseFence = 2;
function compilePattern(pattern) {
  if (!pattern._compiled) {
    const before = (pattern.atBreak ? "[\\r\\n][\\t ]*" : "") + (pattern.before ? "(?:" + pattern.before + ")" : "");
    pattern._compiled = new RegExp(
      (before ? "(" + before + ")" : "") + (/[|\\{}()[\]^$+*?.-]/.test(pattern.character) ? "\\" : "") + pattern.character + (pattern.after ? "(?:" + pattern.after + ")" : ""),
      "g"
    );
  }
  return pattern._compiled;
}
const toMarkdown = (opts = {}) => {
  const applyAutomaticUnwrap = (node, { safeTypes = [] }) => {
    const isSafe = (type) => NON_UNWRAPPABLE_TYPES.has(type) || safeTypes.includes(type);
    if (!node.mdc?.unwrapped) {
      return;
    }
    node.children = [
      {
        type: node.mdc.unwrapped,
        children: node.children.filter((child) => !isSafe(child.type))
      },
      ...node.children.filter((child) => isSafe(child.type))
    ];
  };
  const frontmatter = (node) => {
    const entries = Object.entries(node.fmAttributes || {});
    if (entries.length === 0) {
      return "";
    }
    const attrs = entries.sort(([key1], [key2]) => key1.localeCompare(key2)).reduce((acc, [key, value2]) => {
      if (key?.startsWith(":") && isValidJSON(value2)) {
        try {
          value2 = JSON.parse(value2);
        } catch {
        }
        key = key.slice(1);
      }
      acc[key] = value2;
      return acc;
    }, {});
    return "\n" + (opts?.yamlCodeBlockProps ? stringifyCodeBlockProps(attrs).trim() : stringifyFrontMatter(attrs).trim());
  };
  const processNode = (node) => {
    if (opts.autoUnwrap) {
      applyAutomaticUnwrap(node, typeof opts.autoUnwrap === "boolean" ? {} : opts.autoUnwrap);
    }
  };
  function componentContainerSection(node, _, context) {
    context.indexStack = context.stack;
    processNode(node);
    return `#${node.name}${attributes(node, context)}
${content(node, context)}`.trim();
  }
  function textComponent(node, _, context) {
    let value;
    context.indexStack = context.stack;
    const exit = context.enter(node.type);
    if (node.name === "span") {
      value = `[${content(node, context)}]${attributes(node, context)}`;
    } else if (node.name === "binding") {
      const attrs = node.attributes || {};
      value = attrs.defaultValue ? `{{ ${attrs.value} || '${attrs.defaultValue}' }}` : `{{ ${attrs.value} }}`;
    } else {
      value = ":" + (node.name || "") + label(node, context) + attributes(node, context);
    }
    exit();
    return value;
  }
  let nest = 0;
  function containerComponent(node, _, context) {
    context.indexStack = context.stack;
    const prefix = ":".repeat(baseFence + nest);
    nest += 1;
    const exit = context.enter(node.type);
    let value = prefix + (node.name || "") + label(node, context);
    const defaultSlotChildren = node.children.filter((child) => child.type !== "componentContainerSection");
    const slots = node.children.filter((child) => child.type === "componentContainerSection");
    node.children = [
      ...defaultSlotChildren,
      ...slots
    ];
    node.fmAttributes = node.fmAttributes || {};
    const attributesText = attributes(node, context);
    if ((value + attributesText).length > (opts?.maxAttributesLength || 80) || Object.keys(node.fmAttributes).length > 0 || node.children?.some((child) => child.type === "componentContainerSection")) {
      Object.assign(node.fmAttributes, node.attributes);
      node.attributes = [];
    }
    processNode(node);
    value += attributes(node, context);
    value += frontmatter(node);
    let subvalue;
    if (node.type === "containerComponent") {
      subvalue = content(node, context);
      if (subvalue) {
        value += "\n" + subvalue;
      }
      value += "\n" + prefix;
      if (nest > 1) {
        value = value.split("\n").map((line) => "  " + line).join("\n");
      }
    }
    nest -= 1;
    exit();
    return value;
  }
  containerComponent.peek = function peekComponent() {
    return ":";
  };
  function label(node, context) {
    let label2 = node;
    if (node.type === "containerComponent") {
      if (!inlineComponentLabel(node)) {
        return "";
      }
      label2 = node.children[0];
    }
    const exit = context.enter("label");
    const subexit = context.enter(node.type + "Label");
    const value = containerPhrasing(label2, context, { before: "[", after: "]" });
    subexit();
    exit();
    return value ? "[" + value + "]" : "";
  }
  const isValidJSON = (str) => {
    try {
      JSON.parse(str);
      return true;
    } catch {
      return false;
    }
  };
  function attributes(node, context) {
    const quote = checkQuote(context);
    const subset = node.type === "textComponent" ? [quote] : [quote, "\n", "\r"];
    const attrs = Object.fromEntries(
      Object.entries(node.attributes || {}).sort(([key1], [key2]) => key1.localeCompare(key2))
    );
    const values = [];
    let id;
    let classesFull = "";
    let classes = "";
    let value;
    let key;
    let index;
    for (key in attrs) {
      if (own.call(attrs, key) && attrs[key] != null) {
        value = String(attrs[key]);
        if (key === "id") {
          id = shortcut.test(value) ? "#" + value : quoted("id", value);
        } else if (key === "class" || key === "className") {
          value = Array.isArray(attrs[key]) ? attrs[key].join(" ") : value;
          value = value.split(/[\t\n\r ]+/g).filter(Boolean);
          classesFull = [];
          classes = [];
          index = -1;
          while (++index < value.length) {
            (shortcut.test(value[index]) ? classes : classesFull).push(value[index]);
          }
          classesFull = classesFull.length ? quoted("class", classesFull.join(" ")) : "";
          classes = classes.length ? "." + classes.join(".") : "";
        } else if (key.startsWith(":") && value === "true") {
          values.push(key.slice(1));
        } else if (key.startsWith(":") && isValidJSON(value)) {
          values.push(`${key}='${value.replace(/([^/])'/g, "$1\\'")}'`);
        } else {
          values.push(quoted(key, value));
        }
      }
    }
    if (classesFull) {
      values.unshift(classesFull);
    }
    if (classes) {
      values.unshift(classes);
    }
    if (id) {
      values.unshift(id);
    }
    return values.length ? "{" + values.join(" ") + "}" : "";
    function quoted(key2, value2) {
      return key2 + "=" + quote + stringifyEntitiesLight(value2, { subset }) + quote;
    }
  }
  function content(node, context) {
    const content2 = inlineComponentLabel(node) ? Object.assign({}, node, { children: node.children.slice(1) }) : node;
    let result = node.type === "textComponent" ? inlineContainerFlow(content2, context) : containerFlow(content2, context);
    if (result.includes("&#x")) {
      result = convertHtmlEntitiesToChars(result);
    }
    return result;
  }
  function inlineComponentLabel(node) {
    return node.children && node.children[0] && node.children[0].data && node.children[0].data.componentLabel;
  }
  return {
    compilePattern,
    unsafe: [
      {
        character: "\r",
        inConstruct: ["leafComponentLabel", "containerComponentLabel"]
      },
      {
        character: "\n",
        inConstruct: ["leafComponentLabel", "containerComponentLabel"]
      },
      {
        before: "[^:]",
        character: ":",
        after: "[A-Za-z]",
        inConstruct: ["phrasing"]
      },
      { atBreak: true, character: ":", after: ":" }
    ],
    handlers: {
      containerComponent,
      textComponent,
      componentContainerSection,
      image: (node, _, state, info) => {
        return defaultHandlers.image(node, _, state, info) + attributes(node, state);
      },
      link: (node, _, state, info) => {
        return defaultHandlers.link(node, _, state, info) + attributes(node, state);
      },
      linkReference: (node, _, state, info) => {
        return defaultHandlers.linkReference(node, _, state, info) + attributes(node, state);
      },
      strong: (node, _, state, info) => {
        return defaultHandlers.strong(node, _, state, info) + attributes(node, state);
      },
      inlineCode: (node, _, state) => {
        state.compilePattern = state.compilePattern || compilePattern;
        return defaultHandlers.inlineCode(node, _, state) + attributes(node, state);
      },
      emphasis: (node, _, state, info) => {
        return defaultHandlers.emphasis(node, _, state, info) + attributes(node, state);
      }
    }
  };
};

const fromMarkdown = (opts = {}) => {
  const canContainEols = ["textComponent"];
  const applyYamlCodeBlockProps = (node) => {
    const firstSection = node.children[0];
    if (firstSection && firstSection.children?.length && firstSection.children[0].type === "code" && firstSection.children[0].lang === "yaml" && firstSection.children[0].meta === "[props]") {
      node.rawData = firstSection.children[0].value;
      node.mdc = node.mdc || {};
      node.mdc.codeBlockProps = true;
      firstSection.children.splice(0, 1);
    }
  };
  const applyAutomaticUnwrap = (node, { safeTypes = [] }) => {
    if (!CONTAINER_NODE_TYPES.has(node.type)) {
      return;
    }
    const nonSlotChildren = node.children.filter((child2) => child2.type !== "componentContainerSection");
    if (nonSlotChildren.length !== 1) {
      return;
    }
    const child = nonSlotChildren[0];
    if (NON_UNWRAPPABLE_TYPES.has(child.type) || safeTypes.includes(child.type)) {
      return;
    }
    const childIndex = node.children.indexOf(child);
    node.children.splice(childIndex, 1, ...child?.children || []);
    node.mdc = node.mdc || {};
    node.mdc.unwrapped = child.type;
  };
  const processNode = (node) => {
    if (opts.yamlCodeBlockProps) {
      applyYamlCodeBlockProps(node);
    }
    if (opts.autoUnwrap) {
      applyAutomaticUnwrap(node, typeof opts.autoUnwrap === "boolean" ? {} : opts.autoUnwrap);
    }
  };
  const enter = {
    componentContainer: enterContainer,
    componentContainerSection: enterContainerSection,
    componentContainerDataSection: enterContainerDataSection,
    componentContainerAttributes: enterAttributes,
    componentContainerLabel: enterContainerLabel,
    bindingContent: enterBindingContent,
    componentLeaf: enterLeaf,
    componentLeafAttributes: enterAttributes,
    componentText: enterText,
    textSpan: enterTextSpan,
    componentTextAttributes: enterAttributes
  };
  const exit = {
    bindingContent: exitBindingContent,
    componentContainerSectionTitle: exitContainerSectionTitle,
    listUnordered: conditionalExit,
    listOrdered: conditionalExit,
    listItem: conditionalExit,
    componentContainerSection: exitContainerSection,
    componentContainerDataSection: exitContainerDataSection,
    componentContainer: exitContainer,
    componentContainerAttributeClassValue: exitAttributeClassValue,
    componentContainerAttributeIdValue: exitAttributeIdValue,
    componentContainerAttributeName: exitAttributeName,
    componentContainerAttributeValue: exitAttributeValue,
    componentContainerAttributes: exitAttributes,
    componentContainerLabel: exitContainerLabel,
    componentContainerName,
    componentContainerAttributeInitializerMarker() {
      const attributes = this.data.componentAttributes;
      attributes[attributes.length - 1][1] = "";
    },
    componentLeaf: exitToken,
    componentLeafAttributeClassValue: exitAttributeClassValue,
    componentLeafAttributeIdValue: exitAttributeIdValue,
    componentLeafAttributeName: exitAttributeName,
    componentLeafAttributeValue: exitAttributeValue,
    componentLeafAttributes: exitAttributes,
    componentLeafName: exitName,
    componentText: exitToken,
    textSpan: exitToken,
    componentTextAttributeClassValue: exitAttributeClassValue,
    componentTextAttributeIdValue: exitAttributeIdValue,
    componentTextAttributeName: exitAttributeName,
    componentTextAttributeValue: exitAttributeValue,
    componentTextAttributes: exitAttributes,
    componentTextName: componentContainerName
  };
  function enterBindingContent(token) {
    const regex = /([^|]*)(?:\|\|\s*'(.*)')?/;
    const values = regex.exec(this.sliceSerialize(token));
    this.enter({
      type: "textComponent",
      name: "binding",
      attributes: {
        value: values?.[1]?.trim(),
        defaultValue: values?.[2]
      }
    }, token);
  }
  function exitBindingContent(token) {
    this.exit(token);
  }
  function enterContainer(token) {
    enterToken.call(this, "containerComponent", token);
  }
  function exitContainer(token) {
    const container = this.stack[this.stack.length - 1];
    if (container.children.length > 1) {
      const dataSection = container.children.find((child) => child.rawData);
      container.rawData = dataSection?.rawData;
    }
    processNode(container);
    container.children = container.children.flatMap((child) => {
      if (child.rawData) {
        return [];
      }
      if (child.name === "default" && Object.keys(child.attributes).length === 0 || !child.name) {
        if (child.mdc?.unwrapped) {
          container.mdc = container.mdc || {};
          container.mdc.unwrapped = child.mdc?.unwrapped;
        }
        return child.children;
      }
      child.data = {
        hName: "component-slot",
        hProperties: {
          ...child.attributes,
          [`v-slot:${child.name}`]: ""
        }
      };
      return child;
    });
    this.exit(token);
  }
  function enterContainerSection(token) {
    enterToken.call(this, "componentContainerSection", token);
  }
  function enterContainerDataSection(token) {
    enterToken.call(this, "componentContainerDataSection", token);
  }
  function exitContainerSection(token) {
    const section = this.stack[this.stack.length - 1];
    attemptClosingOpenListSection.call(this, section);
    processNode(section);
    this.exit(token);
  }
  function exitContainerDataSection(token) {
    let section = this.stack[this.stack.length - 1];
    section = attemptClosingOpenListSection.call(this, section);
    if (section.type === "componentContainerDataSection") {
      section.rawData = this.sliceSerialize(token);
      this.exit(token);
    }
  }
  function exitContainerSectionTitle(token) {
    this.stack[this.stack.length - 1].name = this.sliceSerialize(token)?.trim();
  }
  function enterLeaf(token) {
    enterToken.call(this, "leafComponent", token);
  }
  function enterTextSpan(token) {
    this.enter({ type: "textComponent", name: "span", attributes: {}, children: [] }, token);
  }
  function enterText(token) {
    enterToken.call(this, "textComponent", token);
  }
  function enterToken(type, token) {
    this.enter({ type, name: "", attributes: {}, children: [] }, token);
  }
  function componentContainerName(token) {
    this.stack[this.stack.length - 1].name = kebabCase(this.sliceSerialize(token));
  }
  function exitName(token) {
    this.stack[this.stack.length - 1].name = this.sliceSerialize(token);
  }
  function enterContainerLabel(token) {
    this.enter({ type: "paragraph", data: { componentLabel: true }, children: [] }, token);
  }
  function exitContainerLabel(token) {
    this.exit(token);
  }
  function enterAttributes() {
    this.data.componentAttributes = [];
    this.buffer();
  }
  function exitAttributeIdValue(token) {
    this.data.componentAttributes.push(["id", parseEntities(this.sliceSerialize(token))]);
  }
  function exitAttributeClassValue(token) {
    this.data.componentAttributes.push(["class", parseEntities(this.sliceSerialize(token))]);
  }
  function exitAttributeValue(token) {
    const attributes = this.data.componentAttributes;
    const lastAttribute = attributes[attributes.length - 1];
    lastAttribute[1] = (typeof lastAttribute[1] === "string" ? lastAttribute[1] : "") + parseEntities(this.sliceSerialize(token));
  }
  function exitAttributeName(token) {
    this.data.componentAttributes.push([this.sliceSerialize(token), true]);
  }
  function exitAttributes() {
    const attributes = this.data.componentAttributes;
    const cleaned = {};
    let index = -1;
    let attribute;
    while (++index < attributes.length) {
      attribute = attributes[index];
      const name = kebabCase(attribute[0]);
      if (name === "class" && cleaned.class) {
        cleaned.class += " " + attribute[1];
      } else {
        cleaned[name] = attribute[1];
      }
    }
    this.data.componentAttributes = attributes;
    this.resume();
    let stackTop = this.stack[this.stack.length - 1];
    if (stackTop.type !== "textComponent" || stackTop.name === "span") {
      while (!stackTop.position?.end && stackTop.children?.length > 0) {
        stackTop = stackTop.children[stackTop.children.length - 1];
      }
    }
    stackTop.attributes = cleaned;
  }
  function exitToken(token) {
    this.exit(token);
  }
  function conditionalExit(token) {
    const [section] = this.tokenStack[this.tokenStack.length - 1];
    if (section.type === token.type) {
      this.exit(token);
    }
  }
  function attemptClosingOpenListSection(section) {
    while (section.type === "listItem" || section.type === "list") {
      const [stackToken] = this.tokenStack[this.tokenStack.length - 1];
      this.exit(stackToken);
      section = this.stack[this.stack.length - 1];
    }
    return section;
  }
  return {
    canContainEols,
    enter,
    exit
  };
};

const ContainerSequenceSize = 2;
const SectionSequenceSize = 3;
const slotSeparatorCode = 35;
const slotSeparatorLength = 1;
const Codes = {
  /**
   * null
   */
  EOF: null,
  /**
   * ' '
   */
  space: 32,
  /**
   * '"'
   */
  quotationMark: 34,
  /**
   * '#'
   */
  hash: 35,
  /**
   * ' ' '
   */
  apostrophe: 39,
  /**
   * '('
   */
  openingParentheses: 40,
  /**
   * '*'
   */
  star: 42,
  /**
   * '-'
   */
  dash: 45,
  /**
   * '.'
   */
  dot: 46,
  /**
   * ':'
   */
  colon: 58,
  /**
   * '<'
   */
  LessThan: 60,
  /**
   * '='
   */
  equals: 61,
  /**
   * '>'
   */
  greaterThan: 62,
  /**
   * 'X'
   */
  uppercaseX: 88,
  /**
   * '['
   */
  openingSquareBracket: 91,
  /**
   * '\'
   */
  backSlash: 92,
  /**
   * ']'
   */
  closingSquareBracket: 93,
  /**
   * '_'
   */
  underscore: 95,
  /**
   * '`'
   */
  backTick: 96,
  /**
   * 'x'
   */
  lowercaseX: 120,
  /**
   * '{'
   */
  openingCurlyBracket: 123,
  /**
   * '}'
   */
  closingCurlyBracket: 125
};

function createLabel(effects, ok, nok, type, markerType, stringType, disallowEol) {
  let size = 0;
  let balance = 0;
  return start;
  function start(code) {
    if (code !== Codes.openingSquareBracket) {
      throw new Error("expected `[`");
    }
    effects.enter(type);
    effects.enter(markerType);
    effects.consume(code);
    effects.exit(markerType);
    return afterStart;
  }
  function afterStart(code) {
    if (code === Codes.closingSquareBracket) {
      effects.enter(markerType);
      effects.consume(code);
      effects.exit(markerType);
      effects.exit(type);
      return ok;
    }
    effects.enter(stringType);
    return atBreak(code);
  }
  function atBreak(code) {
    if (code === Codes.EOF || size > 999) {
      return nok(code);
    }
    if (code === Codes.closingSquareBracket && !balance--) {
      return atClosingBrace(code);
    }
    if (markdownLineEnding(code)) {
      if (disallowEol) {
        return nok(code);
      }
      effects.enter("lineEnding");
      effects.consume(code);
      effects.exit("lineEnding");
      return atBreak;
    }
    effects.enter("chunkText", { contentType: "text" });
    return label(code);
  }
  function label(code) {
    if (code === Codes.EOF || markdownLineEnding(code) || size > 999) {
      effects.exit("chunkText");
      return atBreak(code);
    }
    if (code === Codes.openingSquareBracket && ++balance > 3) {
      return nok(code);
    }
    if (code === Codes.closingSquareBracket && !balance--) {
      effects.exit("chunkText");
      return atClosingBrace(code);
    }
    effects.consume(code);
    return code === Codes.backSlash ? labelEscape : label;
  }
  function atClosingBrace(code) {
    effects.exit(stringType);
    effects.enter(markerType);
    effects.consume(code);
    effects.exit(markerType);
    effects.exit(type);
    return ok;
  }
  function labelEscape(code) {
    if (code === Codes.openingSquareBracket || code === Codes.backSlash || code === Codes.closingSquareBracket) {
      effects.consume(code);
      size++;
      return label;
    }
    return label(code);
  }
}

const label$2 = { tokenize: tokenizeLabel$2, partial: true };
const gfmCheck = { tokenize: checkGfmTaskCheckbox, partial: true };
const doubleBracketCheck = { tokenize: checkDoubleBracket, partial: true };
function tokenize$6(effects, ok, nok) {
  const self = this;
  return start;
  function start(code) {
    if (code !== Codes.openingSquareBracket) {
      throw new Error("expected `[`");
    }
    if (self.previous === Codes.EOF && self._gfmTasklistFirstContentOfListItem) {
      return effects.check(gfmCheck, nok, attemptLabel)(code);
    }
    if (self.previous === Codes.openingSquareBracket) {
      return nok(code);
    }
    return effects.check(doubleBracketCheck, nok, attemptLabel)(code);
  }
  function attemptLabel(code) {
    effects.enter("textSpan");
    return effects.attempt(label$2, exit, nok)(code);
  }
  function exit(code) {
    if (code === Codes.openingParentheses || code === Codes.openingSquareBracket) {
      return nok(code);
    }
    return exitOK(code);
  }
  function exitOK(code) {
    effects.exit("textSpan");
    return ok(code);
  }
}
function tokenizeLabel$2(effects, ok, nok) {
  return createLabel(effects, ok, nok, "componentTextLabel", "componentTextLabelMarker", "componentTextLabelString");
}
const tokenizeSpan = {
  tokenize: tokenize$6
};
function checkGfmTaskCheckbox(effects, ok, nok) {
  return enter;
  function enter(code) {
    effects.enter("formGfmTaskCheckbox");
    effects.consume(code);
    return check;
  }
  function check(code) {
    if (markdownSpace(code)) {
      effects.consume(code);
      return check;
    }
    if (code === Codes.uppercaseX || code === Codes.lowercaseX) {
      effects.consume(code);
      return check;
    }
    if (code === Codes.closingSquareBracket) {
      effects.exit("formGfmTaskCheckbox");
      return ok(code);
    }
    return nok(code);
  }
}
function checkDoubleBracket(effects, ok, nok) {
  return enter;
  function enter(code) {
    effects.enter("doubleBracket");
    effects.consume(code);
    return check;
  }
  function check(code) {
    if (code !== Codes.openingSquareBracket) {
      return nok(code);
    }
    effects.exit("doubleBracket");
    return ok(code);
  }
}

function createAttributes(effects, ok, nok, attributesType, attributesMarkerType, attributeType, attributeIdType, attributeClassType, attributeNameType, attributeInitializerType, attributeValueLiteralType, attributeValueType, attributeValueMarker, attributeValueData, disallowEol) {
  let type;
  let marker;
  let isBindAttribute = false;
  return start;
  function start(code) {
    effects.enter(attributesType);
    effects.enter(attributesMarkerType);
    effects.consume(code);
    effects.exit(attributesMarkerType);
    return between;
  }
  function between(code) {
    if (code === Codes.hash) {
      type = attributeIdType;
      return shortcutStart(code);
    }
    if (code === Codes.dot) {
      type = attributeClassType;
      return shortcutStart(code);
    }
    if (code === Codes.colon || code === Codes.underscore || asciiAlpha(code)) {
      effects.enter(attributeType);
      effects.enter(attributeNameType);
      effects.consume(code);
      isBindAttribute = code === Codes.colon;
      return code === Codes.colon ? bindAttributeName : name;
    }
    if (disallowEol && markdownSpace(code)) {
      return factorySpace(effects, between, "whitespace")(code);
    }
    if (!disallowEol && markdownLineEndingOrSpace(code)) {
      return factoryWhitespace(effects, between)(code);
    }
    return end(code);
  }
  function shortcutStart(code) {
    effects.enter(attributeType);
    effects.enter(type);
    effects.enter(type + "Marker");
    effects.consume(code);
    effects.exit(type + "Marker");
    return shortcutStartAfter;
  }
  function shortcutStartAfter(code) {
    if (code === Codes.EOF || code === Codes.quotationMark || code === Codes.hash || code === Codes.apostrophe || code === Codes.dot || code === Codes.LessThan || code === Codes.equals || code === Codes.greaterThan || code === Codes.backTick || code === Codes.closingCurlyBracket || markdownLineEndingOrSpace(code)) {
      return nok(code);
    }
    effects.enter(type + "Value");
    effects.consume(code);
    return shortcut;
  }
  function shortcut(code) {
    if (code === Codes.EOF || code === Codes.quotationMark || code === Codes.apostrophe || code === Codes.LessThan || code === Codes.equals || code === Codes.greaterThan || code === Codes.backTick) {
      return nok(code);
    }
    if (code === Codes.hash || code === Codes.dot || code === Codes.closingCurlyBracket || markdownLineEndingOrSpace(code)) {
      effects.exit(type + "Value");
      effects.exit(type);
      effects.exit(attributeType);
      return between(code);
    }
    effects.consume(code);
    return shortcut;
  }
  function bindAttributeName(code) {
    if (code === Codes.dash || asciiAlphanumeric(code)) {
      effects.consume(code);
      return bindAttributeName;
    }
    effects.exit(attributeNameType);
    if (disallowEol && markdownSpace(code)) {
      return factorySpace(effects, bindAttributeNameAfter, "whitespace")(code);
    }
    if (!disallowEol && markdownLineEndingOrSpace(code)) {
      return factoryWhitespace(effects, bindAttributeNameAfter)(code);
    }
    return bindAttributeNameAfter(code);
  }
  function bindAttributeNameAfter(code) {
    if (code === Codes.equals) {
      effects.enter(attributeInitializerType);
      effects.consume(code);
      effects.exit(attributeInitializerType);
      return valueBefore;
    }
    effects.exit(attributeType);
    return nok(code);
  }
  function name(code) {
    if (code === Codes.dash || code === Codes.dot || code === Codes.colon || code === Codes.underscore || asciiAlphanumeric(code)) {
      effects.consume(code);
      return name;
    }
    effects.exit(attributeNameType);
    if (disallowEol && markdownSpace(code)) {
      return factorySpace(effects, nameAfter, "whitespace")(code);
    }
    if (!disallowEol && markdownLineEndingOrSpace(code)) {
      return factoryWhitespace(effects, nameAfter)(code);
    }
    return nameAfter(code);
  }
  function nameAfter(code) {
    if (code === Codes.equals) {
      effects.enter(attributeInitializerType);
      effects.consume(code);
      effects.exit(attributeInitializerType);
      return valueBefore;
    }
    effects.exit(attributeType);
    return between(code);
  }
  function valueBefore(code) {
    if (code === Codes.EOF || code === Codes.LessThan || code === Codes.equals || code === Codes.greaterThan || code === Codes.backTick || code === Codes.closingCurlyBracket || disallowEol && markdownLineEnding(code)) {
      return nok(code);
    }
    if (code === Codes.quotationMark || code === Codes.apostrophe) {
      effects.enter(attributeValueLiteralType);
      effects.enter(attributeValueMarker);
      effects.consume(code);
      effects.exit(attributeValueMarker);
      marker = code;
      return valueQuotedStart;
    }
    if (disallowEol && markdownSpace(code)) {
      return factorySpace(effects, valueBefore, "whitespace")(code);
    }
    if (!disallowEol && markdownLineEndingOrSpace(code)) {
      return factoryWhitespace(effects, valueBefore)(code);
    }
    effects.enter(attributeValueType);
    effects.enter(attributeValueData);
    effects.consume(code);
    marker = void 0;
    return valueUnquoted;
  }
  function valueUnquoted(code) {
    if (code === Codes.EOF || code === Codes.quotationMark || code === Codes.apostrophe || code === Codes.LessThan || code === Codes.equals || code === Codes.greaterThan || code === Codes.backTick) {
      return nok(code);
    }
    if (code === Codes.closingCurlyBracket || markdownLineEndingOrSpace(code)) {
      effects.exit(attributeValueData);
      effects.exit(attributeValueType);
      effects.exit(attributeType);
      return between(code);
    }
    effects.consume(code);
    return valueUnquoted;
  }
  function valueQuotedStart(code) {
    if (code === marker) {
      effects.enter(attributeValueMarker);
      effects.consume(code);
      effects.exit(attributeValueMarker);
      effects.exit(attributeValueLiteralType);
      effects.exit(attributeType);
      return valueQuotedAfter;
    }
    effects.enter(attributeValueType);
    return valueQuotedBetween(code);
  }
  function valueQuotedBetween(code) {
    if (code === marker) {
      effects.exit(attributeValueType);
      return valueQuotedStart(code);
    }
    if (code === Codes.EOF) {
      return nok(code);
    }
    if (markdownLineEnding(code)) {
      return disallowEol ? nok(code) : factoryWhitespace(effects, valueQuotedBetween)(code);
    }
    effects.enter(attributeValueData);
    effects.consume(code);
    return valueQuoted;
  }
  function valueQuoted(code) {
    if (isBindAttribute && code === Codes.backSlash) {
      effects.exit(attributeValueData);
      effects.exit(attributeValueType);
      effects.enter("escapeCharacter");
      effects.consume(code);
      effects.exit("escapeCharacter");
      effects.enter(attributeValueType);
      effects.enter(attributeValueData);
      return valueQuotedEscape;
    }
    if (code === marker || code === Codes.EOF || markdownLineEnding(code)) {
      effects.exit(attributeValueData);
      return valueQuotedBetween(code);
    }
    effects.consume(code);
    return valueQuoted;
  }
  function valueQuotedEscape(code) {
    effects.consume(code);
    return valueQuoted;
  }
  function valueQuotedAfter(code) {
    return code === Codes.closingCurlyBracket || markdownLineEndingOrSpace(code) ? between(code) : end(code);
  }
  function end(code) {
    if (code === Codes.closingCurlyBracket) {
      effects.enter(attributesMarkerType);
      effects.consume(code);
      effects.exit(attributesMarkerType);
      effects.exit(attributesType);
      return ok;
    }
    return nok(code);
  }
}

const attributes$2 = { tokenize: tokenizeAttributes$2, partial: true };
const validEvents = [
  /**
   * Span
   */
  "textSpan",
  /**
   * Bold & Italic
   */
  "attentionSequence",
  /**
   * Inline Code
   */
  "codeText",
  /**
   * Link
   */
  "link",
  /**
   * Image
   */
  "image"
];
function tokenize$5(effects, ok, nok) {
  const self = this;
  return start;
  function start(code) {
    if (code !== Codes.openingCurlyBracket) {
      throw new Error("expected `{`");
    }
    const event = self.events[self.events.length - 1];
    if (markdownLineEnding(self.previous) || !event || !validEvents.includes(event[1].type)) {
      return nok(code);
    }
    return effects.attempt(attributes$2, ok, nok)(code);
  }
}
function tokenizeAttributes$2(effects, ok, nok) {
  return createAttributes(
    effects,
    ok,
    nok,
    "componentTextAttributes",
    "componentTextAttributesMarker",
    "componentTextAttribute",
    "componentTextAttributeId",
    "componentTextAttributeClass",
    "componentTextAttributeName",
    "componentTextAttributeInitializerMarker",
    "componentTextAttributeValueLiteral",
    "componentTextAttributeValue",
    "componentTextAttributeValueMarker",
    "componentTextAttributeValueData"
  );
}
const tokenizeAttribute = {
  tokenize: tokenize$5
};

function attempClose(effects, ok, nok) {
  return start;
  function start(code) {
    if (code !== Codes.closingCurlyBracket) {
      return nok(code);
    }
    effects.exit("bindingContent");
    effects.enter("bindingFence");
    effects.consume(code);
    return secondBracket;
  }
  function secondBracket(code) {
    if (code !== Codes.closingCurlyBracket) {
      return nok(code);
    }
    effects.consume(code);
    effects.exit("bindingFence");
    return ok;
  }
}
function tokenize$4(effects, ok, nok) {
  return start;
  function start(code) {
    if (code !== Codes.openingCurlyBracket) {
      throw new Error("expected `{`");
    }
    effects.enter("bindingFence");
    effects.consume(code);
    return secondBracket;
  }
  function secondBracket(code) {
    if (code !== Codes.openingCurlyBracket) {
      return nok(code);
    }
    effects.consume(code);
    effects.exit("bindingFence");
    effects.enter("bindingContent");
    return content;
  }
  function content(code) {
    if (code === Codes.closingCurlyBracket) {
      return effects.attempt({ tokenize: attempClose, partial: true }, close, (code2) => {
        effects.consume(code2);
        return content;
      })(code);
    }
    effects.consume(code);
    return content;
  }
  function close(code) {
    return ok(code);
  }
}
const tokenizeBinding = {
  tokenize: tokenize$4
};

function createName(effects, ok, nok, nameType) {
  const self = this;
  return start;
  function start(code) {
    if (asciiAlpha(code)) {
      effects.enter(nameType);
      effects.consume(code);
      return name;
    }
    return nok(code);
  }
  function name(code) {
    if (code === Codes.dash || code === Codes.underscore || asciiAlphanumeric(code)) {
      effects.consume(code);
      return name;
    }
    effects.exit(nameType);
    return self.previous === Codes.underscore ? nok(code) : ok(code);
  }
}

const label$1 = { tokenize: tokenizeLabel$1, partial: true };
const attributes$1 = { tokenize: tokenizeAttributes$1, partial: true };
function previous(code) {
  return code !== Codes.colon || this.events[this.events.length - 1][1].type === "characterEscape";
}
function tokenize$3(effects, ok, nok) {
  const self = this;
  return start;
  function start(code) {
    if (code !== Codes.colon) {
      throw new Error("expected `:`");
    }
    if (self.previous !== null && !markdownLineEndingOrSpace(self.previous) && ![Codes.openingSquareBracket, Codes.star, Codes.underscore, Codes.openingParentheses].includes(self.previous)) {
      return nok(code);
    }
    if (!previous.call(self, self.previous)) {
      throw new Error("expected correct previous");
    }
    effects.enter("componentText");
    effects.enter("componentTextMarker");
    effects.consume(code);
    effects.exit("componentTextMarker");
    return createName.call(self, effects, afterName, nok, "componentTextName");
  }
  function afterName(code) {
    if (code === Codes.colon) {
      return nok(code);
    }
    if (code === Codes.openingSquareBracket) {
      return effects.attempt(label$1, afterLabel, afterLabel)(code);
    }
    if (code === Codes.openingCurlyBracket) {
      return effects.attempt(attributes$1, afterAttributes, afterAttributes)(code);
    }
    return exit(code);
  }
  function afterAttributes(code) {
    if (code === Codes.openingSquareBracket) {
      return effects.attempt(label$1, afterLabel, afterLabel)(code);
    }
    return exit(code);
  }
  function afterLabel(code) {
    if (code === Codes.openingCurlyBracket) {
      return effects.attempt(attributes$1, exit, exit)(code);
    }
    return exit(code);
  }
  function exit(code) {
    effects.exit("componentText");
    return ok(code);
  }
}
function tokenizeLabel$1(effects, ok, nok) {
  return createLabel(effects, ok, nok, "componentTextLabel", "componentTextLabelMarker", "componentTextLabelString");
}
function tokenizeAttributes$1(effects, ok, nok) {
  return createAttributes(
    effects,
    ok,
    nok,
    "componentTextAttributes",
    "componentTextAttributesMarker",
    "componentTextAttribute",
    "componentTextAttributeId",
    "componentTextAttributeClass",
    "componentTextAttributeName",
    "componentTextAttributeInitializerMarker",
    "componentTextAttributeValueLiteral",
    "componentTextAttributeValue",
    "componentTextAttributeValueMarker",
    "componentTextAttributeValueData"
  );
}
const tokenizeInline = {
  tokenize: tokenize$3,
  previous
};

function sizeChunks(chunks) {
  let index = -1;
  let size = 0;
  while (++index < chunks.length) {
    size += typeof chunks[index] === "string" ? chunks[index].length : 1;
  }
  return size;
}
function prefixSize(events, type) {
  const tail = events[events.length - 1];
  if (!tail || tail[1].type !== type) {
    return 0;
  }
  return sizeChunks(tail[2].sliceStream(tail[1]));
}
function linePrefixSize(events) {
  let size = 0;
  let index = events.length - 1;
  let tail = events[index];
  while (index >= 0 && tail && tail[1].type === "linePrefix" && tail[0] === "exit") {
    size += sizeChunks(tail[2].sliceStream(tail[1]));
    index -= 1;
    tail = events[index];
  }
  return size;
}
const useTokenState = (tokenName) => {
  const token = {
    isOpen: false,
    /**
     * Enter into token, close previous open token if any
     */
    enter: (effects) => {
      const initialState = token.isOpen;
      token.exit(effects);
      effects.enter(tokenName);
      token.isOpen = true;
      return () => {
        token.isOpen = initialState;
      };
    },
    /**
     * Enter into token only once, if token is already open, do nothing
     */
    enterOnce: (effects) => {
      const initialState = token.isOpen;
      if (!token.isOpen) {
        effects.enter(tokenName);
        token.isOpen = true;
      }
      return () => {
        token.isOpen = initialState;
      };
    },
    /**
     * Exit from token if it is open
     */
    exit: (effects) => {
      const initialState = token.isOpen;
      if (token.isOpen) {
        effects.exit(tokenName);
        token.isOpen = false;
      }
      return () => {
        token.isOpen = initialState;
      };
    }
  };
  return token;
};
const tokenizeCodeFence = { tokenize: checkCodeFenced, partial: true };
function checkCodeFenced(effects, ok, nok) {
  let backTickCount = 0;
  return start;
  function start(code) {
    effects.enter("codeFenced");
    return after(code);
  }
  function after(code) {
    if (code === Codes.backTick) {
      backTickCount++;
      effects.consume(code);
      return after;
    }
    effects.exit("codeFenced");
    if (backTickCount >= 3) {
      return ok(code);
    }
    return nok(code);
  }
}

function tokenizeFrontMatter(effects, ok, _nok, next, initialPrefix) {
  let previous;
  return effects.attempt({
    tokenize: tokenizeDataSection,
    partial: true
  }, dataSectionOpen, next);
  function tokenizeDataSection(effects2, ok2, nok) {
    const self = this;
    let size = 0;
    let sectionIndentSize = 0;
    return closingPrefixAfter;
    function dataLineFirstSpaces(code) {
      if (markdownSpace(code)) {
        effects2.consume(code);
        sectionIndentSize += 1;
        return dataLineFirstSpaces;
      }
      effects2.exit("space");
      return closingPrefixAfter(code);
    }
    function closingPrefixAfter(code) {
      if (markdownSpace(code)) {
        effects2.enter("space");
        return dataLineFirstSpaces(code);
      }
      if (sectionIndentSize === 0) {
        sectionIndentSize = linePrefixSize(self.events);
      }
      effects2.enter("componentContainerSectionSequence");
      return closingSectionSequence(code);
    }
    function closingSectionSequence(code) {
      if (code === Codes.dash || markdownSpace(code)) {
        effects2.consume(code);
        size++;
        return closingSectionSequence;
      }
      if (size < SectionSequenceSize) {
        return nok(code);
      }
      if (sectionIndentSize !== initialPrefix) {
        return nok(code);
      }
      if (!markdownLineEnding(code)) {
        return nok(code);
      }
      effects2.exit("componentContainerSectionSequence");
      return factorySpace(effects2, ok2, "whitespace")(code);
    }
  }
  function dataSectionOpen(code) {
    effects.enter("componentContainerDataSection");
    return effects.attempt({
      tokenize: tokenizeDataSection,
      partial: true
    }, dataSectionClose, dataChunkStart)(code);
  }
  function dataChunkStart(code) {
    if (code === null) {
      effects.exit("componentContainerDataSection");
      effects.exit("componentContainer");
      return ok(code);
    }
    const token = effects.enter("chunkDocument", {
      contentType: "document",
      previous
    });
    if (previous) {
      previous.next = token;
    }
    previous = token;
    return dataContentContinue(code);
  }
  function dataContentContinue(code) {
    if (code === null) {
      effects.exit("chunkDocument");
      effects.exit("componentContainerDataSection");
      effects.exit("componentContainer");
      return ok(code);
    }
    if (markdownLineEnding(code)) {
      effects.consume(code);
      effects.exit("chunkDocument");
      return effects.attempt({
        tokenize: tokenizeDataSection,
        partial: true
      }, dataSectionClose, dataChunkStart);
    }
    effects.consume(code);
    return dataContentContinue;
  }
  function dataSectionClose(code) {
    effects.exit("componentContainerDataSection");
    return factorySpace(effects, next, "whitespace")(code);
  }
}

const label = { tokenize: tokenizeLabel, partial: true };
const attributes = { tokenize: tokenizeAttributes, partial: true };
function tokenize$2(effects, ok, nok) {
  const self = this;
  const initialPrefix = linePrefixSize(this.events);
  let sizeOpen = 0;
  let previous;
  const childContainersSequenceSize = [];
  let containerFirstLine = true;
  let visitingCodeFenced = false;
  const section = useTokenState("componentContainerSection");
  return start;
  function start(code) {
    if (code !== Codes.colon) {
      throw new Error("expected `:`");
    }
    effects.enter("componentContainer");
    effects.enter("componentContainerFence");
    effects.enter("componentContainerSequence");
    return sequenceOpen(code);
  }
  function tokenizeSectionClosing(effects2, ok2, nok2) {
    let size = 0;
    let sectionIndentSize = 0;
    let revertSectionState;
    return closingPrefixAfter;
    function closingPrefixAfter(code) {
      sectionIndentSize = linePrefixSize(self.events);
      revertSectionState = section.exit(effects2);
      effects2.enter("componentContainerSectionSequence");
      return closingSectionSequence(code);
    }
    function closingSectionSequence(code) {
      if (code === slotSeparatorCode) {
        effects2.consume(code);
        size++;
        return closingSectionSequence;
      }
      if (size !== slotSeparatorLength) {
        revertSectionState();
        return nok2(code);
      }
      if (sectionIndentSize !== initialPrefix) {
        revertSectionState();
        return nok2(code);
      }
      if (!asciiAlpha(code)) {
        revertSectionState();
        return nok2(code);
      }
      effects2.exit("componentContainerSectionSequence");
      return factorySpace(effects2, ok2, "whitespace")(code);
    }
  }
  function sectionOpen(code) {
    section.enter(effects);
    if (markdownLineEnding(code)) {
      return factorySpace(effects, lineStart, "whitespace")(code);
    }
    effects.enter("componentContainerSectionTitle");
    return sectionTitle(code);
  }
  function sectionTitle(code) {
    if (code === Codes.openingCurlyBracket) {
      return effects.check(
        attributes,
        (code2) => {
          effects.exit("componentContainerSectionTitle");
          return effects.attempt(attributes, factorySpace(effects, lineStart, "linePrefix", 4), nok)(code2);
        },
        (code2) => {
          effects.consume(code2);
          return sectionTitle;
        }
      )(code);
    }
    if (markdownLineEnding(code)) {
      effects.exit("componentContainerSectionTitle");
      return factorySpace(effects, lineStart, "linePrefix", 4)(code);
    }
    effects.consume(code);
    return sectionTitle;
  }
  function sequenceOpen(code) {
    if (code === Codes.colon) {
      effects.consume(code);
      sizeOpen++;
      return sequenceOpen;
    }
    if (sizeOpen < ContainerSequenceSize) {
      return nok(code);
    }
    effects.exit("componentContainerSequence");
    return createName.call(self, effects, afterName, nok, "componentContainerName")(code);
  }
  function afterName(code) {
    return code === Codes.openingSquareBracket ? effects.attempt(label, afterLabel, afterLabel)(code) : afterLabel(code);
  }
  function afterLabel(code) {
    return code === Codes.openingCurlyBracket ? effects.attempt(attributes, afterAttributes, afterAttributes)(code) : afterAttributes(code);
  }
  function afterAttributes(code) {
    return factorySpace(effects, openAfter, "whitespace")(code);
  }
  function openAfter(code) {
    effects.exit("componentContainerFence");
    if (code === null) {
      effects.exit("componentContainer");
      return ok(code);
    }
    if (markdownLineEnding(code)) {
      effects.enter("lineEnding");
      effects.consume(code);
      effects.exit("lineEnding");
      return self.interrupt ? ok : contentStart;
    }
    return nok(code);
  }
  function contentStart(code) {
    if (code === null) {
      effects.exit("componentContainer");
      return ok(code);
    }
    if (containerFirstLine && (code === Codes.dash || markdownSpace(code))) {
      containerFirstLine = false;
      return tokenizeFrontMatter(effects, ok, nok, contentStart, initialPrefix)(code);
    }
    effects.enter("componentContainerContent");
    return lineStart(code);
  }
  function lineStartAfterPrefix(code) {
    if (code === null) {
      return after(code);
    }
    if (code === Codes.backTick) {
      return effects.check(
        tokenizeCodeFence,
        (code2) => {
          visitingCodeFenced = !visitingCodeFenced;
          return chunkStart(code2);
        },
        chunkStart
      )(code);
    }
    if (visitingCodeFenced) {
      return chunkStart(code);
    }
    if (!childContainersSequenceSize.length && (code === slotSeparatorCode || code === Codes.space)) {
      return effects.attempt(
        { tokenize: tokenizeSectionClosing, partial: true },
        sectionOpen,
        chunkStart
      )(code);
    }
    if (code === Codes.colon) {
      return effects.attempt(
        { tokenize: tokenizeClosingFence, partial: true },
        after,
        chunkStart
      )(code);
    }
    return chunkStart(code);
  }
  function lineStart(code) {
    if (code === null) {
      return after(code);
    }
    return initialPrefix ? factorySpace(effects, lineStartAfterPrefix, "linePrefix", initialPrefix + 1)(code) : lineStartAfterPrefix(code);
  }
  function chunkStart(code) {
    if (code === null) {
      return after(code);
    }
    section.enterOnce(effects);
    const token = effects.enter("chunkDocument", {
      contentType: "document",
      previous
    });
    if (previous) {
      previous.next = token;
    }
    previous = token;
    return contentContinue(code);
  }
  function contentContinue(code) {
    if (code === null) {
      effects.exit("chunkDocument");
      return after(code);
    }
    if (markdownLineEnding(code)) {
      effects.consume(code);
      effects.exit("chunkDocument");
      return lineStart;
    }
    effects.consume(code);
    return contentContinue;
  }
  function after(code) {
    section.exit(effects);
    effects.exit("componentContainerContent");
    effects.exit("componentContainer");
    return ok(code);
  }
  function tokenizeClosingFence(effects2, ok2, nok2) {
    let size = 0;
    return factorySpace(effects2, closingPrefixAfter, "linePrefix", 4);
    function closingPrefixAfter(code) {
      effects2.enter("componentContainerFence");
      effects2.enter("componentContainerSequence");
      return closingSequence(code);
    }
    function closingSequence(code) {
      if (code === Codes.colon) {
        effects2.consume(code);
        size++;
        return closingSequence;
      }
      if (childContainersSequenceSize.length) {
        if (size === childContainersSequenceSize[childContainersSequenceSize.length - 1]) {
          childContainersSequenceSize.pop();
        }
        return nok2(code);
      }
      if (size !== sizeOpen) {
        return nok2(code);
      }
      effects2.exit("componentContainerSequence");
      return factorySpace(effects2, closingSequenceEnd, "whitespace")(code);
    }
    function closingSequenceEnd(code) {
      if (code === null || markdownLineEnding(code)) {
        effects2.exit("componentContainerFence");
        return ok2(code);
      }
      childContainersSequenceSize.push(size);
      return nok2(code);
    }
  }
}
function tokenizeLabel(effects, ok, nok) {
  return createLabel(
    effects,
    ok,
    nok,
    "componentContainerLabel",
    "componentContainerLabelMarker",
    "componentContainerLabelString",
    true
  );
}
function tokenizeAttributes(effects, ok, nok) {
  return createAttributes(
    effects,
    ok,
    nok,
    "componentContainerAttributes",
    "componentContainerAttributesMarker",
    "componentContainerAttribute",
    "componentContainerAttributeId",
    "componentContainerAttributeClass",
    "componentContainerAttributeName",
    "componentContainerAttributeInitializerMarker",
    "componentContainerAttributeValueLiteral",
    "componentContainerAttributeValue",
    "componentContainerAttributeValueMarker",
    "componentContainerAttributeValueData",
    true
  );
}
const tokenizeContainer = {
  tokenize: tokenize$2,
  concrete: true
};

function tokenize$1(effects, ok, nok) {
  const self = this;
  return factorySpace(effects, lineStart, "linePrefix");
  function lineStart(code) {
    if (prefixSize(self.events, "linePrefix") < 4) {
      return nok(code);
    }
    switch (code) {
      case Codes.backTick:
        return codeFenced.tokenize.call(self, effects, ok, nok)(code);
      case Codes.colon:
        return tokenizeContainer.tokenize.call(self, effects, ok, nok)(code);
      default:
        return nok(code);
    }
  }
}
const tokenizeContainerIndented = {
  tokenize: tokenize$1
};

function tokenize(effects, ok, nok) {
  const self = this;
  const tokenizeSugerSyntax = tokenizeInline.tokenize.call(
    self,
    effects,
    factorySpace(effects, exit, "linePrefix"),
    nok
  );
  return factorySpace(effects, lineStart, "linePrefix");
  function lineStart(code) {
    if (code === Codes.colon) {
      return tokenizeSugerSyntax(code);
    }
    return nok(code);
  }
  function exit(code) {
    if (markdownLineEnding(code) || code === Codes.EOF) {
      return ok(code);
    }
    return nok(code);
  }
}
const tokenizeContainerSuger = {
  tokenize
};

function micromarkComponentsExtension() {
  return {
    text: {
      [Codes.colon]: tokenizeInline,
      [Codes.openingSquareBracket]: [tokenizeSpan],
      [Codes.openingCurlyBracket]: [tokenizeBinding, tokenizeAttribute]
    },
    flow: {
      [Codes.colon]: [tokenizeContainer, tokenizeContainerSuger]
    },
    flowInitial: {
      "-2": tokenizeContainerIndented,
      "-1": tokenizeContainerIndented,
      [Codes.space]: tokenizeContainerIndented
    }
  };
}

const toFrontMatter = (yamlString) => `---
${yamlString}
---`;
const remarkMDC = (function remarkMDC(opts = {}) {
  const data = this.data();
  if (opts.autoUnwrap === void 0 && opts.experimental?.autoUnwrap) {
    opts.autoUnwrap = opts.experimental.autoUnwrap ? { safeTypes: [] } : false;
  }
  if (opts.yamlCodeBlockProps === void 0 && opts.experimental?.componentCodeBlockYamlProps) {
    opts.yamlCodeBlockProps = opts.experimental.componentCodeBlockYamlProps;
  }
  add("micromarkExtensions", micromarkComponentsExtension());
  add("fromMarkdownExtensions", fromMarkdown(opts));
  add("toMarkdownExtensions", toMarkdown(opts));
  function add(field, value) {
    if (!data[field]) {
      data[field] = [];
    }
    data[field].push(value);
  }
  if (opts?.components?.length) {
    return async (tree, { data: data2 }) => {
      const jobs = [];
      visit(tree, ["textComponent", "leafComponent", "containerComponent"], (node) => {
        bindNode(node);
        const { instance: handler, options } = opts.components.find((c) => c.name === node.name) || {};
        if (handler) {
          jobs.push(handler(options)(node, data2));
        }
      });
      await Promise.all(jobs);
      return tree;
    };
  }
  return (tree) => {
    visit(tree, ["textComponent", "leafComponent", "containerComponent"], (node) => {
      bindNode(node);
    });
  };
});
function bindNode(node) {
  const nodeData = node.data || (node.data = {});
  node.fmAttributes = getNodeData(node);
  nodeData.hName = kebabCase(node.name);
  nodeData.hProperties = bindData(
    {
      ...node.attributes,
      // Parse data slots and retrieve data
      ...node.fmAttributes
    }
  );
}
function getNodeData(node) {
  if (node.rawData) {
    const yaml = node.rawData.replace(/\s-+$/, "");
    const { data } = parseFrontMatter(toFrontMatter(yaml));
    return data;
  }
  return {};
}
function bindData(data) {
  const entries = Object.entries(data).map(([key, value]) => {
    if (key.startsWith(":")) {
      return [key, value];
    }
    if (typeof value === "string") {
      return [key, value];
    }
    return [`:${key}`, JSON.stringify(value)];
  });
  return Object.fromEntries(entries);
}

export { convertHtmlEntitiesToChars, remarkMDC as default, micromarkComponentsExtension as micromarkExtension, parseFrontMatter, stringifyFrontMatter };
