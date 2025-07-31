# Search Performance Optimization

Laravel Restify includes an optional search performance optimization that replaces inefficient subqueries with JOIN-based searches for related fields.

## Overview

By default, when searching through BelongsTo relationship fields, Laravel Restify uses subqueries which can be slow for large datasets:

```sql
-- Default behavior (subqueries)
SELECT * FROM `invoices` WHERE (
    UPPER(`invoices`.`gross_amount`) LIKE '%CSC%'
    OR (SELECT `vendors`.`code` FROM `vendors` WHERE `vendors`.`id` = `invoices`.`vendor_id` LIMIT 1) LIKE '%csc%'
    OR (SELECT `vendors`.`name` FROM `vendors` WHERE `vendors`.`id` = `invoices`.`vendor_id` LIMIT 1) LIKE '%csc%'
)
```

With JOIN optimization enabled, these become efficient JOIN-based queries:

```sql
-- Optimized behavior (JOINs)
SELECT invoices.* FROM `invoices`
LEFT JOIN `vendors` AS vendors_for_vendor ON invoices.vendor_id = vendors_for_vendor.id
WHERE (
    UPPER(invoices.gross_amount) LIKE '%CSC%'
    OR UPPER(vendors_for_vendor.code) LIKE '%CSC%'
    OR UPPER(vendors_for_vendor.name) LIKE '%CSC%'
)
```

## Configuration

### Enabling JOIN Optimization

Add the following to your `.env` file:

```env
RESTIFY_SEARCH_USE_JOINS=true
```

Or configure it directly in `config/restify.php`:

```php
'search' => [
    'case_sensitive' => true,
    'use_joins' => true, // Enable JOIN optimization
],
```

### Default Behavior

**By default, JOIN optimization is disabled** to maintain backward compatibility. The system will continue using subqueries until explicitly enabled.

## When JOINs Are Used

JOIN optimization is **only applied when**:

1. ✅ The `restify.search.use_joins` config is set to `true`
2. ✅ You're searching through a **BelongsTo relationship** field
3. ✅ The relationship field is marked as **searchable**

JOIN optimization is **NOT applied for**:

- ❌ Direct field searches on the main model
- ❌ HasMany or other relationship types  
- ❌ When the config flag is disabled (default)

## Repository Configuration

No changes are needed to your existing repository configuration. The optimization works with your current setup:

```php
class InvoiceRepository extends Repository
{
    public static function searchables(): array
    {
        return [
            'gross_amount', // Direct field - no JOIN needed
            'number',       // Direct field - no JOIN needed  
        ];
    }

    public static function related(): array
    {
        return [
            'vendor' => BelongsTo::make('vendor', VendorRepository::class)->searchable([
                'vendors.code', // Will use JOIN when optimization enabled
                'vendors.name', // Will use JOIN when optimization enabled
            ]),
        ];
    }
}
```

## Performance Benefits

### Query Efficiency
- **Eliminates N+1 subquery problems** - Each related field search no longer creates separate subqueries
- **Improved query performance** - JOINs are typically much faster than correlated subqueries
- **Reduced database load** - Single query instead of multiple subqueries per search term

### Eager Loading Optimization
When JOIN optimization is enabled, the system automatically:
- **Detects already-joined relationships** 
- **Excludes them from eager loading** to prevent duplicate queries
- **Maintains data integrity** while improving performance

### Example Performance Impact

**Before Optimization (3 separate subqueries)**:
```sql
-- Main query + 2 subqueries for each search term
SELECT * FROM invoices WHERE (
    gross_amount LIKE '%term%' OR
    (SELECT code FROM vendors WHERE...) LIKE '%term%' OR  
    (SELECT name FROM vendors WHERE...) LIKE '%term%'
)
```

**After Optimization (1 JOIN query)**:
```sql
-- Single query with JOIN
SELECT invoices.* FROM invoices 
LEFT JOIN vendors AS vendors_for_vendor ON invoices.vendor_id = vendors_for_vendor.id
WHERE (
    gross_amount LIKE '%term%' OR
    vendors_for_vendor.code LIKE '%term%' OR
    vendors_for_vendor.name LIKE '%term%'
)
```

## Backward Compatibility

This feature is **100% backward compatible**:

- **Default behavior unchanged** - Existing applications continue working without any changes
- **Opt-in optimization** - You must explicitly enable JOIN optimization
- **No breaking changes** - All existing repository configurations work as before
- **Gradual adoption** - Enable per environment (dev/staging first, then production)

## Best Practices

### When to Enable
- ✅ **Large datasets** with frequent relationship searches
- ✅ **Performance-critical applications** where search speed matters
- ✅ **Well-tested environments** where you can validate the behavior

### When to Keep Disabled  
- ❌ **Small datasets** where performance difference is negligible
- ❌ **Legacy systems** where you want to maintain exact query behavior
- ❌ **Complex relationship setups** that need specific subquery behavior

### Testing Strategy
1. **Enable in development/staging first**
2. **Run your existing test suite** to ensure no regressions
3. **Monitor query performance** before and after
4. **Gradually roll out to production**

## Troubleshooting

### Common Issues

**Q: JOINs aren't being used even though the config is enabled**
A: Verify that:
- The searchable fields are on BelongsTo relationships (not direct model fields)
- The relationship is properly configured with `->searchable([...])`
- Cache has been cleared after config changes

**Q: Getting different search results after enabling JOINs**  
A: This could indicate:
- Multi-tenant constraints that were handled differently in subqueries
- Complex relationship setups that need adjustment
- Consider disabling the optimization for that specific use case

**Q: Performance didn't improve as expected**
A: Check that:
- You have proper database indexes on JOIN columns
- The dataset is large enough to see meaningful performance differences
- Other query bottlenecks aren't masking the improvement

### Debugging

Enable query logging to see the generated SQL:

```php
DB::enableQueryLog();
// Perform your search
$queries = DB::getQueryLog();
dd($queries);
```

Look for `LEFT JOIN` statements in the search queries when optimization is enabled.

## Migration Guide

### Step 1: Test in Development
```env
# In .env
RESTIFY_SEARCH_USE_JOINS=true
```

### Step 2: Validate Query Behavior
Run your test suite and verify search results remain consistent.

### Step 3: Performance Testing  
Measure query performance before and after enabling the optimization.

### Step 4: Production Rollout
Enable in production after thorough testing in staging environments.

### Step 5: Monitor
Watch for any performance regressions or unexpected query behavior.