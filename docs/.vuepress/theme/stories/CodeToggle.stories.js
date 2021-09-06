import { withKnobs, array, object } from "@storybook/addon-knobs";
import CodeToggle from "../components/CodeToggle";
import ContentContainer from "./ContentContainer";

//import "../styles/code.styl";
//import "../styles/code.pcss";
import "../styles/index.pcss";

export default {
  title: "components/CodeToggle",
  decorators: [withKnobs]
};

const codeSample = `
  <template slot="twig">
    <div class="language-twig extra-class"><pre class="language-twig"><code><span class="token tag"><span class="token ld"><span class="token punctuation">{%</span> <span class="token keyword">set</span></span> <span class="token property">companyInfo</span> <span class="token operator">=</span> <span class="token property">craft</span><span class="token punctuation">.</span><span class="token property">globals</span><span class="token punctuation">(</span><span class="token punctuation">)</span><span class="token punctuation">.</span><span class="token property">getSetByHandle</span><span class="token punctuation">(</span><span class="token string"><span class="token punctuation">'</span>companyInfo<span class="token punctuation">'</span></span><span class="token punctuation">)</span> <span class="token rd"><span class="token punctuation">%}</span></span></span></code></pre></div>
  </template>
  <template slot="php">
    <div class="language-php extra-class"><pre class="language-php"><code><span class="token variable">$companyInfo</span> <span class="token operator">=</span> \<span class="token package">Craft</span><span class="token punctuation">:</span><span class="token punctuation">:</span><span class="token variable">$app</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token function">getGlobals</span><span class="token punctuation">(</span><span class="token punctuation">)</span><span class="token operator">-</span><span class="token operator">&gt;</span><span class="token function">getSetByHandle</span><span class="token punctuation">(</span><span class="token single-quoted-string string">'companyInfo'</span><span class="token punctuation">)</span><span class="token punctuation">;</span></code></pre></div>
  </template>
`;

export const Default = () => ({
  components: { CodeToggle, ContentContainer },
  props: {
    languages: {
      default: array("Languages", ["twig", "php"])
    },
    labels: {
      default: object("Labels", { twig: "Twig", php: "PHP" })
    }
  },
  template: `<ContentContainer :vertical-center="true">
      <CodeToggle
        :languages="languages"
        :labels="labels">${codeSample}</CodeToggle>
    </ContentContainer>`
});
