---
title: MCP Actions
menuTitle: Actions
category: MCP
position: 6
---

Laravel Restify Actions can be exposed to AI agents through the Model Context Protocol (MCP), allowing intelligent automation of complex operations. This page covers how to configure actions for MCP integration and optimize them for AI consumption.

## Enabling MCP Actions

To expose actions to the MCP server, you must enable them in your repository by overriding the `mcpAllowsActions()` method:

```php
use Binaryk\LaravelRestify\Traits\HasMcpTools;

#[Model(Post::class)]
class PostRepository extends Repository
{
    use HasMcpTools;

    // Enable action tools for AI agents
    public function mcpAllowsActions(): bool
    {
        return true; // Default: false
    }

    public function actions(RestifyRequest $request): array
    {
        return [
            PublishPostsAction::new(),
            ArchivePostsAction::new(),
            SendNewsletterAction::new()->standalone(),
        ];
    }
}
```

Once enabled, all actions defined in the `actions()` method will be automatically exposed as MCP tools to AI agents.

## Action Descriptions for AI Agents

Providing clear descriptions helps AI agents understand when and how to use your actions. You can customize action descriptions using the `$description` property or by overriding the `description()` method.

### Using the Description Property

```php
use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Illuminate\Support\Collection;

class PublishPostsAction extends Action
{
    public string $description = 'Publish selected blog posts and optionally notify subscribers via email. Posts must be in draft status to be published.';

    public function handle(ActionRequest $request, Collection $posts)
    {
        // Action implementation
    }
}
```

### Using the Description Method

For dynamic descriptions based on context:

```php
class PublishPostsAction extends Action
{
    public function description(RestifyRequest $request): string
    {
        $userRole = $request->user()?->role;

        if ($userRole === 'editor') {
            return 'Publish selected posts after editorial review. Sends notifications to authors and subscribers.';
        }

        return 'Publish selected posts immediately. Requires publish permissions.';
    }

    //...
}
```

## Validation Rules for AI Schema

The `rules()` method is crucial for MCP integration. Restify automatically converts your Laravel validation rules into JSON Schema that AI agents understand and use for parameter validation.

```php
class PublishPostsAction extends Action
{
    public string $description = 'Publish posts with optional scheduling and notification settings';

    public function rules(): array
    {
        return [
            'publish_immediately' => ['boolean'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'notify_subscribers' => ['boolean'],
            'notification_message' => ['nullable', 'string', 'max:500'],
            'notify_authors' => ['boolean'],
        ];
    }

    public function handle(ActionRequest $request, Collection $posts)
    {
        $request->validate($this->rules());

        // Action implementation
    }
}
```

The AI agent will automatically receive a JSON Schema with:
- `publish_immediately`: boolean (optional)
- `scheduled_at`: date string (optional, must be in the future)
- `notify_subscribers`: boolean (optional)
- `notification_message`: string (optional, max 500 characters)
- `notify_authors`: boolean (optional)

## Action Scopes and MCP Enforcement

Actions can have different scopes that determine what parameters AI agents must provide. The scope affects the generated MCP tool schema and validation.

### Index Actions (Multiple Resources)

Index actions operate on multiple resources. When using `onlyOnIndex()`, AI agents are **required** to provide a `resources` parameter containing an array of model IDs:

```php
class ArchivePostsAction extends Action
{
    public string $description = 'Archive multiple posts and remove them from public view';

    public function handle(ActionRequest $request, Collection $posts)
    {
        $posts->each->archive();
    }
}

// In PostRepository
public function actions(RestifyRequest $request): array
{
    return [
        ArchivePostsAction::new()->onlyOnIndex(),
    ];
}
```

**Generated MCP Tool Schema:**
```json
{
    "name": "posts-archive-posts-action-tool",
    "description": "Execute Archive Posts action on Post records in the posts repository.",
    "inputSchema": {
        "type": "object",
        "properties": {
            "resources": {
                "type": "array",
                "items": {
                    "type": "string",
                    "description": "The ID of the resource Post to perform the action on.",
                    "required": true
                },
                "title": "resources",
                "description": "The ids of the resources Post to perform the action on. Use string 'all' to select all resources.",
                "required": true
            }
        },
        "required": ["resources"]
    }
}
```

