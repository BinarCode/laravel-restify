<template>
  <div class="grid-item-wrapper">
    <div class="grid-item">
      <img class="block" :class="{ 'no-zoom': disableZoom }" :src="data.imageSrc" alt />
    </div>
    <span class="label">{{ label }}</span>
  </div>
</template>

<style lang="postcss">
.grid-container {
  @apply w-full;
  display: grid;
  grid-template-columns: 23% 23% 23% 23%;
  grid-gap: 1rem 3%;
}

.grid-item-wrapper {
  @apply w-full;
}

.grid-item {
  text-align: center;
  line-height: 0;
  @apply block overflow-hidden text-center rounded bg-transparent;
}

.grid-item-wrapper .label {
  @apply block w-full text-center mt-2;
  color: #637599;
}
</style>

<script>
export default {
  computed: {
    data() {
      return {
        imageSrc: this.$withBase(this.src)
      };
    }
  },
  props: {
    label: {
      type: String,
      required: true
    },
    disableZoom: {
      type: Boolean,
      required: false,
      default: true
    },
    src: {
      type: String,
      default: "https://via.placeholder.com/400x200/eee"
    }
  }
};
</script>