![Nuxt MDC](https://github.com/nuxt-modules/mdc/assets/904724/ce6aa142-0820-4bcc-988c-a926cf03f0a5)

# Nuxt MDC

[![npm version][npm-version-src]][npm-version-href]
[![npm downloads][npm-downloads-src]][npm-downloads-href]
[![License][license-src]][license-href]
[![Nuxt][nuxt-src]][nuxt-href]

MDC supercharges regular Markdown to write documents interacting deeply with any Vue component. MDC stands for MarkDown Components.

- [✨ &nbsp;Release Notes](https://github.com/nuxt-modules/mdc/releases)
- [🏀 &nbsp;Online Playground](https://stackblitz.com/github/nuxt-modules/mdc?file=playground%2Fapp.vue)
- [🧩 &nbsp;VS Code Extension](https://marketplace.visualstudio.com/items?itemName=Nuxt.mdc)

## Features

- Mix Markdown syntax with HTML tags or Vue components
- Unwrap any generated content (ex: `<p>` added by each Markdown paragraph)
- Use Vue components with named slots
- Support inline components
- Support asynchronous rendering of nested components
- Add attributes and classes to inline HTML tags

Learn more about the MDC syntax on https://content.nuxtjs.org/guide/writing/mdc

> [!Note]
> You may utilize this package inside of your Nuxt project (standard configuration) or within any Vue project.
>
> See [Rendering in your Vue project](#rendering-in-your-vue-project) below for more information.

## Install

```bash
npx nuxi@latest module add mdc
```

Then, add `@nuxtjs/mdc` to the modules section of your `nuxt.config.ts`

```ts [nuxt.config.ts]
export default defineNuxtConfig({
  modules: ['@nuxtjs/mdc']
})
```

That's it! You can start writing and rendering markdown files in your Nuxt project ✨

## Rendering

`@nuxtjs/mdc` exposes three components to render markdown files.

### `<MDC>`

Using `<MDC>`, you can parse and render markdown contents right inside your components/pages. This component takes raw markdown, parses it using the `parseMarkdown` function, and then renders it with `<MDCRenderer>`.

```html
<script setup lang="ts">
const md = `
::alert
Hello MDC
::
`
</script>

<template>
  <MDC :value="md" tag="article" />
</template>
```

Note that `::alert` will use the `components/global/Alert.vue` component.

### `<MDCRenderer>`

This component will take the result of [`parseMarkdown`](#parsing-markdown) function and render the contents. For example, this is an extended version of the sample code in the [Browser section](#browser) which uses `MDCRenderer` to render the parsed markdown.

```html [mdc-test.vue]
<script setup lang="ts">
import { parseMarkdown } from '@nuxtjs/mdc/runtime'

const { data: ast } = await useAsyncData('markdown', () => parseMarkdown('::alert\nMissing markdown input\n::'))
</script>

<template>
  <MDCRenderer :body="ast.body" :data="ast.data" />
</template>
```

### `<MDCSlot>`

This component is a replacement for Vue's `<slot/>` component, specifically designed for MDC. Using this component, you can render a component's children while removing one or multiple wrapping elements. In the below example, the Alert component receives text and its default slot (children). But if the component renders this slot using the normal `<slot/>`, it will render a `<p>` element around the text.

```md [markdown.md]
::alert
This is an Alert
::
```

```html [Alert.vue]
<template>
  <div class="alert">
    <!-- Slot will render <p> tag around the text -->
    <slot />
  </div>
</template>
```

It is the default behavior of markdown to wrap every text inside a paragraph. MDC didn't come to break markdown behavior; instead, the goal of MDC is to make markdown powerful. In this example and all similar situations, you can use `<MDCSlot />` to remove unwanted wrappers.

```html [Alert.vue]
<template>
  <div class="alert">
    <!-- MDCSlot will only render the actual text without the wrapping <p> -->
    <MDCSlot unwrap="p" />
  </div>
</template>
```

### Prose Components

Prose components are a list of components that will be rendered instead of regular HTML tags. For example, instead of rendering a `<p>` tag, `@nuxtjs/mdc` renders a `<ProseP>` component. This is useful when you want to add extra features to your markdown files. For example, you can add a `copy` button to your code blocks.

You can disable prose components by setting the `prose` option to `false` in `nuxt.config.ts`. Or extend the map of prose components to add your own components.

```ts [nuxt.config.ts]
export default defineNuxtConfig({
  modules: ['@nuxtjs/mdc'],
  mdc: {
    components: {
      prose: false, // Disable predefined prose components
      map: {
        p: 'MyCustomPComponent'
      }
    }
  }
})
```

Here is the list of available prose components:

| Tag | Component | Source | Description |
| -- | -- | -- | -- |
| `p` | `<ProseP>` | [ProseP.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseP.vue) | Paragraph |
| `h1` | `<ProseH1>` | [ProseH1.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseH1.vue) | Heading 1 |
| `h2` | `<ProseH2>` | [ProseH2.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseH2.vue) | Heading 2 |
| `h3` | `<ProseH3>` | [ProseH3.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseH3.vue) | Heading 3 |
| `h4` | `<ProseH4>` | [ProseH4.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseH4.vue) | Heading 4 |
| `h5` | `<ProseH5>` | [ProseH5.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseH5.vue) | Heading 5 |
| `h6` | `<ProseH6>` | [ProseH6.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseH6.vue) | Heading 6 |
| `ul` | `<ProseUl>` | [ProseUl.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseUl.vue) | Unordered List |
| `ol` | `<ProseOl>` | [ProseOl.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseOl.vue) | Ordered List |
| `li` | `<ProseLi>` | [ProseLi.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseLi.vue) | List Item |
| `blockquote` | `<ProseBlockquote>` | [ProseBlockquote.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseBlockquote.vue) | Blockquote |
| `hr` | `<ProseHr>` | [ProseHr.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseHr.vue) | Horizontal Rule |
| `pre` | `<ProsePre>` | [ProsePre.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProsePre.vue) | Preformatted Text |
| `code` | `<ProseCode>` | [ProseCode.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseCode.vue) | Code Block |
| `table` | `<ProseTable>` | [ProseTable.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseTable.vue) | Table |
| `thead` | `<ProseThead>` | [ProseThead.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseThead.vue) | Table Head |
| `tbody` | `<ProseTbody>` | [ProseTbody.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseTbody.vue) | Table Body |
| `tr` | `<ProseTr>` | [ProseTr.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseTr.vue) | Table Row |
| `th` | `<ProseTh>` | [ProseTh.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseTh.vue) | Table Header |
| `td` | `<ProseTd>` | [ProseTd.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseTd.vue) | Table Data |
| `a` | `<ProseA>` | [ProseA.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseA.vue) | Anchor Link |
| `img` | `<ProseImg>` | [ProseImg.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseImg.vue) | Image |
| `em` | `<ProseEm>` | [ProseEm.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseEm.vue) | Emphasis |
| `strong` | `<ProseStrong>` | [ProseStrong.vue](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/components/prose/ProseStrong.vue) | Strong |

## Parsing Markdown

Nuxt MDC exposes a handy helper to parse MDC files. You can import the `parseMarkdown` function from `@nuxtjs/mdc/runtime` and use it to parse markdown files written with MDC syntax.

### Node.js

```ts
// server/api/parse-mdc.ts
import { parseMarkdown } from '@nuxtjs/mdc/runtime'

export default eventHandler(async () => {
  const mdc = [
    '# Hello MDC',
    '',
    '::alert',
    'This is an Alert',
    '::'
  ].join('\n')

  const ast = await parseMarkdown(mdc)

  return ast
})
```

### Browser

The `parseMarkdown` function is a universal helper, and you can also use it in the browser, for example inside a Vue component.

```vue
<script setup lang="ts">
import { parseMarkdown } from '@nuxtjs/mdc/runtime'

const { data: ast } = await useAsyncData('markdown', () => parseMarkdown('::alert\nMissing markdown input\n::'))
</script>

<template>
  <MDCRenderer :body="ast.body" :data="ast.data" />
</template>
```

### Options

The `parseMarkdown` helper also accepts options as the second argument to control the parser's behavior. (Checkout [`MDCParseOptions` interface↗︎](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/types/parser.ts)).

| Name | Default | Description |
| --  | -- | -- |
| `remark.plugins` | `{}` | Register / Configure parser's remark plugins. |
| `rehype.options` | `{}` | Configure `remark-rehype` options.  |
| `rehype.plugins` | `{}` | Register / Configure parser's rehype plugins. |
| `highlight` | `false` | Control whether code blocks should highlight or not. You can also provide a custom highlighter.  |
| `toc.depth` | `2` | Maximum heading depth to include in the table of contents.  |
| `toc.searchDepth` | `2` | Maximum depth of nested tags to search for heading. |

Checkout [`MDCParseOptions` types↗︎](https://github.com/nuxt-modules/mdc/blob/main/src/runtime/types/parser.ts).

## Configurations

You can configure the module by providing the `mdc` property in your `nuxt.config.js`; here are the default options:

```ts
import { defineNuxtConfig } from 'nuxt/config'

export default defineNuxtConfig({
  modules: ['@nuxtjs/mdc'],
  mdc: {
    remarkPlugins: {
      plugins: {
        // Register/Configure remark plugin to extend the parser
      }
    },
    rehypePlugins: {
      options: {
        // Configure rehype options to extend the parser
      },
      plugins: {
        // Register/Configure rehype plugin to extend the parser
      }
    },
    headings: {
      anchorLinks: {
        // Enable/Disable heading anchor links. { h1: true, h2: false }
      }
    },
    highlight: false, // Control syntax highlighting
    components: {
      prose: false, // Add predefined map to render Prose Components instead of HTML tags, like p, ul, code
      map: {
        // This map will be used in `<MDCRenderer>` to control rendered components
      }
    }
  }
})
```

Checkout [`ModuleOptions` types↗︎](https://github.com/nuxt-modules/mdc/blob/main/src/types.ts).

---

## Render nested async components

The `MDCRenderer` also supports rendering _nested_ async components by waiting for any child component in its tree to resolve its top-level `async setup()`.

This behavior allows for rendering asynchronous [MDC block components](https://content.nuxt.com/usage/markdown#block-components) (e.g. via `defineAsyncComponent`) as well as introducing components that themselves internally utilize the `MDCRenderer` to render markdown before allowing the parent component to resolve.

In order for the parent `MDCRenderer` component to properly wait for the child async component(s) to resolve:

1. All functionality in the child component **must** be executed within an async setup function with top-level `await` (if no async/await behavior is needed in the child, e.g. no data fetching, then the component will resolve normally).
2. The child component's `template` content **should** be wrapped with the built-in [`Suspense` component](https://vuejs.org/guide/built-ins/suspense.html#suspense) with the [`suspensible` prop](https://vuejs.org/guide/built-ins/suspense.html#nested-suspense) set to `true`.

    ```vue
    <template>
      <Suspense suspensible>
        <pre>{{ data }}</pre>
      </Suspense>
    </template>

    <script setup>
    const { data } = await useAsyncData(..., {
      immediate: true, // This is the default, but is required for this functionality
    })
    </script>
    ```

    In a Nuxt application, this means that setting `immediate: false` on any `useAsyncData` or `useFetch` calls would _prevent_ the parent `MDCRenderer` from waiting and the parent would potentially resolve before the child components have finished rendering, causing hydration errors or missing content.

### Simple Example: Async Component

Your nested MDC block components can utilize top-level `async setup()` as part of their lifecycle, such as awaiting data fetching before allowing the parent component to resolve.

See the code in the playground [`AsyncComponent` component](/playground/components/global/AsyncComponent.vue) as an example, and to see the behavior in action, check out the playground by running `pnpm dev` and navigating to the `/async-components` route.

### Advanced Example: MDC "snippets"

To demonstrate how powerful these nested async block components can be, you could allow users to define a subset of markdown documents in your project that will be utilized as reusable "snippets" in a parent document.

You would create a custom block component in your project that handles fetching the snippet markdown content from the API, use `parseMarkdown` to get the `ast` nodes, and render it in its own `MDC` or `MDCRenderer` component.

See the code in the playground [`PageSnippet` component](/playground/components/global/PageSnippet.vue) as an example, and to see the behavior in action, check out the playground by running `pnpm dev` and navigating to the `/async-components/advanced` route.

#### Handling recursion

If your project implements a "reusable snippet" type of approach, you will likely want to prevent the use of recursive snippets, whereby a nested `MDCRenderer` attempts to then load another child somewhere in its component tree with the same content (meaning, importing itself) and your application would be thrown into an infinite loop.

One way to get around this is to utilize Vue's [`provide/inject`](https://vuejs.org/guide/components/provide-inject.html#provide-inject) to pass down the history of rendered "snippets" so that a child can properly determine if it is being called recursively, and stop the chain. This can be used in combination with parsing the `ast` document nodes after calling the `parseMarkdown` function to strip out recursive node trees from the `ast` before rendering the content in the DOM.

For an example on how to prevent infinite loops and recursion with this pattern, please see the code in the playground's [`PageSnippet` component](/playground/components/global/PageSnippet.vue).

---

## Rendering in your Vue project

The `<MDCRenderer>` component in combination with a few exported package utilities may also be utilized inside a normal (non-Nuxt) Vue project.

To implement in your standard Vue project, follow the instructions below.

### Install the package

Follow the [install instructions above](#install), ignoring the step of adding the Nuxt module to a `nuxt.config.ts` file.

### Stub Nuxt module imports

Since you're not using Nuxt, you'll need to stub a few of the module's imports in your Vue projects's Vite config file. This is necessary to avoid errors when the module tries to access Nuxt-specific imports.

Create a new file in your Vue project's root directory, such as `stub-mdc-imports.js`, and add the following content:

```ts
// stub-mdc-imports.js
export default {}
```

Next, update your Vue project's Vite config file (e.g. `vite.config.ts`) to alias the module's imports to the stub file:

```ts
import { defineConfig } from 'vite'
import path from 'path'

export default defineConfig({
  resolve: {
    alias: {
      '#mdc-imports': path.resolve(__dirname, './stub-mdc-imports.js'),
      '#mdc-configs': path.resolve(__dirname, './stub-mdc-imports.js'),
    }
  }
})
```

### Usage

Next, let's create a new [Vue composable](https://vuejs.org/guide/reusability/composables.html) to handle parsing the markdown content, as well as adding syntax highlighting to code blocks with [Shiki](https://shiki.style/).

```ts
// composables/useMarkdownParser.ts
// Import package exports
import {
  createMarkdownParser,
  rehypeHighlight,
  createShikiHighlighter,
} from '@nuxtjs/mdc/runtime'
// Import desired Shiki themes and languages
import MaterialThemePalenight from 'shiki/themes/material-theme-palenight.mjs'
import HtmlLang from 'shiki/langs/html.mjs'
import MdcLang from 'shiki/langs/mdc.mjs'
import TsLang from 'shiki/langs/typescript.mjs'
import VueLang from 'shiki/langs/vue.mjs'
import ScssLang from 'shiki/langs/scss.mjs'
import YamlLang from 'shiki/langs/yaml.mjs'

export default function useMarkdownParser() {
  let parser: Awaited<ReturnType<typeof createMarkdownParser>>

  const parse = async (markdown: string) => {
    if (!parser) {
      parser = await createMarkdownParser({
        rehype: {
          plugins: {
            highlight: {
              instance: rehypeHighlight,
              options: {
                // Pass in your desired theme(s)
                theme: 'material-theme-palenight',
                // Create the Shiki highlighter
                highlighter: createShikiHighlighter({
                  bundledThemes: {
                    'material-theme-palenight': MaterialThemePalenight,
                  },
                  // Configure the bundled languages
                  bundledLangs: {
                    html: HtmlLang,
                    mdc: MdcLang,
                    vue: VueLang,
                    yml: YamlLang,
                    scss: ScssLang,
                    ts: TsLang,
                    typescript: TsLang,
                  },
                }),
              },
            },
          },
        },
      })
    }
    return parser(markdown)
  }

  return parse
}
```

Now import the `useMarkdownParser` composable we just created along with an exported type interface into your host project's Vue component, and utilize them to process the raw markdown and initialize the `<MDCRenderer>` component.

```vue
<script setup lang="ts">
import { onBeforeMount, ref, watch } from 'vue'
// Import package exports
import MDCRenderer from '@nuxtjs/mdc/runtime/components/MDCRenderer.vue'
import type { MDCParserResult } from '@nuxtjs/mdc'
import useMarkdownParser from './composables/useMarkdownParser';

const md = ref(`
# Just a Vue app

This is markdown content rendered via the \`<MDCRenderer>\` component, including MDC below.

::alert
Hello MDC
::

\`\`\`ts
const a = 1;
\`\`\`
`);

const ast = ref<MDCParserResult | null>(null)
const parse = useMarkdownParser()

onBeforeMount(async () => {
  ast.value = await parse(md.value)
})
</script>

<template>
  <Suspense>
    <MDCRenderer v-if="ast?.body" :body="ast.body" :data="ast.data" />
  </Suspense>
</template>
```

---

## Contributing

You can dive into this module online using StackBlitz:

[![Edit @nuxtjs/mdc](https://developer.stackblitz.com/img/open_in_stackblitz.svg)](https://stackblitz.com/github/nuxt-modules/mdc?file=playground%2Fapp.vue)

Or locally:

1. Clone this repository
2. Install dependencies using `pnpm install`
3. Start the development server using `pnpm dev`

## License

[MIT License](https://github.com/nuxt-modules/mdc/blob/main/LICENSE)

Copyright (c) NuxtLabs

<!-- Badges -->
[npm-version-src]: https://img.shields.io/npm/v/@nuxtjs/mdc/latest.svg?style=flat&colorA=18181B&colorB=28CF8D
[npm-version-href]: https://npmjs.com/package/@nuxtjs/mdc

[npm-downloads-src]: https://img.shields.io/npm/dm/@nuxtjs/mdc.svg?style=flat&colorA=18181B&colorB=28CF8D
[npm-downloads-href]: https://npmjs.com/package/@nuxtjs/mdc

[license-src]: https://img.shields.io/github/license/nuxt-modules/mdc.svg?style=flat&colorA=18181B&colorB=28CF8D
[license-href]: https://github.com/nuxt-modules/mdc/blob/main/LICENSE


[nuxt-src]: https://img.shields.io/badge/Nuxt-18181B?logo=nuxt.js
[nuxt-href]: https://nuxt.com