**AI Agent Usage:**
```javascript
// Must provide resources array
{
    "resources": ["1", "2", "3"]
}

// Or use "all" to apply to all resources
{
    "resources": "all"
}
```

### Show Actions (Single Resource)

Show actions operate on a single resource. When using `onlyOnShow()`, AI agents are **required** to provide an `id` parameter:

```php
class PublishPostAction extends Action
{
    public string $description = 'Publish a specific post and notify its author';

    public function handle(ActionRequest $request, Post $post)
    {
        $post->publish();
        $post->author->notify(new PostPublishedNotification($post));
    }
}

// In PostRepository
public function actions(RestifyRequest $request): array
{
    return [
        PublishPostAction::new()->onlyOnShow(),
    ];
}
```

**Generated MCP Tool Schema:**
```json
{
    "name": "posts-publish-post-action-tool",
    "description": "Execute Publish Post action on a specific Post record in the posts repository.",
    "inputSchema": {
        "type": "object",
        "properties": {
            "id": {
                "type": "string",
                "title": "id",
                "description": "The ID of the resource to perform the action on.",
                "required": true
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
    "id": "123"
}
```

### Default Actions (Index Behavior)

If you don't specify a scope (no `onlyOnShow()` or `onlyOnIndex()`), the action defaults to **index behavior** and still requires the `resources` parameter:

```php
class NotifyAuthorsAction extends Action
{
    public string $description = 'Send notification to authors of selected posts';

    public function handle(ActionRequest $request, Collection $posts)
    {
        // Notify authors
    }
}

// In PostRepository - no scope modifier
public function actions(RestifyRequest $request): array
{
    return [
        NotifyAuthorsAction::new(), // Defaults to index behavior
    ];
}
```

**AI Agent Usage:**
```javascript
// Still requires resources array (index behavior)
{
    "resources": ["1", "2", "3"]
}
```

### Standalone Actions (No Resources Required)

Standalone actions don't operate on specific resources. When using `standalone()`, AI agents are **not required** to provide any resource identifiers:

```php
class GenerateMonthlyReportAction extends Action
{
    public bool $standalone = true;

    public string $description = 'Generate a monthly report of all blog activity and email it to administrators';

    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2020'],
            'include_drafts' => ['boolean'],
        ];
    }

    public function handle(ActionRequest $request)
    {
        // Generate report logic - no models required
        $report = Report::generate($request->validated());

        return response()->json(['report_url' => $report->url]);
    }
}

// In PostRepository
public function actions(RestifyRequest $request): array
{
    return [
        GenerateMonthlyReportAction::new()->standalone(),
    ];
}
```

**Generated MCP Tool Schema:**
```json
{
    "name": "posts-generate-monthly-report-action-tool",
    "description": "Execute Generate Monthly Report action (standalone - no models required) in the posts repository.",
    "inputSchema": {
        "type": "object",
        "properties": {
            "month": {
                "type": "integer",
                "minimum": 1,
                "maximum": 12,
                "required": true
            },
            "year": {
                "type": "integer",
                "minimum": 2020,
                "required": true
            },
            "include_drafts": {
                "type": "boolean"
            }
        },
        "required": ["month", "year"]
    }
}
```

**AI Agent Usage:**
```javascript
// No id or resources required - only action parameters
{
    "month": 10,
    "year": 2025,
    "include_drafts": true
}
```

## Scope Comparison Table

| Scope | Method | Required Parameter | Parameter Type | Use Case |
|-------|--------|-------------------|----------------|----------|
| **Index** | `onlyOnIndex()` | `resources` | `array` or `"all"` | Operate on multiple resources |
| **Show** | `onlyOnShow()` | `id` | `string` | Operate on a single resource |
| **Default** | _(none)_ | `resources` | `array` or `"all"` | Same as index (optimized default) |
| **Standalone** | `standalone()` | _(none)_ | - | No resources needed |

