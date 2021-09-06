import { withKnobs, select } from "@storybook/addon-knobs";
import ContentContainer from "./ContentContainer.vue";

export default {
  title: "content/Custom Blocks",
  decorators: [withKnobs]
};

export const Tip = () => ({
  components: { ContentContainer },
  props: {
    type: {
      default: select("Type", ["tip", "warning", "danger"], "tip")
    }
  },
  template: `<ContentContainer :vertical-center="true">
      <div class="custom-block" :class="type">
        <p>
          Craft’s Control Panel browser requirements have nothing to do with your
          actual website. If you’re a glutton for punishment and want your website to
          look flawless on IE 6, that’s your choice.
        </p>
      </div>
    </ContentContainer>`
});

export const Warning = () => ({
  components: { ContentContainer },
  props: {
    type: {
      default: select("Type", ["tip", "warning", "danger"], "warning")
    }
  },
  template: `<ContentContainer :vertical-center="true">
      <div class="custom-block" :class="type">
        <p>
          Step at least thirty feet away after lighting the fuse, ideally behind some sort of thick, solid barrier.
        </p>
      </div>
    </ContentContainer>`
});

export const Danger = () => ({
  components: { ContentContainer },
  props: {
    type: {
      default: select("Type", ["tip", "warning", "danger"], "danger")
    }
  },
  template: `<ContentContainer :vertical-center="true">
      <div class="custom-block" :class="type">
        <p>
          Disconnect the saw from its power source before attempting to service the blade.
        </p>
      </div>
    </ContentContainer>`
});
