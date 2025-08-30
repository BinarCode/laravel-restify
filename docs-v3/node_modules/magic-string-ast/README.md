# magic-string-ast [![npm](https://img.shields.io/npm/v/magic-string-ast.svg)](https://npmjs.com/package/magic-string-ast) [![JSR](https://jsr.io/badges/@sxzz/magic-string-ast)](https://jsr.io/@sxzz/magic-string-ast)

[![Unit Test](https://github.com/sxzz/magic-string-ast/actions/workflows/unit-test.yml/badge.svg)](https://github.com/sxzz/magic-string-ast/actions/workflows/unit-test.yml)

[magic-string](https://github.com/rich-harris/magic-string) with Babel AST shortcut.

## Install

```bash
# npm
npm i magic-string-ast

# jsr
npx jsr add -D @sxzz/magic-string-ast
```

## Usage

```ts
import { MagicStringAST } from 'magic-string-ast'

const offset = 0
const node = {
  // AST node from @babel/parser
  start: 6,
  end: 7,
  // ...
}

const s = new MagicStringAST('const a = 1')
s.sliceNode(node, { offset }) // 'a'
s.removeNode(node)
s.moveNode(node, 0)
s.overwriteNode(node, 'foo')
```

For more APIs, see [docs](https://jsr.io/@sxzz/magic-string-ast/doc) and [magic-string](https://github.com/rich-harris/magic-string#usage).

## Sponsors

<p align="center">
  <a href="https://cdn.jsdelivr.net/gh/sxzz/sponsors/sponsors.svg">
    <img src='https://cdn.jsdelivr.net/gh/sxzz/sponsors/sponsors.svg'/>
  </a>
</p>

## License

[MIT](./LICENSE) License © 2023-PRESENT [三咲智子](https://github.com/sxzz)
