---
title: MCP Getters
menuTitle: Getters
category: MCP
position: 4
---

Laravel Restify Getters can be exposed to AI agents through the Model Context Protocol (MCP), enabling intelligent data retrieval and analytics. This page covers how to configure getters for MCP integration and optimize them for AI consumption.

## Enabling MCP Getters

To expose getters to the MCP server, you must enable them in your repository by overriding the `mcpAllowsGetters()` method:

```php
use Binaryk\LaravelRestify\Traits\HasMcpTools;

#[Model(User::class)]
class UserRepository extends Repository
{
    use HasMcpTools;

    // Enable getter tools for AI agents
    public function mcpAllowsGetters(): bool
    {
        return true; // Default: false
    }

    public function getters(RestifyRequest $request): array
    {
        return [
            StripeInformationGetter::new()->onlyOnShow(),
            UserAnalyticsGetter::new()->onlyOnShow(),
            EngagementMetricsGetter::new()->onlyOnShow(),
        ];
    }
}
```

Once enabled, all getters defined in the `getters()` method will be automatically exposed as MCP tools to AI agents.

## Getter Descriptions for AI Agents

Providing clear descriptions helps AI agents understand when and how to retrieve data using your getters. You can customize getter descriptions using the `$description` property or by overriding the `description()` method.

### Using the Description Property

```php
use Binaryk\LaravelRestify\Getters\Getter;
use Illuminate\Http\Request;

class StripeInformationGetter extends Getter
{
    public string $description = 'Retrieve Stripe customer information including subscription status, payment methods, and billing history for a specific user.';

    public function handle(Request $request, User $user)
    {
        return response()->json([
            'data' => $user->asStripeCustomer(),
        ]);
    }
}
```

### Using the Description Method

For dynamic descriptions based on context:

```php
class UserAnalyticsGetter extends Getter
{
    public function description(RestifyRequest $request): string
    {
        $userRole = $request->user()?->role;

        if ($userRole === 'admin') {
            return 'Retrieve comprehensive user analytics including activity metrics, engagement scores, and revenue attribution.';
        }

        return 'Retrieve basic user analytics including login history and profile completion status.';
    }

    //...
}
```

## Validation Rules for AI Schema

The `rules()` method is crucial for MCP integration. Restify automatically converts your Laravel validation rules into JSON Schema that AI agents understand and use for parameter validation.

```php
class UserAnalyticsGetter extends Getter
{
    public string $description = 'Get user analytics and activity metrics for a specific date range';

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'before:end_date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'metrics' => ['array'],
            'metrics.*' => ['string', 'in:views,clicks,conversions,revenue'],
            'granularity' => ['string', 'in:daily,weekly,monthly'],
            'include_comparison' => ['boolean'],
        ];
    }

    public function handle(Request $request, User $user)
    {
        $validated = $request->validate($this->rules());

        return response()->json([
            'data' => $user->analytics($validated),
        ]);
    }
}
```

The AI agent will automatically receive a JSON Schema with:
- `start_date`: date string (required, must be before end_date)
- `end_date`: date string (required, must be after start_date)
- `metrics`: array (optional)
- `metrics.*`: string items (must be one of: views, clicks, conversions, revenue)
- `granularity`: string (must be one of: daily, weekly, monthly)
- `include_comparison`: boolean (optional)

## Getter Scope and MCP Enforcement

Unlike Actions, Getters in Laravel Restify are designed specifically for **single resource data retrieval**. Getters always operate on a single model instance and require AI agents to provide an `id` parameter. Getters do not support batch operations (`resources` parameter) or standalone mode like Actions do.

### Important Differences from Actions

- **No `standalone()` method**: Getters always operate on a single resource
- **No `onlyOnIndex()` method**: Getters don't support batch operations
- **No `resources` parameter**: AI agents never provide a list of IDs
- **Always uses `onlyOnShow()`**: This is the only scope modifier for getters

### Show Getters (Single Resource Only)

Getters retrieve data for a single resource. When using `onlyOnShow()`, AI agents are **required** to provide an `id` parameter:

