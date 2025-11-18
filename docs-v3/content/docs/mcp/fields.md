---
title: MCP Fields
menuTitle: Fields
category: MCP
position: 3
---

Laravel Restify fields provide MCP-specific methods to optimize data structures for AI agent consumption, reduce token usage, and control field visibility based on user permissions.

## MCP Field Methods

MCP field methods follow the same pattern as regular field methods but are prefixed with `fieldsForMcp`. These methods take priority over regular field methods when handling MCP requests.

### Field Priority for MCP

When an MCP request is made, Restify follows this priority order:

1. **MCP-specific methods** (`fieldsForMcpIndex`, `fieldsForMcpShow`, etc.) - **Highest priority**
2. **Default fields method** (`fields`) - **Fallback**

This allows you to provide optimized field sets for AI agents while falling back to the standard fields method when no MCP-specific method is defined.

### Available MCP Field Methods

```php
class PostRepository extends Repository
{
    // Regular fields for human consumption
    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
            field('content'),
            field('excerpt'),
            field('meta_description'),
            field('tags'),
            field('author_id'),
            field('published_at'),
            field('created_at'),
            field('updated_at'),
        ];
    }
    
    // Optimized fields for AI index requests (saves 60-70% tokens)
    public function fieldsForMcpIndex(RestifyRequest $request): array
    {
        return [
            field('id'),
            field('title'),
            field('excerpt'),
            field('published_at'),
        ];
    }
    
    // Focused fields for AI detail views (saves 40-50% tokens)
    public function fieldsForMcpShow(RestifyRequest $request): array
    {
        return [
            field('title'),
            field('content'),
            field('author', fn() => $this->author->name),
            field('tags'),
            field('published_at'),
        ];
    }
    
    // Fields AI agents can use for creation
    public function fieldsForMcpStore(RestifyRequest $request): array
    {
        return [
            field('title')->required(),
            field('content')->required(),
            field('excerpt'),
            field('tags'),
        ];
    }
    
    // Fields AI agents can modify
    public function fieldsForMcpUpdate(RestifyRequest $request): array
    {
        return [
            field('title'),
            field('content'),
            field('excerpt'),
            field('tags'),
        ];
    }
```

## Token Usage Optimization

### Index Optimization Example

**Regular fields method (for humans):**
```php
public function fields(RestifyRequest $request): array
{
    return [
        field('id'),
        field('title'),
        field('content'),
        field('excerpt'), 
        field('meta_description'),
        field('meta_keywords'),
        field('author_id'),
        field('category_id'),
        field('status'),
        field('featured'),
        field('view_count'),
        field('comment_count'),
        field('published_at'),
        field('created_at'),
        field('updated_at'),
    ]; // ~15 fields
}
```

**MCP optimized for listing (saves ~70% tokens):**
```php
public function fieldsForMcpIndex(RestifyRequest $request): array
{
    return [
        field('id'),
        field('title'),
        field('excerpt'),
        field('published_at'),
        field('status'),
    ]; // Only 5 essential fields
}
```

### Show Optimization Example

**MCP optimized for detail view (saves ~50% tokens):**
```php
public function fieldsForMcpShow(RestifyRequest $request): array
{
    return [
        field('title'),
        field('content'),
        field('author_name', fn() => $this->author->name),
        field('category', fn() => $this->category->name),
        field('tags'),
        field('published_at'),
        field('status'),
        // Removed: meta fields, timestamps, IDs, counts
    ];
}
```

## MCP-Aware Relationships

Handle relationships efficiently for AI agents:

```php
class PostRepository extends Repository
{
    public static function related(): array
    {
        return [
            'author' => BelongsTo::make('user', UserRepository::class),
            'comments' => HasMany::make('comments', CommentRepository::class),
            'tags' => BelongsToMany::make('tags', TagRepository::class),
        ];
    }
    
    // AI agents get optimized relationship data
    public function fieldsForMcpShow(RestifyRequest $request): array
    {
        return [
            field('title'),
            field('content'),
            // Inline relationship data to reduce API calls
            field('author_name', fn() => $this->user->name),
            field('author_email', fn() => $this->user->email),
            field('comment_count', fn() => $this->comments()->count()),
            field('tag_names', fn() => $this->tags->pluck('name')->toArray()),
        ];
    }
}
```

## Conditional MCP Fields

Provide different fields based on AI agent context or user permissions using field-level visibility controls:

### Using canSee() Method

