<template>
  <div :class="ui.root({ class: [props.ui?.root], filename: !!filename })">
    <div v-if="filename && !hideHeader" :class="ui.header({ class: props.ui?.header })">
      <span 
        v-if="language === 'php'" 
        class="inline-flex items-center justify-center h-5 px-1.5 text-xs font-bold text-blue-700 dark:text-blue-200 bg-blue-100 dark:bg-blue-900/30 rounded border border-blue-300 dark:border-blue-700"
      >
        PHP
      </span>
      <UIcon v-else :name="icon || getFileTypeIcon(filename)" :class="ui.icon({ class: props.ui?.icon })" />

      <span :class="ui.filename({ class: props.ui?.filename })">{{ filename }}</span>
    </div>

    <UButton
      :icon="copied ? appConfig.ui.icons.copyCheck : appConfig.ui.icons.copy"
      color="neutral"
      variant="outline"
      size="sm"
      :aria-label="copied ? 'Copied!' : 'Copy'"
      :label="copied ? 'Copied' : 'Copy'"
      :class="ui.copy({ class: props.ui?.copy })"
      tabindex="-1"
      @click="copyCode"
    />

    <pre :class="ui.base({ class: [props.ui?.base, props.class] })" v-bind="$attrs"><slot /></pre>
  </div>
</template>

<script setup>
import theme from "#build/ui/prose/pre";
import { computed } from "vue";
import { useClipboard } from "@vueuse/core";
import { useAppConfig } from "#imports";
import { tv } from "tailwind-variants";

const props = defineProps({
  icon: { type: [String, Object], required: false },
  code: { type: String, required: false },
  language: { type: String, required: false },
  filename: { type: String, required: false },
  hideHeader: { type: Boolean, required: false },
  class: { type: null, required: false },
  ui: { type: null, required: false }
});
defineSlots();

const { copy, copied } = useClipboard();
const appConfig = useAppConfig();
const ui = computed(() => tv({ extend: tv(theme), ...appConfig.ui?.prose?.pre || {} })());

const getFileTypeIcon = (filename) => {
  if (!filename) return null;
  
  const cleanFilename = filename.replace(/\s*\(.*\)\s*$/, "");
  const extension = cleanFilename.includes(".") && cleanFilename.split(".").pop();
  
  return extension ? `i-vscode-icons-file-type-${extension}` : null;
};

const copyCode = async () => {
  await copy(props.code || '');
};
</script>