```php
class StripeInformationGetter extends Getter
{
    public string $description = 'Retrieve detailed Stripe customer information and subscription data for a specific user';

    public function rules(): array
    {
        return [
            'include_invoices' => ['boolean'],
            'include_payment_methods' => ['boolean'],
        ];
    }

    public function handle(Request $request, User $user)
    {
        $validated = $request->validate($this->rules());

        return response()->json([
            'data' => [
                'customer' => $user->asStripeCustomer(),
                'invoices' => $validated['include_invoices'] ?? false
                    ? $user->invoices()
                    : null,
                'payment_methods' => $validated['include_payment_methods'] ?? false
                    ? $user->paymentMethods()
                    : null,
            ],
        ]);
    }
}

// In UserRepository
public function getters(RestifyRequest $request): array
{
    return [
        StripeInformationGetter::new()->onlyOnShow(),
    ];
}
```

**Generated MCP Tool Schema:**
```json
{
    "name": "users-stripe-information-getter-tool",
    "description": "Execute Stripe Information getter to retrieve data for a specific User record in the users repository.",
    "inputSchema": {
        "type": "object",
        "properties": {
            "id": {
                "type": "string",
                "title": "id",
                "description": "The ID of the resource (User) to perform the getter on.",
                "required": true
            },
            "include_invoices": {
                "type": "boolean"
            },
            "include_payment_methods": {
                "type": "boolean"
            }
        },
        "required": ["id"]
    }
}
```

**AI Agent Usage:**
```javascript
// Must provide the id
{
    "id": "123",
    "include_invoices": true,
    "include_payment_methods": true
}
```

## Complete Getter Example

Here's a comprehensive example showing all aspects of MCP-optimized getters:

```php
use Binaryk\LaravelRestify\Getters\Getter;
use Illuminate\Http\Request;

class UserEngagementMetricsGetter extends Getter
{
    // Clear description for AI agents
    public string $description = 'Retrieve detailed engagement metrics for a user including login frequency, feature usage, and interaction patterns over a specified time period.';

    // Comprehensive validation rules converted to JSON Schema
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'before:end_date'],
            'end_date' => ['required', 'date', 'after:start_date', 'before_or_equal:today'],
            'metrics' => ['array'],
            'metrics.*' => ['string', 'in:logins,features_used,time_spent,interactions'],
            'granularity' => ['string', 'in:hourly,daily,weekly'],
            'include_breakdown' => ['boolean'],
            'compare_to_average' => ['boolean'],
        ];
    }

    public function handle(Request $request, User $user)
    {
        $validated = $request->validate($this->rules());

        $metrics = $user->engagementMetrics(
            $validated['start_date'],
            $validated['end_date'],
            $validated['metrics'] ?? ['logins', 'features_used'],
            $validated['granularity'] ?? 'daily'
        );

        $response = [
            'user_id' => $user->id,
            'period' => [
                'start' => $validated['start_date'],
                'end' => $validated['end_date'],
            ],
            'metrics' => $metrics,
        ];

        if ($validated['include_breakdown'] ?? false) {
            $response['breakdown'] = $user->engagementBreakdown($validated);
        }

        if ($validated['compare_to_average'] ?? false) {
            $response['platform_average'] = User::averageEngagement($validated);
            $response['percentile'] = $user->engagementPercentile($validated);
        }

        return response()->json(['data' => $response]);
    }
}

// In UserRepository
public function getters(RestifyRequest $request): array
{
    return [
        UserEngagementMetricsGetter::new()->onlyOnShow(),
    ];
}
```

## Hiding Getters from MCP

You can selectively hide getters from MCP while keeping them available for regular API requests. This is useful when you want certain data to be accessible only through manual user requests but not to AI agents.

### Using hideFromMcp()

```php
public function getters(RestifyRequest $request): array
{
    return [
        // Available to both API and MCP
        UserAnalyticsGetter::new()->onlyOnShow(),

        // Available only to API requests, hidden from AI agents
        StripeInformationGetter::new()
            ->hideFromMcp()
            ->onlyOnShow(),

        // Conditionally hide based on environment
        DebugInformationGetter::new()
            ->hideFromMcp(app()->environment('production'))
            ->onlyOnShow(),

        // Available only in development for AI testing
        TestDataGetter::new()
            ->hideFromMcp(! app()->environment('local'))
            ->onlyOnShow(),
    ];
}
```

### Use Cases for Hiding Getters

