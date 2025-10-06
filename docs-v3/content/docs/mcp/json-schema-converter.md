---
title: JSON Schema Converter
menuTitle: JSON Schema Converter
category: MCP
position: 5
---

The `JsonSchemaFromRulesAction` is a powerful utility that automatically converts Laravel validation rules into JSON Schema format. This enables AI agents and other consumers to understand the expected data structure and validation constraints of your API endpoints.

## Overview

While this converter is automatically used by Restify Actions and Getters for MCP integration, you can also use it directly in your custom controllers, form requests, or any other part of your application where you need to expose validation rules as JSON Schema.

## Why JSON Schema?

JSON Schema provides a standardized way to describe data structures that:
- **AI Agents** can understand parameter requirements and constraints
- **API Documentation** tools can auto-generate accurate API specs
- **Frontend Applications** can validate data before submission
- **Third-party Integrations** can programmatically understand your API

## Basic Usage

The `JsonSchemaFromRulesAction` is an invocable class that takes two parameters:

1. **JsonSchema Factory** - An instance of `Illuminate\JsonSchema\JsonSchemaTypeFactory`
2. **Validation Rules** - Standard Laravel validation rules array

```php
use Binaryk\LaravelRestify\MCP\Actions\JsonSchemaFromRulesAction;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

$converter = new JsonSchemaFromRulesAction();
$schema = new JsonSchemaTypeFactory();

$rules = [
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email'],
    'age' => ['required', 'integer', 'min:18', 'max:120'],
    'is_active' => ['boolean'],
];

$jsonSchema = $converter($schema, $rules);

// $jsonSchema is now an array of JSON Schema types
// ['name' => StringType, 'email' => StringType, 'age' => IntegerType, 'is_active' => BooleanType]
```

## Using in Custom Controllers

### Example 1: Custom API Endpoint

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Binaryk\LaravelRestify\MCP\Actions\JsonSchemaFromRulesAction;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

class UserRegistrationController extends Controller
{
    public function schema()
    {
        $converter = new JsonSchemaFromRulesAction();
        $schema = new JsonSchemaTypeFactory();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'terms_accepted' => ['required', 'accepted'],
        ];

        $jsonSchema = $converter($schema, $rules);

        // Convert to JSON response
        return response()->json([
            'schema' => array_map(fn($type) => $type->toArray(), $jsonSchema),
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate($this->getRules());

        // Registration logic...
    }

    private function getRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'terms_accepted' => ['required', 'accepted'],
        ];
    }
}
```

**Generated JSON Schema:**
```json
{
    "schema": {
        "name": {
            "type": "string",
            "maxLength": 255,
            "description": "This field is required. Must not be greater than 255 characters.",
            "required": true
        },
        "email": {
            "type": "string",
            "format": "email",
            "description": "This field is required. Must be a valid email address.",
            "required": true
        },
        "password": {
            "type": "string",
            "minLength": 8,
            "description": "This field is required. Must be at least 8 characters.",
            "required": true
        },
        "date_of_birth": {
            "type": "string",
            "format": "date",
            "description": "This field is required. Must be before: today",
            "required": true
        },
        "phone": {
            "type": "string",
            "pattern": "^[0-9]{10}$"
        },
        "terms_accepted": {
            "type": "string",
            "description": "This field is required."
        }
    }
}
```

### Example 2: Dynamic Form Builder

```php
<?php

namespace App\Http\Controllers;

use Binaryk\LaravelRestify\MCP\Actions\JsonSchemaFromRulesAction;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

class DynamicFormController extends Controller
{
    public function getFormSchema(string $formType)
    {
        $converter = new JsonSchemaFromRulesAction();
        $schema = new JsonSchemaTypeFactory();

        $rules = match($formType) {
            'contact' => $this->getContactFormRules(),
            'survey' => $this->getSurveyFormRules(),
            'application' => $this->getApplicationFormRules(),
            default => throw new \InvalidArgumentException("Unknown form type: {$formType}"),
        };

        $jsonSchema = $converter($schema, $rules);

        return response()->json([
            'form_type' => $formType,
            'fields' => array_map(fn($type) => $type->toArray(), $jsonSchema),
        ]);
    }

    private function getContactFormRules(): array
    {
        return [
            'subject' => ['required', 'string', 'in:support,sales,general'],
            'message' => ['required', 'string', 'min:10', 'max:1000'],
            'priority' => ['required', 'integer', 'between:1,5'],
        ];
    }

