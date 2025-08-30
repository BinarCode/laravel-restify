<script>
import { defineComponent, h, useSlots } from "vue";
import ContentQuery from "./ContentQuery.vue";
const emptyNode = (slot, data) => h("pre", null, JSON.stringify({ message: "You should use slots with <ContentList>", slot, data }, null, 2));
const ContentList = defineComponent({
  name: "ContentList",
  props: {
    /**
     * Query props
     */
    /**
     * The path of the content to load from content source.
     * @default '/'
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
    }
  },
  /**
   * Content empty fallback
   * @slot empty
   */
  /**
   * Content not found fallback
   * @slot not-found
   */
  render(ctx) {
    const slots = useSlots();
    const { path, query } = ctx;
    const contentQueryProps = {
      ...query || {},
      path: path || query?.path || "/"
    };
    return h(
      ContentQuery,
      contentQueryProps,
      {
        // Default slot
        default: slots?.default ? ({ data, refresh, isPartial }) => slots.default({ list: data, refresh, isPartial, ...this.$attrs }) : (bindings) => emptyNode("default", bindings.data),
        // Empty slot
        empty: (bindings) => slots?.empty ? slots.empty(bindings) : emptyNode("default", bindings?.data),
        // Not Found slot
        "not-found": (bindings) => slots?.["not-found"] ? slots?.["not-found"]?.(bindings) : emptyNode("not-found", bindings?.data)
      }
    );
  }
});
export default ContentList;
</script>
