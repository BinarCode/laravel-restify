import { existsSync, promises } from 'node:fs';
import process from 'node:process';
import { defineCommand } from 'citty';
import { resolve, extname, dirname } from 'pathe';
import { l as loadKit } from '../shared/cli.qKvs7FJ2.mjs';
import { l as logger } from '../shared/cli.B9AmABr3.mjs';
import { pascalCase, camelCase } from 'scule';
import { a as logLevelArgs, c as cwdArgs } from '../shared/cli.CTXRG5Cu.mjs';
import 'node:url';
import 'exsolve';
import 'consola';
import 'node:path';
import 'std-env';

const httpMethods = [
  "connect",
  "delete",
  "get",
  "head",
  "options",
  "post",
  "put",
  "trace",
  "patch"
];
const api = ({ name, args, nuxtOptions }) => {
  return {
    path: resolve(nuxtOptions.srcDir, nuxtOptions.serverDir, `api/${name}${applySuffix(args, httpMethods, "method")}.ts`),
    contents: `
export default defineEventHandler(event => {
  return 'Hello ${name}'
})
`
  };
};

const app = ({ args, nuxtOptions }) => ({
  path: resolve(nuxtOptions.srcDir, "app.vue"),
  contents: args.pages ? `
<script setup lang="ts"><\/script>

<template>
  <div>
    <NuxtLayout>
      <NuxtPage/>
    </NuxtLayout>
  </div>
</template>

<style scoped></style>
` : `
<script setup lang="ts"><\/script>

<template>
  <div>
    <h1>Hello World!</h1>
  </div>
</template>

<style scoped></style>
`
});

const appConfig = ({ nuxtOptions }) => ({
  path: resolve(nuxtOptions.srcDir, "app.config.ts"),
  contents: `
export default defineAppConfig({})
`
});

const component = ({ name, args, nuxtOptions }) => ({
  path: resolve(nuxtOptions.srcDir, `components/${name}${applySuffix(
    args,
    ["client", "server"],
    "mode"
  )}.vue`),
  contents: `
<script setup lang="ts"><\/script>

<template>
  <div>
    Component: ${name}
  </div>
</template>

<style scoped></style>
`
});

const composable = ({ name, nuxtOptions }) => {
  const nameWithoutUsePrefix = name.replace(/^use-?/, "");
  const nameWithUsePrefix = `use${pascalCase(nameWithoutUsePrefix)}`;
  return {
    path: resolve(nuxtOptions.srcDir, `composables/${name}.ts`),
    contents: `
export const ${nameWithUsePrefix} = () => {
  return ref()
}
    `
  };
};

const error = ({ nuxtOptions }) => ({
  path: resolve(nuxtOptions.srcDir, "error.vue"),
  contents: `
<script setup lang="ts">
import type { NuxtError } from '#app'

const props = defineProps({
  error: Object as () => NuxtError
})
<\/script>

<template>
  <div>
    <h1>{{ error.statusCode }}</h1>
    <NuxtLink to="/">Go back home</NuxtLink>
  </div>
</template>

<style scoped></style>
`
});

const layer = ({ name, nuxtOptions }) => {
  return {
    path: resolve(nuxtOptions.rootDir, `layers/${name}/nuxt.config.ts`),
    contents: `
export default defineNuxtConfig({})
`
  };
};

const layout = ({ name, nuxtOptions }) => ({
  path: resolve(nuxtOptions.srcDir, nuxtOptions.dir.layouts, `${name}.vue`),
  contents: `
<script setup lang="ts"><\/script>

<template>
  <div>
    Layout: ${name}
    <slot />
  </div>
</template>

<style scoped></style>
`
});

const middleware = ({ name, args, nuxtOptions }) => ({
  path: resolve(nuxtOptions.srcDir, nuxtOptions.dir.middleware, `${name}${applySuffix(args, ["global"])}.ts`),
  contents: `
export default defineNuxtRouteMiddleware((to, from) => {})
`
});

const module = ({ name, nuxtOptions }) => ({
  path: resolve(nuxtOptions.rootDir, "modules", `${name}.ts`),
  contents: `
import { defineNuxtModule } from 'nuxt/kit'

export default defineNuxtModule({
  meta: {
    name: '${name}'
  },
  setup () {}
})
`
});

