import EditionComparison from "../global-components/EditionComparison";
import ContentContainer from "./ContentContainer";

export default { title: "global-components/Edition Comparison" };

export const Default = () => ({
  components: { EditionComparison, ContentContainer },
  template: `<ContentContainer :vertical-center="false"><EditionComparison /></ContentContainer>`
});
