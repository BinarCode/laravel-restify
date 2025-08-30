<script>
import { defineComponent, h, useSlots } from "vue";
import { withTrailingSlash } from "ufo";
import ContentRenderer from "./ContentRenderer.vue";
import ContentQuery from "./ContentQuery.vue";
import { useRuntimeConfig, useRoute, useContentHead } from "#imports";
const ContentDoc = defineComponent({
  name: "ContentDoc",
  props: {
    /**
     * Renderer props
     */
    /**
     * The tag to use for the renderer element if it is used.
     * @default 'div'
     */
    tag: {
      type: String,
      required: false,
      default: "div"
    },
    /**
     * Whether or not to render the excerpt.
     * @default false
     */
    excerpt: {
      type: Boolean,
      default: false
    },
    /**
     * Query props
     */
    /**
     * The path of the content to load from content source.
     * @default useRoute().path
     */
    path: {
      type: String,
      required: false,
      default: void 0
    },
    /**
     * A query builder params object to be passed to <ContentQuery /> component.
     */
    query: {
      type: Object,
      required: false,
      default: void 0
    },
    /**
     * Whether or not to map the document data to the `head` property.
     */
    head: {
      type: Boolean,
      required: false,
      default: void 0
    }
  },
  /**
   * Document empty fallback
   * @slot empty
   */
  /**
   * Document not found fallback
   * @slot not-found
   */
  render(ctx) {
    const { contentHead } = useRuntimeConfig().public.content;
    const slots = useSlots();
    const { tag, excerpt, path, query, head } = ctx;
    const shouldInjectContentHead = head === void 0 ? contentHead : head;
    const contentQueryProps = {
      ...query || {},
      path: path || query?.path || withTrailingSlash(useRoute().path),
      find: "one"
    };
    const emptyNode = (slot, data) => h("pre", null, JSON.stringify({ message: "You should use slots with <ContentDoc>", slot, data }, null, 2));
    return h(
      ContentQuery,
      contentQueryProps,
      {
        // Default slot
        default: slots?.default ? ({ data, refresh, isPartial }) => {
          if (shouldInjectContentHead) {
            useContentHead(data);
          }
          return slots.default?.({ doc: data, refresh, isPartial, excerpt, ...this.$attrs });
        } : ({ data }) => {
          if (shouldInjectContentHead) {
            useContentHead(data);
          }
          return h(
            ContentRenderer,
            { value: data, excerpt, tag, ...this.$attrs },
            // Forward local `empty` slots to ContentRenderer if it is used.
            { empty: (bindings) => slots?.empty ? slots.empty(bindings) : emptyNode("default", data) }
          );
        },
        // Empty slot
        empty: (bindings) => slots?.empty?.(bindings) || h("p", null, "Document is empty, overwrite this content with #empty slot in <ContentDoc>."),
        // Not Found slot
        "not-found": (bindings) => slots?.["not-found"]?.(bindings) || h("p", null, "Document not found, overwrite this content with #not-found slot in <ContentDoc>.")
      }
    );
  }
});
export default ContentDoc;
</script>