const page = ({ name, nuxtOptions }) => ({
  path: resolve(nuxtOptions.srcDir, nuxtOptions.dir.pages, `${name}.vue`),
  contents: `
<script setup lang="ts"><\/script>

<template>
  <div>
    Page: ${name}
  </div>
</template>

<style scoped></style>
`
});

const plugin = ({ name, args, nuxtOptions }) => ({
  path: resolve(nuxtOptions.srcDir, nuxtOptions.dir.plugins, `${name}${applySuffix(args, ["client", "server"], "mode")}.ts`),
  contents: `
export default defineNuxtPlugin(nuxtApp => {})
  `
});

const serverMiddleware = ({ name, nuxtOptions }) => ({
  path: resolve(nuxtOptions.srcDir, nuxtOptions.serverDir, "middleware", `${name}.ts`),
  contents: `
export default defineEventHandler(event => {})
`
});

const serverPlugin = ({ name, nuxtOptions }) => ({
  path: resolve(nuxtOptions.srcDir, nuxtOptions.serverDir, "plugins", `${name}.ts`),
  contents: `
export default defineNitroPlugin(nitroApp => {})
`
});

const serverRoute = ({ name, args, nuxtOptions }) => ({
  path: resolve(nuxtOptions.srcDir, nuxtOptions.serverDir, args.api ? "api" : "routes", `${name}.ts`),
  contents: `
export default defineEventHandler(event => {})
`
});

const serverUtil = ({ name, nuxtOptions }) => ({
  path: resolve(nuxtOptions.srcDir, nuxtOptions.serverDir, "utils", `${name}.ts`),
  contents: `
export function ${camelCase(name)}() {}
`
});

const templates = {
  "api": api,
  "app": app,
  "app-config": appConfig,
  "component": component,
  "composable": composable,
  "error": error,
  "layer": layer,
  "layout": layout,
  "middleware": middleware,
  "module": module,
  "page": page,
  "plugin": plugin,
  "server-middleware": serverMiddleware,
  "server-plugin": serverPlugin,
  "server-route": serverRoute,
  "server-util": serverUtil
};
function applySuffix(args, suffixes, unwrapFrom) {
  let suffix = "";
  for (const s of suffixes) {
    if (args[s]) {
      suffix += `.${s}`;
    }
  }
  if (unwrapFrom && args[unwrapFrom] && suffixes.includes(args[unwrapFrom])) {
    suffix += `.${args[unwrapFrom]}`;
  }
  return suffix;
}

const templateNames = Object.keys(templates);
const add = defineCommand({
  meta: {
    name: "add",
    description: "Create a new template file."
  },
  args: {
    ...cwdArgs,
    ...logLevelArgs,
    force: {
      type: "boolean",
      description: "Force override file if it already exists",
      default: false
    },
    template: {
      type: "positional",
      required: true,
      valueHint: templateNames.join("|"),
      description: `Specify which template to generate`
    },
    name: {
      type: "positional",
      required: true,
      description: "Specify name of the generated file"
    }
  },
  async run(ctx) {
    const cwd = resolve(ctx.args.cwd);
    const templateName = ctx.args.template;
    if (!templateNames.includes(templateName)) {
      logger.error(
        `Template ${templateName} is not supported. Possible values: ${Object.keys(
          templates
        ).join(", ")}`
      );
      process.exit(1);
    }
    const ext = extname(ctx.args.name);
    const name = ext === ".vue" || ext === ".ts" ? ctx.args.name.replace(ext, "") : ctx.args.name;
    if (!name) {
      logger.error("name argument is missing!");
      process.exit(1);
    }
    const kit = await loadKit(cwd);
    const config = await kit.loadNuxtConfig({ cwd });
    const template = templates[templateName];
    const res = template({ name, args: ctx.args, nuxtOptions: config });
    if (!ctx.args.force && existsSync(res.path)) {
      logger.error(
        `File exists: ${res.path} . Use --force to override or use a different name.`
      );
      process.exit(1);
    }
    const parentDir = dirname(res.path);
    if (!existsSync(parentDir)) {
      logger.info("Creating directory", parentDir);
      if (templateName === "page") {
        logger.info("This enables vue-router functionality!");
      }
      await promises.mkdir(parentDir, { recursive: true });
    }
    await promises.writeFile(res.path, `${res.contents.trim()}
`);
    logger.info(`\u{1FA84} Generated a new ${templateName} in ${res.path}`);
  }
});

export { add as default };
