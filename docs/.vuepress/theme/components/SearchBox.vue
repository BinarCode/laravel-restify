<template>
  <div class="search-box relative w-full">
    <input
      ref="input"
      aria-label="Search"
      :value="query"
      :class="{ focused: focused }"
      :placeholder="placeholder"
      class="rounded-md w-full px-5 py-2 text-sm"
      autocomplete="off"
      spellcheck="false"
      @input="query = $event.target.value"
      @focus="focused = true"
      @blur="focused = false"
      @keyup.enter="go(focusIndex)"
      @keyup.up="onUp"
      @keyup.down="onDown"
    />
    <div
      v-if="showSuggestions"
      class="suggestions"
      :class="{ 'align-right': alignRight }"
      @mouseleave="unfocus"
    >
      <div v-for="(s, i) in suggestions" :key="i">
        <div
          v-if="!$activeSet && shouldShowSetTitle(s, i)"
          class="suggestion-doc-set"
          :class="{ first: shouldShowSetTitle(s, i) && i === 0 }"
        >{{ s.docSetTitle }}</div>
        <div
          class="suggestion"
          :class="{
            focused: i === focusIndex,
            first: i === 0,
            last: i === suggestions.length - 1
          }"
          @mousedown="go(i)"
          @mouseenter="focus(i)"
        >
          <a :href="s.path + s.slug" @click.prevent>
            <!-- <div
            v-if="s.parentPageTitle"
            class="parent-page-title"
            v-html="highlight(s.parentPageTitle)"
            />-->
            <div class="suggestion-row">
              <div
                class="page-title"
                v-html="
                  s.match == 'title'
                    ? highlight(s.title || s.path)
                    : s.title || s.path
                "
              ></div>
              <div class="suggestion-content">
                <div
                  class="header"
                  v-if="s.headingStr"
                  v-html="
                    s.match == 'header' ? highlight(s.headingStr) : s.headingStr
                  "
                ></div>
                <div
                  class="excerpt"
                  v-if="s.contentStr && s.headingStr != s.contentStr"
                  v-html="
                    s.match == 'content'
                      ? highlight(s.contentStr)
                      : s.contentStr
                  "
                ></div>
              </div>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import searchService from "../util/flexsearch-service";

