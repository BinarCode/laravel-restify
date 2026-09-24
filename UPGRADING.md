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

### `relatedRepository`, `viaRelationship` and `repositoryId` no longer read from the body

`attach`, `detach` and `sync` used to resolve the related repository and the relation
through `$request->relatedRepository` / `$request->viaRelationship`, and every write
endpoint resolved the current repository's id through `$request->repositoryId` /
`request('repositoryId')`. All of these are Laravel's magic request accessors, which
read the JSON/form body (or the query string) before falling back to the route
segment - so a request body carrying a same-named key silently redirected which
relation got written to, or which id a hook received, while authorization kept
running against the field and id named by the URL.

Body and query values for `relatedRepository`, `viaRelationship` and `repositoryId`
are now ignored everywhere they used to be read: the relation always comes from the
resolved field's own relation, and the id always comes from the route's
`{repositoryId}` segment.

If you override `attach()`, `detach()`, `sync()`, `show()`, `update()`, `patch()` or
`destroy()` and read one of these off the request yourself, read it from the route
instead:

```php
// before
$relatedRepository = $request->relatedRepository;
$repositoryId = $request->repositoryId; // or request('repositoryId')

// after
$relatedRepository = $request->relatedRepositoryKey();
$repositoryId = $request->repositoryIdFromRoute();
```

`relatedRepositoryKey()` throws `InvalidArgumentException` when called outside a
route that has a `relatedRepository` segment (attach/detach/sync); the same is true
of `repositoryIdFromRoute()` outside a route that has a `repositoryId` segment.
