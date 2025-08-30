# detab

[![Build][build-badge]][build]
[![Coverage][coverage-badge]][coverage]
[![Downloads][downloads-badge]][downloads]
[![Size][size-badge]][size]

Replace tabs with spaces.

## Contents

*   [What is this?](#what-is-this)
*   [When should I use this?](#when-should-i-use-this)
*   [Install](#install)
*   [Use](#use)
*   [API](#api)
    *   [`detab(value[, size=4])`](#detabvalue-size4)
*   [Types](#types)
*   [Compatibility](#compatibility)
*   [Contribute](#contribute)
*   [Security](#security)
*   [License](#license)

## What is this?

This package can turn a string with tabs into a string with spaces.

## When should I use this?

You can use this package if *you* want to define how big the tab size is.

## Install

This package is [ESM only][esm].
In Node.js (version 14.14+, 16.0+), install with [npm][]:

```sh
npm install detab
```

In Deno with [`esm.sh`][esmsh]:

```js
import {detab} from 'https://esm.sh/detab@3'
```

In browsers with [`esm.sh`][esmsh]:

```html
<script type="module">
  import {detab} from 'https://esm.sh/detab@3?bundle'
</script>
```

## Use

```js
import {detab} from 'detab'

console.log(detab('\tfoo\nbar\tbaz'))
console.log(detab('\tfoo\nbar\tbaz', 2))
console.log(detab('\tfoo\nbar\tbaz', 8))
```

Yields:

```txt
    foo
bar baz
```

```txt
  foo
bar baz
```

```txt
        foo
bar     baz
```

## API

This package exports the identifier `detab`.
There is no default export.

### `detab(value[, size=4])`

Replace tabs with spaces in `value` (`string`), being smart about which column
the tab is at and which `size` (`number`, default: `4`) should be used.

## Types

This package is fully typed with [TypeScript][].
It exports no additional types.

## Compatibility

This package is at least compatible with all maintained versions of Node.js.
As of now, that is Node.js 14.14+ and 16.0+.
It also works in Deno and modern browsers.

## Contribute

Yes please!
See [How to Contribute to Open Source][contribute].

## Security

This package is safe.

## License

[MIT][license] © [Titus Wormer][author]

<!-- Definitions -->

[build-badge]: https://github.com/wooorm/detab/workflows/main/badge.svg

[build]: https://github.com/wooorm/detab/actions

[coverage-badge]: https://img.shields.io/codecov/c/github/wooorm/detab.svg

[coverage]: https://codecov.io/github/wooorm/detab

[downloads-badge]: https://img.shields.io/npm/dm/detab.svg

[downloads]: https://www.npmjs.com/package/detab

[size-badge]: https://img.shields.io/bundlephobia/minzip/detab.svg

[size]: https://bundlephobia.com/result?p=detab

[npm]: https://docs.npmjs.com/cli/install

[esm]: https://gist.github.com/sindresorhus/a39789f98801d908bbc7ff3ecc99d99c

[esmsh]: https://esm.sh

[typescript]: https://www.typescriptlang.org

[contribute]: https://opensource.guide/how-to-contribute/

[license]: license

[author]: https://wooorm.com
