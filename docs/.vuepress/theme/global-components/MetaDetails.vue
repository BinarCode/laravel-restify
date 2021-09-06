<template>
  <div class="meta-details">
    <p>
      <span v-for="(item, index) in items" :key="index" class="item">
        <span class="label">{{ item.label }}:</span>
        <span class="value">{{ item.value }}</span>
      </span>
    </p>
  </div>
</template>

<style>
.meta-details {
  width: 100%;
  font-size: 0.8rem;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.meta-details p {
  margin: 0.25rem 0;
}

.meta-details .item {
  margin-right: 1rem;
}

.meta-details .label {
  opacity: 0.6;
  margin-right: 0.125rem;
}

.meta-details .value {
}
</style>

<script>
/**
 * ```yaml
 * - metaDetails:
 *  - readTime: 5
 *  - skillLevel: advanced
 *  - edition: pro
 * ```
 */
export default {
  props: {
    items: {
      type: Array
    }
  }
};
</script>