## Complete Action Example

Here's a comprehensive example showing all aspects of MCP-optimized actions:

```php
use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Illuminate\Support\Collection;

class SchedulePostPublicationAction extends Action
{
    // Clear description for AI agents
    public string $description = 'Schedule posts for future publication with optional notification settings. Posts will be automatically published at the specified date and time.';

    // Comprehensive validation rules converted to JSON Schema
    public function rules(): array
    {
        return [
            'publish_at' => ['required', 'date', 'after:now'],
            'timezone' => ['nullable', 'string', 'timezone'],
            'notify_subscribers' => ['boolean'],
            'send_social_media' => ['boolean'],
            'social_platforms' => ['nullable', 'array'],
            'social_platforms.*' => ['string', 'in:twitter,facebook,linkedin'],
            'custom_message' => ['nullable', 'string', 'max:280'],
        ];
    }

    public function handle(ActionRequest $request, Collection $posts)
    {
        $validated = $request->validate($this->rules());

        foreach ($posts as $post) {
            $post->update([
                'scheduled_at' => $validated['publish_at'],
                'timezone' => $validated['timezone'] ?? 'UTC',
            ]);

            if ($validated['notify_subscribers'] ?? false) {
                $post->scheduleSubscriberNotification();
            }

            if ($validated['send_social_media'] ?? false) {
                $post->scheduleSocialMediaPosts(
                    $validated['social_platforms'] ?? [],
                    $validated['custom_message'] ?? null
                );
            }
        }

        return ok('Posts scheduled successfully');
    }
}

// In PostRepository
public function actions(RestifyRequest $request): array
{
    return [
        SchedulePostPublicationAction::new()->onlyOnIndex(),
    ];
}
```

## Hiding Actions from MCP

You can selectively hide actions from MCP while keeping them available for regular API requests. This is useful when you want certain actions to be manually triggered by users but not accessible to AI agents.

### Using hideFromMcp()

```php
public function actions(RestifyRequest $request): array
{
    return [
        // Available to both API and MCP
        PublishPostsAction::new()->onlyOnIndex(),

        // Available only to API requests, hidden from AI agents
        MarkInvoiceAsPaidRestifyAction::new()
            ->hideFromMcp()
            ->onlyOnShow(),

        // Conditionally hide based on environment
        DeletePostsAction::new()
            ->hideFromMcp(app()->environment('production'))
            ->onlyOnIndex(),

        // Available only in development for AI testing
        TestDataGeneratorAction::new()
            ->hideFromMcp(! app()->environment('local'))
            ->standalone(),
    ];
}
```

### Use Cases for Hiding Actions

**1. High-Risk Operations**
```php
public function actions(RestifyRequest $request): array
{
    return [
        // Never expose deletion to AI agents
        PermanentlyDeleteUsersAction::new()
            ->hideFromMcp()
            ->onlyOnIndex(),

        // Never expose financial operations to AI
        ProcessRefundAction::new()
            ->hideFromMcp()
            ->onlyOnShow(),
    ];
}
```

**2. Manual-Only Workflows**
```php
public function actions(RestifyRequest $request): array
{
    return [
        // Require human approval, not AI
        ApproveComplianceDocumentAction::new()
            ->hideFromMcp()
            ->onlyOnShow(),

        // Manual verification required
        VerifyIdentityAction::new()
            ->hideFromMcp()
            ->onlyOnShow(),
    ];
}
```

**3. Environment-Specific Exposure**
```php
public function actions(RestifyRequest $request): array
{
    return [
        // Only expose to AI in development
        SeedTestDataAction::new()
            ->hideFromMcp(! app()->environment('local'))
            ->standalone(),

        // Hide from AI in production
        ExperimentalFeatureAction::new()
            ->hideFromMcp(app()->environment('production'))
            ->onlyOnIndex(),
    ];
}
```

