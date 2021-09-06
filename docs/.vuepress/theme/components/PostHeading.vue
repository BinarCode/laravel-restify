<template>
  <div class="post-heading">
    <div
      v-if="suggestedUpdatePath"
      class="version-warning block w-full mt-2 px-3 py-2 rounded border border-yellow-300"
    >
      This document is for an older version of {{ $activeSet.setTitle }}.
      <RouterLink :to="suggestedUpdatePath">View latest version →</RouterLink>
    </div>
    <div
      class="auto-toc block xl:hidden"
      v-if="headingItems.length && headingItems[0].children.length"
    >
      <SidebarLinks :depth="0" :items="headingItems" fixed-heading="On this Page" />
    </div>
  </div>
</template>

<style lang="postcss">
.theme-default-content {
  .post-heading {
    .auto-toc {
      @apply w-full mt-3 mb-6 border-t border-b;
      border-color: var(--border-color);
    }
    .sidebar-links {
      @apply list-none p-0 mx-0 mb-3;
      li {
        @apply list-none;
        a {
          @apply text-blue px-0;
        }
      }
    }

    .sidebar-heading {
      @apply p-0 text-xs mx-0 mb-2 tracking-wide uppercase;
    }
  }
}
</style>

<script>
import { resolveHeaders, getSameContentForVersion } from "../util";
import SidebarLinks from "./SidebarLinks";

export default {
  components: {
    SidebarLinks,
  },
  mounted() {
    this.checkReferrer();
  },
  data() {
    return {
      suggestedUpdatePath: null,
    };
  },
  computed: {
    headingItems() {
      return resolveHeaders(this.$page);
    },
  },
  methods: {
    checkReferrer() {
      if (
        !this.$activeSet ||
        !this.$activeSet.versions ||
        !this.$activeVersion
      ) {
        // nothing to do; only set content should generate suggestions
        return;
      }

      const isDefaultVersion =
        this.$activeSet.defaultVersion === this.$activeVersion;
      const isNewestVersion =
        this.$activeVersion === this.$activeSet.versions[0][0];

      if (isDefaultVersion || isNewestVersion) {
        // only make suggestions for past versions
        return;
      }

      const alternateVersionPath = getSameContentForVersion(
        this.$activeSet.defaultVersion,
        this.$activeSet,
        this.$activeVersion,
        this.$page,
        this.$site.pages,
        false
      );

      if (alternateVersionPath === false) {
        return;
      }

      const searchMatch = [
        /google\.com/,
        /yahoo\.com/,
        /bing\.com/,
        /duckduckgo\.com/,
      ];

      // does it look like the visitor came from a search engine?
      const isSearchReferral = searchMatch.some((item) =>
        item.test(document.referrer)
      );

      if (isSearchReferral) {
        this.suggestedUpdatePath = alternateVersionPath;
      }
    },
  },
};
</script>
