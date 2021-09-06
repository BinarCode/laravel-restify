<script>
export default {
  functional: true,
  props: {
    type: {
      type: String,
      default: "tip",
    },
    title: String,
    text: String,
    vertical: {
      type: String,
      default: "top",
    },
  },
  render(h, { props, slots }) {
    return h(
      "span",
      {
        class: ["badge", props.type],
        style: {
          verticalAlign: props.vertical,
        },
        attrs: {
          title: props.title,
        },
      },
      props.text || slots().default
    );
  },
};
</script>

<style lang="postcss" scoped>
.badge {
  @apply inline-block text-white rounded-sm py-0 px-1;
  font-size: 14px;
  height: 18px;
  line-height: 18px;
  background-color: #42b983;

  &.tip,
  &.green {
    background-color: #42b983;
  }

  &.error {
    background-color: #da5961;
  }

  &.warning,
  &.warn,
  &.yellow {
    background-color: #e7c000;
  }

  &.verb {
    @apply uppercase bg-transparent tracking-widest leading-none h-auto font-mono;
    font-size: smaller;
    border-width: 1px;
    border-color: var(--border-color);
    color: var(--sidebar-link-color);
    padding: 4px calc(5px - 0.1em) 4px 5px;
  }

  &.edition {
    @apply uppercase bg-transparent text-xs tracking-widest leading-none h-auto text-center font-medium;
    border-width: 1px;
    border-color: var(--text-color);
    color: var(--text-color);
    padding: 1px 0.175rem 1px 0.25rem;
  }

  & + & {
    margin-left: 5px;
  }
}
</style>
