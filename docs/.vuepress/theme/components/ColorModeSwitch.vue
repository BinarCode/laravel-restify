<template>
  <button
    @click="handleClick"
    class="dark-mode-toggle"
    :class="{ 'dark': on, 'light': ! on }"
    :aria-label="`Switch dark/light mode`"
  >
    <div class="backdrop relative w-full h-full">
      <div class="knob"></div>
    </div>
  </button>
</template>

<style lang="postcss">
.dark-mode-toggle {
  @apply inline-block border border-2 rounded-full h-5 relative mt-4 outline-none appearance-none overflow-hidden;
  width: 44px;
  height: 22px;

  &:focus {
    @apply outline-none;
  }

  .knob {
    @apply block rounded-full absolute;
    left: 2px;
    top: 2px;
    width: 14px;
    height: 14px;
    transition: all 0.25s cubic-bezier(0.86, 0, 0.07, 1);
    background: #a0aec0;

    &:after {
      content: "";
      @apply absolute;
      transition: all 0.25s cubic-bezier(0.86, 0, 0.07, 1);
    }
  }
}

.dark-mode-toggle {
  &.dark {
    border-color: rgba(45, 55, 72, 1);
    background: transparent;

    .knob {
      left: 23px;

      &:after {
        content: "";
        @apply bg-gray-900;
        height: 110%;
        width: 160%;
        right: 40%;
        top: -5%;
        border-radius: 40%/60%;
      }
    }
  }
}
</style>

<script>
export default {
  props: {
    on: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      enabled: this.on,
    };
  },
  methods: {
    handleClick() {
      this.enabled = !this.enabled;
      this.$emit("toggle-color-mode");
    },
  },
};
</script>