### Combining with Other Modifiers

The `hideFromMcp()` method works seamlessly with other action modifiers:

```php
public function actions(RestifyRequest $request): array
{
    return [
        MarkInvoiceAsPaidRestifyAction::new()
            ->hideFromMcp()                    // Hide from AI agents
            ->onlyOnShow()                     // Only for single invoices
            ->canSee(fn() => $request->user()->can('manage-invoices')),  // Authorization

        BulkDeleteAction::new()
            ->hideFromMcp(app()->environment('production'))  // Conditional hiding
            ->onlyOnIndex()
            ->canSee(fn() => $request->user()->isAdmin()),
    ];
}
```

<alert type="warning">
Hidden actions are completely excluded from MCP tool discovery. AI agents will not see them in the available tools list and cannot execute them, even if they somehow know the action URI key.
</alert>

## Conditional Action Visibility

You can control which actions are visible to AI agents based on permissions:

```php
class PostRepository extends Repository
{
    use HasMcpTools;

    public function mcpAllowsActions(): bool
    {
        // Only enable actions for specific roles
        return request()->user()?->hasRole(['admin', 'editor']) ?? false;
    }

    public function actions(RestifyRequest $request): array
    {
        return [
            // Only admins can delete
            DeletePostsAction::new()
                ->canSee(fn() => $request->user()?->hasRole('admin')),

            // Editors and admins can publish
            PublishPostsAction::new()
                ->canSee(fn() => $request->user()?->hasRole(['admin', 'editor'])),

            // Everyone with action access can archive
            ArchivePostsAction::new(),

            // Hidden from AI but available to API
            CriticalOperationAction::new()
                ->hideFromMcp(),
        ];
    }
}
```

## Best Practices for MCP Actions

### 1. Write Clear Descriptions

```php
// Good: Specific, explains purpose and behavior
public string $description = 'Archive selected posts and move them to the archive section. Archived posts are hidden from public view but remain searchable by administrators.';

// Poor: Too vague
public string $description = 'Archive posts';
```

### 2. Use Comprehensive Validation Rules

```php
// Good: Detailed validation that generates rich JSON Schema
public function rules(): array
{
    return [
        'priority' => ['required', 'integer', 'between:1,5'],
        'category' => ['required', 'string', 'in:urgent,normal,low'],
        'assign_to' => ['nullable', 'exists:users,id'],
        'due_date' => ['nullable', 'date', 'after:today'],
        'tags' => ['array', 'max:5'],
        'tags.*' => ['string', 'max:50'],
    ];
}

// Poor: Minimal validation
public function rules(): array
{
    return [
        'priority' => ['integer'],
    ];
}
```

### 3. Choose the Right Scope

```php
// Use standalone for actions that don't need resources
GenerateReportAction::new()->standalone()

// Use onlyOnShow for single-resource operations
PublishPostAction::new()->onlyOnShow()

// Use onlyOnIndex for bulk operations
ArchivePostsAction::new()->onlyOnIndex()
```

### 4. Provide Helpful Error Messages

```php
public function handle(ActionRequest $request, Collection $posts)
{
    $validated = $request->validate($this->rules());

    if ($posts->isEmpty()) {
        return error('No posts selected for archiving', 422);
    }

    if ($posts->count() > 100) {
        return error('Cannot archive more than 100 posts at once. Please select fewer posts.', 422);
    }

    // Action implementation
}
```

## Security Considerations

1. **Validate all input**: Always validate using `$request->validate($this->rules())`
2. **Check permissions**: Use authorization gates or policies
3. **Limit bulk operations**: Set maximum limits for index actions
4. **Audit actions**: Log important actions for security tracking

```php
public function handle(ActionRequest $request, Collection $posts)
{
    // Validate input
    $validated = $request->validate($this->rules());

    // Check authorization
    if (! $request->user()->can('publish', Post::class)) {
        return error('Unauthorized', 403);
    }

    // Limit bulk operations
    if ($posts->count() > 50) {
        return error('Cannot publish more than 50 posts at once', 422);
    }

    // Log the action
    activity()
        ->causedBy($request->user())
        ->withProperties(['post_ids' => $posts->pluck('id')])
        ->log('bulk_publish_posts');

    // Perform action
    $posts->each->publish();

    return ok('Posts published successfully');
}
```

