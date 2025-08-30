# structured-clone-es

[![npm version][npm-version-src]][npm-version-href]
[![npm downloads][npm-downloads-src]][npm-downloads-href]
[![bundle][bundle-src]][bundle-href]
[![JSDocs][jsdocs-src]][jsdocs-href]
[![License][license-src]][license-href]

A redistribution of [`@ungap/structured-clone`](https://github.com/ungap/structured-clone) that ships Node.js compatible ESM.

As `@ungap/structured-clone` use `.js` for both CJS and ESM, making the ESM version not working in Node.js. This package re-bundles it, ships TypeScript definitions, and adds ESM support for Node.js.

```ts
import { parse, stringify, structuredClone } from 'structured-clone-es'
```

The `structuredClone` function is moved from default export to named export. `parse` and `stringify` are also exported from the main entry, the `/json` sub module is removed.

<!-- Badges -->

[npm-version-src]: https://img.shields.io/npm/v/structured-clone-es?style=flat&colorA=080f12&colorB=1fa669
[npm-version-href]: https://npmjs.com/package/structured-clone-es
[npm-downloads-src]: https://img.shields.io/npm/dm/structured-clone-es?style=flat&colorA=080f12&colorB=1fa669
[npm-downloads-href]: https://npmjs.com/package/structured-clone-es
[bundle-src]: https://img.shields.io/bundlephobia/minzip/structured-clone-es?style=flat&colorA=080f12&colorB=1fa669&label=minzip
[bundle-href]: https://bundlephobia.com/result?p=structured-clone-es
[license-src]: https://img.shields.io/github/license/antfu/structured-clone-es.svg?style=flat&colorA=080f12&colorB=1fa669
[license-href]: https://github.com/antfu/structured-clone-es/blob/main/LICENSE
[jsdocs-src]: https://img.shields.io/badge/jsdocs-reference-080f12?style=flat&colorA=080f12&colorB=1fa669
[jsdocs-href]: https://www.jsdocs.io/package/structured-clone-es