export default {
  name: "SearchBox",
  data() {
    return {
      query: "",
      focused: false,
      focusIndex: 0,
      maxSuggestions: 10,
      suggestions: null,
      hotkeys: ["s", "/"],
    };
  },
  computed: {
    queryTerms() {
      if (!this.query) return [];
      const result = this.query
        .trim()
        .toLowerCase()
        .split(/[^\p{L}]+/iu)
        .filter((t) => t);
      return result;
    },
    showSuggestions() {
      return this.focused && this.suggestions && this.suggestions.length;
    },

    // make suggestions align right when there are not enough items
    alignRight() {
      const navCount = (this.$site.themeConfig.nav || []).length;
      const repo = this.$site.repo ? 1 : 0;
      return navCount + repo <= 2;
    },

    placeholder() {
      return (
        this.$activeSet.searchPlaceholder ||
        this.$site.themeConfig.searchPlaceholder ||
        ""
      );
    },
  },
  watch: {
    query() {
      this.getSuggestions();
    },
  },
  mounted() {
    searchService.buildIndex(this.$site.pages);
    document.addEventListener("keydown", this.onHotkey);
  },
  beforeDestroy() {
    document.removeEventListener("keydown", this.onHotkey);
  },
  methods: {
    highlight(str) {
      if (!this.queryTerms.length) return str;
      // safely use HTML lines in result
      str = this.escapeHtml(str.trim());
      return str.replace(new RegExp(this.query, "gi"), (match) => {
        return `<mark>${match}</mark>`;
      });
    },
    escapeHtml(unsafe) {
      return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    },
    async getSuggestions() {
      if (!this.query || !this.queryTerms.length) {
        this.suggestions = [];
        return;
      }

      // if no active set, search primaries + default version + default lang
      // otherwise, search current set + current lang + current version

      this.suggestions = await searchService.match(
        this.query,
        this.queryTerms,
        this.$activeSet ? this.$activeSet.handle : false,
        this.$activeVersion,
        this.$lang,
        this.maxSuggestions
      );
    },
    getPageLocalePath(page) {
      for (const localePath in this.$site.locales || {}) {
        if (localePath !== "/" && page.path.indexOf(localePath) === 0) {
          return localePath;
        }
      }
      return "/";
    },
    onHotkey(event) {
      // take us to the search input if it doesn’t have focus
      if (
        event.srcElement !== this.$refs.input &&
        this.hotkeys.includes(event.key)
      ) {
        this.$refs.input.focus();
        event.preventDefault();
      }

      // escape
      if (event.keyCode === 27) {
        this.query = "";
        this.$refs.input.blur();
        event.preventDefault();
      }
    },
    onUp() {
      if (this.showSuggestions) {
        if (this.focusIndex > 0) {
          this.focusIndex--;
        } else {
          this.focusIndex = this.suggestions.length - 1;
        }
      }
    },
    onDown() {
      if (this.showSuggestions) {
        if (this.focusIndex < this.suggestions.length - 1) {
          this.focusIndex++;
        } else {
          this.focusIndex = 0;
        }
      }
    },
    go(i) {
      if (!this.showSuggestions) {
        return;
      }
      this.$router.push(this.suggestions[i].path + this.suggestions[i].slug);
      this.query = "";
      this.$refs.input.blur();
      this.focusIndex = 0;
    },
    focus(i) {
      this.focusIndex = i;
    },
    unfocus() {
      this.focusIndex = -1;
    },
    shouldShowSetTitle(suggestion, index) {
      let previousSuggestion = this.suggestions[index - 1];

      return (
        !previousSuggestion ||
        previousSuggestion.docSetTitle !== suggestion.docSetTitle
      );
    },
  },
};
</script>

<style lang="postcss">
.search-box {
  input {
    background-color: var(--search-bg-color);
  }
}

.suggestions {
  @apply bg-white w-full absolute z-20 rounded list-none;
  box-shadow: 0 20px 55px rgba(0, 0, 0, 0.3);
  top: 2.6rem;

  &:before {
    @apply block absolute w-0 h-0;
    content: "";
    border-top: 1px solid transparent;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-bottom: 10px solid #fff;
    top: -10px;
    left: 1.75rem;
  }

  mark {
    @apply text-slate;
    background-color: rgba(74, 124, 246, 0.1);
  }
}

.suggestion {
  @apply cursor-pointer rounded mx-2;
  line-height: 1.4;
  /* padding: 0.4rem 0.6rem; */

  a {
    @apply whitespace-normal;
    color: #5d81a5;

    .page-title {
      @apply font-semibold;
    }

    .header {
      font-size: 0.9em;
      margin-left: 0.25em;
    }
  }

  &.focused {
    background-color: #f3f4f5;

    a {
      @apply text-blue;
    }

    .suggestion-row {
      .page-title {
        border-color: #e6e6e6;
      }
    }
  }

  &.first {
    @apply mt-2;
  }

  &.last {
    @apply mb-2;
  }
}

.suggestion-doc-set {
  @apply relative px-4 py-1 mt-2 mb-2 font-bold text-sm text-slate select-none bg-softer;

  &.first {
    @apply rounded-t mt-0 mb-0;
  }
}

.suggestion-row {
  @apply flex w-full;

  .page-title,
  .suggestion-content {
    padding: 0.4rem 0.6rem;
  }

  .page-title {
    @apply w-1/3 border-r;
    border-color: #f3f4f5;
  }

  .suggestion-content {
    @apply w-2/3;

    .header {
      @apply font-semibold m-0 p-0;
    }

    .excerpt {
      @apply text-sm overflow-hidden;
    }
  }
}
</style>
