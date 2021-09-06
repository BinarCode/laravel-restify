import { withKnobs, text, select } from "@storybook/addon-knobs";
import ContentContainer from "./ContentContainer";
import CodePlaceholder from "../global-components/CodePlaceholder";

export default {
  title: "global-components/CodePlaceholder",
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

const exampleSnippet = `
<div class="language-twig extra-class"><pre class="language-twig"><code><span class="token tag"><span class="token ld"><span class="token punctuation">{%</span> <span class="token keyword">set</span></span> <span class="token property">field</span> <span class="token operator">=</span> <span class="token property">craft</span><span class="token punctuation">.</span><span class="token property">app</span><span class="token punctuation">.</span><span class="token property">fields</span><span class="token punctuation">.</span><span class="token property">getFieldByHandle</span><span class="token punctuation">(</span><span class="token string"><span class="token punctuation">'</span><code-placeholder>FieldHandle</code-placeholder><span class="token punctuation">'</span></span><span class="token punctuation">)</span> <span class="token rd"><span class="token punctuation">%}</span></span></span>

<span class="token other"><span class="token tag"><span class="token tag"><span class="token punctuation">&lt;</span>ul</span><span class="token punctuation">&gt;</span></span></span>
    <span class="token tag"><span class="token ld"><span class="token punctuation">{%</span> <span class="token keyword">for</span></span> <span class="token property">option</span> <span class="token operator">in</span> <span class="token property">field</span><span class="token punctuation">.</span><span class="token property">options</span> <span class="token rd"><span class="token punctuation">%}</span></span></span>

        <span class="token tag"><span class="token ld"><span class="token punctuation">{%</span> <span class="token keyword">set</span></span> <span class="token property">selected</span> <span class="token operator">=</span> <span class="token property">entry</span> <span class="token operator">is</span> <span class="token property">defined</span>
            <span class="token operator">?</span> <span class="token property">entry</span><span class="token punctuation">.</span><code-placeholder>FieldHandle</code-placeholder><span class="token punctuation">.</span><span class="token property">value</span> <span class="token operator">==</span> <span class="token property">option</span><span class="token punctuation">.</span><span class="token property">value</span>
            <span class="token punctuation">:</span> <span class="token property">option</span><span class="token punctuation">.</span><span class="token property">default</span> <span class="token rd"><span class="token punctuation">%}</span></span></span>

        <span class="token other"><span class="token tag"><span class="token tag"><span class="token punctuation">&lt;</span>li</span><span class="token punctuation">&gt;</span></span><span class="token tag"><span class="token tag"><span class="token punctuation">&lt;</span>label</span><span class="token punctuation">&gt;</span></span>
            &lt;input type="radio"
                name="fields[<code-placeholder>FieldHandle</code-placeholder>]"
                value="</span><span class="token tag"><span class="token ld"><span class="token punctuation">{{</span></span> <span class="token property">option</span><span class="token punctuation">.</span><span class="token property">value</span> <span class="token rd"><span class="token punctuation">}}</span></span></span><span class="token other">"</span>
                <span class="token tag"><span class="token ld"><span class="token punctuation">{%</span> <span class="token keyword">if</span></span> <span class="token property">selected</span> <span class="token rd"><span class="token punctuation">%}</span></span></span><span class="token other">checked</span><span class="token tag"><span class="token ld"><span class="token punctuation">{%</span> <span class="token keyword">endif</span></span> <span class="token rd"><span class="token punctuation">%}</span></span></span><span class="token other">&gt;</span>
            <span class="token tag"><span class="token ld"><span class="token punctuation">{{</span></span> <span class="token property">option</span><span class="token punctuation">.</span><span class="token property">label</span> <span class="token rd"><span class="token punctuation">}}</span></span></span>
        <span class="token other"><span class="token tag"><span class="token tag"><span class="token punctuation">&lt;/</span>label</span><span class="token punctuation">&gt;</span></span><span class="token tag"><span class="token tag"><span class="token punctuation">&lt;/</span>li</span><span class="token punctuation">&gt;</span></span></span>
    <span class="token tag"><span class="token ld"><span class="token punctuation">{%</span> <span class="token keyword">endfor</span></span> <span class="token rd"><span class="token punctuation">%}</span></span></span>
<span class="token other"><span class="token tag"><span class="token tag"><span class="token punctuation">&lt;/</span>ul</span><span class="token punctuation">&gt;</span></span></span>
</code></pre></div>
`;

export const Twig = () => ({
  components: { CodePlaceholder, ContentContainer },
  props: {},
  template: `<ContentContainer :vertical-center="true">
    ${exampleSnippet}
  </ContentContainer>`
});
