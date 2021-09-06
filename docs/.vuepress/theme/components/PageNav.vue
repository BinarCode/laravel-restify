<template>
  <div>
    <div v-if="prev || next" class="page-nav content-wrapper">
      <p class="inner border-t mt-0 pt-4 overflow-auto">
        <span v-if="prev" class="prev">
          <span class="paging-arrow inline-block">←</span>
          <a
            v-if="prev.type === 'external'"
            class="prev"
            :href="prev.path"
            target="_blank"
            rel="noopener noreferrer"
          >
            {{ prev.title || prev.path }}
            <OutboundLink />
          </a>

          <RouterLink v-else class="prev" :to="prev.path">
            {{
            prev.title || prev.path
            }}
          </RouterLink>
        </span>

        <span v-if="next" class="next float-right">
          <a
            v-if="next.type === 'external'"
            :href="next.path"
            target="_blank"
            rel="noopener noreferrer"
          >
            {{ next.title || next.path }}
            <OutboundLink />
          </a>

          <RouterLink v-else :to="next.path">
            {{
            next.title || next.path
            }}
          </RouterLink>
          <span class="paging-arrow inline-block">→</span>
        </span>
      </p>
    </div>
  </div>
</template>

<style lang="postcss">
.page-nav {
  .inner {
    min-height: 2rem;
    border-color: var(--border-color);
  }

  .paging-arrow {
    color: #718096;
  }
}
</style>

<script>
import { resolvePage, resolveHeaders } from "../util";
import isString from "lodash/isString";
import isNil from "lodash/isNil";
import SidebarLinks from "./SidebarLinks";

export default {
  name: "PageNav",

  props: ["sidebarItems"],

  components: { SidebarLinks },

  computed: {
    headingItems() {
      return resolveHeaders(this.$page);
    },

    prev() {
      return resolvePageLink(LINK_TYPES.PREV, this);
    },

    next() {
      return resolvePageLink(LINK_TYPES.NEXT, this);
    },
  },
};

function resolvePrev(page, items) {
  return find(page, items, -1);
}

function resolveNext(page, items) {
  return find(page, items, 1);
}

const LINK_TYPES = {
  NEXT: {
    resolveLink: resolveNext,
    getThemeLinkConfig: ({ nextLinks }) => nextLinks,
    getPageLinkConfig: ({ frontmatter }) => frontmatter.next,
  },
  PREV: {
    resolveLink: resolvePrev,
    getThemeLinkConfig: ({ prevLinks }) => prevLinks,
    getPageLinkConfig: ({ frontmatter }) => frontmatter.prev,
  },
};

function resolvePageLink(
  linkType,
  { $themeConfig, $page, $route, $site, sidebarItems }
) {
  const { resolveLink, getThemeLinkConfig, getPageLinkConfig } = linkType;

  // Get link config from theme
  const themeLinkConfig = getThemeLinkConfig($themeConfig);

  // Get link config from current page
  const pageLinkConfig = getPageLinkConfig($page);

  // Page link config will overwrite global theme link config if defined
  const link = isNil(pageLinkConfig) ? themeLinkConfig : pageLinkConfig;

  if (link === false) {
    return;
  } else if (isString(link)) {
    return resolvePage($site.pages, link, $route.path);
  } else {
    return resolveLink($page, sidebarItems);
  }
}

/**
 * Flatten page hierarchy, identify current page in the sequence, and return
 * the item at the given offset.
 */
function find(page, items, offset) {
  const res = [];
  flatten(items, res);
  for (let i = 0; i < res.length; i++) {
    const cur = res[i];
    if (cur.type === "page" && cur.path === decodeURIComponent(page.path)) {
      return res[i + offset];
    }
  }
}

function flatten(items, res) {
  for (let i = 0, l = items.length; i < l; i++) {
    if (items[i].type === "group") {
      flatten(items[i].children || [], res);
    } else {
      res.push(items[i]);
    }
  }
}
</script>