    private function getSurveyFormRules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,10'],
            'feedback' => ['nullable', 'string', 'max:500'],
            'would_recommend' => ['required', 'boolean'],
        ];
    }

    private function getApplicationFormRules(): array
    {
        return [
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:2048'],
            'cover_letter' => ['required', 'string', 'min:100'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:50'],
            'references' => ['array', 'min:2'],
            'references.*' => ['email'],
        ];
    }
}
```

### Example 3: API Documentation Endpoint

```php
<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Controller;
use Binaryk\LaravelRestify\MCP\Actions\JsonSchemaFromRulesAction;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

class ApiDocsController extends Controller
{
    public function endpoints()
    {
        $converter = new JsonSchemaFromRulesAction();
        $schema = new JsonSchemaTypeFactory();

        return response()->json([
            'endpoints' => [
                [
                    'path' => '/api/users',
                    'method' => 'POST',
                    'description' => 'Create a new user',
                    'request_schema' => $this->convertToArray(
                        $converter($schema, $this->getUserCreationRules())
                    ),
                ],
                [
                    'path' => '/api/posts',
                    'method' => 'POST',
                    'description' => 'Create a new post',
                    'request_schema' => $this->convertToArray(
                        $converter($schema, $this->getPostCreationRules())
                    ),
                ],
            ],
        ]);
    }

    private function convertToArray(array $jsonSchema): array
    {
        return array_map(fn($type) => $type->toArray(), $jsonSchema);
    }