**1. Sensitive Financial Data**
```php
public function getters(RestifyRequest $request): array
{
    return [
        // Never expose payment details to AI
        PaymentMethodsGetter::new()
            ->hideFromMcp()
            ->onlyOnShow(),

        // Never expose financial reports to AI
        RevenueBreakdownGetter::new()
            ->hideFromMcp()
            ->onlyOnShow(),
    ];
}
```

**2. Personal Identifiable Information (PII)**
```php
public function getters(RestifyRequest $request): array
{
    return [
        // Require human oversight for PII access
        FullUserDetailsGetter::new()
            ->hideFromMcp()
            ->onlyOnShow(),

        // Hide social security numbers from AI
        ComplianceDataGetter::new()
            ->hideFromMcp()
            ->onlyOnShow(),
    ];
}
```

**3. Debug and Internal Data**
```php
public function getters(RestifyRequest $request): array
{
    return [
        // Hide debug info in production
        SystemDiagnosticsGetter::new()
            ->hideFromMcp(app()->environment('production'))
            ->onlyOnShow(),

        // Internal metrics not for AI consumption
        InternalMetricsGetter::new()
            ->hideFromMcp()
            ->onlyOnShow(),
    ];
}
```

**4. Rate-Limited or Expensive Operations**
```php
public function getters(RestifyRequest $request): array
{
    return [
        // Prevent AI from triggering expensive external API calls
        ExternalDataSyncGetter::new()
            ->hideFromMcp()
            ->onlyOnShow(),

        // Rate-limited third-party service
        ThirdPartyIntegrationGetter::new()
            ->hideFromMcp()
            ->onlyOnShow(),
    ];
}
```

### Combining with Other Modifiers

The `hideFromMcp()` method works seamlessly with other getter modifiers:

```php
public function getters(RestifyRequest $request): array
{
    return [
        StripeInformationGetter::new()
            ->hideFromMcp()                    // Hide from AI agents
            ->onlyOnShow()                     // Only for single users
            ->canSee(fn() => $request->user()->can('view-billing')),  // Authorization

        SensitiveAnalyticsGetter::new()
            ->hideFromMcp(app()->environment('production'))  // Conditional hiding
            ->onlyOnShow()
            ->canSee(fn() => $request->user()->isAdmin()),
    ];
}
```

<alert type="warning">
Hidden getters are completely excluded from MCP tool discovery. AI agents will not see them in the available tools list and cannot execute them, even if they somehow know the getter URI key.
</alert>

<alert type="info">
Use `hideFromMcp()` liberally for sensitive data retrieval. It's better to be conservative and explicitly expose getters to AI rather than accidentally exposing sensitive information.
</alert>

## Conditional Getter Visibility

You can control which getters are visible to AI agents based on permissions:

```php
class UserRepository extends Repository
{
    use HasMcpTools;

    public function mcpAllowsGetters(): bool
    {
        // Only enable getters for specific roles
        return request()->user()?->hasRole(['admin', 'analyst']) ?? false;
    }

    public function getters(RestifyRequest $request): array
    {
        return [
            // Only admins can see financial data
            StripeInformationGetter::new()
                ->canSee(fn() => $request->user()?->hasRole('admin')),

            // Analysts and admins can see analytics
            UserAnalyticsGetter::new()
                ->canSee(fn() => $request->user()?->hasRole(['admin', 'analyst'])),

            // Everyone with getter access can see basic stats
            ActiveStatusGetter::new(),

            // Hidden from AI but available to API
            SensitiveDataGetter::new()
                ->hideFromMcp(),
        ];
    }
}
```

## Best Practices for MCP Getters

### 1. Write Clear Descriptions

```php
// Good: Specific, explains what data is retrieved
public string $description = 'Retrieve user subscription details including plan type, billing cycle, next payment date, and usage limits. Includes historical subscription changes if requested.';

// Poor: Too vague
public string $description = 'Get user subscription';
```

### 2. Use Comprehensive Validation Rules

```php
// Good: Detailed validation that generates rich JSON Schema
public function rules(): array
{
    return [
        'date_range' => ['required', 'string', 'in:7d,30d,90d,1y,custom'],
        'start_date' => ['required_if:date_range,custom', 'date'],
        'end_date' => ['required_if:date_range,custom', 'date', 'after:start_date'],
        'metrics' => ['array', 'min:1'],
        'metrics.*' => ['string', 'in:revenue,users,churn,mrr'],
        'group_by' => ['nullable', 'string', 'in:day,week,month'],
    ];
}

// Poor: Minimal validation
public function rules(): array
{
    return [
        'metrics' => ['array'],
    ];
}
```

