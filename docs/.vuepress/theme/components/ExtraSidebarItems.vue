<template>
  <div>
    <div class="sidebar-extra-top"></div>
    <div v-for="item in items">
      <RouterLink v-if=" ! isExternal(item.link)" :to="item.link" class="sidebar-extra-item">
        <span class="sidebar-extra-icon">
          <img :src="item.icon" />
        </span>
        <span class="sidebar-extra-title">{{ item.title }}</span>
      </RouterLink>
      <a
        v-else
        :href="item.link"
        class="sidebar-extra-item"
        rel="noopener noreferrer"
        target="_blank"
      >
        <span class="sidebar-extra-icon">
          <img :src="item.icon" />
        </span>
        <span class="sidebar-extra-title mr-1">{{ item.title }}</span>
        <OutboundLink />
      </a>
    </div>
  </div>
</template>

<style lang="postcss">
.sidebar-extra-top {
  @apply border-t mt-12 pt-3 mx-4;
  border-color: var(--border-color);
}

.sidebar-extra-item {
  @apply flex px-4 py-2 items-center text-base text-slate leading-none font-medium;
  color: var(--sidebar-link-color);

  &:hover {
    color: var(--sidebar-active-link-color);
  }
}

.sidebar-extra-icon {
  @apply inline-block w-4 h-4 mr-2;
}
</style>

<script>
import { isExternal, isMailto, isTel, ensureExt } from "../util";

export default {
  props: ["items"],
  methods: {
    isExternal(link) {
      return isExternal(link);
    },
  },
};
</script>