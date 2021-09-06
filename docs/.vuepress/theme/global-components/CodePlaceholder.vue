<template>
  <div class="code-placeholder" :title="title">
    <slot></slot>
  </div>
</template>

<script>
export default {
  props: ['title']
};
</script>