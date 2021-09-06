<template>
  <fragment>
    <RouterLink
      class="icon-link"
      v-if="isInternal"
      :to="link"
      :class="{ 'large': iconSize == 'large' }"
    >
      <div class="icon absolute left-0">
        <img :src="icon" class="no-zoom" alt />
      </div>
      <div class="text">
        <span class="leading-none text-lg font-medium block mb-1">
          {{
          title
          }}
        </span>
        <span class="text-sm text-slate block leading-tight">
          {{
          subtitle
          }}
        </span>
      </div>
    </RouterLink>
    <a
      v-else
      class="icon-link"
      :href="link"
      :target="target"
      :rel="rel"
      :class="{ 'large': iconSize == 'large' }"
    >
      <div class="icon absolute left-0">
        <img :src="icon" class="no-zoom" alt />
      </div>
      <div class="text">
        <span class="leading-none text-lg font-medium block mb-1">
          {{
          title
          }}
        </span>
        <span class="text-sm text-slate block leading-tight">
          {{
          subtitle
          }}
        </span>
      </div>
    </a>
  </fragment>
</template>

<style lang="postcss">
.icon-link {
  @apply block relative my-2;

  .icon {
    @apply flex items-center content-center justify-center w-3 h-3;

    svg {
      @apply max-w-full h-auto;
    }
  }

  .text {
    @apply ml-5;
  }

  &:hover {
    @apply no-underline !important;
  }

  &.large {
    .icon {
      @apply opacity-50;
      width: 1.25rem;
      height: 1.25rem;
    }

    .text {
      @apply ml-8;
    }

    &:hover {
      .icon {
        @apply opacity-100;
      }
    }
  }
}
</style>

<script>
import { isExternal, isMailto, isTel, ensureExt } from "../util";
import { Fragment } from "vue-fragment";

export default {
  components: {
    Fragment,
  },
  props: ["icon", "iconSize", "title", "link", "subtitle", "repo"],
  computed: {
    isNonHttpURI() {
      return isMailto(this.link) || isTel(this.link);
    },

    isBlankTarget() {
      return this.target === "_blank";
    },

    isInternal() {
      return !isExternal(this.link) && !this.isBlankTarget;
    },

    target() {
      if (this.isNonHttpURI) {
        return null;
      }
      return isExternal(this.link) ? "_blank" : "";
    },

    rel() {
      if (this.isNonHttpURI) {
        return null;
      }
      return this.isBlankTarget ? "noopener noreferrer" : "";
    },
  },
};
</script>
