<template>
  <slot
    :data="data?.data"
    :body="data?.body"
    :toc="data?.toc"
    :excerpt="data?.excerpt"
    :error="error"
  >
    <MDCRenderer
      v-if="body"
      :tag="props.tag"
      :class="props.class"
      :body="body"
      :data="data?.data"
      :unwrap="props.unwrap"
    />
  </slot>
</template>

<script setup lang="ts">
import { hash } from 'ohash'
import { useAsyncData } from 'nuxt/app'
import { watch, computed, type PropType } from 'vue'
import type { MDCParseOptions } from '@nuxtjs/mdc'
import { parseMarkdown } from '../parser'

const props = defineProps({
  tag: {
    type: [String, Boolean],
    default: 'div'
  },
  /**
   * Raw markdown string or parsed markdown object from `parseMarkdown`
   */
  value: {
    type: [String, Object],
    required: true
  },
  /**
   * Render only the excerpt
   */
  excerpt: {
    type: Boolean,
    default: false
  },
  /**
   * Options for `parseMarkdown`
   */
  parserOptions: {
    type: Object as PropType<MDCParseOptions>,
    default: () => ({})
  },
  /**
   * Class to be applied to the root element
   */
  class: {
    type: [String, Array, Object],
    default: ''
  },
  /**
   * Tags to unwrap separated by spaces
   * Example: 'ul li'
   */
  unwrap: {
    type: [Boolean, String],
    default: false
  }
})

const key = computed(() => hash(props.value))

const { data, refresh, error } = await useAsyncData(key.value, async () => {
  if (typeof props.value !== 'string') {
    return props.value
  }
  return await parseMarkdown(props.value, props.parserOptions)
})

const body = computed(() => props.excerpt ? data.value?.excerpt : data.value?.body)

watch(() => props.value, () => {
  refresh()
})
</script>
