import { withKnobs, object } from "@storybook/addon-knobs";
import MetaDetails from "../global-components/MetaDetails";
import ContentContainer from "./ContentContainer";

export default {
  title: "global-components/MetaDetails",
  decorators: [withKnobs]
};

const exampleItems = [
  {
    label: "Read Time",
    value: "5 minutes"
  },
  {
    label: "Skill Level",
    value: "Advanced"
  },
  {
    label: "Edition",
    value: "Craft Pro"
  }
];

export const Default = () => ({
  components: { MetaDetails, ContentContainer },
  props: {
    items: { default: object("Items", exampleItems) }
  },
  template: `<ContentContainer :vertical-center="true"><MetaDetails :items="items" /></ContentContainer>`
});
