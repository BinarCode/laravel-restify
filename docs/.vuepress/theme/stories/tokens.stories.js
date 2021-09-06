import resolveConfig from "tailwindcss/resolveConfig";
import tailwindConfig from "../../../../tailwind.config.js";
import ContentContainer from "./ContentContainer.vue";

const fullConfig = resolveConfig(tailwindConfig);

// [x] colors
// [ ] fonts
// [ ] font sizes
// [ ] font weights
// [ ] body content (headings, p, ul, ol, etc.)
// [ ] breakpoints
// [x] box rounding
// [x] shadows

export default {
  title: "tokens"
};

export const Color = () => ({
  components: {},
  props: {
    colors: { default: Object.entries(fullConfig.theme.colors) }
  },
  template: `
      <div class="flex flex-wrap items-center content-center justify-center p-8 h-screen">
        <div v-for="[name, value] in colors" class="color-card w-32 shadow rounded m-2">
         <div v-if="typeof(value) == 'string'" class="w-full h-24 rounded-t" :class="'bg-' + name"></div>
         <div v-else v-for="variant in Object.keys(value)" class="w-full h-3 text-xs leading-none text-right" :class="'bg-' + name + '-' + variant"><span class="opacity-25">{{ variant }}</span></div>
         <div class="p-2">
           <h3 class="text-sm py-0 my-0 leading-snug">{{ name }}</h3>
           <p class="font-mono text-xs opacity-50 my-0 leading-snug" v-if="typeof(value) == 'string'">{{ value }}</p>
         </div>
        </div>
      </div>
    `
});

export const Shadow = () => ({
  components: {},
  props: {
    boxShadow: { default: Object.entries(fullConfig.theme.boxShadow) }
  },
  template: `
      <div class="p-6 flex flex-wrap w-full h-screen items-center justify-center">
        <div v-for="[name, value] in boxShadow"
          class="w-64 h-32 m-8 p-6 rounded leading-tight"
          :class="name == 'default' ? 'shadow' : 'shadow-' + name"
        >
          <p class="text-sm font-bold p-0 m-0">{{ name }}</p>
          <code class="text-xs leading-none opacity-50">{{ value }}</code>
        </div>
      </div>
    `
});

export const BorderRadius = () => ({
  components: {},
  props: {
    borderRadius: {
      default: Object.entries(fullConfig.theme.borderRadius)
    }
  },
  template: `
    <div class="flex w-full h-screen items-center content-center justify-center flex-wrap">
      <div v-for="[name, value] in borderRadius"
        class="w-24 h-24 m-8 p-4 bg-gray-300 flex content-center justify-center items-center text-center"
        :class="name == 'default' ? 'rounded' : 'rounded-' + name"
      >
        <div>
          <p class="text-sm font-bold p-0 m-0">{{ name }}</p>
          <code class="text-xs leading-none opacity-50">{{ value }}</code>
        </div>
      </div>
    </div>
    `
});

export const Typography = () => ({
  components: { ContentContainer },
  template: `
  <ContentContainer>
    <h1>Heading Level One</h1>
    <h2>Heading Level Two</h2>
    <h3>Heading Level Three</h3>
    <h4>Heading Level Four</h4>
    <h5>Heading Level Five</h5>
    <h6>Heading Level Six</h6>

    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>

    <ul>
      <li>Unordered list item.</li>
      <li>Unordered list item.</li>
      <li>Unordered list item.</li>
      <li>Really long unordered list item that’s so long it eventually wraps because it goes on such that it occupies more than one line.</li>
      <li>Unordered list item.</li>
    </ul>

    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>

    <ol>
      <li>Ordered list item.</li>
      <li>Ordered list item.</li>
      <li>Ordered list item.</li>
      <li>Really long ordered list item that’s so long it eventually wraps because it goes on such that it occupies more than one line.</li>
      <li>Ordered list item.</li>
    </ol>

    <p>And here’s a table:</p>

    <div class="table"><table><thead><tr><th>Param</th> <th>Description</th></tr></thead> <tbody><tr><td><code>authorId</code></td> <td>The ID of the user account that should be set as the entry author. (Defaults to the entry’s current author, or the logged-in user.)</td></tr> <tr><td><code>enabledForSite</code></td> <td>Whether the entry should be enabled for the current site (<code>1</code>/<code>0</code>), or an array of site IDs that the entry should be enabled for. (Defaults to the <code>enabled</code> param.)</td></tr> <tr><td><code>enabled</code></td> <td>Whether the entry should be enabled (<code>1</code>/<code>0</code>). (Defaults to enabled.)</td></tr> <tr><td><code>entryId</code></td> <td>Fallback if <code>sourceId</code> isn’t passed, for backwards compatibility.</td></tr> <tr><td><code>entryVariable</code></td> <td>The hashed name of the variable that should reference the entry, if a validation error occurs. (Defaults to <code>entry</code>.)</td></tr> <tr><td><code>expiryDate</code></td> <td>The expiry date for the entry. (Defaults to the current expiry date, or <code>null</code>.)</td></tr> <tr><td><code>failMessage</code></td> <td>The hashed flash notice that should be displayed, if the entry is not saved successfully. (Only used for <code>text/html</code> requests.)</td></tr> <tr><td><code>fieldsLocation</code></td> <td>The name of the param that holds the custom field values. (Defaults to <code>fields</code>.)</td></tr> <tr><td><code>fields</code></td> <td>An array of new custom field values, indexed by field handles. (The param name can be customized via <code>fieldsLocation</code>.) Only fields that are included in this array will be&nbsp;updated.</td></tr> <tr><td><code>parentId</code></td> <td>The ID of the parent entry, if it belongs to a structure&nbsp;section.</td></tr> <tr><td><code>postDate</code></td> <td>The post date for the entry. (Defaults to the current post date, or the current time.)</td></tr> <tr><td><code>redirect</code></td> <td>The hashed URL to redirect the browser to, if the entry is saved successfully. (The requested URI will be used by default.)</td></tr> <tr><td><code>revisionNotes</code></td> <td>Notes that should be stored on the new entry revision.</td></tr> <tr><td><code>siteId</code></td> <td>The ID of the site to save the entry in.</td></tr> <tr><td><code>slug</code></td> <td>The entry slug. (Defaults to the current slug, or an auto-generated slug.)</td></tr> <tr><td><code>sourceId</code></td> <td>The ID of the entry to save, if updating an existing entry.</td></tr> <tr><td><code>successMessage</code></td> <td>The hashed flash notice that should be displayed, if the entry is saved successfully. (Only used for <code>text/html</code> requests.)</td></tr> <tr><td><code>title</code></td> <td>The entry title. (Defaults to the current entry title.)</td></tr> <tr><td><code>typeId</code></td> <td>The entry type ID to save the entry as. (Defaults to the current entry type.)</td></tr></tbody></table></div>
    
  </ContentContainer>
  `
});