## Real-World Examples

Here are complete, production-ready examples of each action type with their generated MCP schemas.

### Example 1: Show Action - Mark Invoice as Paid

**Action Class:**
```php
<?php

namespace App\Restify\Invoices\Actions;

use App\Domains\Invoices\Models\Invoice;
use App\Domains\Permissions\Enum\Permissions;
use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Illuminate\Http\JsonResponse;

class MarkInvoiceAsPaidRestifyAction extends Action
{
    public bool $showOnShow = true;

    public static $uriKey = 'mark-as-paid';

    public string $description = 'Mark a specific invoice as paid by recording the payment date. This updates the invoice status and triggers payment confirmation emails.';

    public function handle(ActionRequest $request, Invoice $invoice): JsonResponse
    {
        abort_if(
            ! $request->user()->can(Permissions::manageInvoices),
            403,
            __('Unauthorized to perform this action!')
        );

        $request->validate($this->rules());

        $invoice->markAsPaid($request->date('date'));

        return data($invoice->fresh());
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
```

**Repository Registration:**
```php
public function actions(RestifyRequest $request): array
{
    return [
        MarkInvoiceAsPaidRestifyAction::make()->onlyOnShow(),
    ];
}
```

**Generated MCP Tool Schema:**
```json
{
    "name": "invoices-mark-as-paid-action-tool",
    "title": "Mark Invoice As Paid Restify Action",
    "description": "Mark a specific invoice as paid by recording the payment date. This updates the invoice status and triggers payment confirmation emails.",
    "inputSchema": {
        "properties": {
            "date": {
                "description": "This field is required. Must be a valid date. Must be a date before or equal to today.",
                "type": "string"
            },
            "id": {
                "title": "id",
                "description": "The ID of the resource to perform the action on.",
                "type": "string"
            }
        },
        "type": "object",
        "required": [
            "date",
            "id"
        ]
    }
}
```

**AI Agent Usage:**
```javascript
// AI must provide both the invoice ID and payment date
{
    "id": "123",
    "date": "2025-10-05"
}
```

**Same Action with `->onlyOnIndex()` Scope:**

If the same `MarkInvoiceAsPaidRestifyAction` was registered with `->onlyOnIndex()` instead:

```php
// Different repository registration
public function actions(RestifyRequest $request): array
{
    return [
        MarkInvoiceAsPaidRestifyAction::make()->onlyOnIndex(), // Changed from onlyOnShow()
    ];
}
```

**Generated MCP Tool Schema Changes:**
```json
{
    "name": "invoices-mark-as-paid-action-tool",
    "title": "Mark Invoice As Paid Restify Action",
    "description": "Execute Mark Invoice As Paid Restify Action action on Invoice records in the invoices repository.",
    "inputSchema": {
        "properties": {
            "date": {
                "description": "This field is required",
                "type": "string"
            },
            "resources": {
                "title": "resources",
                "description": "The ids of the resources Invoice to perform the action on. Use string 'all' to select all resources.",
                "items": {
                    "description": "The ID of the resource Invoice to perform the action on.",
                    "type": "string"
                },
                "type": "array"
            }
        },
        "type": "object",
        "required": [
            "date",
            "resources"
        ]
    }
}
```

**Key Differences:**
- Description changes from "on a specific Invoice record" to "on Invoice records" (plural)
- `id` parameter replaced with `resources` array
- AI must provide multiple invoice IDs instead of a single ID

**AI Agent Usage with Index Scope:**
```javascript
// AI must provide resources array instead of single id
{
    "resources": ["123", "124", "125"],
    "date": "2025-10-05"
}

// Or mark all invoices as paid
{
    "resources": "all",
    "date": "2025-10-05"
}
```