    private function getUserCreationRules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    private function getPostCreationRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,published,archived'],
            'tags' => ['array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}
```

## Supported Validation Rules

The converter supports **108 Laravel validation rules**, providing comprehensive coverage for all common and advanced validation scenarios:

### String Rules
- `accepted`, `alpha`, `alpha_dash`, `alpha_num`, `ascii`
- `email`, `string`, `lowercase`, `uppercase`
- `starts_with`, `ends_with`, `contains`, `doesnt_contain`
- `regex`, `not_regex`, `uuid`, `ulid`, `mac_address`

### Numeric Rules
- `integer`, `numeric`, `decimal`, `digits`, `digits_between`
- `min`, `max`, `between`, `gt`, `gte`, `lt`, `lte`
- `min_digits`, `max_digits`, `multiple_of`

### Date & Time Rules
- `date`, `date_format`, `date_equals`
- `before`, `before_or_equal`, `after`, `after_or_equal`
- `timezone`

### Array Rules
- `array`, `in`, `in_array`, `in_array_keys`, `not_in`
- `list`, `distinct`, `required_array_keys`

### File Rules
- `file`, `image`, `mimes`, `mimetypes`
- `dimensions`, `extensions`

### Conditional Rules
- `required`, `required_if`, `required_unless`, `required_with`, `required_without`
- `required_if_accepted`, `required_if_declined`
- `prohibited`, `prohibited_if`, `prohibited_unless`
- `exclude`, `exclude_if`, `exclude_unless`, `exclude_with`
- `present`, `present_if`, `present_unless`, `present_with`
- `missing`, `missing_if`, `missing_unless`, `missing_with`

### Database Rules
- `exists`, `unique`

### Special Rules
- `confirmed`, `same`, `different`
- `nullable`, `filled`, `bail`
- `url`, `active_url`, `ip`, `ipv4`, `ipv6`, `json`
- `hex_color`, `boolean`

## Automatic Field Descriptions

One of the most powerful features of the JSON Schema converter is **automatic description generation**. Every validation rule is converted into a human-readable description that helps AI agents, documentation tools, and developers understand field requirements.

### How Descriptions Work

The converter automatically generates contextual descriptions based on the validation rules applied to each field. These descriptions are:

- **Context-Aware**: The same rule generates different descriptions based on field type
- **Cumulative**: Multiple rules combine to create comprehensive descriptions
- **AI-Friendly**: Formatted specifically for AI agents and documentation tools

### Description Examples by Rule Type

#### Type Validators

Each type validator adds a clear description of the expected data type:

```php
$rules = [
    'name' => ['string'],
    'age' => ['integer'],
    'active' => ['boolean'],
    'score' => ['numeric'],
    'tags' => ['array'],
];

// Generated descriptions:
// name: "Must be a string"
// age: "Must be an integer"
// active: "Must be a boolean (true/false)"
// score: "Must be a numeric value"
// tags: "Must be an array"
```

#### Size Constraints (Context-Aware)

The `min`, `max`, `between`, and `size` rules generate **context-aware descriptions** based on the field type:

```php
// For strings - describes character length
['username' => ['string', 'min:3', 'max:50']]
// Description: "Minimum length: 3 characters", "Maximum length: 50 characters"

// For arrays - describes item count
['tags' => ['array', 'min:1', 'max:5']]
// Description: "Minimum items: 1", "Maximum items: 5"

// For numbers - describes numeric value
['age' => ['integer', 'min:18', 'max:120']]
// Description: "Minimum value: 18", "Maximum value: 120"

// For ranges
['priority' => ['integer', 'between:1,10']]
// Description: "Must be between 1 and 10"

// For exact size
['code' => ['string', 'size:6']]
// Description: "Must be exactly 6 characters"
```

#### String Format Validators

Format validators explain the expected format:

```php
$rules = [
    'email' => ['email'],
    'website' => ['url'],
    'timezone' => ['timezone'],
    'user_id' => ['uuid'],
    'tracking_id' => ['ulid'],
    'ip_address' => ['ip'],
    'config' => ['json'],
];

// Generated descriptions:
// email: "Must be a valid email address"
// website: "Must be a valid URL"
// timezone: "Must be a valid timezone"
// user_id: "Must be a valid UUID"
// tracking_id: "Must be a valid ULID"
// ip_address: "Must be a valid IP address"
// config: "Must be valid JSON"
```

#### String Pattern Validators

Pattern validators describe character requirements:

```php
$rules = [
    'letters' => ['alpha'],
    'username' => ['alpha_dash'],
    'alphanumeric' => ['alpha_num'],
];

// Generated descriptions:
// letters: "Must contain only alphabetic characters"
// username: "Must contain only alpha-numeric characters, dashes, and underscores"
// alphanumeric: "Must contain only alpha-numeric characters"
```

#### Enum/Choice Validators

The `in` rule lists all valid options:

```php
['status' => ['in:pending,approved,rejected']]
// Description: "Must be one of: pending, approved, rejected"

['role' => ['in:admin,editor,viewer']]
// Description: "Must be one of: admin, editor, viewer"
```

#### Date Validators

Date rules explain temporal constraints:

```php
$rules = [
    'birth_date' => ['date', 'before:today'],
    'start_date' => ['date', 'after:2024-01-01'],
    'appointment' => ['date_format:Y-m-d H:i'],
];

// Generated descriptions:
// birth_date: "Must be a valid date format", "This is a date attribute. Must be before: today"
// start_date: "Must be a valid date format", "Date attribute, must be after: 2024-01-01"
// appointment: "Must match date format: Y-m-d H:i"
```

#### File Validators

File rules describe allowed file types:

```php
$rules = [
    'avatar' => ['image'],
    'document' => ['file', 'mimes:pdf,doc,docx'],
    'attachment' => ['mimetypes:application/pdf,image/jpeg'],
];

// Generated descriptions:
// avatar: "Must be a valid image file"
// document: "Must be a valid file", "Allowed file extensions: pdf, doc, docx"
// attachment: "Allowed MIME types: application/pdf, image/jpeg"
```

#### Database Validators

Database rules indicate persistence requirements:

```php
$rules = [
    'user_id' => ['exists:users,id'],
    'email' => ['unique:users,email'],
];

// Generated descriptions:
// user_id: "Must exist in the database"
// email: "Must be unique in the database"
```

#### Required/Optional Validators

```php
$rules = [
    'name' => ['required', 'string'],
    'bio' => ['nullable', 'string'],
];

// Generated descriptions:
// name: "This field is required", "Must be a string"
// bio: "This field is optional", "Must be a string"
```

### Combined Descriptions Example

When multiple rules are applied, the descriptions work together to provide complete field documentation:

```php
$rules = [
    'email' => ['required', 'email', 'unique:users'],
    'age' => ['required', 'integer', 'min:18', 'max:120'],
    'tags' => ['array', 'min:1', 'max:5'],
    'tags.*' => ['string', 'in:tech,design,business'],
];

// Generated JSON Schema with descriptions:
{
    "email": {
        "type": "string",
        "format": "email",
        "description": "This field is required. Must be a valid email address. Must be unique in the database.",
        "required": true
    },
    "age": {
        "type": "integer",
        "minimum": 18,
        "maximum": 120,
        "description": "This field is required. Must be an integer. Minimum value: 18. Maximum value: 120.",
        "required": true
    },
    "tags": {
        "type": "array",
        "minItems": 1,
        "maxItems": 5,
        "description": "Must be an array. Minimum items: 1. Maximum items: 5.",
        "items": {
            "type": "string",
            "enum": ["tech", "design", "business"],
            "description": "Must be a string. Must be one of: tech, design, business"
        }
    }
}
```

### Why Automatic Descriptions Matter

**For AI Agents:**
- Understand exact field requirements without guessing
- Generate correct data that passes validation
- Provide better error messages to users

**For Documentation:**
- Auto-generated API docs are always accurate
- No manual description writing needed
- Single source of truth for validation logic

**For Developers:**
- Quickly understand field constraints
- Reduce onboarding time for new team members
- Consistent documentation across all endpoints

### Customizing Descriptions

If you need to override the automatic descriptions, you can do so in your Field classes by using the `description()` method:

```php
use Binaryk\LaravelRestify\Fields\Field;

Field::make('age')
    ->rules('required', 'integer', 'min:18')
    ->description('User age - must be 18 or older for account creation');

// This custom description will be used instead of the auto-generated one
```

## Advanced Examples

### Nested Arrays

The converter automatically handles nested array validation:

```php
$rules = [
    'users' => ['required', 'array', 'min:1'],
    'users.*.name' => ['required', 'string', 'max:255'],
    'users.*.email' => ['required', 'email'],
    'users.*.roles' => ['array'],
    'users.*.roles.*' => ['string', 'in:admin,editor,viewer'],
];

$jsonSchema = $converter($schema, $rules);
```

**Generated Schema:**
```json
{
    "users": {
        "type": "array",
        "minItems": 1,
        "items": {
            "type": "object",
            "properties": {
                "name": {
                    "type": "string",
                    "maxLength": 255,
                    "required": true
                },
                "email": {
                    "type": "string",
                    "format": "email",
                    "required": true
                },
                "roles": {
                    "type": "array",
                    "items": {
                        "type": "string",
                        "enum": ["admin", "editor", "viewer"]
                    }
                }
            }
        }
    }
}
```

### Complex Validation Scenarios

```php
$rules = [
    'event_name' => ['required', 'string', 'max:100'],
    'start_date' => ['required', 'date', 'after:today'],
    'end_date' => ['required', 'date', 'after:start_date'],
    'max_attendees' => ['required', 'integer', 'between:10,1000'],
    'ticket_price' => ['required', 'numeric', 'min:0', 'decimal:2'],
    'categories' => ['required', 'array', 'min:1', 'max:5'],
    'categories.*' => ['string', 'in:conference,workshop,webinar,meetup'],
    'organizer_email' => ['required', 'email', 'exists:users,email'],
    'registration_url' => ['nullable', 'url', 'active_url'],
    'is_public' => ['boolean'],
];

$jsonSchema = $converter($schema, $rules);
```

**Key Generated Constraints:**
- `event_name`: max length 100
- `start_date`: must be after today
- `end_date`: must be after start_date
- `max_attendees`: between 10 and 1000
- `ticket_price`: minimum 0, decimal with 2 places
- `categories`: array with 1-5 items, each from specified enum
- `organizer_email`: valid email that exists in users table
- `registration_url`: valid, active URL
- `is_public`: boolean type

## Integration with Form Requests

You can easily expose JSON Schema from your Form Requests:

```php
<?php

namespace App\Http\Requests;

use Binaryk\LaravelRestify\MCP\Actions\JsonSchemaFromRulesAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'shipping_address' => ['required', 'string'],
            'billing_address' => ['nullable', 'string'],
            'payment_method' => ['required', 'in:credit_card,paypal,bank_transfer'],
            'coupon_code' => ['nullable', 'string', 'exists:coupons,code'],
        ];
    }

    public function jsonSchema(): array
    {
        $converter = new JsonSchemaFromRulesAction();
        $schema = new JsonSchemaTypeFactory();

        $jsonSchema = $converter($schema, $this->rules());

        return array_map(fn($type) => $type->toArray(), $jsonSchema);
    }
}

