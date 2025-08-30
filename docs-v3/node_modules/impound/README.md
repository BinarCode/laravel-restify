# impound

[![npm version][npm-version-src]][npm-version-href]
[![npm downloads][npm-downloads-src]][npm-downloads-href]
[![Github Actions][github-actions-src]][github-actions-href]
[![Codecov][codecov-src]][codecov-href]

> Build plugin to restrict import patterns in certain parts of your code-base.

This package is an [unplugin](https://unplugin.unjs.io/) which provides support for a wide range of bundlers.

## Usage

Install package:

```sh
# npm
npm install impound
```

```js
// rollup.config.js
import { dirname } from 'node:path'
import { fileURLToPath } from 'node:url'
import { ImpoundPlugin } from 'impound'

export default {
  plugins: [
    ImpoundPlugin.rollup({
      cwd: dirname(fileURLToPath(import.meta.url)),
      include: [/src\/*/],
      patterns: [
        [/^node:.*/], // disallows all node imports
        ['@nuxt/kit', 'Importing from @nuxt kit is not allowed in your src/ directory'] // custom error message
      ]
    }),
  ],
}
```

## 💻 Development

- Clone this repository
- Enable [Corepack](https://github.com/nodejs/corepack) using `corepack enable`
- Install dependencies using `pnpm install`
- Run interactive tests using `pnpm dev`

## License

Made with ❤️

Published under [MIT License](./LICENCE).

<!-- Badges -->

[npm-version-src]: https://img.shields.io/npm/v/impound?style=flat-square
[npm-version-href]: https://npmjs.com/package/impound
[npm-downloads-src]: https://img.shields.io/npm/dm/impound?style=flat-square
[npm-downloads-href]: https://npm.chart.dev/impound
[github-actions-src]: https://img.shields.io/github/actions/workflow/status/unjs/impound/ci.yml?branch=main&style=flat-square
[github-actions-href]: https://github.com/unjs/impound/actions?query=workflow%3Aci
[codecov-src]: https://img.shields.io/codecov/c/gh/unjs/impound/main?style=flat-square
[codecov-href]: https://codecov.io/gh/unjs/impound
