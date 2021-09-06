<template>
  <div class="code-toggle">
    <ul class="code-language-switcher" v-if="!usePageToggle">
      <li v-for="(language, index) in languages" :key="index">
        <a
          :class="{ active: language === selectedLanguage }"
          @click="setLanguage(language)"
        >{{ getLanguageLabel(language) }}</a>
      </li>
    </ul>
    <div v-for="(language, index) in languages" :key="index">
      <slot :name="language" v-if="isSelectedLanguage(language)" />
    </div>
  </div>
</template>

<style lang="postcss">
.code-toggle {
  @apply w-full mx-0 my-4;

  div[class*="language-"] {
    @apply rounded-t-none rounded-b my-0;

    &:before {
      @apply hidden;
    }
  }

  & > div > div[class*="language-"] {
    & > pre,
    & > pre[class*="language-"] {
      @apply m-0;
    }
  }
}

ul.code-language-switcher {
  @apply flex flex-row rounded-t box-border m-0 px-4 py-2;
  background: #e1e9f0;
  z-index: 2;

  li {
    @apply p-0 mr-1 list-none;

    a {
      @apply block font-medium px-3 cursor-pointer rounded leading-relaxed;
      font-size: 15px;
      color: #476582;
      padding-top: 0.2rem;
      padding-bottom: 0.2rem;

      &:hover {
        text-decoration: none !important;

        &:not(.active) {
          background: rgba(255, 255, 255, 0.5);
        }
      }

      &.active {
        @apply cursor-default bg-white;
      }
    }
  }
}

.theme-default-content {
  ul.code-language-switcher {
    @apply mb-0;
  }
}
</style>

<script>
export default {
  props: ["languages", "labels"],

  data() {
    return {
      selectedLanguage: this.languages[0]
    };
  },

  computed: {
    usePageToggle() {
      if (this.$page === undefined) {
        return false;
      }

      return this.$page.frontmatter.split && this.$page.frontmatter.code;
    }
  },

  methods: {
    setLanguage(language) {
      this.selectedLanguage = language;
    },
    getLanguageLabel(language) {
      if (this.labels && this.labels[language]) {
        return this.labels[language];
      }

      const themeLanguages =
        this.$site !== undefined ? this.$site.themeConfig.codeLanguages : false;

      return (
        (this.labels && this.labels[language]) ||
        (themeLanguages && themeLanguages[language]) ||
        language
      );

      return language;
    },
    isSelectedLanguage(language) {
      return (
        language ==
        (this.usePageToggle
          ? this.$store.state.codeLanguage
          : this.selectedLanguage)
      );
    }
  }
};
</script>