```php
class PostRepository extends Repository
{
    public function fields(RestifyRequest $request): array
    {
        return [
            field('title')->required()->searchable()->sortable(),
            field('content')->string(),
            field('status')->matchable(),
            field('category')->matchable(),
            
            // Admin-only fields
            field('internal_notes')
                ->canSee(fn($request) => $request->user()->hasRole('admin')),
                
            field('performance_metrics', fn() => [
                'views' => $this->view_count,
                'engagement' => $this->calculateEngagement(),
                'conversion_rate' => $this->calculateConversion(),
            ])->canSee(fn($request) => $request->user()->hasRole('admin')),
            
            // Content manager fields
            field('author_info', fn() => [
                'name' => $this->author->name,
                'email' => $this->author->email,
                'posts_count' => $this->author->posts_count,
            ])->canSee(fn($request) => $request->user()->hasPermissionTo('manage-content')),
        ];
    }
}
```

### Using hideFromMcp() Method

When you want to hide certain fields specifically from MCP requests (while keeping them for REST endpoints), use the `hideFromMcp()` method:

```php
class PostRepository extends Repository
{
    public function fields(RestifyRequest $request): array
    {
        return [
            field('title')->required()->searchable()->sortable(),
            field('content')->string(),
            field('status')->matchable(),
            field('category')->matchable(),
            
            // Hide sensitive data from AI agents
            field('internal_notes')
                ->hideFromMcp(fn($request) => !$request->user()->hasRole('admin')),
                
            // Always hide from MCP
            field('secret_api_key')->hideFromMcp(),
            
            // Conditionally hide from MCP based on user role
            field('draft_content')
                ->hideFromMcp(fn($request) => !$request->user()->hasPermissionTo('view-drafts')),
                
            // Hide heavy computational fields from MCP to save tokens
            field('full_statistics', fn() => $this->generateDetailedStats())
                ->hideFromMcp(), // Use dedicated MCP fields instead
        ];
    }
    
    // Use dedicated MCP field method for optimized AI responses
    public function fieldsForMcpShow(RestifyRequest $request): array
    {
        return [
            field('title'),
            field('content'),
            field('status'),
            field('category'),
            // Light-weight stats for AI agents
            field('basic_stats', fn() => [
                'views' => $this->view_count,
                'comments' => $this->comments_count,
            ]),
        ];
    }
}
```

### Benefits of Field-Level Control

- **No duplicate logic**: Define visibility rules once in the main `fields()` method
- **Automatic application**: Rules apply to all MCP operations (index, show, store, update)
- **Flexible conditions**: Use any logic in the callback to determine visibility
- **Performance optimization**: Hide expensive computed fields from AI agents
- **Security**: Keep sensitive data away from AI agents while maintaining REST functionality

## Testing MCP Field Methods

Test your MCP-specific field methods to ensure they work correctly:

```php [tests/Feature/McpRepositoryTest.php]
class McpRepositoryTest extends TestCase
{
    public function test_mcp_index_fields_are_optimized()
    {
        $repository = new PostRepository();
        $mcpRequest = new McpRequest();
        $mcpRequest->setIsIndexRequest(true);
        
        $fields = $repository->collectFields($mcpRequest);
        
        // Assert only essential fields are returned
        $this->assertCount(4, $fields);
        $this->assertTrue($fields->contains('attribute', 'title'));
        $this->assertTrue($fields->contains('attribute', 'excerpt'));
        $this->assertFalse($fields->contains('attribute', 'meta_description'));
    }
    
    public function test_mcp_fields_fall_back_to_regular_fields()
    {
        $repository = new PostRepository();
        $mcpRequest = new McpRequest();
        
        // Remove MCP method to test fallback
        $fields = $repository->collectFields($mcpRequest);
        
        // Should fall back to regular fields method
        $this->assertGreaterThan(4, $fields->count());
    }
}
```

## MCP Performance Monitoring

Monitor token usage and performance of your MCP endpoints:

```php
class PostRepository extends Repository
{
    public function fieldsForMcpIndex(RestifyRequest $request): array
    {
        $startTime = microtime(true);
        
        $fields = [
            field('id'),
            field('title'),
            field('excerpt'),
            field('published_at'),
        ];
        
        // Log performance metrics for AI optimization
        Log::info('MCP Index Fields', [
            'repository' => static::class,
            'field_count' => count($fields),
            'execution_time' => microtime(true) - $startTime,
            'user_agent' => $request->userAgent(),
        ]);
        
        return $fields;
    }
}
```

## File Field with Custom Filenames

The File field supports custom filenames from request data, perfect for automation workflows like n8n where you want to control the filename during upload.

### Basic File Upload

