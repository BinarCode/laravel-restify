<template>
  <ul v-if="items.length" class="sidebar-links pl-0">
    <li v-for="(item, i) in items" :key="i">
      <SidebarGroup
        v-if="item.type === 'group' && hasChildItems(item)"
        :class="'group-' + i"
        :item="item"
        :open="i === openGroupIndex"
        :collapsable="item.collapsable || item.collapsible"
        :sidebar-depth="sidebarDepth"
        :depth="depth"
        :fixed-heading="fixedHeading"
        @toggle="toggleGroup(i)"
      />
      <ToggleSidebarGroup
        v-if="hasToggleChildItems(item)"
        :item="item"
        :depth="depth"
        :sidebar-depth="sidebarDepth"
      />
      <SidebarLink
        v-else-if="item.type !== 'group'"
        :sidebar-depth="sidebarDepth || 0"
        :item="item"
      />
      <!-- insert only after last group -->
      <ExtraSidebarItems
        v-if="extraItems && extraItems.length && item.type == 'group' && i == items.length - 1"
        :items="extraItems"
      />
    </li>
  </ul>
</template>

<script>
import SidebarGroup from "./SidebarGroup.vue";
import ToggleSidebarGroup from "./ToggleSidebarGroup.vue";
import SidebarLink from "./SidebarLink.vue";
import ExtraSidebarItems from "./ExtraSidebarItems.vue";
import { isActive } from "../util";

export default {
  name: "SidebarLinks",

  components: {
    SidebarGroup,
    ToggleSidebarGroup,
    SidebarLink,
    ExtraSidebarItems,
  },

  props: {
    // items to be rendered
    items: {
      type: Array
    },
    // secondary items for a “more” expander
    extraItems: {
      type: Array
    },
    // depth of current sidebar links
    depth: {
      type: Number
    },
    sidebarDepth: {
      type: Number
    },
    // heading to be displayed above links
    fixedHeading: {
      type: String
    },
  },

  data() {
    return {
      openGroupIndex: 0,
    };
  },

  watch: {
    $route() {
      this.refreshIndex();
    },
  },

  created() {
    this.refreshIndex();
  },

  methods: {
    refreshIndex() {
      const index = resolveOpenGroupIndex(this.$route, this.items);
      if (index > -1) {
        this.openGroupIndex = index;
      }
    },

    toggleGroup(index) {
      this.openGroupIndex = index === this.openGroupIndex ? -1 : index;
    },

    isActive(page) {
      return isActive(this.$route, page.regularPath);
    },

    hasChildItems(groupItem) {
      return groupItem.children.length > 0;
    },

    hasToggleChildItems(groupItem) {
      return groupItem.toggleChildren && groupItem.toggleChildren.length > 0;
    },
  },
};

function resolveOpenGroupIndex(route, items) {
  for (let i = 0; i < items.length; i++) {
    const item = items[i];
    if (descendantIsActive(route, item)) {
      return i;
    }
  }
  return -1;
}

function descendantIsActive(route, item) {
  if (item.type === "group") {
    return item.children.some((child) => {
      if (child.type === "group") {
        return descendantIsActive(route, child);
      } else {
        return child.type === "page" && isActive(route, child.path);
      }
    });
  }
  return false;
}
</script>
