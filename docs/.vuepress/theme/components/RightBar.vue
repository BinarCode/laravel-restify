<template>
  <div class="right-bar">
    <div class="switch-wrapper hidden xl:block">
      <ColorModeSwitch v-on="$listeners" :on="isDark" />
    </div>
    <div class="sidebar-link-wrapper">
      <SidebarLinks
        :depth="depth"
        :sidebar-depth="$page.frontmatter.sidebarDepth || sidebarDepth"
        :items="headingItems"
        fixed-heading="On this Page"
      />
    </div>
  </div>
</template>

<script>
import SidebarLinks from "./SidebarLinks";
import ColorModeSwitch from "./ColorModeSwitch";

export default {
  components: {
    SidebarLinks,
    ColorModeSwitch,
  },
  props: {
    headingItems: {
      type: Array,
    },
    isDark: {
      type: Boolean,
    },
    depth: {
      type: Number,
      default: 0,
    },
    sidebarDepth: {
      type: Number,
      default: 0,
    },
  },
};
</script>

<style lang="postcss">
.right-bar {
  @apply w-64 absolute right-0 top-0 bottom-0 hidden;

  .sidebar-heading {
    @apply text-xs mx-0 tracking-wide uppercase;
    padding: 0.35rem 0;
    border-left: none;
  }

  .sidebar-link-wrapper {
    @apply fixed mt-24 w-64 pt-1 overflow-hidden;
  }

  .sidebar-group:not(.collapsable) .sidebar-heading:not(.clickable) {
    color: #a0aec0;
  }

  .sidebar-links {
    @apply ml-0 pl-0 pb-4 overflow-y-auto;
    max-height: calc(100vh - 6.5rem);

    .sidebar-links {
      @apply pb-0;
      max-height: none;
    }

    .sidebar-link {
      @apply mx-0 px-0 border-0 pr-4;
      color: var(--sidebar-link-color);

      opacity: 0.6;

      &.active {
        @apply opacity-100;
        color: var(--sidebar-link-color);
      }
    }
  }

  .switch-wrapper {
    @apply absolute;
    top: 0.125rem;
    right: 1.325rem;
  }
}

@screen xl {
  .right-bar {
    @apply block;
    width: calc(50% - 384px);
  }
}
</style>
