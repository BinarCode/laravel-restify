# Remark MDC

[![npm version][npm-version-src]][npm-version-href]
[![npm downloads][npm-downloads-src]][npm-downloads-href]
[![License][license-src]][license-href]

Remark plugin to parse Markdown Components syntax.

For MDC syntax highlight on VS Code, checkout [vscode-mdc](https://github.com/nuxtlabs/vscode-mdc).

## Setup

Add the `remark-mdc` dependency to your project:

```bash
# yarn
yarn add --dev remark-mdc
# npm
npm install --save-dev remark-mdc
# pnpm
pnpm add --save-dev remark-mdc
```

Then, add `remark-mdc` to the `unified` streams:

```ts 
import { unified } from 'unified'
import remarkParse from 'remark-parse'
import remarkMDC from 'remark-mdc'

function parse(md: string) {
  const processor = unified()
  processor.use(remarkParse)

  // Use `remark-mdc` plugin to parse MDC syntax
  processor.use(remarkMDC)

  // ...

  return processor.process({ value: content, data: frontmatter })
}
```

That's it! ✨

## Syntax

### `^-` Frontmatter

Front-matter is a convention of Markdown-based CMS to provide metadata to documents, like description or title. Remark MDC uses the YAML syntax with `key: value` pairs.

To define frontmatter, start your document with `---\n---` section and put your desired data in YAML format within this section.

```md
---
title: 'Title of the page'
description: 'meta description of the page'
---

<!-- Content of the page -->
```

### `:` Inline Components

Inline components are entries that will stick inside the parent paragraph. Like spans, emojis, icons, etc. Inline components can be defined by a single `:` followed by the component name.

```md
A simple :inline-component
```

You may want to pass some text into an inline component; you can do it using the `[TEXT]` syntax.

```md
A simple :inline-component[John Doe]
```

If you want to use an inline component followed by specific characters like `-`, `_`, or `:`, you can use a dummy props specifier after it.

```md
How to say :hello{}-world in Markdown
```

In this example, `:hello{}` will search for the `<Hello />` component, and `-world` will be plain text.

> Note: If you put an inline component alone in a single line, it will be transformed into a block component. This is sugar syntax for block components.
> ```md
> Paragraph a
> 
> :block-component
> 
> Paragraph b
> ```


### `::` Block Components

Block components are components that accept Markdown content or another component as a slot.

Block components are defined by the `::` identifier.

```md
::card
The content of the card
::
```

Block components can be used without any content.

```md
::card
::
```

Or with sugar syntax. Note that in sugar syntax, it is important to put the component alone on a separate line.

```md
A paragraph

:card
```

### `#` Slots

Block components can accept slots (like Vue slots) with different names. The content of these slots can be anything from a normal markdown paragraph to a nested block component.

- The `default` slot renders the top-level content inside the block component.
- Named slots use the `#` identifier to render the corresponding content.

```md
::hero
Default slot text

#description
This will be rendered inside the `description` slot.
```

### `:::` Nesting

MDC supports nested components inside slots by indenting them. To make nested components visually distinguishable, you can indent nested components and add more `:` when you define them.

```md
::hero
  :::card
    A nested card

    ::::card
      A super nested card
    ::::
  :::
::
```

### `[]` Span

To create inline spans in your text, you can use the `[]` identifier.

```md
Hello [World]
```

This syntax is useful in combination with inline props to make text visually different from the rest of the paragraph. Check out the inline props section to read more about props.

```md
Hello [World]{.bg-blue-500}!
```

### `{}` Inline Props

Using the inline props syntax, you can pass props and attributes to your components. MDC goes a step further and allows you to pass attributes to markdown native elements like images, links, bold texts, and more.

To define properties for a component or a markdown element, you need to create a props scope `{}` exactly after the component/element definition. Then you can define the properties inline within this scope using a `key=value` syntax.

```md
Inline :component{key="value" key2=value2}

::block-component{no-border title="My Component"}
::

[Link](https://nuxt.com){class="nuxt"}

![Nuxt Logo](https://nuxt.com/assets/design-kit/logo/icon-green.svg){class=".nuxt-logo"}

`code`{style="color: red"}

_italic_{style="color: blue"}

**bold**{style="color: blue"}
```

There are also a couple of sugar syntaxes for common use-cases:

- `id` attribute: `_italic_{#the_italic_text}`
- `class` attribute: `**bold**{.bold .text.with_attribute}`
- No value (boolean props): `:component{no-border}`
- Single string without any space: `**bold**{class=red}`

If you want to pass arrays or objects as props to components, you can pass them as a JSON string and prefix the prop key with a colon to automatically decode the JSON string. Note that in this case, you should use single quotes for the value string so you can use double quotes to pass a valid JSON string:

```md
::dropdown{:items='["Nuxt", "Vue", "React"]'}
String Array
::

::dropdown{:items='[1,2,3.5]'}
Number Array
::

::chart{:options='{"responsive": true, "scales": {"y": {"beginAtZero": true}}}'}
Object
::
```

> **Escape Character Behavior:** In MDC, the escape character (backslash `\`) can be used to escape special characters within attribute values. However, this behavior only applies to bound attributes. For example, in a bound attribute like `:list="[\"item 1\"]"`, the escape character will allow you to include quotes within the value. In contrast, for non-bound attributes such as `class="my-class"`, the escape character will not have any effect, and the value will be rendered as is. Therefore, it's important to use binding when you need to include special characters in attribute values.

### `---` Yaml Props

The YAML method uses the `---` identifier to declare one prop per line, which can be useful for readability.

```md
::icon-card
---
icon: IconNuxt
description: Harness the full power of Nuxt and the Nuxt ecosystem.
title: Nuxt Architecture.
---
::
```

### `{{}}` Binding Variables

The `{{ $doc.variable || 'defaultValue' }}` syntax allows you to bind variables in your Markdown content. This is especially useful when you want to dynamically insert values into your document.

To use this syntax, simply enclose the variable name within double curly braces, like so:

```md
---
color: blue
---

# The color is {{ $doc.color || 'red' }}.
```

## Contributing

You can contribute to this module online with CodeSandbox:

[![Edit remark-mdc](https://codesandbox.io/static/img/play-codesandbox.svg)](https://codesandbox.io/s/github/nuxtlabs/remark-mdc/tree/main/?fontsize=14&hidenavigation=1&theme=dark)

Or locally:

1. Clone this repository
2. Install dependencies using `pnpm install`
3. Start the development server using `pnpm dev`

## License

[MIT License](https://github.com/nuxtlabs/remark-mdc/blob/main/LICENSE)

Copyright (c) NuxtLabs


<!-- Badges -->
[npm-version-src]: https://img.shields.io/npm/v/remark-mdc/latest.svg?style=flat&colorA=020420&colorB=28CF8D
[npm-version-href]: https://npmjs.com/package/remark-mdc

[npm-downloads-src]: https://img.shields.io/npm/dm/remark-mdc.svg?style=flat&colorA=020420&colorB=28CF8D
[npm-downloads-href]: https://npmjs.com/package/remark-mdc

[license-src]: https://img.shields.io/github/license/nuxtlabs/remark-mdc.svg?style=flat&colorA=020420&colorB=28CF8D
[license-href]: https://github.com/nuxtlabs/remark-mdc/blob/main/LICENSE