<alert type="info">
The same action class can be registered with different scopes depending on your use case. Use `onlyOnShow()` when you want AI agents to mark individual invoices as paid, or `onlyOnIndex()` when you want to enable bulk payment marking.
</alert>

### Example 2: Index Action - Archive Multiple Posts

**Action Class:**
```php
<?php

namespace App\Restify\Posts\Actions;

use App\Models\Post;
use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class ArchivePostsRestifyAction extends Action
{
    public bool $showOnIndex = true;

    public static $uriKey = 'archive-posts';

    public string $description = 'Archive multiple blog posts and optionally notify their authors. Archived posts are hidden from public view but remain accessible to administrators.';

    public function handle(ActionRequest $request, Collection $posts): JsonResponse
    {
        abort_if(
            ! $request->user()->can('archive', Post::class),
            403,
            __('Unauthorized to archive posts!')
        );

        $validated = $request->validate($this->rules());

        $archivedCount = 0;

        foreach ($posts as $post) {
            $post->update([
                'archived_at' => now(),
                'archive_reason' => $validated['reason'] ?? null,
            ]);

            if ($validated['notify_authors'] ?? false) {
                $post->author->notify(new PostArchivedNotification($post, $validated['reason'] ?? null));
            }

            $archivedCount++;
        }

        return data([
            'message' => "Successfully archived {$archivedCount} posts",
            'archived_count' => $archivedCount,
        ]);
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
            'notify_authors' => ['boolean'],
        ];
    }
}
```

**Repository Registration:**
```php
public function actions(RestifyRequest $request): array
{
    return [
        ArchivePostsRestifyAction::make()->onlyOnIndex(),
    ];
}
```

**Generated MCP Tool Schema:**
```json
{
    "name": "posts-archive-posts-action-tool",
    "title": "Archive Posts Restify Action",
    "description": "Archive multiple blog posts and optionally notify their authors. Archived posts are hidden from public view but remain accessible to administrators.",
    "inputSchema": {
        "properties": {
            "reason": {
                "description": "Must not be greater than 500 characters.",
                "type": "string",
                "maxLength": 500
            },
            "notify_authors": {
                "type": "boolean"
            },
            "resources": {
                "type": "array",
                "items": {
                    "type": "string",
                    "description": "The ID of the resource Post to perform the action on.",
                    "required": true
                },
                "title": "resources",
                "description": "The ids of the resources Post to perform the action on. Use string 'all' to select all resources.",
                "required": true
            }
        },
        "type": "object",
        "required": [
            "resources"
        ]
    }
}
```

**AI Agent Usage:**
```javascript
// AI must provide the resources array
{
    "resources": ["45", "67", "89"],
    "reason": "Content outdated and needs revision",
    "notify_authors": true
}

// Or archive all posts matching filters
{
    "resources": "all",
    "reason": "Seasonal content cleanup",
    "notify_authors": false
}
```

### Example 3: Standalone Action - Generate Monthly Report

**Action Class:**
```php
<?php

namespace App\Restify\Reports\Actions;

use App\Services\ReportGenerator;
use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Illuminate\Http\JsonResponse;

class GenerateMonthlyReportRestifyAction extends Action
{
    public bool $standalone = true;

    public static $uriKey = 'generate-monthly-report';

    public string $description = 'Generate a comprehensive monthly report including sales metrics, user growth, revenue analysis, and top performing content. The report is generated in PDF format and emailed to specified recipients.';

    public function handle(ActionRequest $request): JsonResponse
    {
        abort_if(
            ! $request->user()->hasRole('admin'),
            403,
            __('Only administrators can generate reports!')
        );

        $validated = $request->validate($this->rules());

        $report = app(ReportGenerator::class)->generateMonthlyReport(
            month: $validated['month'],
            year: $validated['year'],
            includeDrafts: $validated['include_drafts'] ?? false,
            sections: $validated['sections'] ?? ['sales', 'users', 'content']
        );

        if ($validated['send_email'] ?? false) {
            foreach ($validated['recipients'] as $email) {
                $report->sendTo($email);
            }
        }

        return data([
            'report_id' => $report->id,
            'report_url' => $report->download_url,
            'generated_at' => $report->created_at,
            'email_sent' => $validated['send_email'] ?? false,
        ]);
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2030'],
            'include_drafts' => ['boolean'],
            'sections' => ['array'],
            'sections.*' => ['string', 'in:sales,users,content,revenue,analytics'],
            'send_email' => ['boolean'],
            'recipients' => ['required_if:send_email,true', 'array'],
            'recipients.*' => ['email'],
        ];
    }
}
```

