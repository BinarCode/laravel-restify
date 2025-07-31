# Search Performance Optimization

Laravel Restify includes an optional JOIN-based search optimization for better performance when searching through BelongsTo relationship fields.

## Overview

**Default behavior (subqueries):**
```sql
SELECT * FROM `invoices` WHERE (
    UPPER(`invoices`.`gross_amount`) LIKE '%CSC%'
    OR (SELECT `vendors`.`code` FROM `vendors` WHERE `vendors`.`id` = `invoices`.`vendor_id` LIMIT 1) LIKE '%csc%'
)
```

**Optimized behavior (JOINs):**
```sql
SELECT invoices.* FROM `invoices`
LEFT JOIN `vendors` AS vendors_for_vendor ON invoices.vendor_id = vendors_for_vendor.id
WHERE (
    UPPER(invoices.gross_amount) LIKE '%CSC%'
    OR UPPER(vendors_for_vendor.code) LIKE '%CSC%'
)
```

## Configuration

Enable in your `.env` file:
```env
RESTIFY_SEARCH_USE_JOINS=true
```

Or in `config/restify.php`:
```php
'search' => [
    'use_joins' => true,
],
```

**Default: `false`** (backward compatible)

## When JOINs Are Used

✅ **Applied when:**
- Config `restify.search.use_joins` is `true`
- Searching BelongsTo relationship fields marked as searchable

❌ **NOT applied for:**
- Direct field searches on main model
- HasMany or other relationship types
- When config is disabled (default)

## Usage

No repository changes needed:

```php
class InvoiceRepository extends Repository
{
    public static function searchables(): array
    {
        return ['gross_amount', 'number']; // Direct fields - no JOINs
    }

    public static function related(): array
    {
        return [
            'vendor' => BelongsTo::make('vendor', VendorRepository::class)->searchable([
                'vendors.code', // Will use JOIN when enabled
                'vendors.name', // Will use JOIN when enabled
            ]),
        ];
    }
}
```

## Benefits

- **Better performance** - JOINs instead of subqueries
- **Fewer queries** - Eliminates N+1 subquery problems
- **Automatic eager loading optimization** - Skips already-joined relationships

## Testing

Test in development first:
```php
// Enable query logging to verify behavior
DB::enableQueryLog();
// Perform search
$queries = DB::getQueryLog();
```

Look for `LEFT JOIN` statements when optimization is enabled.