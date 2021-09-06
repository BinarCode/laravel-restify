import ContentContainer from "./ContentContainer";
import LoadingBars from "../components/LoadingBars";

export default {
  title: "components/LoadingBars"
};

export const Default = () => ({
  components: { LoadingBars, ContentContainer },
  props: { },
  template: `<ContentContainer :vertical-center="true" class="justify-center">
    <loading-bars>
  </ContentContainer>`
});
