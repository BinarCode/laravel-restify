<template>
  <ul class="code-language-switcher split">
    <li v-for="(language, index) in $page.frontmatter.code" :key="index">
      <a
        :class="{ active: $store.state.codeLanguage === language }"
        @click="$store.commit('changeCodeLanguage', language)"
        >{{ $site.themeConfig.codeLanguages[language] }}</a
      >
    </li>
  </ul>
</template>

<style lang="postcss">
.code-language-switcher.split {
  @apply rounded-none;
}
</style>

<script>
import { getStorage } from "../Storage";

export default {
  mounted() {
    if (
      this.$page.frontmatter.code.indexOf(this.$store.state.codeLanguage) === -1
    ) {
      let storageValue = getStorage("codeLanguage", this.$site.base);

      if (
        typeof storageValue !== "undefined" &&
        this.$page.frontmatter.code.indexOf(storageValue) !== -1
      ) {
        this.$store.commit("changeCodeLanguage", storageValue);
      } else {
        this.$store.commit(
          "changeCodeLanguage",
          this.$page.frontmatter.code[0]
        );
      }
    }
  },
};
</script>