```php
class ExpenseRepository extends Repository
{
    public function fields(RestifyRequest $request): array
    {
        return [
            field('receipt_path')->file()
                ->path('expense_receipts/'.Auth::id())
                ->storeOriginalName('receipt_filename')
                ->storeSize('receipt_size')
                ->deletable()
                ->disk('s3'),

            field('receipt_filename')
                ->description('Original filename of the uploaded receipt.'),

            field('receipt_size')
                ->description('Size of the uploaded receipt in bytes.'),
        ];
    }
}
```

### Custom Filename from Request

Use `storeAs()` with a callback to read the custom filename from the request:

```php
field('receipt_path')->file()
    ->path('expense_receipts/'.Auth::id())
    ->storeAs(fn($request) => $request->input('receipt_filename'))
    ->storeOriginalName('receipt_filename')
    ->storeSize('receipt_size')
    ->deletable()
    ->disk('s3'),

field('receipt_filename')
    ->description('Custom filename for the receipt. Provide a meaningful name.'),
```

### Smart Extension Handling

The File field automatically handles file extensions:

```php
// Request: receipt_filename = "Invoice_Jan_2024"
// Uploaded file: expense.pdf
// Result: Invoice_Jan_2024.pdf (extension auto-appended)

// Request: receipt_filename = "Invoice_Jan_2024.pdf"
// Uploaded file: expense.pdf
// Result: Invoice_Jan_2024.pdf (used as-is)

// Request: receipt_filename = "" or null
// Uploaded file: expense.pdf
// Result: a1b2c3d4e5f6.pdf (fallback to auto-generated hash)
```

### File Field Behaviors

**With Callable `storeAs()`:**
```php
->storeAs(fn($request) => $request->input('custom_name'))
```
- Uses the returned filename for storage
- Uses the same filename for `storeOriginalName()` column
- Auto-appends extension if missing
- Falls back to auto-generated name if returns empty/null

**With Static `storeAs()`:**
```php
->storeAs('avatar.jpg')
```
- Uses the static filename for storage
- Uses uploaded file's original name for `storeOriginalName()` column
- Extension must be included in the static string

**Without `storeAs()`:**
```php
field('receipt')->file()
```
- Auto-generates hash-based filename
- Uses uploaded file's original name for `storeOriginalName()` column

### MCP-Optimized File Fields

Hide file fields from MCP responses to reduce token usage:

```php
class ExpenseRepository extends Repository
{
    public function fields(RestifyRequest $request): array
    {
        return [
            field('receipt_path')->file()
                ->path('expense_receipts/'.Auth::id())
                ->storeAs(fn($request) => $request->input('receipt_filename'))
                ->storeOriginalName('receipt_filename')
                ->resolveUsingTemporaryUrl($request->boolean('temporary_urls'))
                ->hideFromMcp()
                ->description('Only send the absolute URL if you have it, otherwise do not send this field.')
                ->disk('s3'),

            field('receipt_filename')
                ->description('Filename of the receipt. Keep it descriptive.'),
        ];
    }
}
```

### File Upload Automation Example

Perfect for n8n workflows where files are extracted from emails:

```json
{
  "receipt_path": "<uploaded_file>",
  "receipt_filename": "Invoice_ABC_Company_Jan_2024",
  "amount": 1500.00,
  "date": "2024-01-15",
  "vendor": "ABC Company"
}
```

The file will be stored as `expense_receipts/123/Invoice_ABC_Company_Jan_2024.pdf` and the `receipt_filename` column will contain `Invoice_ABC_Company_Jan_2024.pdf`.

## Best Practices

### 1. Field Selection Strategy

- **Index**: Include only essential fields for listing (id, title, status, dates)
- **Show**: Focus on content fields, inline simple relationships
- **Store/Update**: Include only fields AI agents should modify

### 2. Token Optimization

- Remove unnecessary metadata fields
- Inline simple relationship data instead of separate API calls
- Use computed fields to provide aggregated information
- Avoid deeply nested relationship structures
- Hide file fields from MCP using `hideFromMcp()` to save tokens

### 3. Security Considerations

- Same authorization rules apply to MCP requests
- Use conditional fields based on AI agent permissions
- Log AI agent activities for audit purposes
- Validate AI agent inputs thoroughly

### 4. Development Workflow

1. Start with regular field methods
2. Identify token-heavy operations through monitoring
3. Create MCP-specific methods for optimization
4. Test both human and AI agent access patterns
5. Monitor and iterate based on usage patterns

This MCP field system allows you to provide highly optimized data structures for AI agents while maintaining full functionality for human users, all from a single, unified codebase.