import { withKnobs, number, text, boolean } from "@storybook/addon-knobs";
import ContentContainer from "./ContentContainer";
import BrowserShot from "../global-components/BrowserShot";

export default {
  title: "global-components/BrowserShot",
  decorators: [withKnobs],
  component: BrowserShot
};

export const External = () => ({
  components: { BrowserShot, ContentContainer },
  props: {
    url: { default: text("URL", "https://google.com") },
    link: { default: boolean("Use Anchor Link?", true) },
    cleanUrl: { default: boolean("Clean URL?", true) },
    caption: {
      default: text("Caption", "This is an optional caption for the image.")
    },
    maxHeight: { default: number("Max Height", 0) }
  },
  methods: {},
  template: `
    <ContentContainer>
      <BrowserShot :url="url" :link="link" :clean-url="cleanUrl" :caption="caption" :max-height="maxHeight">
        <img src="https://placekitten.com/900/500" alt="adorable kitten image" />
      </BrowserShot>
    </ContentContainer>
  `
});

export const NoLink = () => ({
  components: { BrowserShot, ContentContainer },
  props: {
    url: { default: text("URL", "https://google.com") },
    link: { default: boolean("Use Anchor Link?", false) },
    cleanUrl: { default: boolean("Clean URL?", true) },
    caption: {
      default: text("Caption", "This is an optional caption for the image.")
    },
    maxHeight: { default: number("Max Height", 0) }
  },
  methods: {},
  template: `
    <ContentContainer>
      <BrowserShot :url="url" :link="link" :clean-url="cleanUrl" :caption="caption" :max-height="maxHeight">
        <img src="https://placekitten.com/900/500" alt="adorable kitten image" />
      </BrowserShot>
    </ContentContainer>
  `
});

export const WithMaxHeight = () => ({
  components: { BrowserShot, ContentContainer },
  props: {
    url: { default: text("URL", "https://google.com") },
    link: { default: boolean("Use Anchor Link?", false) },
    cleanUrl: { default: boolean("Clean URL?", true) },
    caption: {
      default: text("Caption", "This is an optional caption for the image.")
    },
    maxHeight: { default: number("Max Height", 350) }
  },
  methods: {},
  template: `
    <ContentContainer>
      <BrowserShot :url="url" :link="link" :clean-url="cleanUrl" :caption="caption" :max-height="maxHeight">
        <img src="https://placekitten.com/900/500" alt="adorable kitten image" />
      </BrowserShot>
    </ContentContainer>
  `
});
