---
title: JSON Schema Converter
menuTitle: JSON Schema Converter
category: MCP
position: 5
---

`JsonSchemaFromRulesAction` converts Laravel validation rules into `Illuminate\\JsonSchema` types that are ready to surface to Model Context Protocol (MCP) agents. Restify runs this converter automatically for MCP actions and getters, but you can invoke it yourself whenever you need a programmatic JSON Schema.

## How It Works

- **Rule parsing** – `JsonSchemaFromRulesAction` normalizes the rules through Laravel's validator (`validator()->make()->getRules()`) so aliases like `string|min:3` are expanded exactly as the framework would interpret them.
- **Rule to schema mapping** – The heavy lifting happens inside `SchemaAttributes` where more than one hundred `validate{Rule}` methods translate individual validation rules into schema metadata (type, min/max, enum, textual description, etc.).
- **Array wildcards** – After each attribute is processed, `processWildcardRules()` inspects `attribute.*` definitions and attaches their schema to the parent array items, ensuring nested collection rules (for example `tags.*`) are represented correctly.

## Using the Helper Function

The simplest way to convert validation rules to JSON Schema is using the `mcpSchema()` helper function:

```php
$rules = [
    'event_date' => ['required', 'date', 'before:2025-12-31'],
    'age' => ['required', 'integer', 'min:18'],
];

$types = mcpSchema($rules);
$payload = array_map(fn ($type) => $type->toArray(), $types);
```

This helper automatically resolves the `JsonSchema` instance and runs the converter, returning an array of `Type` instances keyed by attribute name.

## Using the Converter Directly

If you need more control, you can instantiate the converter directly:

```php
use Binaryk\LaravelRestify\MCP\Actions\JsonSchemaFromRulesAction;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

$converter = new JsonSchemaFromRulesAction();
$schema = new JsonSchemaTypeFactory();

$rules = [
    'event_date' => ['required', 'date', 'before:2025-12-31'],
    'age' => ['required', 'integer', 'min:18'],
];

$types = $converter($schema, $rules);
$payload = array_map(fn ($type) => $type->toArray(), $types);
```

`$types` is an array of `Type` instances keyed by attribute. Turn them into JSON (as above) or compose them inside an `ObjectType` if you need an OpenAPI-style structure with a `required` array.

## Practical Examples

### Event Scheduling

```php
$rules = [
    'event_date' => ['required', 'date', 'before:2025-12-31'],
];
```

Result:

```json
{
  "event_date": {
    "type": "string",
    "description": "This is a date attribute. Must be before: 2025-12-31",
    "required": true
  }
}
```

### Numeric Bounds for MCP Actions

```php
$rules = [
    'quantity' => ['required', 'integer', 'min:1', 'max:100'],
];
```

Result:

```json
{
  "quantity": {
    "type": "integer",
    "minimum": 1,
    "maximum": 100,
    "description": "Maximum value: 100"
  }
}
```

`SchemaAttributes::validateMin()` and `validateMax()` detect the integer type and translate the Laravel limits into JSON Schema `minimum` / `maximum` constraints so MCP agents know the allowed range.

### Array Items with Wildcard Rules

```php
$rules = [
    'tags' => ['required', 'array', 'min:1', 'max:5'],
    'tags.*' => ['string', 'max:20'],
];
```

Result:

```json
{
  "tags": {
    "type": "array",
    "minItems": 1,
    "maxItems": 5,
    "description": "Maximum items: 5",
    "items": {
      "type": "string",
      "maxLength": 20,
      "description": "Maximum length: 20 characters"
    }
  }
}
```

`processWildcardRules()` notices the `tags.*` rule, builds a temporary schema for the wildcard attribute, and attaches it to `tags` as the `items` definition. The same mechanism powers nested MCP action parameters such as cart line items or recipient lists.

## Tips

- The converter gracefully handles Closure-based rules and custom `Rule` objects by defaulting to `string` types with descriptive text, so MCP consumers still receive a hint about the constraint.
- If you need bespoke descriptions, add your own rule-specific method to a class that uses `SchemaAttributes`, or post-process the returned `Type` objects before serializing them.
- When exposing the schema publicly, consider wrapping the output in an `ObjectType` (`$schema->object()->properties($types)`) to emit a JSON Schema document that lists required properties explicitly.

With just a handful of rule definitions you can now expose accurate JSON Schema payloads to MCP agents, front-end builders, or any consumer expecting machine-readable validation metadata.
