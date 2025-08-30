import { defineAsyncComponent, defineComponent, h, hydrateOnIdle, hydrateOnInteraction, hydrateOnMediaQuery, hydrateOnVisible, mergeProps, watch } from "vue";
import { useNuxtApp } from "#app/nuxt";
function defineLazyComponent(props, defineStrategy) {
  return (id, loader) => defineComponent({
    inheritAttrs: false,
    props,
    emits: ["hydrated"],
    setup(props2, ctx) {
      if (import.meta.server) {
        const nuxtApp = useNuxtApp();
        nuxtApp.hook("app:rendered", ({ ssrContext }) => {
          ssrContext.modules.delete(id);
        });
      }
      const child = defineAsyncComponent({ loader });
      const comp = defineAsyncComponent({
        hydrate: defineStrategy(props2),
        loader: () => Promise.resolve(child)
      });
      const onVnodeMounted = () => {
        ctx.emit("hydrated");
      };
      return () => h(comp, mergeProps(ctx.attrs, { onVnodeMounted }), ctx.slots);
    }
  });
}
export const createLazyVisibleComponent = defineLazyComponent(
  {
    hydrateOnVisible: {
      type: [Object, Boolean],
      required: false
    }
  },
  (props) => hydrateOnVisible(props.hydrateOnVisible === true ? void 0 : props.hydrateOnVisible)
);
export const createLazyIdleComponent = defineLazyComponent(
  {
    hydrateOnIdle: {
      type: [Number, Boolean],
      required: true
    }
  },
  (props) => props.hydrateOnIdle === 0 ? void 0 : hydrateOnIdle(props.hydrateOnIdle === true ? void 0 : props.hydrateOnIdle)
);
const defaultInteractionEvents = ["pointerenter", "click", "focus"];
export const createLazyInteractionComponent = defineLazyComponent(
  {
    hydrateOnInteraction: {
      type: [String, Array],
      required: false,
      default: defaultInteractionEvents
    }
  },
  (props) => hydrateOnInteraction(props.hydrateOnInteraction === true ? defaultInteractionEvents : props.hydrateOnInteraction || defaultInteractionEvents)
);
export const createLazyMediaQueryComponent = defineLazyComponent(
  {
    hydrateOnMediaQuery: {
      type: String,
      required: true
    }
  },
  (props) => hydrateOnMediaQuery(props.hydrateOnMediaQuery)
);
export const createLazyIfComponent = defineLazyComponent(
  {
    hydrateWhen: {
      type: Boolean,
      default: true
    }
  },
  (props) => props.hydrateWhen ? void 0 : (hydrate) => {
    const unwatch = watch(() => props.hydrateWhen, () => hydrate(), { once: true });
    return () => unwatch();
  }
);
export const createLazyTimeComponent = defineLazyComponent(
  {
    hydrateAfter: {
      type: Number,
      required: true
    }
  },
  (props) => props.hydrateAfter === 0 ? void 0 : (hydrate) => {
    const id = setTimeout(hydrate, props.hydrateAfter);
    return () => clearTimeout(id);
  }
);
const hydrateNever = /* @__NO_SIDE_EFFECTS__ */ () => {
};
export const createLazyNeverComponent = defineLazyComponent(
  {
    hydrateNever: {
      type: Boolean,
      required: true
    }
  },
  () => hydrateNever
);
