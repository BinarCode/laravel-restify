# Upgrading

## Unreleased

### `updatedBulk()` and `savedBulk()` receive models

`POST /api/restify/{repository}/bulk/update` used to hand its lifecycle hooks the raw
request body - one array per row, holding only the keys the client sent. They now
receive the updated Eloquent models, which is what the bulk store hooks already passed
and what the documentation already described.

```php
// before - $models held the request payload
public static function updatedBulk(Collection $models, RestifyRequest $request): void
{
    $ids = $models->pluck('id');
}

// after - $models holds Post instances
public static function updatedBulk(Collection $models, RestifyRequest $request): void
{
    $ids = $models->modelKeys();
}
```

A hook that only reads attributes needs no change: a model is `ArrayAccess`, so
`pluck()`, `$row['title']` and `data_get()` keep working for any column. Update a hook
that reads a key the payload carried but the table does not, or that writes back into
the array it was given.

`storedBulk()` is unaffected - it already passed models. `deletedBulk()` is unaffected -
it still passes the deleted rows as attribute arrays, since the models are gone by the
time it runs.