**Repository Registration:**
```php
// Can be registered in any repository since it's standalone
public function actions(RestifyRequest $request): array
{
    return [
        GenerateMonthlyReportRestifyAction::make()->standalone(),
    ];
}
```

**Generated MCP Tool Schema:**
```json
{
    "name": "posts-generate-monthly-report-action-tool",
    "title": "Generate Monthly Report Restify Action",
    "description": "Generate a comprehensive monthly report including sales metrics, user growth, revenue analysis, and top performing content. The report is generated in PDF format and emailed to specified recipients.",
    "inputSchema": {
        "properties": {
            "month": {
                "type": "integer",
                "minimum": 1,
                "maximum": 12,
                "description": "This field is required. Must be between 1 and 12."
            },
            "year": {
                "type": "integer",
                "minimum": 2020,
                "maximum": 2030,
                "description": "This field is required. Must be at least 2020. Must not be greater than 2030."
            },
            "include_drafts": {
                "type": "boolean"
            },
            "sections": {
                "type": "array",
                "items": {
                    "type": "string",
                    "enum": ["sales", "users", "content", "revenue", "analytics"]
                }
            },
            "send_email": {
                "type": "boolean"
            },
            "recipients": {
                "type": "array",
                "description": "This field is required when send email is true.",
                "items": {
                    "type": "string",
                    "format": "email",
                    "description": "Must be a valid email address."
                }
            }
        },
        "type": "object",
        "required": [
            "month",
            "year"
        ]
    }
}
```

**AI Agent Usage:**
```javascript
// No id or resources required - only action parameters
{
    "month": 9,
    "year": 2025,
    "include_drafts": false,
    "sections": ["sales", "users", "revenue"],
    "send_email": true,
    "recipients": ["admin@example.com", "manager@example.com"]
}
```

### Example 4: Default Action (Index Behavior)

**Action Class:**
```php
<?php

namespace App\Restify\Users\Actions;

use App\Notifications\BulkNotification;
use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class SendBulkNotificationRestifyAction extends Action
{
    // No scope modifier - defaults to index behavior

    public static $uriKey = 'send-notification';

    public string $description = 'Send a custom notification to multiple users via email, SMS, or push notification. Allows personalized messages with template variables.';

    public function handle(ActionRequest $request, Collection $users): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $sentCount = 0;

        foreach ($users as $user) {
            $user->notify(new BulkNotification(
                message: $validated['message'],
                channels: $validated['channels'],
                priority: $validated['priority'] ?? 'normal'
            ));

            $sentCount++;
        }

        return data([
            'message' => "Notification sent to {$sentCount} users",
            'sent_count' => $sentCount,
        ]);
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:1000'],
            'channels' => ['required', 'array'],
            'channels.*' => ['string', 'in:email,sms,push'],
            'priority' => ['string', 'in:low,normal,high,urgent'],
        ];
    }
}
```

**AI Agent Usage:**
```javascript
// Still requires resources array (default index behavior)
{
    "resources": ["12", "34", "56"],
    "message": "Important system update scheduled for tonight",
    "channels": ["email", "push"],
    "priority": "high"
}
```

These examples demonstrate how different action scopes work in practice and how the MCP system generates appropriate schemas for AI agents to interact with your Laravel Restify actions.

## Summary

This MCP action system allows AI agents to perform complex operations on your data while maintaining security, validation, and proper scoping constraints.