### 3. Always Use onlyOnShow()

```php
// Getters always operate on a single resource and use onlyOnShow()
UserAnalyticsGetter::new()->onlyOnShow()

// Even for aggregate data, the getter retrieves it for a specific resource
StripeInformationGetter::new()->onlyOnShow()

// All getters require the AI to provide an id parameter
EngagementMetricsGetter::new()->onlyOnShow()
```

### 4. Optimize Response Size for AI Agents

```php
public function handle(Request $request, User $user)
{
    $validated = $request->validate($this->rules());

    // Only include requested data to minimize token usage
    $response = ['user_id' => $user->id];

    if ($validated['include_profile'] ?? false) {
        $response['profile'] = $user->profile;
    }

    if ($validated['include_subscriptions'] ?? false) {
        $response['subscriptions'] = $user->subscriptions;
    }

    if ($validated['include_activity'] ?? false) {
        $response['activity'] = $user->recentActivity(
            $validated['activity_limit'] ?? 10
        );
    }

    return response()->json(['data' => $response]);
}
```

### 5. Cache Expensive Operations

```php
public function handle(Request $request)
{
    $validated = $request->validate($this->rules());

    $cacheKey = "platform_stats_{$validated['period']}";

    return response()->json([
        'data' => Cache::remember($cacheKey, 300, function () use ($validated) {
            return [
                'total_users' => User::count(),
                'active_users' => User::active()->count(),
                'revenue' => $this->calculateRevenue($validated['period']),
                'growth' => $this->calculateGrowth($validated['period']),
            ];
        }),
    ]);
}
```

## Security Considerations

1. **Validate all input**: Always validate using `$request->validate($this->rules())`
2. **Check permissions**: Ensure users can only access data they're authorized to see
3. **Sanitize output**: Don't expose sensitive data unnecessarily
4. **Rate limit**: Consider rate limiting for expensive getters

```php
public function handle(Request $request, User $user)
{
    // Validate input
    $validated = $request->validate($this->rules());

    // Check authorization
    if (! $request->user()->can('view', $user)) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // Sanitize sensitive data
    $data = $user->analytics($validated);
    unset($data['internal_notes'], $data['admin_flags']);

    return response()->json(['data' => $data]);
}
```

## Common Getter Patterns

### Analytics Getter

```php
class UserActivityAnalyticsGetter extends Getter
{
    public string $description = 'Get detailed activity analytics for a user';

    public function rules(): array
    {
        return [
            'period' => ['required', 'in:7d,30d,90d'],
            'include_trends' => ['boolean'],
        ];
    }

    public function handle(Request $request, User $user)
    {
        $validated = $request->validate($this->rules());

        return response()->json([
            'data' => $user->activityAnalytics($validated),
        ]);
    }
}
```

### Financial Getter

```php
class UserRevenueBreakdownGetter extends Getter
{
    public string $description = 'Get revenue breakdown for a specific user by product and region';

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'currency' => ['string', 'in:USD,EUR,GBP'],
        ];
    }

    public function handle(Request $request, User $user)
    {
        $validated = $request->validate($this->rules());

        return response()->json([
            'data' => $user->revenueBreakdown($validated),
        ]);
    }
}
```

### Subscription Status Getter

```php
class SubscriptionStatusGetter extends Getter
{
    public string $description = 'Check user subscription status and health';

    public function handle(Request $request, User $user)
    {
        return response()->json([
            'data' => [
                'subscription_active' => $user->hasActiveSubscription(),
                'payment_method_valid' => $user->hasValidPaymentMethod(),
                'next_billing_date' => $user->nextBillingDate(),
                'status' => $user->subscriptionStatus(),
            ],
        ]);
    }
}
```

This MCP getter system allows AI agents to retrieve single-resource data intelligently while maintaining security, validation, and proper parameter requirements. Unlike Actions, Getters are focused specifically on retrieving data for individual resources, making them ideal for analytics, status checks, and detailed information retrieval.
