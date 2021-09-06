<template>
  <main class="page">
    <slot name="top" />

    <Content id="content" class="theme-default-content" />

    <PageNav v-bind="{ sidebarItems }" />

    <PageFooter v-on="$listeners" :is-dark="isDark" />

    <slot name="bottom" />
  </main>
</template>

<style lang="postcss">
.page {
  @apply mx-auto pb-8 block relative;
}
</style>

<script>
import PageNav from "./PageNav";
import PageFooter from "./PageFooter";

export default {
  name: "Page",
  components: { PageNav, PageFooter },
  props: ["sidebarItems", "isDark"]
};
</script>
