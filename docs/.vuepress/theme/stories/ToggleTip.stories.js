import { withKnobs, text, number, boolean } from "@storybook/addon-knobs";
import ToggleTip from "../global-components/ToggleTip";
import ContentContainer from "./ContentContainer";

export default {
  title: "global-components/ToggleTip",
  decorators: [withKnobs]
};

const codeSample = `
<div class="language-php extra-class"><pre class="language-php"><code><span class="token keyword">array</span> <span class="token punctuation">(</span>
    <span class="token single-quoted-string string">'id'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'5'</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'isStoreLocation'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token boolean constant">true</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'attention'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'title'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'firstName'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'lastName'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'fullName'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'address1'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'1234 Balboa Towers Circle'</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'address2'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'#100'</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'address3'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'city'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'Los Angeles'</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'zipCode'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'92662'</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'phone'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'(555) 555-5555'</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'alternativePhone'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'label'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'businessName'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'Gobias Industries'</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'businessTaxId'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'businessId'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'stateName'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'countryId'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'236'</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'stateId'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'26'</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'notes'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'custom1'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'custom2'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'custom3'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'custom4'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">''</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'isEstimated'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token boolean constant">false</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'stateValue'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'26'</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'countryIso'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'US'</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'countryText'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'United States'</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'stateText'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'California'</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'abbreviationText'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'CA'</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'addressLines'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token keyword">array</span> <span class="token punctuation">(</span>
      <span class="token single-quoted-string string">'address1'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'1234 Balboa Towers Circle'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'address2'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'#100'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'city'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'Los Angeles'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'zipCode'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'92662'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'phone'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'(555) 555-5555'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'businessName'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'Gobias Industries'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'stateText'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'California'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'countryText'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'United States'</span><span class="token punctuation">,</span>
    <span class="token punctuation">)</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'country'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token keyword">array</span> <span class="token punctuation">(</span>
      <span class="token single-quoted-string string">'id'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'236'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'name'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'United States'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'iso'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'US'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'isStateRequired'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token constant">NULL</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'enabled'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'1'</span><span class="token punctuation">,</span>
    <span class="token punctuation">)</span><span class="token punctuation">,</span>
    <span class="token single-quoted-string string">'state'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token keyword">array</span> <span class="token punctuation">(</span>
      <span class="token single-quoted-string string">'id'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'26'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'name'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'California'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'abbreviation'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'CA'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'countryId'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'236'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'enabled'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'1'</span><span class="token punctuation">,</span>
      <span class="token single-quoted-string string">'sortOrder'</span> <span class="token operator">=</span><span class="token operator">&gt;</span> <span class="token single-quoted-string string">'5'</span><span class="token punctuation">,</span>
    <span class="token punctuation">)</span><span class="token punctuation">,</span>
  <span class="token punctuation">)</span></code></pre></div>`;

export const Default = () => ({
  components: { ToggleTip, ContentContainer },
  props: {
    title: {
      default: text("Title", "This is a title"),
    },
    height: {
      default: number("Max Height", 300),
    },
    expandTerm: {
      default: text("Expand Term", "expand")
    },
    collapseTerm: {
      default: text("Collapse Term", "collapse")
    },
    enableExpand: {
      default: boolean("Enable Expand?", true),
    },
    enableCollapse: {
      default: boolean("Enable Collapse?", false),
    }
},
  template: `<ContentContainer>
      <ToggleTip
        :title="title"
        :height="height"
        :expandTerm="expandTerm"
        :collapseTerm="collapseTerm"
        :enableExpand="enableExpand"
        :enableCollapse="enableCollapse"
        :labels="labels">${codeSample}</ToggleTip>
    </ContentContainer>`
});