// In your controller
public function schema()
{
    $request = new CreateOrderRequest();

    return response()->json([
        'schema' => $request->jsonSchema(),
    ]);
}
```

## Use Cases

### 1. AI Agent Integration

Automatically generate schemas for AI agents to understand your API:

```php
// Expose schema for AI consumption
Route::get('/api/schema/create-user', function () {
    $converter = new JsonSchemaFromRulesAction();
    $schema = new JsonSchemaTypeFactory();

    $rules = User::getValidationRules();
    $jsonSchema = $converter($schema, $rules);

    return response()->json([
        'operation': 'create_user',
        'input_schema' => array_map(fn($type) => $type->toArray(), $jsonSchema),
    ]);
});
```

### 2. Frontend Form Generation

Generate frontend forms dynamically based on backend validation:

```php
public function formDefinition(string $entityType)
{
    $converter = new JsonSchemaFromRulesAction();
    $schema = new JsonSchemaTypeFactory();

    $rules = $this->getRulesForEntity($entityType);
    $jsonSchema = $converter($schema, $rules);

    return response()->json([
        'entity' => $entityType,
        'fields' => $this->transformForFrontend($jsonSchema),
    ]);
}

private function transformForFrontend(array $jsonSchema): array
{
    return array_map(function($type) {
        $config = $type->toArray();

        return [
            'type' => $this->mapToInputType($config['type']),
            'label' => Str::title(str_replace('_', ' ', $config['name'] ?? '')),
            'required' => $config['required'] ?? false,
            'validation' => $config,
        ];
    }, $jsonSchema);
}
```

### 3. OpenAPI Documentation

Generate OpenAPI/Swagger documentation automatically:

```php
public function generateOpenApiSpec()
{
    $converter = new JsonSchemaFromRulesAction();
    $schema = new JsonSchemaTypeFactory();

    $endpoints = [
        'POST /users' => User::getCreationRules(),
        'PUT /users/{id}' => User::getUpdateRules(),
        'POST /posts' => Post::getCreationRules(),
    ];

    $schemas = [];

    foreach ($endpoints as $endpoint => $rules) {
        $jsonSchema = $converter($schema, $rules);
        $schemas[$endpoint] = array_map(fn($type) => $type->toArray(), $jsonSchema);
    }

    return response()->json([
        'openapi' => '3.0.0',
        'paths' => $this->transformToOpenApiPaths($schemas),
    ]);
}
```

## Best Practices

### 1. Reuse Validation Rules

Define your validation rules once and use them everywhere:

```php
class User extends Model
{
    public static function getCreationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public static function getUpdateRules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'email' => ['email', 'unique:users,email,' . request()->route('id')],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }
}

