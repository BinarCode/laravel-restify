const replaceAnchorPrefix = require("../util/replace-anchor-prefixes");

/**
 * Craft URLs.
 */
const craft = [
  {
    source: "craft3:craft\\elements\\Asset",
    result: "https://docs.craftcms.com/api/v3/craft-elements-asset.html"
  },
  {
    source: "craft3:CraftTemplatesService::render()",
    result:
      "https://docs.craftcms.com/api/v3/crafttemplatesservice.html#method-render"
  },
  {
    source: "craft2:Craft::t()",
    result: "https://docs.craftcms.com/api/v2/craft.html#method-t"
  },
  {
    source: "craft2:Craft\\UserModel",
    result: "https://docs.craftcms.com/api/v2/craft-usermodel.html"
  },
  {
    source: "not-an-api-link",
    result: undefined
  }
];

test("handles Craft API links", () => {
  craft.forEach(item => {
    expect(replaceAnchorPrefix.replacePrefix(item.source)).toBe(item.result);
  });
});


/**
 * Commerce URLs.
 */
const commerce = [
  {
    source: "commerce3:craft\\commerce\\elements\\Order",
    result: "https://docs.craftcms.com/commerce/api/v3/craft-commerce-elements-order.html"
  },
];

test("handles Commerce API links", () => {
  commerce.forEach(item => {
    expect(replaceAnchorPrefix.replacePrefix(item.source)).toBe(item.result);
  });
});


/**
 * Yii URLs.
 */
const yii = [
  {
    source: "yii2:yii\\db\\Query::select()",
    result: "https://www.yiiframework.com/doc/api/2.0/yii-db-query#select()-detail"
  },
  {
    source: "yii1:CSecurityManager::validateData()",
    result: "https://www.yiiframework.com/doc/api/1.1/CSecurityManager#validateData-detail"
  },
];

test("handles Yii API links", () => {
  yii.forEach(item => {
    expect(replaceAnchorPrefix.replacePrefix(item.source)).toBe(item.result);
  });
});


/**
 * Craft config URLs.
 */
const config = [
  {
    source: "config3:softDeleteDuration",
    result: "/3.x/config/config-settings.md#softdeleteduration"
  },
  {
    source: "config2:defaultImageQuality",
    result: "/2.x/config-settings.md#defaultimagequality"
  },
];

test("handles config links", () => {
  config.forEach(item => {
    expect(replaceAnchorPrefix.replacePrefix(item.source)).toBe(item.result);
  });
});


/**
 * Bogus URLs and prefixes that shouldn’t do anything.
 */
const invalid = [
  {
    source: "not-an-api-link",
    result: undefined
  },
  {
    source: "craft2:invalidMethodReference()",
    result: undefined
  },
  {
    source: "craft8:CraftDroneService::findBottle()",
    result: undefined
  },
];

test("ignores invalid links & prefixes", () => {
  invalid.forEach(item => {
    expect(replaceAnchorPrefix.replacePrefix(item.source)).toBe(item.result);
  });
});
