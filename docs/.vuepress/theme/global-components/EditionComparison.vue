<template>
  <table class="edition-comparison">
    <tbody>
      <fragment v-for="(feature, index) in features" :key="index">
        <tr v-if="feature.category" class="category">
          <td class="feature">
            <b>{{ feature.category }}</b>
          </td>
          <td class="edition-column">
            <badge text="Lite" type="edition" vertical="middle">Lite</badge>
          </td>
          <td class="edition-column">
            <badge type="edition" vertical="middle">Pro</badge>
          </td>
        </tr>
        <tr v-for="item in feature.items" :key="item.name">
          <td>
            <span>{{ item.name }}</span>
            <info-hud v-if="item.info" class="info">
              <span class="smaller">{{ item.info }}</span>
            </info-hud>
          </td>
          <td class="support">
            <check-mark v-if="item.lite" label="supported in Commerce Lite" />
          </td>
          <td class="support">
            <check-mark v-if="item.pro" label="supported in Commerce Pro" />
          </td>
        </tr>
      </fragment>
    </tbody>
  </table>
</template>

<script>
import Badge from "./Badge";
import CheckMark from "./CheckMark";
import InfoHud from "./InfoHud";
import { Fragment } from "vue-fragment";

export default {
  components: { Fragment, CheckMark, InfoHud, Badge },
  data() {
    return {
      features: [
        {
          category: "Checkout",
          items: [
            {
              name: "Front end cart",
              lite: true,
              pro: true,
            },
            {
              name: "Add products/purchasables to cart",
              lite: true,
              pro: true,
            },
            {
              name: "Attach arbitrary data to line items",
              lite: true,
              pro: true,
            },
            {
              name: "Update line item quantity",
              lite: true,
              pro: true,
            },
            {
              name: "Add multiple line items to cart",
              pro: true,
            },
          ],
        },
        {
          category: "Promotions",
          items: [
            {
              name: "Sales",
              lite: true,
              pro: true,
            },
            {
              name: "Discounts",
              info:
                "Custom rules that can reduce the price of items in the cart based on things like minimum order quantity or price.",
              pro: true,
            },
            {
              name: "Coupon codes",
              pro: true,
            },
          ],
        },
        {
          category: "Shipping",
          items: [
            {
              name: "Single shipping cost+price for orders",
              lite: true,
              pro: true,
            },
            {
              name: "Unlimited shipping methods, categories, and zones",
              info: "Any shipping method may include complex shipping rules.",
              pro: true,
            },
            {
              name: "Customer shipping method selection",
              pro: true,
            },
          ],
        },
        {
          category: "Taxes",
          items: [
            {
              name: "Single tax rate for all orders",
              lite: true,
              pro: true,
            },
            {
              name: "Unlimited tax categories and zones",
              info:
                "Custom tax rules based on configurable multi-state or multi-country zones.",
              pro: true,
            },
            {
              name: "VAT Business ID validation for tax rates",
              pro: true,
            },
          ],
        },
        {
          category: "Orders",
          items: [
            {
              name: "Create orders in the control panel",
              pro: true,
            },
            {
              name:
                "Custom PHP adjusters for modifying tax, shipping, and discounts",
              pro: true,
            },
          ],
        },
      ],
    };
  },
};
</script>

<style lang="postcss">
table.edition-comparison {
  @apply border-0 relative overflow-visible;

  td,
  tr {
    @apply border-0;

    &:first-child,
    &:last-child {
      @apply border-0;
    }
  }

  .theme-default-content {
  }

  .category {
    @apply border-t-0 font-bold;
  }

  .category .feature {
    @apply text-xl pl-0;
  }

  .category td {
    @apply pt-8 border-0;
  }

  .edition-column {
    @apply text-center;
    width: 15%;
  }

  .support {
    @apply relative overflow-visible font-bold text-center;
    svg {
      @apply mx-auto;
    }
  }

  .info {
    @apply ml-3;
  }

  .smaller {
    @apply text-sm leading-tight;
  }
}
</style>