// Use in Form Request
public function rules(): array
{
    return User::getCreationRules();
}

// Use for JSON Schema
$jsonSchema = $converter($schema, User::getCreationRules());
```

### 2. Cache Schema Generation

For production environments, cache generated schemas:

```php
public function getSchema()
{
    return Cache::remember('api.schema.users', 3600, function () {
        $converter = new JsonSchemaFromRulesAction();
        $schema = new JsonSchemaTypeFactory();

        $jsonSchema = $converter($schema, User::getCreationRules());

        return array_map(fn($type) => $type->toArray(), $jsonSchema);
    });
}
```

### 3. Version Your Schemas

Include schema versioning for API evolution:

```php
public function getSchemaVersion(string $version)
{
    $converter = new JsonSchemaFromRulesAction();
    $schema = new JsonSchemaTypeFactory();

    $rules = match($version) {
        'v1' => $this->getRulesV1(),
        'v2' => $this->getRulesV2(),
        default => throw new \InvalidArgumentException("Unknown version: {$version}"),
    };

    $jsonSchema = $converter($schema, $rules);

    return response()->json([
        'version' => $version,
        'schema' => array_map(fn($type) => $type->toArray(), $jsonSchema),
    ]);
}
```

## How It Works

Internally, the `JsonSchemaFromRulesAction`:

1. **Parses Laravel Rules**: Uses Laravel's validation rule parser to extract individual rules
2. **Maps to JSON Schema Types**: Each Laravel validation rule is mapped to corresponding JSON Schema constraints
3. **Handles Wildcards**: Automatically processes nested array rules (e.g., `items.*`)
4. **Builds Type Hierarchy**: Creates proper JSON Schema type structures with all constraints
5. **Marks Required Fields**: Identifies required fields and marks them in the schema

**Example Internal Flow:**

```php
// Input
['age' => ['required', 'integer', 'min:18', 'max:120']]

// Processing
1. Parse 'required' → Mark field as required
2. Parse 'integer' → Set type to 'integer'
3. Parse 'min:18' → Add minimum: 18
4. Parse 'max:120' → Add maximum: 120

// Output
{
    "type": "integer",
    "minimum": 18,
    "maximum": 120,
    "required": true
}
```

## Conclusion

The `JsonSchemaFromRulesAction` provides a powerful bridge between Laravel's validation system and JSON Schema, enabling:

- **Consistent Validation**: Same rules for runtime validation and schema documentation
- **AI Integration**: Automatic schema generation for MCP and other AI systems
- **Developer Experience**: Single source of truth for validation logic
- **API Documentation**: Auto-generated, always-accurate API specifications

Whether you're building AI-powered applications, generating dynamic forms, or documenting your API, this converter ensures your validation rules are accurately represented in a standard, machine-readable format.
