<template>
  <div
    class="content-container theme-default-content"
    :class="{ 'center-vertically': verticalCenter }"
  >
    <slot></slot>
  </div>
</template>

<style lang="postcss">
.sb-show-main {
  @apply m-0 p-0;
}

.content-container {
  @apply mx-auto max-w-screen-md;
}

.center-vertically {
  @apply flex items-center h-screen;
}
</style>

<script>
export default {
  props: {
    verticalCenter: {
      type: Boolean,
      default: false
    }
  }
};
</script>
