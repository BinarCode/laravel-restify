remark-emoji
============
[![CI][ci-badge]][ci]
[![npm][npm-badge]][npm]

[remark-emoji][npm] is a [remark](https://github.com/remarkjs/remark) plugin to replace `:emoji:` to real UTF-8
emojis in Markdown text. This plugin is built on top of [node-emoji](https://www.npmjs.com/package/node-emoji).
The accessibility support and [Emoticon](https://en.wikipedia.org/wiki/Emoticon) support are optionally available.

## Demo

You can find a demo in the following [Codesandbox](https://codesandbox.io/p/sandbox/remark-emoji-example-w6yrmm).

## Usage

```
remark().use(emoji [, options]);
```

```javascript
import { remark } from 'remark';
import emoji from 'remark-emoji';

const doc = 'Emojis in this text will be replaced: :dog::+1:';
const processor = remark().use(emoji);
const file = await processor.process(doc);

console.log(String(file));
// => Emojis in this text will be replaced: 🐶👍
```

Note:

- This package is [ESM only][esm-only] from v3.0.0 since remark packages migrated to ESM.
- This package supports Node.js v18 or later.

## Options

### `options.accessible`

Setting to `true` makes the converted emoji text accessible with `role` and `aria-label` attributes. Each emoji
text is wrapped with `<span>` element. The `role` and `aria-label` attribute are not allowed by default. Please
add them to the sanitization schema used by remark's HTML transformer. The default sanitization schema is exported
from [rehype-sanitize](https://www.npmjs.com/package/rehype-sanitize) package.

For example,

```javascript
import remarkParse from 'remark-parse';
import toRehype from 'remark-rehype';
import sanitize, { defaultSchema } from 'rehype-sanitize';
import stringify from 'rehype-stringify';
import emoji from 'remark-emoji';
import { unified } from 'unified';

// Allow using `role` and `aria-label` attributes in transformed HTML document
const schema = structuredClone(defaultSchema);
if ('span' in schema.attributes) {
    schema.attributes.span.push('role', 'ariaLabel');
} else {
    schema.attributes.span = ['role', 'ariaLabel'];
}

// Markdown text processor pipeline
const processor = unified()
    .use(remarkParse)
    .use(emoji, { accessible: true })
    .use(toRehype)
    .use(sanitize, schema)
    .use(stringify);

const file = await processor.process('Hello :dog:!');
console.log(String(file));
```

yields

```html
Hello <span role="img" aria-label="dog emoji">🐶</span>!
```

Default value is `false`.

### `options.padSpaceAfter`

Setting to `true` means that an extra whitespace is added after emoji.
This is useful when browser handle emojis with half character length and following character is hidden.
Default value is `false`.

### `options.emoticon`

Setting to `true` means that [emoticon](https://www.npmjs.com/package/emoticon) shortcodes are supported (e.g. :-)
will be replaced by 😃). Default value is `false`.

## TypeScript support

remark-emoji package contains [TypeScript](https://www.typescriptlang.org/) type definitions. The package is ready
for use with TypeScript.

Note that the legacy `node` (or `node10`) resolution at [`moduleResolution`](https://www.typescriptlang.org/tsconfig#moduleResolution)
is not available since it enforces CommonJS module resolution and this package is ESM only. Please use `node16`,
`bundler`, or `nodenext` to enable ESM module resolution.

## License

Distributed under [the MIT License](LICENSE).



[ci-badge]: https://github.com/rhysd/remark-emoji/actions/workflows/ci.yml/badge.svg
[ci]: https://github.com/rhysd/remark-emoji/actions/workflows/ci.yml
[npm-badge]: https://badge.fury.io/js/remark-emoji.svg
[npm]: https://www.npmjs.com/package/remark-emoji
[esm-only]: https://gist.github.com/sindresorhus/a39789f98801d908bbc7ff3ecc99d99c
