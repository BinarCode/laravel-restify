"use strict";Object.defineProperty(exports, "__esModule", {value: true}); function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; } function _createStarExport(obj) { Object.keys(obj) .filter((key) => key !== "default" && key !== "__esModule") .forEach((key) => { if (exports.hasOwnProperty(key)) { return; } Object.defineProperty(exports, key, {enumerable: true, configurable: true, get: () => obj[key]}); }); } function _optionalChain(ops) { let lastAccessLHS = undefined; let value = ops[0]; let i = 1; while (i < ops.length) { const op = ops[i]; const fn = ops[i + 1]; i += 2; if ((op === 'optionalAccess' || op === 'optionalCall') && value == null) { return undefined; } if (op === 'access' || op === 'optionalAccess') { lastAccessLHS = value; value = fn(value); } else if (op === 'call' || op === 'optionalCall') { value = fn((...args) => value.call(lastAccessLHS, ...args)); lastAccessLHS = undefined; } } return value; }// src/index.ts
var _magicstring = require('magic-string'); var _magicstring2 = _interopRequireDefault(_magicstring); _createStarExport(_magicstring);

var MagicStringAST = class _MagicStringAST {
  constructor(str, options, prototype = typeof str === "string" ? _magicstring2.default : str.constructor) {
    this.prototype = prototype;
    this.s = typeof str === "string" ? new prototype(str, options) : str;
    return new Proxy(this.s, {
      get: (target, p, receiver) => {
        if (Reflect.has(this, p)) return Reflect.get(this, p, receiver);
        let parent = Reflect.get(target, p, receiver);
        if (typeof parent === "function") parent = parent.bind(target);
        return parent;
      }
    });
  }
  
  getNodePos(nodes, offset = 0) {
    if (Array.isArray(nodes))
      return [offset + nodes[0].start, offset + nodes.at(-1).end];
    else return [offset + nodes.start, offset + nodes.end];
  }
  removeNode(node, { offset } = {}) {
    if (isEmptyNodes(node)) return this;
    this.s.remove(...this.getNodePos(node, offset));
    return this;
  }
  moveNode(node, index, { offset } = {}) {
    if (isEmptyNodes(node)) return this;
    this.s.move(...this.getNodePos(node, offset), index);
    return this;
  }
  sliceNode(node, { offset } = {}) {
    if (isEmptyNodes(node)) return "";
    return this.s.slice(...this.getNodePos(node, offset));
  }
  overwriteNode(node, content, { offset, ...options } = {}) {
    if (isEmptyNodes(node)) return this;
    const _content = typeof content === "string" ? content : this.sliceNode(content);
    this.s.overwrite(...this.getNodePos(node, offset), _content, options);
    return this;
  }
  snipNode(node, { offset } = {}) {
    let newS;
    if (isEmptyNodes(node)) newS = this.s.snip(0, 0);
    else newS = this.s.snip(...this.getNodePos(node, offset));
    return new _MagicStringAST(newS, void 0, this.prototype);
  }
  clone() {
    return new _MagicStringAST(this.s.clone(), void 0, this.prototype);
  }
  toString() {
    return this.s.toString();
  }
};
function isEmptyNodes(nodes) {
  return Array.isArray(nodes) && nodes.length === 0;
}
function generateTransform(s, id) {
  if (_optionalChain([s, 'optionalAccess', _ => _.hasChanged, 'call', _2 => _2()])) {
    return {
      code: s.toString(),
      get map() {
        return s.generateMap({
          source: id,
          includeContent: true,
          hires: "boundary"
        });
      }
    };
  }
}




exports.MagicString = _magicstring2.default; exports.MagicStringAST = MagicStringAST; exports.generateTransform = generateTransform;
